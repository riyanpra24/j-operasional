<?php

namespace App\Libraries;

use RuntimeException;
use SimpleXMLElement;
use ZipArchive;

/** Oracle LR reader with administrator-managed LOB and COA mappings. */
final class OracleLrSalaryParser
{
    private const NS = 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';
    public const RULE = 'oracle_mapping_v8';
    public const PREVIOUS_RULE = 'branch_kur_nonkur_v7';
    public const ALL_SHEET_SIGN_RULE = 'all_sheet_sign_rules_v6';
    public const KANWIL_SIGN_RULE = 'kanwil_sign_rules_v5';
    public const PRECISION_RULE = 'kur_nonkur_precision_v4';
    public const SEGMENT_RULE = 'kur_nonkur_expenses_v3';
    public const KUR_RULE = 'kur_expenses_v2';
    public const LEGACY_RULE = 'kur_salary_v1';
    public const BRANCH_UNITS = ['Surabaya','Kediri','Malang','Madiun','Banyuwangi'];
    public const IMPORT_UNITS = ['Kanwil','Surabaya','Kediri','Malang','Madiun','Banyuwangi'];
    private const UNIT_SHEETS = [
        'Kanwil'=>'KANWIL','Surabaya'=>'SURABAYA','Kediri'=>'KEDIRI',
        'Malang'=>'MALANG','Madiun'=>'MADIUN','Banyuwangi'=>'BANYUWANGI',
    ];

    public function __construct(private ?OracleLrMappingService $mappingService = null)
    {
    }

    public static function columnForLob(string $lob): ?string
    {
        return [
            'kur' => 'KUR', 'pen' => 'PEN', 'non kur' => 'NON KUR',
            'kbg/suretyship' => 'KBG/SURETYSHIP', 'konsumtif' => 'KONSUMTIF', 'produktif' => 'PRODUKTIF',
        ][self::normalizeLabel($lob)] ?? null;
    }

    public static function reportValues(array $result): array
    {
        if (!in_array($result['rule']??'', [self::RULE,self::PREVIOUS_RULE,self::ALL_SHEET_SIGN_RULE,self::KANWIL_SIGN_RULE,self::PRECISION_RULE,self::SEGMENT_RULE,self::KUR_RULE,self::LEGACY_RULE],true)) throw new RuntimeException('Versi aturan laporan berbeda. Hubungi Administrator.');
        $map=LrReportRows::sourceMap(); $values=[];
        foreach ($result['matches'] as $match) {
            if ($result['rule']===self::RULE) {
                $reportLabel=(string)($match['report_label']??'');
                if (!in_array($reportLabel,LrReportRows::mappableLabels(),true)) throw new RuntimeException('Tujuan mapping pada riwayat impor tidak termasuk rincian Laba / Rugi.');
            } else {
                $description=$result['rule']===self::LEGACY_RULE ? 'beban gaji karyawan' : self::normalizeLabel($match['description']??'');
                if (!isset($map[$description])) throw new RuntimeException('Akun pada riwayat impor tidak termasuk rincian beban yang disetujui.');
                $reportLabel=$map[$description];
            }
            $key=self::normalizeLabel($reportLabel);
            $column=in_array($result['rule'],[self::RULE,self::PREVIOUS_RULE,self::ALL_SHEET_SIGN_RULE,self::KANWIL_SIGN_RULE,self::PRECISION_RULE,self::SEGMENT_RULE],true) ? self::columnForLob($match['lob']??'') : 'KUR';
            if ($column===null) throw new RuntimeException('Segmen pada riwayat impor tidak valid.');
            $amount=$result['rule']===self::RULE && isset($match['calculation_amount'])
                ? self::money((string) $match['calculation_amount'])
                : (in_array($result['rule'],[self::PREVIOUS_RULE,self::ALL_SHEET_SIGN_RULE,self::KANWIL_SIGN_RULE],true)
                    ? LrSignRules::calculationValue($map[$description],$match['source_amount'],strtoupper($result['unit']??'KANWIL'))
                    : self::money($match['source_amount']));
            $values[$key][$column]=LrMoney::add($values[$key][$column]??'0.00',$amount);
        }
        return $values;
    }

    public static function normalizeLabel(string $text): string
    {
        return mb_strtolower(trim(preg_replace('/[\s\x{00a0}]+/u', ' ', $text)));
    }

    /** Preserve every source decimal; no cent normalization or intermediate rounding. */
    public static function money(string $raw): string
    {
        return LrMoney::decimal($raw);
    }

