<?php
/** Workbook export: system detail values and auditable totals. */
ob_start();
define('FCPATH', realpath(__DIR__ . '/../../public') . DIRECTORY_SEPARATOR);
define('ENVIRONMENT', 'testing');
require __DIR__ . '/../../app/Config/Paths.php';
$paths = new Config\Paths();
require $paths->systemDirectory . '/Boot.php';
CodeIgniter\Boot::bootConsole($paths);

$calculator = new App\Libraries\RkaCalculator();
$blank = $calculator->calculate($calculator->zeros());
$surabayaRka = $blank;
$surabayaRka['C8'] = '123.45';
$surabayaRka['H8'] = '123.45';
$kediriRka = $blank;
$kediriRka['D11'] = '900.25';
$kediriRka['H11'] = '900.25';
$key = static fn (string $label): string => App\Libraries\OracleLrSalaryParser::normalizeLabel($label);
$realization = [
    'Surabaya' => [
        $key('VOLUME') => [
            'KUR' => '12.34', 'PEN' => '2.00', 'NON KUR' => '5.00',
            'KBG/SURETYSHIP' => '1.00', 'KONSUMTIF' => '2.00', 'PRODUKTIF' => '2.00',
            'TOTAL' => '19.34', '%' => '0.156666666666666667',
        ],
        $key('Pendapatan Subrogasi') => [
            'KBG/SURETYSHIP' => '1.00', 'KONSUMTIF' => '2.00', 'PRODUKTIF' => '3.00',
            'NON KUR' => '9.00', 'TOTAL' => '9.00',
        ],
    ],
    'Kediri' => [
        $key('Imbal Jasa Penjaminan Bruto') => ['KUR' => '77.70', 'TOTAL' => '77.70'],
    ],
];

$export = (new App\Libraries\LrDocumentExportService())->createFromData(
    ['Surabaya', 'Kediri'], 2027, 8, 'YTD',
    ['Surabaya' => $surabayaRka, 'Kediri' => $kediriRka],
    $realization
);
if (!is_file($export['path']) || !str_contains($export['filename'], 'YTD_AGUSTUS_2027_2_UNIT')) {
    throw new RuntimeException('Export file or filename is incorrect.');
}

