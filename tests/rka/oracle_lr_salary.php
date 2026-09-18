<?php
require __DIR__.'/../../app/Libraries/RkaMoney.php';
require __DIR__.'/../../app/Libraries/LrMoney.php';
require __DIR__.'/../../app/Libraries/OracleLrSalaryParser.php';
require __DIR__.'/../../app/Libraries/LrReportRows.php';
require __DIR__.'/../../app/Libraries/LrSignRules.php';
require __DIR__.'/../../app/Libraries/OracleLrMappingService.php';
use App\Libraries\OracleLrSalaryParser;
$parser=new OracleLrSalaryParser(); $checks=0;
function lrCheck(bool $ok,string $label): void { global $checks; if (!$ok) throw new RuntimeException($label); $checks++; }
lrCheck(OracleLrSalaryParser::IMPORT_UNITS===['Kanwil','Surabaya','Kediri','Malang','Madiun','Banyuwangi'],'Bulk import includes Kanwil and all five branches in the approved order');
function lrFixture(array $rows, callable $check, string $sheetName='KANWIL', string $header='Ending Balance', ?callable $modify=null): void {
    $ns='http://schemas.openxmlformats.org/spreadsheetml/2006/main';
    $text=static fn($value)=>htmlspecialchars($value,ENT_XML1|ENT_QUOTES,'UTF-8');
    $str=static fn($address,$value)=>'<c r="'.$address.'" t="inlineStr"><is><t>'.$text($value).'</t></is></c>';
    $xml='<worksheet xmlns="'.$ns.'"><sheetData><row r="5">'.$str('A5','Periode : AGUSTUS-26').'</row><row r="7">'.$str('B7','LOB (Segment 1)').$str('D7','Description COA').$str('H7',$header).'</row>';
    foreach ($rows as $index=>$item) {
        $r=$index+8;
        $xml.='<row r="'.$r.'">'.$str('B'.$r,$item[0]).$str('C'.$r,'6270201000000').$str('D'.$r,$item[1]);
        if ($item[2]!==null) $xml.='<c r="H'.$r.'"'.($item[3]??'').'><v>'.$text($item[2]).'</v>'.($item[4]??'').'</c>';
        $xml.='</row>';
    }
    $summaryRow=count($rows)+8;
    $xml.='<row r="'.$summaryRow.'">'.$str('B'.$summaryRow,'').$str('D'.$summaryRow,'LABA SEBELUM PAJAK').'<c r="H'.$summaryRow.'"><v>100.5001</v></c></row>';
    $xml.='<row r="'.($summaryRow+1).'">'.$str('D'.($summaryRow+1),'LABA TAHUN BERJALAN').'<c r="H'.($summaryRow+1).'"><v>-200.2501</v></c></row>';
    $xml.='<row r="'.($summaryRow+2).'">'.$str('D'.($summaryRow+2),'JUMLAH LABA KOMPREHENSIF TAHUN BERJALAN').'<c r="H'.($summaryRow+2).'"><v>300.7501</v></c></row>';
    $xml.='</sheetData></worksheet>';
    $path=tempnam(sys_get_temp_dir(),'oracle-lr-check-');
    try {
        $zip=new ZipArchive(); $zip->open($path,ZipArchive::OVERWRITE);
        $zip->addFromString('xl/workbook.xml','<workbook xmlns="'.$ns.'" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="'.$text($sheetName).'" sheetId="1" r:id="rId1"/></sheets></workbook>');
        $zip->addFromString('xl/_rels/workbook.xml.rels','<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Target="worksheets/sheet1.xml"/></Relationships>');
        $zip->addFromString('xl/worksheets/sheet1.xml',$xml);
        if ($modify!==null) $modify($zip);
        $zip->close(); $check($path);
    } finally { if (is_file($path)) unlink($path); }
}
function lrReject(array $rows,string $message, string $sheetName='KANWIL',string $header='Ending Balance',?callable $modify=null): void {
    global $parser;
    lrFixture($rows,static function($path) use($parser,$message) {
        try { $parser->parse($path); } catch (RuntimeException|InvalidArgumentException $e) { lrCheck(str_contains($e->getMessage(),$message),'Wrong validation: '.$e->getMessage()); return; }
        throw new RuntimeException('Invalid workbook was accepted: '.$message);
    },$sheetName,$header,$modify);
}
$rows=[[' KUR ',"\u{00a0} Beban Gaji Karyawan",'100.01'],['kur','beban   gaji karyawan','-25.02'],['KUR','Beban gaji karyawan','0.01'],['NON KUR','Beban gaji karyawan','999'],['KUR Mikro','Beban gaji karyawan','888'],['KUR','Beban gaji karyawan lainnya','777']];
lrFixture($rows,static function($path)use($parser){
    $result=$parser->parse($path);
    $values=OracleLrSalaryParser::reportValues($result);
    lrCheck($values['beban gaji karyawan']===['KUR'=>'75.00','NON KUR'=>'999.00'],'KUR and NON KUR sum independently');
    lrCheck(count($result['matches'])===4,'Only exact approved LOB and description matching');
    lrCheck(count($result['unmapped'])===2 && $result['unmapped'][0]['reason']==='lob' && $result['unmapped'][1]['reason']==='account','Unknown LOB and COA rows are retained for administrator review');
    lrCheck($result['year']===2026 && $result['month']===8 && $result['unit']==='Kanwil','Correct source period and unit');
    lrCheck(array_column($result['all_sheet_sign_rule_inputs'],'calculation_amount')===['200.2501','-300.7501'],'Synthetic all-sheet signs invert in both directions');
});
lrFixture([
    ['KUR','Beban gaji karyawan','100.2501'],
    ['NON KUR','Beban gaji karyawan','20.5002'],
    ['KUR','Sewa gudang','-5.0003'],
    ['KUR','Beban Klaim','999.99'],
    ['NON KUR','Pendapatan Subrogasi','888.88'],
    ['KUR','Imbal Jasa Penjaminan Bruto','777.77'],
    ['KUR','Pendapatan jasa giro','-12.5004'],
    ['NON KUR','Pendapatan lainnya','4.5005'],
],static function($path)use($parser){
    $result=$parser->parse($path,'Surabaya'); $values=OracleLrSalaryParser::reportValues($result);
    lrCheck($result['unit']==='Surabaya' && $result['sheet']==='SURABAYA','Requested branch is read from its own exact sheet');
    lrCheck($values['beban gaji karyawan']===['KUR'=>'100.2501','NON KUR'=>'20.5002'] && $values['sewa gudang']['KUR']==='-5.0003','Branch KUR and NON KUR use the approved expense matching rule');
    lrCheck(!isset($values['beban klaim']) && !isset($values['pendapatan subrogasi']) && !isset($values['imbal jasa penjaminan bruto']),'Branch claim and guarantee-income rows stay excluded pending their own rules');
    lrCheck($values['pendapatan jasa giro']['KUR']==='12.5004' && $values['pendapatan lainnya']['NON KUR']==='-4.5005','Other-income details use the approved workpaper sign in every unit');
    lrCheck($result['segment_counts']===['KUR'=>3,'NON KUR'=>2,'PEN'=>0] && $result['sign_rule_inputs']===[],'Only approved branch rows are counted');
},'SURABAYA');
lrFixture([
    ['KUR','Beban lembur karyawan','10.05'], ['kur','Beban lembur karyawan','-2.01'],
    ['KUR','Imbal jasa profesional - lainnya','30.01'], ['KUR','Sewa gudang','-5.02'],
    ['KUR','Beban penyusutan aset tetap - gedung','9.03'], ['KUR','Beban Klaim','7.04'],
    ['KUR','Tranportasi dinas dalam negeri','11.00'], ['KUR','Transportasi dinas dalam negeri','2.00'],
    ['NON KUR','Sewa gudang','999'], ['KUR Mikro','Sewa gudang','888'], ['KUR','Sewa gudang lainnya','777'],
],static function($path)use($parser){
    $result=$parser->parse($path); $values=OracleLrSalaryParser::reportValues($result);
    lrCheck($values['beban lembur karyawan']['KUR']==='8.04','Multiple signed rows sum independently by description');
    lrCheck($values['imbal jasa profesional - lainnya']['KUR']==='30.01' && $values['sewa gudang']['KUR']==='-5.02','Administrative expenses retain exact signs');
    lrCheck($values['beban penyusutan aset tetap - gedung']['KUR']==='9.03' && $values['beban klaim']['KUR']==='7.04','Depreciation and claims match exact labels');
    lrCheck($values['transportasi dinas dalam negeri']['KUR']==='13.00','Only verified source spelling alias is allowed');
    lrCheck(count($result['matches'])===9 && count($values)===6 && $values['sewa gudang']['NON KUR']==='999.00' && !isset($values['beban gaji karyawan']),'Segments stay isolated and missing accounts remain absent');
    lrCheck(in_array('Beban gaji karyawan',$result['missing_labels'],true),'Audit exposes unmatched expense labels');
});
lrFixture([['KUR','Sewa gudang','0']],static function($path)use($parser){ lrCheck(OracleLrSalaryParser::reportValues($parser->parse($path))['sewa gudang']['KUR']==='0.00','Source zero is distinct from missing'); });
lrCheck(OracleLrSalaryParser::reportValues(['rule'=>OracleLrSalaryParser::LEGACY_RULE,'matches'=>[['source_amount'=>'10.01'],['source_amount'=>'-2.02']]])===['beban gaji karyawan'=>['KUR'=>'7.99']],'Legacy salary imports remain readable');
lrCheck(OracleLrSalaryParser::reportValues(['rule'=>OracleLrSalaryParser::KUR_RULE,'matches'=>[['description'=>'Sewa gudang','source_amount'=>'10.01']]])===['sewa gudang'=>['KUR'=>'10.01']],'Legacy KUR-only expense imports never fabricate NON KUR');
lrFixture([['NON KUR','Sewa gudang','10.01']],static function($path)use($parser){
    $result=$parser->parse($path);
    lrCheck(OracleLrSalaryParser::reportValues($result)===['sewa gudang'=>['NON KUR'=>'10.01']] && $result['segment_counts']['KUR']===0,'A NON KUR-only workbook is valid without adding a KUR zero');
});
lrFixture([
    ['NON KUR','Beban gaji karyawan','100.01'], ['non   kur','Beban gaji karyawan','-25.02'], ['NON KUR','Beban gaji karyawan','0.01'],
    ['KUR','Beban gaji karyawan','10.05'], ['PEN','Beban gaji karyawan','999'], ['NON KUR lainnya','Beban gaji karyawan','888'],
    ['NON KUR','Beban gaji karyawan lainnya','777'], ['NON KUR','Sewa gudang','0'], ['NON KUR','Beban Klaim','-5.06'],
],static function($path)use($parser){
    $result=$parser->parse($path); $values=OracleLrSalaryParser::reportValues($result);
    lrCheck($values['beban gaji karyawan']===['NON KUR'=>'75.00','KUR'=>'10.05','PEN'=>'999.00'],'KUR, NON KUR and PEN remain independently grouped');
    lrCheck($values['sewa gudang']===['NON KUR'=>'0.00'] && $values['beban klaim']===['NON KUR'=>'-5.06'],'NON KUR zero and negative values retain meaning');
    lrCheck($result['segment_counts']===['KUR'=>1,'NON KUR'=>5,'PEN'=>1],'Source row counts reflect each exact segment');
    lrCheck(!in_array('Beban gaji karyawan',$result['missing_labels_by_segment']['NON KUR'],true) && in_array('Sewa gudang',$result['missing_labels_by_segment']['KUR'],true),'Missing-account audit is segment specific');
});
foreach (['774895270.36000001'=>'774895270.36000001','-5867662416.0000001'=>'-5867662416.0000001','-0.010000001'=>'-0.010000001','7.7489527036000001e8'=>'774895270.36000001'] as $raw=>$expected) lrCheck(OracleLrSalaryParser::money($raw)===$expected,'Every source digit / exact sign retained: '.$raw);
lrFixture([['KUR','Beban gaji karyawan','100.4999'],['KUR','Beban gaji karyawan','0.0002'],['NON KUR','Beban gaji karyawan','-2.5000'],['NON KUR','Beban gaji karyawan','0.0001']],static function($path)use($parser){
    $result=$parser->parse($path); $values=OracleLrSalaryParser::reportValues($result);
    lrCheck($values['beban gaji karyawan']===['KUR'=>'100.5001','NON KUR'=>'-2.4999'],'All decimals are summed before any rounding');
    lrCheck(App\Libraries\LrMoney::reportDisplay($values['beban gaji karyawan']['KUR'])==='101' && App\Libraries\LrMoney::reportDisplay($values['beban gaji karyawan']['NON KUR'])==='*2','Presentation rounds the final exact value, not individual rows');
    lrCheck($result['matches'][0]['source_amount']==='100.4999' && $result['matches'][0]['amount']==='100.4999','Persisted raw and canonical source amounts retain full precision');
});
lrCheck(App\Libraries\LrMoney::add('0.0049','0.0049')==='0.0098' && App\Libraries\LrMoney::add('0.00000000000000001','0.00000000000000002')==='0.00000000000000003','Tiny fractions are never discarded');
foreach ([['Pendapatan jasa giro','-100.5001','100.5001'],['Pendapatan jasa giro','100.5001','-100.5001'],['Pendapatan lainnya','-2.00','2.00'],['LABA SEBELUM PAJAK','7.25','-7.25'],['Beban gaji karyawan','-9.50','-9.50']] as [$label,$source,$expected]) lrCheck(App\Libraries\LrSignRules::calculationValue($label,$source)===$expected,'Explicit sign rule: '.$label.' '.$source);
lrCheck(App\Libraries\LrSignRules::calculationValue('Pendapatan jasa giro lainnya','-5.00')==='-5.00','Sign rule uses exact labels, not partial text');
foreach ([['LABA TAHUN BERJALAN','SURABAYA','-100.25','100.25'],['JUMLAH LABA KOMPREHENSIF TAHUN BERJALAN','BANYUWANGI','100.25','-100.25'],['LABA TAHUN BERJALAN','KANWIL','7.00','-7.00']] as [$label,$sheet,$source,$expected]) lrCheck(App\Libraries\LrSignRules::calculationValue($label,$source,$sheet)===$expected,'All-sheet sign rule: '.$sheet.' '.$label);
lrCheck(App\Libraries\LrSignRules::calculationValue('Pendapatan jasa giro','-5.00','SURABAYA')==='-5.00','Kanwil-only rules do not leak to another sheet');
lrFixture([['KUR','Pendapatan jasa giro','10.2501'],['NON KUR','Pendapatan lainnya','-4.5002']],static function($path)use($parser){
    $result=$parser->parse($path); $values=OracleLrSalaryParser::reportValues($result);
    lrCheck($values['pendapatan jasa giro']['KUR']==='-10.2501' && $values['pendapatan lainnya']['NON KUR']==='4.5002','Parser applies both directions of the exact sign rule');
    lrCheck($result['matches'][0]['source_amount']==='10.2501' && $result['matches'][0]['calculation_amount']==='-10.2501','Source and transformed values remain separately auditable');
    lrCheck($result['sign_rule_inputs'][0]['source_amount']==='100.5001' && $result['sign_rule_inputs'][0]['calculation_amount']==='-100.5001','Unsegmented pre-tax-profit source uses the same sign rule');
});
foreach (['1234.499999'=>'1.234','1234.500000'=>'1.235','-1234.500000'=>'*1.235','-0.0001'=>'*0','0.0000'=>'0','9999999999999999999999.4999'=>'9.999.999.999.999.999.999.999'] as $raw=>$expected) lrCheck(App\Libraries\LrMoney::reportDisplay($raw)===$expected,'Exact whole-rupiah formatting: '.$raw);
lrReject([['KUR','Beban gaji karyawan','(123)']],'valid');
lrReject([['KUR','Beban gaji karyawan','99',' t="str"']],'angka sumber');
lrReject([['KUR','Beban gaji karyawan','99','', '<f>1+2</f>']],'angka sumber');
lrReject([['KUR','Beban gaji karyawan',null]],'angka sumber');
lrFixture([['PEN','Beban gaji karyawan','99']],static function($path)use($parser){ lrCheck(OracleLrSalaryParser::reportValues($parser->parse($path))['beban gaji karyawan']['PEN']==='99.00','PEN follows its own Excel grouping'); });
lrFixture([
    ['KUR','Beban Pajak PPh 21 Non Karywan','900.5001'],
    ['NON KUR','Beban Pajak PPh 21 Non Karywan','125.5001'],
],static function($path)use($parser){
    $result=$parser->parse($path,'Surabaya');
    $values=OracleLrSalaryParser::reportValues($result);
    lrCheck($values['pendapatan subrogasi']===['NON KUR'=>'-125.5001'],'Every branch conditionally includes the exact PPh 21 Non Karywan source in NON KUR subrogation with the approved sign');
    lrCheck(count($result['unmapped'])===1 && $result['unmapped'][0]['reason']==='segment','The same PPh source in KUR is retained for review and excluded from calculation');
},'SURABAYA');
lrFixture([['NON KUR','Beban gaji karyawan','10']],static function($path)use($parser){
    $values=OracleLrSalaryParser::reportValues($parser->parse($path,'Malang'));
    lrCheck(!isset($values['pendapatan subrogasi']),'A missing PPh 21 Non Karywan source never fabricates a zero subrogation value');
},'MALANG');
lrFixture([
    ['KUR','Pendapatan premi penjaminan kredit','-100.2501'],
    ['PEN','Pendapatan premi penjaminan kredit','-25.5002'],
],static function($path)use($parser){
    $values=OracleLrSalaryParser::reportValues($parser->parse($path,'Surabaya'));
    lrCheck($values['imbal jasa penjaminan bruto']===['KUR'=>'100.2501','PEN'=>'25.5002'],'Every branch inverts Oracle premium revenue for KUR and PEN without losing decimals');
},'SURABAYA');
lrFixture([['KUR','Pendapatan premi penjaminan kredit','-100.2501']],static function($path)use($parser){
    $values=OracleLrSalaryParser::reportValues($parser->parse($path,'Kediri'));
    lrCheck($values['imbal jasa penjaminan bruto']===['KUR'=>'100.2501'] && !isset($values['imbal jasa penjaminan bruto']['PEN']),'A missing PEN premium source remains absent instead of becoming zero');
},'KEDIRI');
lrFixture([['NON KUR','Beban gaji karyawan','0.001']],static function($path)use($parser){ lrCheck(OracleLrSalaryParser::reportValues($parser->parse($path))['beban gaji karyawan']['NON KUR']==='0.001','NON KUR fractions beyond cents are accepted and retained'); });
lrReject([['NON KUR','Beban gaji karyawan','99','', '<f>1+2</f>']],'angka sumber');
lrReject([['KUR','Beban gaji karyawan','99']],'KANWIL','SURABAYA');
lrReject([['KUR','Beban gaji karyawan','99']],'Header H7','KANWIL','Beginning Balance');
lrReject([['KUR','Beban gaji karyawan','99']],'makro','KANWIL','Ending Balance',static fn($zip)=>$zip->addFromString('xl/vbaProject.bin','test'));
lrReject([['KUR','Beban gaji karyawan','99',' s="1"']],'angka sumber','KANWIL','Ending Balance',static fn($zip)=>$zip->addFromString('xl/styles.xml','<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><cellXfs><xf numFmtId="0"/><xf numFmtId="14"/></cellXfs></styleSheet>'));
if (($argv[1]??'')==='--source') {
    $result=$parser->parse('C:/Users/Jamkrindo/Downloads/LR SEKANWIL YTD AGUSTUS 2026.xlsx');
    $values=OracleLrSalaryParser::reportValues($result);
    lrCheck($values['beban gaji karyawan']['KUR']==='774895270.36000001','Source Kanwil KUR salary retains every digit from row 23');
    lrCheck($values['beban gaji karyawan']['NON KUR']==='340370165.67000002','Source Kanwil NON KUR salary retains every digit from row 24');
    lrCheck($values['pendapatan jasa giro']===['NON KUR'=>'12009993.00','KUR'=>'2711823.00'] && $values['pendapatan lainnya']['NON KUR']==='442503.47','Negative other-income sources become positive calculation values');
    lrCheck($result['sign_rule_inputs'][0]['source_amount']==='7126234676.1700001' && $result['sign_rule_inputs'][0]['calculation_amount']==='-7126234676.1700001','Positive LABA SEBELUM PAJAK source becomes negative future-calculation value');
    lrCheck(count($result['all_sheet_sign_rule_inputs'])===12,'Two all-sheet sign inputs captured from each of six sheets');
    $global=[]; foreach ($result['all_sheet_sign_rule_inputs'] as $input) $global[$input['sheet']][OracleLrSalaryParser::normalizeLabel($input['description'])]=$input;
    lrCheck($global['KANWIL']['laba tahun berjalan']['calculation_amount']==='-7129179041.1700001' && $global['SURABAYA']['laba tahun berjalan']['calculation_amount']==='90099233401.539993','Positive Kanwil and negative Surabaya running profit signs both invert');
    lrCheck($global['BANYUWANGI']['jumlah laba komprehensif tahun berjalan']['calculation_amount']==='16643973542.41','Comprehensive running profit is inverted outside Kanwil');
    lrCheck(count($values)===47,'Source expense and other-income labels plus verified transportation spelling');
    $expectedBranchRows=[
        'Surabaya'=>['KUR'=>87,'NON KUR'=>188,'PEN'=>7], 'Kediri'=>['KUR'=>86,'NON KUR'=>142,'PEN'=>6],
        'Malang'=>['KUR'=>81,'NON KUR'=>144,'PEN'=>5], 'Madiun'=>['KUR'=>83,'NON KUR'=>136,'PEN'=>5],
        'Banyuwangi'=>['KUR'=>82,'NON KUR'=>140,'PEN'=>6],
    ];
    $expectedSubrogationTax=['Kediri'=>'-1633980.00','Madiun'=>'-140571.00'];
    foreach ($expectedBranchRows as $unit=>$counts) {
        $branch=$parser->parse('C:/Users/Jamkrindo/Downloads/LR SEKANWIL YTD AGUSTUS 2026.xlsx',$unit);
        $branchValues=OracleLrSalaryParser::reportValues($branch);
        lrCheck($branch['segment_counts']===$counts,$unit.' source branch row counts are exact');
        lrCheck(isset($branchValues['beban gaji karyawan'],$branchValues['pendapatan jasa giro'],$branchValues['beban klaim']),$unit.' includes the complete approved realization sources');
        lrCheck(($branchValues['imbal jasa penjaminan bruto']['PEN']??null)===($unit==='Madiun'?'400000.00':null),$unit.' PEN premium is calculated only when its Oracle COA is present');
        $tax=array_values(array_filter($branch['matches'],static fn(array $row): bool => OracleLrSalaryParser::normalizeLabel((string)$row['description'])==='beban pajak pph 21 non karywan'));
        lrCheck(($tax[0]['calculation_amount']??null)===($expectedSubrogationTax[$unit]??null),$unit.' conditionally applies the exact PPh 21 Non Karywan subrogation component');
        lrCheck(count($branch['all_sheet_sign_rule_inputs'])===12 && $branch['all_sheet_sign_rule_inputs'][0]['sheet']==='KANWIL',$unit.' retains the correctly labeled all-sheet audit values');
    }
    echo 'Source checked read-only: '.count($result['matches']).' matched rows, '.count($values).' report accounts; exact decimals and sign rules reconciled.' . "\n";
}
echo "Oracle LR salary parser: $checks checks OK.\n";