    public function parse(string $path,string $unit='Kanwil',bool $includeHelperRows=false): array
    {
        if (!isset(self::UNIT_SHEETS[$unit])) throw new RuntimeException('Unit laporan tidak didukung.');
        $sheetName=self::UNIT_SHEETS[$unit];
        if (!extension_loaded('bcmath')) throw new RuntimeException('BCMath diperlukan untuk menghitung nominal rupiah.');
        if (!is_file($path) || filesize($path)>5*1024*1024) throw new RuntimeException('Berkas Excel maksimal 5 MB.');
        $zip = new ZipArchive();
        if ($zip->open($path)!==true) throw new RuntimeException('Berkas harus berupa Excel .xlsx yang valid.');
        try {
            if ($zip->numFiles>500) throw new RuntimeException('Struktur Excel terlalu besar.');
            $expanded = 0;
            for ($i=0;$i<$zip->numFiles;$i++) {
                $entry = $zip->statIndex($i); $expanded += $entry['size'];
                $printer = preg_match('~^xl/printerSettings/printerSettings\d+\.bin$~D',$entry['name']);
                if ($expanded>50*1024*1024 || preg_match('~externalLinks/|vbaProject~i',$entry['name'])
                    || (preg_match('~\.bin$~i',$entry['name']) && !$printer)) throw new RuntimeException('Excel terlalu besar, mengandung makro, atau tautan eksternal.');
            }
            $book = $this->xml($zip,'xl/workbook.xml'); $book->registerXPathNamespace('s',self::NS);
            $selected = null; $sheetEntries=[];
            foreach ($book->xpath('//s:sheets/s:sheet') as $info) {
                $entry=['name'=>(string)$info['name'],'id'=>(string)$info->attributes('http://schemas.openxmlformats.org/officeDocument/2006/relationships')['id']];
                $sheetEntries[]=$entry;
                if (mb_strtolower((string)$info['name'])===mb_strtolower($sheetName)) {
                    if ($selected!==null) throw new RuntimeException('Sheet '.$sheetName.' duplikat.');
                    $selected = $info;
                }
            }
            if ($selected===null) throw new RuntimeException('Sheet '.$sheetName.' tidak ditemukan. Gunakan nama sheet '.$sheetName.'.');
            $id = (string)$selected->attributes('http://schemas.openxmlformats.org/officeDocument/2006/relationships')['id'];
            $relations = $this->xml($zip,'xl/_rels/workbook.xml.rels'); $sheetPath = null; $sheetPaths=[];
            foreach ($relations->children('http://schemas.openxmlformats.org/package/2006/relationships') as $relation) {
                $attributes=$relation->attributes();
                if ((string)$attributes['TargetMode']==='External') throw new RuntimeException('Tautan eksternal tidak didukung.');
                $relationId=(string)$attributes['Id'];
                if (in_array($relationId,array_column($sheetEntries,'id'),true)) {
                    $target = ltrim((string)$attributes['Target'],'/');
                    if (str_starts_with($target,'xl/')) $target=substr($target,3);
                    if (!preg_match('~^worksheets/sheet\d+\.xml$~D',$target)) throw new RuntimeException('Lokasi worksheet Excel tidak valid.');
                    $sheetPaths[$relationId]='xl/'.$target;
                    if ($relationId===$id) $sheetPath='xl/'.$target;
                }
            }
            if ($sheetPath===null) throw new RuntimeException('Isi sheet '.$sheetName.' tidak ditemukan.');
            $strings=[];
            if ($zip->locateName('xl/sharedStrings.xml')!==false) {
                $shared=$this->xml($zip,'xl/sharedStrings.xml'); $shared->registerXPathNamespace('s',self::NS);
                foreach ($shared->xpath('//s:si') as $item) {
                    $item->registerXPathNamespace('s',self::NS);
                    $strings[]=implode('',array_map('strval',$item->xpath('.//s:t')));
                }
            }
            $cells=$this->sheetCells($zip,$sheetPath,$strings);
            $layout=$this->sheetLayout($cells,$sheetName);
            $months=['januari'=>1,'februari'=>2,'maret'=>3,'april'=>4,'mei'=>5,'juni'=>6,'juli'=>7,'agustus'=>8,'september'=>9,'oktober'=>10,'november'=>11,'desember'=>12];
            $periodAddress=$layout['period'].'5';
            $period=self::normalizeLabel($cells[$periodAddress]['value']??'');
            if (!preg_match('/^periode\s*:\s*([a-z]+)\s*-\s*(\d{4}|\d{2})$/D',$period,$parts) || !isset($months[$parts[1]])) throw new RuntimeException('Periode laporan pada '.$periodAddress.' tidak dapat dibaca.');
            $year=strlen($parts[2])===2 ? 2000+(int)$parts[2] : (int)$parts[2];
            if ($year<2000 || $year>2100) throw new RuntimeException('Tahun laporan harus 2000–2100.');
            $matches=[]; $unmapped=[]; $sourceTotal='0.00'; $calculationTotal='0.00';
            $helperRows=['KUR'=>[],'NON KUR'=>[],'PEN'=>[]];
            $mappingService=$this->mappingService ??= new OracleLrMappingService();
            $sourceMap=$mappingService->accountMap($unit);
            $sourceLabels=$mappingService->reportLabels($unit);
            $segmentCounts=array_fill_keys(OracleLrMappingService::TARGET_COLUMNS,0);
            $dateStyles=$this->dateStyles($zip);
            foreach ($cells as $address=>$lob) {
                if (!preg_match('/^'.preg_quote($layout['lob'],'/').'(\d+)$/D',$address,$row) || (int)$row[1]<=7) continue;
                if (self::normalizeLabel((string)$lob['value'])==='') continue;
                $r=(int)$row[1]; $description=$cells[$layout['description'].$r]??null; $descriptionLob=$cells[$layout['description_lob'].$r]['value']??'';
                $descriptionKey=self::normalizeLabel($description['value']??'');
                $amountAddress=$layout['amount'].$r; $amount=$cells[$amountAddress]??null;
                $lobMapping=$mappingService->classify((string) $lob['value'],(string) $descriptionLob);
                $formulaSegments=$unit==='Kanwil' ? null : LrReportRows::branchFormulaSourceSegments($descriptionKey);
                $formulaSegmentAllowed=$formulaSegments===null || ($lobMapping!==null && in_array($lobMapping['target_column'],$formulaSegments,true));
                if ($amount===null || $amount['value']==='') {
                    if ($lobMapping!==null && isset($sourceMap[$descriptionKey])) throw new RuntimeException('Nominal '.$amountAddress.' harus angka sumber Oracle, bukan teks, tanggal, atau rumus Excel.');
                    continue;
                }
                if ($lob['formula'] || ($description['formula']??false) || $amount['formula']
                    || !in_array($amount['type'],['','n'],true) || isset($dateStyles[$amount['style']])) throw new RuntimeException('Nominal '.$amountAddress.' harus angka sumber Oracle, bukan teks, tanggal, atau rumus Excel.');
                try { $normalized=self::money($amount['value']); }
                catch (\InvalidArgumentException $e) { throw new RuntimeException($amountAddress.': '.$e->getMessage()); }
                if ($includeHelperRows && $lobMapping!==null) {
                    $helperRows[$lobMapping['target_column']][]=[
                        'row'=>$r,
                        'description'=>trim((string)preg_replace('/[\s\x{00a0}]+/u',' ',(string)($description['value']??''))),
                        'description_lob'=>trim((string)preg_replace('/[\s\x{00a0}]+/u',' ',(string)$descriptionLob)),
                        'amount'=>$normalized,
                    ];
                }
                if ($lobMapping===null || !isset($sourceMap[$descriptionKey]) || !$formulaSegmentAllowed) {
                    $unmapped[]=[
                        'row'=>$r,'source_lob'=>trim((string)$lob['value']),'description_lob'=>trim((string)$descriptionLob),
                        'description'=>trim((string)($description['value']??'')),'source_amount'=>$amount['value'],
                        'reason'=>$lobMapping===null ? 'lob' : (!isset($sourceMap[$descriptionKey]) ? 'account' : 'segment'),
                    ];
                    continue;
                }
                $column=$lobMapping['target_column']; $accountMapping=$sourceMap[$descriptionKey];
                if ($lob['formula'] || $description['formula'] || $amount===null || $amount['formula']
                    || !in_array($amount['type'],['','n'],true) || $amount['value']==='' || isset($dateStyles[$amount['style']])) throw new RuntimeException('Nominal '.$amountAddress.' harus angka sumber Oracle, bukan teks, tanggal, atau rumus Excel.');
                $calculation=$mappingService->calculationValue($accountMapping['sign_mode'],$normalized,$sheetName);
                $sourceTotal=LrMoney::add($sourceTotal,$normalized); $calculationTotal=LrMoney::add($calculationTotal,$calculation);
                $segmentCounts[$column]++;
                $matches[]=['row'=>$r,'lob'=>$column,'source_lob'=>trim((string)$lob['value']),'description_lob'=>trim((string)$descriptionLob),
                    'account'=>$cells[$layout['account'].$r]['value']??'','description'=>trim($description['value']),
                    'report_label'=>$accountMapping['report_label'],'source_amount'=>$amount['value'],'amount'=>$normalized,'calculation_amount'=>$calculation,
                    'lob_mapping_id'=>$lobMapping['id'],'account_mapping_id'=>$accountMapping['id'],'sign_mode'=>$accountMapping['sign_mode'],
                    'sign_inverted'=>$calculation!==$normalized];
            }
            if (!$matches) throw new RuntimeException('Tidak ada baris KUR, NON KUR, atau PEN dengan Description COA yang cocok dengan mapping aktif pada sheet '.$sheetName.'. Data sebelumnya tidak diubah.');
            $signRuleInputs=[];
            if ($unit==='Kanwil') {
                foreach ($cells as $address=>$description) {
                    if (!preg_match('/^'.preg_quote($layout['description'],'/').'(\d+)$/D',$address,$row) || self::normalizeLabel($description['value'])!=='laba sebelum pajak') continue;
                    $r=(int)$row[1]; $amountAddress=$layout['amount'].$r; $amount=$cells[$amountAddress]??null;
                    if ($description['formula'] || $amount===null || $amount['formula'] || !in_array($amount['type'],['','n'],true) || $amount['value']==='' || isset($dateStyles[$amount['style']])) throw new RuntimeException('Nominal '.$amountAddress.' harus angka sumber Oracle, bukan teks, tanggal, atau rumus Excel.');
                    $normalized=self::money($amount['value']);
                    $signRuleInputs[]=['row'=>$r,'description'=>'LABA SEBELUM PAJAK','source_amount'=>$amount['value'],
                        'calculation_amount'=>LrSignRules::calculationValue('laba sebelum pajak',$normalized),'sign_inverted'=>true];
                }
                if (count($signRuleInputs)!==1) throw new RuntimeException('Sheet KANWIL harus memiliki tepat satu baris LABA SEBELUM PAJAK. Data sebelumnya tidak diubah.');
            }
            $allSheetSignInputs=[];
            foreach ($sheetEntries as $sheetEntry) {
                if (!in_array(mb_strtoupper($sheetEntry['name']),array_values(self::UNIT_SHEETS),true)) continue;
                if (!isset($sheetPaths[$sheetEntry['id']])) throw new RuntimeException('Isi sheet '.$sheetEntry['name'].' tidak ditemukan.');
                $sheetCells=mb_strtolower($sheetEntry['name'])===mb_strtolower($sheetName) ? $cells : $this->sheetCells($zip,$sheetPaths[$sheetEntry['id']],$strings);
                $sheetLayout=mb_strtolower($sheetEntry['name'])===mb_strtolower($sheetName) ? $layout : $this->sheetLayout($sheetCells,$sheetEntry['name']);
                $seen=[];
                foreach ($sheetCells as $address=>$description) {
                    if (!preg_match('/^'.preg_quote($sheetLayout['description'],'/').'(\d+)$/D',$address,$row) || !LrSignRules::isAllSheets($description['value'])) continue;
                    $r=(int)$row[1]; $label=self::normalizeLabel($description['value']);
                    if (isset($seen[$label])) throw new RuntimeException('Uraian '.$description['value'].' duplikat pada sheet '.$sheetEntry['name'].'.');
                    $seen[$label]=true; $amountAddress=$sheetLayout['amount'].$r; $amount=$sheetCells[$amountAddress]??null;
                    if ($description['formula'] || $amount===null || $amount['formula'] || !in_array($amount['type'],['','n'],true) || $amount['value']==='' || isset($dateStyles[$amount['style']])) throw new RuntimeException('Nominal '.$sheetEntry['name'].'!'.$amountAddress.' harus angka sumber Oracle, bukan teks, tanggal, atau rumus Excel.');
                    $normalized=self::money($amount['value']);
                    $allSheetSignInputs[]=['sheet'=>$sheetEntry['name'],'row'=>$r,'description'=>trim($description['value']),'source_amount'=>$amount['value'],
                        'calculation_amount'=>LrSignRules::calculationValue($description['value'],$normalized,$sheetEntry['name']),'sign_inverted'=>true];
                }
            }
            $result=['rule'=>self::RULE,'unit'=>$unit,'sheet'=>$sheetName,'year'=>$year,'month'=>$months[$parts[1]],'period'=>trim($cells[$periodAddress]['value']),
                'source_total'=>$sourceTotal,'calculation_total'=>$calculationTotal,'matches'=>$matches,'sign_rule_inputs'=>$signRuleInputs,'all_sheet_sign_rule_inputs'=>$allSheetSignInputs,
                'precision'=>'source_exact_display_half_up','segment_counts'=>$segmentCounts,'unmapped'=>$unmapped,
                'mapping_version'=>$mappingService->version()];
            if ($includeHelperRows) $result['helper_rows']=$helperRows;
            $values=self::reportValues($result);
            foreach (OracleLrMappingService::TARGET_COLUMNS as $segment) $result['missing_labels_by_segment'][$segment]=array_values(array_filter($sourceLabels,static fn($label)=>!isset($values[self::normalizeLabel($label)][$segment])));
            $result['missing_labels']=$result['missing_labels_by_segment']['KUR'];
            return $result;
        } finally { $zip->close(); }
    }

