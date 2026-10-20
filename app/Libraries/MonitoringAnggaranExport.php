<?php

namespace App\Libraries;

use DOMDocument;
use DOMElement;
use DOMXPath;
use RuntimeException;
use ZipArchive;

/** Builds the seven-sheet monitoring workpaper from system data. */
final class MonitoringAnggaranExport
{
    private const NS = 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';
    private const UNITS = ['Konsolidasi', 'Kanwil', 'Surabaya', 'Kediri', 'Malang', 'Madiun', 'Banyuwangi'];
    private const NAMES = ['KONSOL', 'KANWIL', 'SURABAYA', 'KEDIRI', 'MALANG', 'MADIUN', 'BANYUWANGI'];

    /** @return array{path:string,filename:string} */
    public function create(array $sheets, string $unit, int $year, int $month, string $document = 'all'): array
    {
        if ($year < 2000 || $year > 2100 || $month < 1 || $month > 12 || !in_array($unit, self::UNITS, true) || !in_array($document, ['all', 'negative_deviation'], true)) throw new RuntimeException('Data atau periode monitoring tidak valid.');
        $expectedKeys = array_column((new MonitoringAnggaran())->rows(null), 'key');
        foreach (self::UNITS as $name) {
            if (!isset($sheets[$name]['rows'], $sheets[$name]['notes'])) throw new RuntimeException('Data setiap unit belum lengkap.');
            $keys = array_column($sheets[$name]['rows'], 'key');
            if ($document === 'all' && $keys !== $expectedKeys) throw new RuntimeException('Data setiap unit belum lengkap.');
            if ($document === 'negative_deviation' && array_diff($keys, $expectedKeys) !== []) throw new RuntimeException('Filter deviasi setiap unit tidak valid.');
        }
        $base = APPPATH . 'Resources/Templates/monitoring_anggaran/';
        $styles = @file_get_contents($base . 'styles.xml'); $theme = @file_get_contents($base . 'theme.xml');
        if ($styles === false || $theme === false) throw new RuntimeException('Template Monitoring Anggaran belum tersedia.');
        $months = [1=>'Januari',2=>'Februari',3=>'Maret',4=>'April',5=>'Mei',6=>'Juni',7=>'Juli',8=>'Agustus',9=>'September',10=>'Oktober',11=>'November',12=>'Desember'];
        $period = strtoupper($months[$month]) . ' ' . $year;
        $files = []; $overrides = ''; $relations = ''; $sheetList = ''; $rowPositions = [];
        foreach (self::UNITS as $positionUnit => $name) foreach ($sheets[$name]['rows'] as $position => $row) $rowPositions[$name][$row['key']] = 3 + $position + ($positionUnit === 0 ? 0 : 1);
        foreach (self::UNITS as $index => $sheetUnit) {
            $sheetNumber = $index + 1;
            $sheetXml = @file_get_contents($base . 'sheet' . $sheetNumber . '.xml');
            $doc = new DOMDocument();
            if ($sheetXml === false || !$doc->loadXML($sheetXml, LIBXML_NONET)) throw new RuntimeException('Template sheet Monitoring Anggaran tidak valid.');
            $xp = new DOMXPath($doc); $xp->registerNamespace('m', self::NS);
            $offset = $index === 0 ? 0 : 1; $header = 2 + $offset;
            $rows = $sheets[$sheetUnit]['rows']; $notes = $sheets[$sheetUnit]['notes'];
            if ($document === 'negative_deviation' && $rows === []) $rows = [[
                'key' => '_empty', 'label' => 'Tidak ada deviasi negatif untuk periode ini.', 'amount' => null,
                'realization' => null, 'realization_ratio' => null, 'remaining_budget' => null, 'group' => false, 'placeholder' => true,
            ]];
            $this->expandTemplate($doc, $xp, $rows, $offset, $document === 'all');
            $cells = []; $sheetRows = [];
            foreach ($xp->query('//m:sheetData/m:row') as $element) {
                $sheetRows[(int) $element->getAttribute('r')] = $element;
                foreach ($xp->query('./m:c', $element) as $cell) $cells[$cell->getAttribute('r')] = $cell;
            }
            $this->text($doc, $cells, $sheetRows, 'A1', 'MONITORING BEBAN ANGGARAN ' . $year . ($document === 'negative_deviation' ? ' - DEVIASI MINUS' : ''));
            if ($offset) $this->text($doc, $cells, $sheetRows, 'A2', self::NAMES[$index]);
            foreach (['A'=>'NO','B'=>'URAIAN','C'=>'RKU ' . $year,'D'=>"REALISASI \nYTD " . $period,'E'=>'% Realisasi','F'=>'% Proporsional YTD ' . $period,'G'=>'Deviasi','H'=>'Sisa Anggaran','I'=>'Penjelasan'] as $column => $label) $this->text($doc, $cells, $sheetRows, $column . $header, $label);
            $groupPositions = [];
            foreach ($rows as $position => $row) if ($row['group']) $groupPositions[] = 3 + $position + $offset;
            if ($document === 'all' && count($groupPositions) !== 3) throw new RuntimeException('Susunan kelompok monitoring tidak sesuai.');
            $groupEnds = [];
            foreach ($groupPositions as $groupPositionIndex => $groupPosition) {
                $nextGroup = $groupPositions[$groupPositionIndex + 1] ?? null;
                $groupEnds[] = $nextGroup === null ? 2 + count($rows) + $offset : $nextGroup - 1;
            }
            $groupIndex = 0; $number = 0;
            foreach ($rows as $position => $row) {
                $r = 3 + $position + $offset;
                $this->text($doc, $cells, $sheetRows, 'B'.$r, $row['label']);
                $this->text($doc, $cells, $sheetRows, 'I'.$r, (string) ($notes[$row['key']] ?? ''));
                if (($row['placeholder'] ?? false) === true) continue;
                if ($row['group']) {
                    $number = 0; $end = $groupEnds[$groupIndex++];
                    // Unit worksheets use section headings with empty numeric cells in the reference file.
                    if ($index !== 0) continue;
                    if ($document === 'negative_deviation' || $end <= $r) {
                        foreach (['C'=>'amount', 'D'=>'realization'] as $col => $field) $this->number($doc, $cells, $sheetRows, $col.$r, $row[$field]);
                    } else foreach (['C'=>'amount', 'D'=>'realization'] as $col => $field) $this->formula($doc, $cells, $sheetRows, $col.$r, 'IF(COUNT('.$col.($r+1).':'.$col.$end.')='.($end-$r).',SUM('.$col.($r+1).':'.$col.$end.'),"")', $row[$field]);
                } else {
                    $this->number($doc, $cells, $sheetRows, 'A'.$r, (string) ++$number);
                    $this->number($doc, $cells, $sheetRows, 'C'.$r, $row['amount']);
                    // Use the template's inter-sheet link only when it reconciles to the system's corporate figure.
                    $references = []; $sum = '0.00'; $complete = true;
                    if ($document === 'all' && $index === 0) foreach (array_slice(self::UNITS, 1) as $sourceIndex => $source) {
                        $sourcePosition = $rowPositions[$source][$row['key']] ?? null;
                        $sourceRow = $sourcePosition === null ? null : $sheets[$source]['rows'][$sourcePosition - 4] ?? null;
                        $value = $sourceRow['realization'] ?? null;
                        if ($value === null) $complete = false; else $sum = LrMoney::add($sum, $value);
                        $references[] = "'" . self::NAMES[$sourceIndex + 1] . "'!D" . ($sourcePosition ?? 0);
                    }
                    if ($index === 0 && $complete && $row['realization'] !== null && LrMoney::isZero(LrMoney::subtract($sum, $row['realization']))) {
                        $refs = implode(',', $references);
                        $this->formula($doc, $cells, $sheetRows, 'D'.$r, 'IF(COUNT('.$refs.')=6,SUM('.$refs.'),"")', $row['realization']);
                    } else $this->number($doc, $cells, $sheetRows, 'D'.$r, $row['realization']);
                }
                $this->calculations($doc, $cells, $sheetRows, $r, $row['realization_ratio'], $row['remaining_budget'], $month);
            }
            if ($index === 0 && $document === 'all') {
                $groupRows = array_values(array_filter($rows, static fn ($r) => $r['group']));
                $amount = $this->total($groupRows, 'amount'); $realization = $this->total($groupRows, 'realization');
                $ratio = $amount === null || $realization === null ? null : (LrMoney::divide($realization, $amount) ?? '0.00');
                $totalRow = 3 + count($rows);
                foreach (['C'=>$amount,'D'=>$realization] as $col=>$cached) {
                    $refs = implode(',', array_map(static fn ($r) => $col.$r, $groupPositions));
                    $this->formula($doc, $cells, $sheetRows, $col.$totalRow, 'IF(COUNT('.$refs.')=3,SUM('.$refs.'),"")', $cached);
                }
                $this->calculations($doc, $cells, $sheetRows, $totalRow, $ratio, $amount === null || $realization === null ? null : LrMoney::subtract($amount, $realization), $month);
            }
            $files['xl/worksheets/sheet'.$sheetNumber.'.xml'] = $doc->saveXML();
            $overrides .= '<Override PartName="/xl/worksheets/sheet'.$sheetNumber.'.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>';
            $relations .= '<Relationship Id="rId'.$sheetNumber.'" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet'.$sheetNumber.'.xml"/>';
            $sheetList .= '<sheet name="'.self::NAMES[$index].'" sheetId="'.$sheetNumber.'" r:id="rId'.$sheetNumber.'"/>';
        }
        $files += [
            '[Content_Types].xml' => '<?xml version="1.0" encoding="UTF-8"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'.$overrides.'<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/><Override PartName="/xl/theme/theme1.xml" ContentType="application/vnd.openxmlformats-officedocument.theme+xml"/></Types>',
            '_rels/.rels' => '<?xml version="1.0" encoding="UTF-8"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>',
            'xl/workbook.xml' => '<?xml version="1.0" encoding="UTF-8"?><workbook xmlns="'.self::NS.'" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><bookViews><workbookView activeTab="'.array_search($unit, self::UNITS, true).'"/></bookViews><sheets>'.$sheetList.'</sheets><calcPr calcMode="auto" fullCalcOnLoad="1" forceFullCalc="1"/></workbook>',
            'xl/_rels/workbook.xml.rels' => '<?xml version="1.0" encoding="UTF-8"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'.$relations.'<Relationship Id="rId8" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/><Relationship Id="rId9" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/theme" Target="theme/theme1.xml"/></Relationships>',
            'xl/styles.xml' => $styles, 'xl/theme/theme1.xml' => $theme,
        ];
        $dir = WRITEPATH . 'cache/monitoring_exports';
        if (!is_dir($dir) && !mkdir($dir, 0770, true) && !is_dir($dir)) throw new RuntimeException('Penyimpanan sementara ekspor tidak tersedia.');
        $path = $dir . DIRECTORY_SEPARATOR . bin2hex(random_bytes(16)) . '.xlsx'; $zip = new ZipArchive();
        if ($zip->open($path, ZipArchive::CREATE | ZipArchive::EXCL) !== true) throw new RuntimeException('File ekspor belum dapat dibuat.');
        try {
            foreach ($files as $name => $contents) if (!$zip->addFromString($name, $contents)) throw new RuntimeException('Pembuatan workbook gagal.');
            if (!$zip->close()) throw new RuntimeException('Workbook belum dapat disimpan.');
        } catch (\Throwable $error) { $zip->close(); if (is_file($path)) unlink($path); throw $error; }
        return ['path'=>$path, 'filename'=>'Monitoring_Anggaran_'.$months[$month].'_'.$year.($document === 'negative_deviation' ? '_Deviasi_Minus' : '').'.xlsx'];
    }

