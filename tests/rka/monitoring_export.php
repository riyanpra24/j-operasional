<?php
define('APPPATH', __DIR__ . '/../../app/');
define('WRITEPATH', __DIR__ . '/../../writable/');
foreach (['RkaMoney','RkaCalculator','LrMoney','OracleLrSalaryParser','MonitoringAnggaran','MonitoringAnggaranExport'] as $library) require APPPATH . 'Libraries/' . $library . '.php';

$monitor = new App\Libraries\MonitoringAnggaran(); $schema = $monitor->rows(null);
$budgets = []; $actuals = [];
foreach ($schema as $item) {
    if ($item['group']) continue;
    $sourceRow = substr($item['key'], 4); $budgets['H'.$sourceRow] = '0.00';
    $label = match ($sourceRow) { '57'=>'Imbal jasa profesional - konsultan manajemen', '71'=>'Transportasi dinas dalam negeri', '74'=>'Transportasi dinas luar negeri', default=>$item['label'] };
    $actuals[App\Libraries\OracleLrSalaryParser::normalizeLabel($label)] = ['TOTAL'=>'0.00'];
}
$budgets['H29'] = '100.00'; $actuals['beban gaji karyawan']['TOTAL'] = '120.00';
$rows = $monitor->rows($budgets, '', $actuals);
$units = ['Konsolidasi','Kanwil','Surabaya','Kediri','Malang','Madiun','Banyuwangi'];
$sheets = []; foreach ($units as $unit) $sheets[$unit] = ['rows'=>$rows, 'notes'=>[]];
$sheets['Kanwil']['notes']['row_29'] = '=jangan dihitung sebagai rumus';
// Corporate matches the sum of six test units, making the inter-sheet formula valid.
$sheets['Konsolidasi']['rows'][1]['realization'] = '720.00';
$service = new App\Libraries\MonitoringAnggaranExport();
$result = $service->create($sheets, 'Kanwil', 2026, 9);
$zip = new ZipArchive(); if ($zip->open($result['path']) !== true) throw new RuntimeException('Workbook cannot be opened.');
$docs = [];
for ($n=1; $n<=7; $n++) {
    $doc = new DOMDocument(); if (!$doc->loadXML($zip->getFromName('xl/worksheets/sheet'.$n.'.xml'))) throw new RuntimeException('Missing sheet '.$n);
    $xp = new DOMXPath($doc); $xp->registerNamespace('m', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main'); $docs[] = $xp;
}
$get = static fn ($sheet, $ref, $part) => $docs[$sheet-1]->query('//m:c[@r="'.$ref.'"]/m:'.$part)->item(0)?->textContent;
if ($get(2,'C5','v') !== '100.00' || $get(2,'D5','v') !== '120.00') throw new RuntimeException('Incorrect source values.');
if ($get(2,'E5','v') !== '1.200000000000000000000000000000' || $get(2,'G5','v') !== '-0.450000000000000000000000000000' || $get(2,'H5','v') !== '-20.00') throw new RuntimeException('Incorrect cached calculations.');
foreach (['E5','F5','G5','H5'] as $ref) if ($get(2,$ref,'f') === null) throw new RuntimeException('Missing formula at '.$ref);
$last = 3 + count($schema);
foreach (['C3','D3','C'.$last,'D'.$last] as $ref) if ($get(1,$ref,'f') === null) throw new RuntimeException('Missing consolidated formula at '.$ref);
if ($get(2,'C4','f') !== null || $get(2,'C4','v') !== null) throw new RuntimeException('Unit section headings must match the reference template.');
if (!str_contains($get(1,'D4','f') ?? '', "'KANWIL'!D5") || !str_contains($get(1,'D4','f') ?? '', "'BANYUWANGI'!D5")) throw new RuntimeException('Missing consolidated sheet references.');
if ($get(2,'I5','is') !== '=jangan dihitung sebagai rumus' || $get(2,'I5','f') !== null) throw new RuntimeException('Notes must remain plain text.');
if ($get(1,'B4','is') !== 'Beban gaji karyawan' || $get(2,'B5','is') !== 'Beban gaji karyawan') throw new RuntimeException('Template labels not preserved.');
foreach ($units as $i => $unit) foreach ($sheets[$unit]['rows'] as $position => $item) {
    if ($get($i+1, 'B'.(3+$position+($i ? 1 : 0)), 'is') !== $item['label']) throw new RuntimeException('Missing or misplaced RKA label on '.$unit.': '.$item['label']);
}
foreach (['xl/externalLinks/externalLink1.xml','xl/worksheets/sheet8.xml','xl/sharedStrings.xml'] as $name) if ($zip->locateName($name) !== false) throw new RuntimeException('Unexpected external or historical data.');
$zip->close();
if (isset($argv[1]) && !copy($result['path'], $argv[1])) throw new RuntimeException('Could not save verification workbook.');
unlink($result['path']);
$missing = []; foreach ($units as $unit) $missing[$unit] = ['rows'=>$monitor->rows(null), 'notes'=>[]];
$missing['Konsolidasi']['rows'][1]['realization'] = '123.00';
$result = $service->create($missing, 'Konsolidasi', 2026, 9);
$zip = new ZipArchive(); $zip->open($result['path']); $doc = new DOMDocument(); $doc->loadXML($zip->getFromName('xl/worksheets/sheet1.xml')); $zip->close(); unlink($result['path']);
$xp = new DOMXPath($doc); $xp->registerNamespace('m', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
if ($xp->query('//m:c[@r="C4"]/m:v')->length || $xp->query('//m:c[@r="E4" and @t="str"]/m:f')->length !== 1) throw new RuntimeException('Missing inputs must not become zero.');
if ($xp->query('//m:c[@r="D4"]/m:v')->item(0)?->textContent !== '123.00' || $xp->query('//m:c[@r="D4"]/m:f')->length) throw new RuntimeException('Corporate source must not be replaced by an incomplete sum.');
$result = $service->create($sheets, 'Kanwil', 2027, 3);
if ($result['filename'] !== 'Monitoring_Anggaran_Maret_2027.xlsx') throw new RuntimeException('The selected export period must appear in the filename.');
$zip = new ZipArchive(); $zip->open($result['path']);
for ($n = 1; $n <= 7; $n++) {
    $doc = new DOMDocument(); $doc->loadXML($zip->getFromName('xl/worksheets/sheet'.$n.'.xml'));
    $xp = new DOMXPath($doc); $xp->registerNamespace('m', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
    $header = $n === 1 ? 2 : 3; $detail = $n === 1 ? 4 : 5;
    if (!str_contains($xp->query('//m:c[@r="D'.$header.'"]/m:is')->item(0)?->textContent ?? '', 'MARET 2027') || $xp->query('//m:c[@r="F'.$detail.'"]/m:f')->item(0)?->textContent !== '3/12') throw new RuntimeException('All sheets must use the selected export month and year.');
}
$zip->close(); unlink($result['path']);
$negativeRows = App\Libraries\MonitoringAnggaran::negativeDeviationRows($rows, 9);
if (array_column($negativeRows, 'key') !== ['group_29', 'row_29']) throw new RuntimeException('Negative-deviation export must retain only matching details and their group.');
$negativeSheets = []; foreach ($units as $unit) $negativeSheets[$unit] = ['rows' => $negativeRows, 'notes' => []];
$result = $service->create($negativeSheets, 'Kanwil', 2026, 9, 'negative_deviation');
if ($result['filename'] !== 'Monitoring_Anggaran_September_2026_Deviasi_Minus.xlsx') throw new RuntimeException('Negative-deviation export filename is incorrect.');
$zip = new ZipArchive(); $zip->open($result['path']);
$doc = new DOMDocument(); $doc->loadXML($zip->getFromName('xl/worksheets/sheet2.xml')); $zip->close(); unlink($result['path']);
$xp = new DOMXPath($doc); $xp->registerNamespace('m', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
if ($xp->query('//m:c[@r="B4"]/m:is/m:t')->item(0)?->textContent !== 'BEBAN SDM' || $xp->query('//m:c[@r="B5"]/m:is/m:t')->item(0)?->textContent !== 'Beban gaji karyawan') throw new RuntimeException('Negative-deviation workbook lost its matching group or detail.');
if ($xp->query('//m:c[@r="B6"]')->length !== 0 || $xp->query('//m:c[@r="G5"]/m:f')->length !== 1) throw new RuntimeException('Negative-deviation workbook must omit nonmatching rows while retaining formulas.');
$emptyNegativeSheets = []; foreach ($units as $unit) $emptyNegativeSheets[$unit] = ['rows' => [], 'notes' => []];
$result = $service->create($emptyNegativeSheets, 'Kanwil', 2026, 9, 'negative_deviation');
$zip = new ZipArchive(); $zip->open($result['path']); $doc = new DOMDocument(); $doc->loadXML($zip->getFromName('xl/worksheets/sheet2.xml')); $zip->close(); unlink($result['path']);
$xp = new DOMXPath($doc); $xp->registerNamespace('m', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
if ($xp->query('//m:c[@r="B4"]/m:is/m:t')->item(0)?->textContent !== 'Tidak ada deviasi negatif untuk periode ini.') throw new RuntimeException('An empty negative-deviation export must explain that no rows match.');
echo "Monitoring workbook export: passed\n";
