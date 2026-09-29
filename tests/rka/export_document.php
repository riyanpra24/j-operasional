<?php
ob_start();
define('FCPATH', realpath(__DIR__ . '/../../public') . DIRECTORY_SEPARATOR);
define('ENVIRONMENT', 'testing');
require __DIR__ . '/../../app/Config/Paths.php';
$paths = new Config\Paths();
require $paths->systemDirectory . '/Boot.php';
CodeIgniter\Boot::bootConsole($paths);

$calculator = new App\Libraries\RkaCalculator();
$rka = [];
foreach (App\Libraries\RkaCalculator::SOURCE_UNITS as $unit) {
    $inputs = $calculator->zeros();
    if ($unit === 'Surabaya') $inputs['C8'] = '100.00';
    if ($unit === 'Kediri') $inputs['C8'] = '50.00';
    $rka[$unit] = $calculator->calculate($inputs);
}
$corporateInputs = [];
foreach (App\Libraries\RkaCalculator::SOURCE_UNITS as $unit) $corporateInputs[$unit] = array_intersect_key($rka[$unit], $calculator->zeros());
$rka['Korporat Kanwil'] = $calculator->calculate($calculator->consolidate($corporateInputs));
$key = static fn (string $label): string => App\Libraries\OracleLrSalaryParser::normalizeLabel($label);
$realization = [
    'Surabaya' => [$key('VOLUME') => ['KUR' => '10.00', 'PEN' => '1.00', 'NON KUR' => '4.00', 'TOTAL' => '15.00', '%' => '0.15']],
    'Kediri' => [$key('VOLUME') => ['KUR' => '7.00', 'PEN' => '0.00', 'NON KUR' => '0.00', 'TOTAL' => '7.00', '%' => App\Libraries\LrMoney::divide('7.00', '50.00', 18)]],
    'Korporat Kanwil' => [$key('VOLUME') => ['KUR' => '17.00', 'PEN' => '1.00', 'NON KUR' => '4.00', 'TOTAL' => '22.00', '%' => App\Libraries\LrMoney::divide('22.00', '150.00', 18)]],
];
$export = (new App\Libraries\LrDocumentExportService())->createFromData(
    App\Libraries\RkaCalculator::UNITS, 2027, 8, 'YTD', $rka, $realization
);
if (!is_file($export['path']) || !str_contains($export['filename'], 'Realisasi_Anggaran_YTD_AGUSTUS_2027_SEKANWIL')) throw new RuntimeException('Export filename is incorrect.');

