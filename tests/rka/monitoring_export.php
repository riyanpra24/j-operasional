<?php
define('APPPATH', __DIR__ . '/../../app/');
define('WRITEPATH', __DIR__ . '/../../writable/');
foreach (['RkaMoney','RkaCalculator','LrMoney','OracleLrSalaryParser','MonitoringAnggaran','MonitoringAnggaranExport'] as $library) require APPPATH . 'Libraries/' . $library . '.php';

$monitor = new App\Libraries\MonitoringAnggaran(); $schema = $monitor->rows(null);
$budgets = []; $actuals = [];
foreach ($schema as $item) {
    if ($item['group']) continue;
    $sourceRow = substr($item['key'], 4); $budgets['H'.$sourceRow] = '0.00';
    $label = $sourceRow === '71' ? 'Transportasi dinas dalam negeri' : $item['label'];
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
foreach (['C3','D3','C64','D64'] as $ref) if ($get(1,$ref,'f') === null) throw new RuntimeException('Missing consolidated formula at '.$ref);
if ($get(2,'C4','f') !== null || $get(2,'C4','v') !== null) throw new RuntimeException('Unit section headings must match the reference template.');
if (!str_contains($get(1,'D4','f') ?? '', "'KANWIL'!D5") || !str_contains($get(1,'D4','f') ?? '', "'BANYUWANGI'!D5")) throw new RuntimeException('Missing consolidated sheet references.');
if ($get(2,'I5','is') !== '=jangan dihitung sebagai rumus' || $get(2,'I5','f') !== null) throw new RuntimeException('Notes must remain plain text.');
if ($get(1,'B4','is') !== 'Beban gaji karyawan' || $get(2,'B5','is') !== 'Beban gaji karyawan') throw new RuntimeException('Template labels not preserved.');
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
echo "Monitoring workbook export: passed\n";
