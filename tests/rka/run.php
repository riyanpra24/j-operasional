<?php
/** Standalone financial regression checks: php tests/rka/run.php */
require __DIR__ . '/../../app/Libraries/RkaMoney.php';
require __DIR__ . '/../../app/Libraries/RkaCalculator.php';
require __DIR__ . '/../../app/Libraries/RkaWorkbookParser.php';

use App\Libraries\RkaCalculator;
use App\Libraries\RkaMoney;
use App\Libraries\RkaWorkbookParser;

$checks = 0;
function check(bool $condition, string $message): void
{
    global $checks;
    if (! $condition) { throw new RuntimeException($message); }
    $checks++;
}
function rejects(callable $operation, string $message): void
{
    try { $operation(); } catch (RuntimeException|InvalidArgumentException $exception) { check(true, $message); return; }
    throw new RuntimeException($message);
}

foreach (['0' => '0.00', '-0.00' => '0.00', '1.2' => '1.20', '-5867662416' => '-5867662416.00', '1.234e3' => '1234.00', '1e-2' => '0.01', '9999999999999999999999.99' => '9999999999999999999999.99'] as $input => $expected) {
    check(RkaMoney::decimal((string) $input) === $expected, 'Decimal parse: ' . $input);
}
foreach (['' => '0.00', '1.234.567,89' => '1234567.89', '-1.234,56' => '-1234.56', '1.234' => '1234.00', '1234.56' => '1234.56'] as $input => $expected) {
    check(RkaMoney::localized($input) === $expected, 'Localized parse: ' . $input);
}
foreach (['0.001', '1e-3', '1e31', '10000000000000000000000.00', 'NaN', 'abc'] as $input) {
    rejects(static fn () => RkaMoney::decimal($input), 'Reject invalid financial value: ' . $input);
}
check(RkaMoney::format('-1234567.89') === '-1.234.567,89', 'Exact formatting');
check(RkaMoney::reportDisplay('-5867662416.00') === '*5.867.662.416', 'Report-only negative marker');
check(RkaMoney::reportDisplay('-0.01') === '*0,01', 'Negative cents retain precision');
check(RkaMoney::reportDisplay('22334975222.00') === '22.334.975.222', 'Positive reports unchanged');
check(RkaMoney::display('-5867662416.00') === '-5.867.662.416', 'Editable values retain minus sign');
foreach (['1234567.00' => '1.234.567', '0.00' => '0', '-1234.00' => '-1.234', '12.50' => '12,50', '0.01' => '0,01'] as $amount => $display) {
    check(RkaMoney::display($amount) === $display, 'Hide zero cents only: ' . $amount);
    check(RkaMoney::localized($display) === $amount, 'Display round trip preserves exact money: ' . $amount);
}