    /** All Oracle unit sheets must use the same A–G source layout. */
    private function sheetLayout(array $cells,string $sheetName): array
    {
        foreach (['B7'=>'LOB (Segment 1)','D7'=>'Description COA','G7'=>'Ending Balance'] as $address=>$expected) {
            $actual=self::normalizeLabel((string)($cells[$address]['value']??''));
            $expectedNormalized=self::normalizeLabel($expected);
            if ($actual!==$expectedNormalized && !str_contains($actual,$expectedNormalized)) {
                $actualLabel=$actual==='' ? 'kosong' : '"'.mb_substr((string)($cells[$address]['value']??''),0,80).'"';
                throw new RuntimeException('Sheet '.$sheetName.': Header '.$address.' harus memuat "'.$expected.'" (terbaca '.$actualLabel.'). Semua sheet Oracle harus memakai format A–G yang sama.');
            }
        }
        return ['period'=>'A','lob'=>'B','account'=>'C','description'=>'D','description_lob'=>'E','amount'=>'G'];
    }

    private function sheetCells(ZipArchive $zip,string $path,array $strings): array
    {
        $sheet=$this->xml($zip,$path); $sheet->registerXPathNamespace('s',self::NS); $nodes=$sheet->xpath('//s:sheetData/s:row/s:c');
        if (count($nodes)>200000) throw new RuntimeException('Jumlah sel Excel terlalu besar pada '.$path.'.');
        $cells=[];
        foreach ($nodes as $node) {
            $address=(string)$node['r'];
            if (!preg_match('/^[A-Z]{1,3}[1-9]\d{0,5}$/D',$address) || isset($cells[$address])) throw new RuntimeException('Alamat sel duplikat atau tidak valid.');
            $content=$node->children(self::NS); $type=(string)$node['t']; $value=isset($content->v)?(string)$content->v:'';
            if ($type==='s') {
                if (!ctype_digit($value) || !isset($strings[(int)$value])) throw new RuntimeException('Teks Excel tidak valid pada '.$address.'.');
                $value=$strings[(int)$value];
            } elseif ($type==='inlineStr') {
                $node->registerXPathNamespace('s',self::NS); $value=implode('',array_map('strval',$node->xpath('.//s:is//s:t')));
            }
            $cells[$address]=['value'=>$value,'type'=>$type,'formula'=>isset($content->f),'style'=>(int)$node['s']];
        }
        return $cells;
    }