    /** Expand each reference sheet while reusing its own existing row formatting. */
    private function expandTemplate(DOMDocument $doc, DOMXPath $xp, array $rows, int $offset, bool $includeTotal): void
    {
        $data = $xp->query('//m:sheetData')->item(0);
        $original = []; $byLabel = [];
        foreach ($xp->query('./m:row', $data) as $row) {
            $number = (int) $row->getAttribute('r'); $original[$number] = $row;
            $label = $xp->query('./m:c[starts-with(@r,"B")]/m:is/m:t', $row)->item(0)?->textContent;
            if ($label !== null && $label !== '') $byLabel[$label] = $row;
        }
        $prototypes = [4 + $offset, 16 + $offset, 54 + $offset];
        $total = $original[64] ?? null;
        foreach (array_reverse($original, true) as $number => $row) if ($number > 2 + $offset) $data->removeChild($row);
        $group = -1;
        foreach ($rows as $position => $row) {
            if ($row['group']) $group++;
            $prototype = ($row['placeholder'] ?? false) === true ? $prototypes[0] : ($prototypes[$group] ?? 0);
            $source = $byLabel[$row['label']] ?? ($original[$prototype] ?? null);
            if (!$source) throw new RuntimeException('Format baris template tidak tersedia.');
            $clone = $source->cloneNode(true); $r = 3 + $position + $offset;
            $clone->setAttribute('r', (string) $r);
            foreach ($xp->query('./m:c', $clone) as $cell) $cell->setAttribute('r', preg_replace('/\d+$/', (string) $r, $cell->getAttribute('r')));
            $data->appendChild($clone);
        }
        $end = 2 + count($rows) + $offset;
        if ($offset === 0 && $includeTotal) {
            if (!$total) throw new RuntimeException('Format total template tidak tersedia.');
            $clone = $total->cloneNode(true); $end++;
            $clone->setAttribute('r', (string) $end);
            foreach ($xp->query('./m:c', $clone) as $cell) $cell->setAttribute('r', preg_replace('/\d+$/', (string) $end, $cell->getAttribute('r')));
            $data->appendChild($clone);
        }
        $xp->query('//m:dimension')->item(0)?->setAttribute('ref', 'A1:I'.$end);
        $xp->query('//m:autoFilter')->item(0)?->setAttribute('ref', 'A'.(2+$offset).':I'.$end);
        foreach ($xp->query('//m:conditionalFormatting') as $format) {
            $range = $format->getAttribute('sqref');
            if (preg_match('/^([GH])(\d+):[GH]\d+$/D', $range, $match)) $format->setAttribute('sqref', $match[1].$match[2].':'.$match[1].$end);
        }
    }