$calculator = new RkaCalculator();
$parser = new RkaWorkbookParser();
$path = __DIR__ . '/../../writable/templates/rka/Template RKA.xlsx';
$hash = hash_file('sha256', $path);
$parsed = $parser->parse($path);
check($parsed['year'] === 2026, 'Template year');
check(count($parsed['inputs']) === 675, 'All template inputs included');
$zip = new ZipArchive(); $zip->open($path);
$xmlText = $zip->getFromName('xl/worksheets/sheet1.xml');
$xml = simplexml_load_string($xmlText);
$xml->registerXPathNamespace('s', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
$cached = [];
foreach ($xml->xpath('//s:sheetData/s:row/s:c') as $cell) {
    $content = $cell->children('http://schemas.openxmlformats.org/spreadsheetml/2006/main');
    if (isset($content->v) && (string) $cell['t'] === '') { $cached[(string) $cell['r']] = (string) $content->v; }
}
$zip->close();
foreach ($parsed['calculated'] as $cell => $value) {
    check($value === RkaMoney::decimal($cached[$cell]), 'Template reconciliation: ' . $cell);
}
foreach (['H14' => '363399347931.00', 'H21' => '228878232889.00', 'H52' => '11977054143.00', 'H142' => '8580638694.00', 'H160' => '2770444225.00', 'H162' => '23328137062.00', 'H166' => '111192977980.00'] as $cell => $expected) {
    check($parsed['calculated'][$cell] === $expected, 'Key formula: ' . $cell);
}
$cases = [];
$sparseCases = [
    ['C11' => '100.10', 'C12' => '10.05', 'C13' => '20.01', 'C17' => '40.04', 'C18' => '-5.01', 'C19' => '10.02', 'C20' => '2.01', 'C29' => '0.01', 'C55' => '0.02', 'C145' => '0.03', 'C25' => '1.01', 'C164' => '-0.10'],
    ['D18' => '-5867662416.00', 'D19' => '17075082318.00', 'G11' => '90071992547409.91', 'G29' => '0.01'],
    ['E25' => '123.45', 'F164' => '-123.46', 'G55' => '0.01'],
    ['C11' => '99999999999999999999.99', 'C12' => '99999999999999999999.98'],
];
foreach ($sparseCases as $sparse) {
    $inputs = array_replace($calculator->zeros(), $sparse);
    $result = $calculator->calculate($inputs);
    $selected = [];
    foreach ([14, 21, 23, 25, 52, 142, 160, 162, 164, 166] as $row) {
        foreach (['C', 'D', 'E', 'F', 'G', 'H'] as $column) { $selected[$column . $row] = $result[$column . $row]; }
    }
    $cases[] = ['inputs' => $sparse, 'expected' => $selected];
}
check($cases[0]['expected']['H166'] === '47.89', 'Subtract commission and subrogation exactly as template');
check($cases[2]['expected']['H25'] === '123.45', 'Investment total auto sum');
check($cases[2]['expected']['H164'] === '-123.46', 'Other income total auto sum');
rejects(static fn () => $calculator->normalizeInputs(['C8' => '1.00']), 'Reject incomplete submitted inputs');
foreach (['C14','C21','C23','C52','C142','C160','C162','C166','H11','H166'] as $formulaCell) {
    $tamperedInputs = $calculator->zeros(); $tamperedInputs[$formulaCell] = '999.00';
    rejects(static fn () => $calculator->normalizeInputs($tamperedInputs), 'Reject forged manual formula result: '.$formulaCell);
}
$overflow = $calculator->zeros(); $overflow['C8'] = '9999999999999999999999.99'; $overflow['D8'] = '0.01';
rejects(static fn () => $calculator->calculate($overflow), 'Reject total overflow');

function modifiedWorkbook(string $source, string $xml, callable $operation): void
{
    $temporary = tempnam(sys_get_temp_dir(), 'rka-check-');
    try {
        copy($source, $temporary);
        $zip = new ZipArchive(); $zip->open($temporary); $zip->addFromString('xl/worksheets/sheet1.xml', $xml); $zip->close();
        $operation($temporary);
    } finally { if (is_file($temporary)) { unlink($temporary); } }
}
modifiedWorkbook($path, str_replace('C17+C18-C19-C20', 'C17+C18+C19+C20', $xmlText), static function ($modified) use ($parser): void {
    rejects(static fn () => $parser->parse($modified), 'Reject changed claim formula');
});
modifiedWorkbook($path, preg_replace('/(<c r="C11"[^>]*>.*?<v>)[^<]*(<\/v>)/s', '${1}100.01${2}', $xmlText), static function ($modified) use ($parser, $parsed): void {
    $result = $parser->parse($modified);
    check($result['inputs']['C11'] === '100.01', 'Input modified on disposable workbook');
    check($result['calculated']['C14'] !== $parsed['calculated']['C14'], 'Ignore stale Excel calculation cache');
});
modifiedWorkbook($path, preg_replace('/(<c r="C11"[^>]*>.*?<v>)[^<]*(<\/v>)/s', '${1}100.001${2}', $xmlText), static function ($modified) use ($parser): void {
    rejects(static fn () => $parser->parse($modified), 'Reject extra precision without silent rounding');
});
modifiedWorkbook($path, str_replace('</sheetData>', '<row r="167"><c r="C167"><v>100</v></c></row></sheetData>', $xmlText), static function ($modified) use ($parser): void {
    rejects(static fn () => $parser->parse($modified), 'Reject extra values outside template instead of silently omitting them');
});
check(hash_file('sha256', $path) === $hash, 'Original template preserved');
$blankPath = __DIR__ . '/../../writable/templates/rka/Template_RKA_JMK_KV_SBY.xlsx';
$blankHash = hash_file('sha256', $blankPath);
$blank = $parser->parse($blankPath);
check($blank['inputs'] === $calculator->zeros(), 'Download template has all 675 inputs empty/zero');
check(count(array_filter($blank['calculated'], static fn ($value) => $value !== '0.00')) === 0, 'Blank download recalculates to zero');
$blankZip = new ZipArchive(); $blankZip->open($blankPath);
$blankXml = $blankZip->getFromName('xl/worksheets/sheet1.xml'); $blankZip->close();
$document = new DOMDocument(); $document->loadXML($blankXml, LIBXML_NONET);
$xpath = new DOMXPath($document); $xpath->registerNamespace('s', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
foreach (['C11' => '100.10', 'C12' => '10.05', 'C13' => '20.01', 'D18' => '-5.01'] as $address => $value) {
    $cell = $xpath->query('//s:c[@r="' . $address . '"]')->item(0);
    check($cell !== null, 'Blank input address exists: ' . $address);
    $cell->removeAttribute('t');
    while ($cell->firstChild) { $cell->removeChild($cell->firstChild); }
    $cell->appendChild($document->createElementNS('http://schemas.openxmlformats.org/spreadsheetml/2006/main', 'v', $value));
}
modifiedWorkbook($blankPath, $document->saveXML(), static function ($modified) use ($parser): void {
    $result = $parser->parse($modified);
    check($result['calculated']['H14'] === '70.04', 'Filled blank template uses audited net-fee arithmetic');
    check($result['calculated']['H21'] === '-5.01', 'Filled blank template preserves negative claim adjustment');
    check($result['calculated']['H166'] === '75.05', 'Filled blank template rolls up profit without Excel formulas');
});
$badCell = $xpath->query('//s:c[@r="C14"]')->item(0);
$badCell->appendChild($document->createElementNS('http://schemas.openxmlformats.org/spreadsheetml/2006/main', 'f', 'C11+C12+C13'));
modifiedWorkbook($blankPath, $document->saveXML(), static function ($modified) use ($parser): void {
    rejects(static fn () => $parser->parse($modified), 'Blank layout still rejects supplied incorrect formulas');
});
check(hash_file('sha256', $blankPath) === $blankHash, 'Blank downloadable workbook preserved');
if (($argv[1] ?? '') === '--fixtures') {
    file_put_contents(__DIR__ . '/../../tmp/rka_template/verified_cases.json', json_encode($cases, JSON_THROW_ON_ERROR));
}
echo "RKA financial checks: $checks OK. 858 source amounts reconciled.\n";