    private function dateStyles(ZipArchive $zip): array
    {
        if ($zip->locateName('xl/styles.xml')===false) return [];
        $styles=$this->xml($zip,'xl/styles.xml'); $styles->registerXPathNamespace('s',self::NS);
        $formats=[]; $dates=[];
        foreach ($styles->xpath('//s:numFmts/s:numFmt') as $format) $formats[(int)$format['numFmtId']]=(string)$format['formatCode'];
        foreach ($styles->xpath('//s:cellXfs/s:xf') as $index=>$style) {
            $id=(int)$style['numFmtId'];
            $code=preg_replace('/"[^"\\\\]*(?:\\\\.[^"\\\\]*)*"|\\\\.|\[(?![hms]+\])[^\]]*\]/i','',$formats[$id]??'');
            if (($id>=14 && $id<=22) || ($id>=27 && $id<=36) || ($id>=45 && $id<=47) || ($id>=50 && $id<=58) || preg_match('/[ymdhs]/i',$code)) $dates[$index]=true;
        }
        return $dates;
    }

    private function xml(ZipArchive $zip,string $path): SimpleXMLElement
    {
        $raw=$zip->getFromName($path);
        if ($raw===false || preg_match('/<!DOCTYPE|<!ENTITY/i',$raw)) throw new RuntimeException('Struktur XML Excel tidak valid.');
        $previous=libxml_use_internal_errors(true);
        try {
            $xml=simplexml_load_string($raw,SimpleXMLElement::class,LIBXML_NONET);
            if ($xml===false) throw new RuntimeException('XML Excel tidak dapat dibaca.');
            return $xml;
        } finally { libxml_clear_errors(); libxml_use_internal_errors($previous); }
    }
}