$zip = new ZipArchive();
if ($zip->open($export['path']) !== true) throw new RuntimeException('Export workbook cannot be opened.');
$xml = static function (ZipArchive $zip, string $path): DOMDocument {
    $document = new DOMDocument();
    if (!$document->loadXML((string) $zip->getFromName($path))) throw new RuntimeException('Invalid XML: ' . $path);
    return $document;
};
$workbook = $xml($zip, 'xl/workbook.xml');
$workbookXPath = new DOMXPath($workbook);
$workbookXPath->registerNamespace('m', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
$workbookXPath->registerNamespace('r', 'http://schemas.openxmlformats.org/officeDocument/2006/relationships');
$names = [];
foreach ($workbookXPath->query('//m:sheets/m:sheet') as $sheet) $names[] = $sheet->getAttribute('name');
if ($names !== ['SURABAYA', 'KEDIRI']) throw new RuntimeException('Unit sheet filter is incorrect.');

$rels = $xml($zip, 'xl/_rels/workbook.xml.rels');
$relsXPath = new DOMXPath($rels);
$relsXPath->registerNamespace('p', 'http://schemas.openxmlformats.org/package/2006/relationships');
$targets = [];
foreach ($relsXPath->query('//p:Relationship') as $relationship) $targets[$relationship->getAttribute('Id')] = ltrim($relationship->getAttribute('Target'), '/');
$sheetPaths = [];
foreach ($workbookXPath->query('//m:sheets/m:sheet') as $sheet) {
    $sheetPaths[$sheet->getAttribute('name')] = $targets[$sheet->getAttributeNS('http://schemas.openxmlformats.org/officeDocument/2006/relationships', 'id')];
}
$cell = static function (DOMDocument $document, string $reference): DOMElement {
    $xpath = new DOMXPath($document);
    $xpath->registerNamespace('m', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
    $node = $xpath->query('//m:c[@r="' . $reference . '"]')->item(0);
    if (!$node instanceof DOMElement) throw new RuntimeException('Missing cell ' . $reference);
    return $node;
};
$value = static function (DOMElement $cell): string {
    foreach ($cell->childNodes as $child) {
        if ($child->localName === 'v' || $child->localName === 'is') return trim($child->textContent);
    }
    return '';
};
$formula = static function (DOMElement $cell): string {
    foreach ($cell->childNodes as $child) {
        if ($child->localName === 'f') return '=' . trim($child->textContent);
    }
    return '';
};
$surabaya = $xml($zip, $sheetPaths['SURABAYA']);
$kediri = $xml($zip, $sheetPaths['KEDIRI']);
foreach ([$surabaya, $kediri] as $branchSheet) {
    $formulaXPath = new DOMXPath($branchSheet);
    $formulaXPath->registerNamespace('m', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
    foreach ($formulaXPath->query('//m:sheetData/m:row/m:c[m:f]') as $formulaCell) {
        if (!preg_match('/^[KR]\d+$/D', $formulaCell->getAttribute('r'))) {
            throw new RuntimeException('Branch detail or percentage cell unexpectedly retained an Excel formula.');
        }
    }
}
if ($value($cell($surabaya, 'D2')) !== 'LABA RUGI RKA TAHUN 2027'
    || $value($cell($surabaya, 'F9')) !== '123.45'
    || $value($cell($surabaya, 'L9')) !== '12.34'
    || $value($cell($surabaya, 'Q9')) !== '5.00'
    || $value($cell($surabaya, 'R9')) !== '19.34'
    || $value($cell($surabaya, 'S9')) !== '0.156666666666666667'
    || $value($cell($kediri, 'G12')) !== '900.25'
    || $value($cell($kediri, 'L12')) !== '77.70') {
    throw new RuntimeException('RKA or realization values were not written to the expected template cells.');
}
if ($cell($surabaya, 'F9')->getAttribute('s') === '' || $cell($surabaya, 'S9')->getAttribute('s') === '') {
    throw new RuntimeException('Template styles were not preserved.');
}
if ($formula($cell($surabaya, 'F9')) !== ''
    || $formula($cell($surabaya, 'K9')) !== '=SUM(F9:J9)'
    || $formula($cell($surabaya, 'Q9')) !== ''
    || $formula($cell($surabaya, 'R9')) !== '=SUM(L9:M9,Q9)'
    || $formula($cell($surabaya, 'S9')) !== ''
    || $formula($cell($surabaya, 'F15')) !== ''
    || $formula($cell($kediri, 'K12')) !== '=SUM(F12:J12)') {
    throw new RuntimeException('Only the visible total columns may have per-unit formulas.');
}
if ($value($cell($surabaya, 'S12')) !== '' || $value($cell($kediri, 'R9')) !== '') {
    throw new RuntimeException('Unused realization cells must not show source-template values.');
}
if ($formula($cell($surabaya, 'S12')) !== '' || $formula($cell($kediri, 'R9')) !== '') {
    throw new RuntimeException('Missing realization must remain blank, not calculate as zero.');
}
if ($value($cell($surabaya, 'Q20')) !== '9.00' || $formula($cell($surabaya, 'Q20')) !== ''
    || $value($cell($surabaya, 'R20')) !== '9.00' || $formula($cell($surabaya, 'R20')) !== '=SUM(L20:M20,Q20)') {
    throw new RuntimeException('Adjusted NON KUR values must feed an auditable total without overwriting the system result.');
}

$allXml = '';
for ($index = 0; $index < $zip->numFiles; $index++) {
    $name = (string) $zip->getNameIndex($index);
    if (preg_match('/comments|vmlDrawing|persons\/person/i', $name)) throw new RuntimeException('Template comments must not be exported.');
    if (str_ends_with($name, '.xml')) $allXml .= (string) $zip->getFromIndex($index);
}
if (stripos($allXml, 'BOPO') !== false || stripos($allXml, '4 MILLIAR DIPINDAH KE PUSAT') !== false) {
    throw new RuntimeException('BOPO or source-template comments leaked into the export.');
}
$zip->close();
unlink($export['path']);

$rkaForConsolidation = array_fill_keys(App\Libraries\RkaCalculator::UNITS, $blank);
$rkaForConsolidation['Surabaya'] = $surabayaRka;
$rkaForConsolidation['Korporat Kanwil'] = $surabayaRka;
$volumeTotal = [$key('VOLUME') => ['KUR' => '12.34', 'NON KUR' => '5.00', 'TOTAL' => '17.34']];
$realizationForConsolidation = ['Surabaya' => $volumeTotal, 'Korporat Kanwil' => $volumeTotal];
foreach ([App\Libraries\RkaCalculator::UNITS, ['Korporat Kanwil']] as $selection) {
    $filtered = (new App\Libraries\LrDocumentExportService())->createFromData(
        $selection, 2027, 8, 'YTD', $rkaForConsolidation, $realizationForConsolidation
    );
    $filteredZip = new ZipArchive();
    if ($filteredZip->open($filtered['path']) !== true) throw new RuntimeException('Filtered export cannot be opened.');
    $corporate = $xml($filteredZip, 'xl/worksheets/sheet1.xml');
    $corporateFormula = $formula($cell($corporate, 'F9'));
    if (count($selection) === 7 && (
        $corporateFormula !== "=SUM('KANWIL'!F9,'SURABAYA'!F9,'KEDIRI'!F9,'MALANG'!F9,'MADIUN'!F9,'BANYUWANGI'!F9)"
        || $formula($cell($corporate, 'K9')) !== "=SUM('KANWIL'!K9,'SURABAYA'!K9,'KEDIRI'!K9,'MALANG'!K9,'MADIUN'!K9,'BANYUWANGI'!K9)"
        || $formula($cell($corporate, 'R9')) !== "=SUM('KANWIL'!R9,'SURABAYA'!R9,'KEDIRI'!R9,'MALANG'!R9,'MADIUN'!R9,'BANYUWANGI'!R9)"
    )) {
        throw new RuntimeException('Full export totals must show the six contributing sheet names.');
    }
    if (count($selection) === 1 && ($corporateFormula !== '' || $formula($cell($corporate, 'K9')) !== '=SUM(F9:J9)'
        || $formula($cell($corporate, 'R9')) !== '=SUM(L9:M9,Q9)')) {
        throw new RuntimeException('Filtered export must not contain references to removed sheets.');
    }
    $filteredZip->close();
    unlink($filtered['path']);
}

$unmatchedCorporateRka = $rkaForConsolidation;
$unmatchedCorporateRka['Korporat Kanwil']['C8'] = '100.00';
$unmatchedCorporateRka['Korporat Kanwil']['H8'] = '100.00';
$unmatched = (new App\Libraries\LrDocumentExportService())->createFromData(
    App\Libraries\RkaCalculator::UNITS, 2027, 8, 'YTD', $unmatchedCorporateRka, $realizationForConsolidation
);
$unmatchedZip = new ZipArchive();
if ($unmatchedZip->open($unmatched['path']) !== true) throw new RuntimeException('Mismatch export cannot be opened.');
$unmatchedSheet = $xml($unmatchedZip, 'xl/worksheets/sheet1.xml');
if ($value($cell($unmatchedSheet, 'F9')) !== '100.00' || $formula($cell($unmatchedSheet, 'F9')) !== ''
    || $formula($cell($unmatchedSheet, 'K9')) !== '=SUM(F9:J9)') {
    throw new RuntimeException('A corporate budget that differs from source sheets must keep its system value.');
}
$unmatchedZip->close();
unlink($unmatched['path']);

session()->set([
    'auth_user_id' => 1,
    'auth_role' => 'akutansi',
    'auth_display_name' => 'User Akutansi',
    'auth_username' => 'akutansi',
]);
$request = Config\Services::incomingrequest(new Config\App(), false);
Config\Services::injectMock('request', $request);
$controller = new App\Controllers\Akutansi();
$controller->initController($request, Config\Services::response(null, false), Config\Services::logger());
$html = $controller->exportDokumen();
$dom = new DOMDocument();
@$dom->loadHTML('<?xml encoding="UTF-8">' . $html);
$xpath = new DOMXPath($dom);
$viewChecks = [
    'submenu' => $xpath->query('//a[contains(@class,"nav-sublink") and contains(normalize-space(.),"Export Dokumen")]')->length === 1,
    'form' => $xpath->query('//form[@data-export-document-form and @method="post"]')->length === 1,
    'basis' => $xpath->query('//select[@name="jenis_laporan"]/option')->length === 2,
    'months' => $xpath->query('//select[@name="bulan"]/option')->length === 12,
    'units' => $xpath->query('//input[@name="unit_kerja[]" and @data-export-unit]')->length === 7,
    'checked' => $xpath->query('//input[@name="unit_kerja[]" and @checked]')->length === 7,
    'bopo' => str_contains($html, 'bagian BOPO dari template tidak disertakan'),
];
if (in_array(false, $viewChecks, true)) {
    throw new RuntimeException('Export Dokumen page or Accounting submenu is incomplete: ' . implode(', ', array_keys(array_filter($viewChecks, static fn (bool $passed): bool => !$passed))));
}
fwrite(STDOUT, "Export document workbook: passed\n");