$zip = new ZipArchive();
if ($zip->open($export['path']) !== true) throw new RuntimeException('Export workbook cannot be opened.');
$xml = static function (ZipArchive $zip, string $path): DOMDocument { $document = new DOMDocument(); if (!$document->loadXML((string) $zip->getFromName($path))) throw new RuntimeException('Invalid XML: ' . $path); return $document; };
$workbook = $xml($zip, 'xl/workbook.xml');
$xpath = new DOMXPath($workbook);
$xpath->registerNamespace('m', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
$xpath->registerNamespace('r', 'http://schemas.openxmlformats.org/officeDocument/2006/relationships');
$names = [];
foreach ($xpath->query('//m:sheets/m:sheet') as $sheet) $names[] = $sheet->getAttribute('name');
if ($names !== array_map('strtoupper', App\Libraries\RkaCalculator::UNITS)) throw new RuntimeException('The complete consolidation workbook must be exported.');
$rels = $xml($zip, 'xl/_rels/workbook.xml.rels');
$relXpath = new DOMXPath($rels); $relXpath->registerNamespace('r', 'http://schemas.openxmlformats.org/package/2006/relationships');
$targets = [];
foreach ($relXpath->query('//r:Relationship') as $rel) $targets[$rel->getAttribute('Id')] = 'xl/' . ltrim($rel->getAttribute('Target'), '/');
$pathsBySheet = [];
foreach ($xpath->query('//m:sheets/m:sheet') as $sheet) $pathsBySheet[$sheet->getAttribute('name')] = $targets[$sheet->getAttributeNS('http://schemas.openxmlformats.org/officeDocument/2006/relationships', 'id')];
$cell = static function (DOMDocument $document, string $reference): DOMElement { $x = new DOMXPath($document); $x->registerNamespace('m', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main'); $node = $x->query('//m:c[@r="' . $reference . '"]')->item(0); if (!$node instanceof DOMElement) throw new RuntimeException('Missing cell ' . $reference); return $node; };
$value = static function (DOMElement $cell): string { foreach ($cell->childNodes as $child) if (in_array($child->localName, ['v','is'], true)) return trim($child->textContent); return ''; };
$formula = static function (DOMElement $cell): string { foreach ($cell->childNodes as $child) if ($child->localName === 'f') return '=' . trim($child->textContent); return ''; };
$surabaya = $xml($zip, $pathsBySheet['SURABAYA']);
$corporate = $xml($zip, $pathsBySheet['KORPORAT KANWIL']);
if ($value($cell($surabaya, 'D2')) !== 'LABA RUGI RKA TAHUN 2027' || $value($cell($surabaya, 'F8')) !== '100.00' || $value($cell($surabaya, 'L8')) !== '10.00' || $value($cell($surabaya, 'M8')) !== '1.00' || $value($cell($surabaya, 'N8')) !== '4.00') throw new RuntimeException('System values were not mapped to the Realisasi Anggaran template.');
if ($formula($cell($surabaya, 'K8')) !== '=SUM(F8:J8)' || $formula($cell($surabaya, 'O8')) !== '=SUM(L8:N8)' || $formula($cell($surabaya, 'P8')) !== '=O8/K8' || $formula($cell($corporate, 'F8')) !== '=KANWIL!F8+SURABAYA!F8+KEDIRI!F8+MALANG!F8+MADIUN!F8+BANYUWANGI!F8') throw new RuntimeException('Template formulas were not preserved.');
if ($formula($cell($surabaya, 'O4')) !== '' || $value($cell($surabaya, 'O4')) !== '') throw new RuntimeException('Broken template reference must not be exported.');
for ($i = 0; $i < $zip->numFiles; $i++) { $name = (string) $zip->getNameIndex($i); if (str_ends_with($name, '.xml') && str_contains((string) $zip->getFromIndex($i), '#REF!')) throw new RuntimeException('A broken reference remains in the export.'); }
$zip->close();
unlink($export['path']);

$audit = $export['audit'];
if (($audit['formulas_preserved'] ?? 0) !== 4092 || ($audit['system_values_checked'] ?? 0) < 9000 || ($audit['reconciled_totals'] ?? 0) < 2000 || ($audit['broken_template_formulas_removed'] ?? 0) !== 7) throw new RuntimeException('Export audit is incomplete.');
try { (new App\Libraries\LrDocumentExportService())->createFromData(['Surabaya'], 2027, 8, 'YTD', $rka, $realization); throw new RuntimeException('Partial export must be rejected.'); }
catch (RuntimeException $error) { if (!str_contains($error->getMessage(), 'seluruh unit')) throw $error; }

session()->set(['auth_user_id'=>1, 'auth_role'=>'akutansi', 'auth_display_name'=>'User Akutansi', 'auth_username'=>'akutansi']);
$request = Config\Services::incomingrequest(new Config\App(), false); Config\Services::injectMock('request', $request);
$controller = new App\Controllers\Akutansi(); $controller->initController($request, Config\Services::response(null, false), Config\Services::logger());
$html = $controller->exportDokumen();
if (!str_contains($html, 'Cakupan export') || str_contains($html, 'data-export-unit') || !str_contains($html, 'Dokumen selalu memuat Korporat Kanwil')) throw new RuntimeException('Export document page does not match the new template.');
fwrite(STDOUT, "Export document workbook: passed\n");