    private function calculations(DOMDocument $doc, array &$cells, array $rows, int $r, ?string $ratio, ?string $remaining, int $month): void
    {
        $this->formula($doc, $cells, $rows, 'E'.$r, 'IF(COUNT(C'.$r.',D'.$r.')<2,"",IF(C'.$r.'=0,0,D'.$r.'/C'.$r.'))', $ratio);
        $this->formula($doc, $cells, $rows, 'F'.$r, $month.'/12', LrMoney::divide((string) $month, '12'));
        $this->formula($doc, $cells, $rows, 'G'.$r, 'IF(ISNUMBER(E'.$r.'),F'.$r.'-E'.$r.',"")', MonitoringAnggaran::deviation($ratio, $month));
        $this->formula($doc, $cells, $rows, 'H'.$r, 'IF(COUNT(C'.$r.',D'.$r.')<2,"",C'.$r.'-D'.$r.')', $remaining);
    }

    private function cell(DOMDocument $doc, array &$cells, array $rows, string $ref): DOMElement
    {
        if (isset($cells[$ref])) return $cells[$ref];
        preg_match('/^([A-I])(\d+)$/D', $ref, $parts);
        $row = $rows[(int)$parts[2]] ?? throw new RuntimeException('Baris template tidak ditemukan.');
        $cell = $doc->createElementNS(self::NS, 'c'); $cell->setAttribute('r', $ref);
        foreach ($row->childNodes as $existing) if ($existing instanceof DOMElement && $existing->localName === 'c' && strcmp($existing->getAttribute('r')[0], $parts[1]) > 0) { $row->insertBefore($cell, $existing); return $cells[$ref] = $cell; }
        $row->appendChild($cell); return $cells[$ref] = $cell;
    }
    private function clear(DOMElement $cell): void { while ($cell->firstChild) $cell->removeChild($cell->firstChild); $cell->removeAttribute('t'); }
    private function text(DOMDocument $doc, array &$cells, array $rows, string $ref, string $value): void
    {
        $cell = $this->cell($doc, $cells, $rows, $ref); $this->clear($cell); $cell->setAttribute('t', 'inlineStr');
        $is = $doc->createElementNS(self::NS, 'is'); $t = $doc->createElementNS(self::NS, 't'); $t->setAttribute('xml:space', 'preserve'); $t->appendChild($doc->createTextNode($value)); $is->appendChild($t); $cell->appendChild($is);
    }
    private function number(DOMDocument $doc, array &$cells, array $rows, string $ref, ?string $value): void
    {
        $cell = $this->cell($doc, $cells, $rows, $ref); $this->clear($cell);
        if ($value !== null) { $v = $doc->createElementNS(self::NS, 'v'); $v->appendChild($doc->createTextNode(LrMoney::decimal($value))); $cell->appendChild($v); }
    }
    private function formula(DOMDocument $doc, array &$cells, array $rows, string $ref, string $formula, ?string $cached): void
    {
        $cell = $this->cell($doc, $cells, $rows, $ref); $this->clear($cell);
        if ($cached === null) $cell->setAttribute('t', 'str');
        $f = $doc->createElementNS(self::NS, 'f'); $f->appendChild($doc->createTextNode($formula)); $cell->appendChild($f);
        $v = $doc->createElementNS(self::NS, 'v'); if ($cached !== null) $v->appendChild($doc->createTextNode($cached)); $cell->appendChild($v);
    }
    private function total(array $rows, string $key): ?string
    {
        $sum = '0.00'; foreach ($rows as $row) { if ($row[$key] === null) return null; $sum = LrMoney::add($sum, $row[$key]); } return $sum;
    }
}
