<?php
/** Disposable OOXML fixtures only; never changes user workbooks. */
require __DIR__ . '/../../app/Libraries/RkaMoney.php';
require __DIR__ . '/../../app/Libraries/RkaCalculator.php';
require __DIR__ . '/../../app/Libraries/RkaWorkbookParser.php';

use App\Libraries\RkaCalculator;
use App\Libraries\RkaWorkbookParser;

$parser = new RkaWorkbookParser();
$checks = 0;
function assertBulk(bool $condition, string $message): void {
    global $checks;
    if (!$condition) throw new RuntimeException($message);
    $checks++;
}
function fixture(array $names, callable $check, ?callable $modify = null): void {
    $temporary = tempnam(sys_get_temp_dir(), 'rka-bulk-check-');
    try {
        copy(__DIR__ . '/../../writable/templates/rka/Template_RKA_JMK_KV_SBY.xlsx', $temporary);
        $zip = new ZipArchive(); $zip->open($temporary);
        $base = $zip->getFromName('xl/worksheets/sheet1.xml');
        $sheets = ''; $relations = '';
        foreach ($names as $index => $name) {
            $number = $index + 1;
            $sheets .= '<sheet name="' . htmlspecialchars($name, ENT_XML1 | ENT_QUOTES, 'UTF-8') . '" sheetId="' . $number . '" r:id="rId' . $number . '"/>';
            $relations .= '<Relationship Id="rId' . $number . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet' . $number . '.xml"/>';
            $doc = new DOMDocument(); $doc->loadXML($base, LIBXML_NONET);
            $xpath = new DOMXPath($doc); $xpath->registerNamespace('s', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
            foreach (['C11' => ($number * 100) . '.10', 'C12' => '10.05', 'C13' => '20.01', 'D18' => '-5.01'] as $address => $amount) {
                $cell = $xpath->query('//s:c[@r="' . $address . '"]')->item(0);
                while ($cell->firstChild) $cell->removeChild($cell->firstChild);
                $cell->removeAttribute('t');
                $cell->appendChild($doc->createElementNS('http://schemas.openxmlformats.org/spreadsheetml/2006/main','v',$amount));
            }
            $xml = $doc->saveXML();
            if ($modify !== null) $xml = $modify($xml, $number);
            $zip->addFromString('xl/worksheets/sheet' . $number . '.xml', $xml);
        }
        $zip->addFromString('xl/workbook.xml', '<?xml version="1.0"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets>' . $sheets . '</sheets></workbook>');
        $zip->addFromString('xl/_rels/workbook.xml.rels','<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' . $relations . '</Relationships>');
        $zip->close();
        $check($temporary);
    } finally { if (is_file($temporary)) unlink($temporary); }
}
function invalid(array $names, string $message, ?callable $modify = null): void {
    global $parser;
    fixture($names, static function ($path) use ($parser, $message): void {
        try { $parser->parseAll($path); }
        catch (RuntimeException $exception) { assertBulk(str_contains($exception->getMessage(), $message), 'Wrong failure: ' . $exception->getMessage()); return; }
        throw new RuntimeException('Bad all-unit workbook accepted.');
    }, $modify);
}
fixture(RkaCalculator::UNITS, static function ($path) use ($parser): void {
    $parsed = $parser->parseAll($path);
    assertBulk(count($parsed) === 7, 'Must parse all seven units');
    foreach (RkaCalculator::UNITS as $index => $unit) {
        $input = $unit === 'Korporat Kanwil' ? '2700.60' : (($index + 1) * 100) . '.10';
        $profit = $unit === 'Korporat Kanwil' ? '2550.30' : (($index + 1) * 100 - 25) . '.05';
        assertBulk($parsed[$unit]['inputs']['C11'] === $input, 'Unit mapping and corporate source sum must be exact');
        assertBulk($parsed[$unit]['calculated']['H166'] === $profit, 'Exact financial arithmetic must remain unchanged');
    }
    try { $parser->parse($path); } catch (RuntimeException $exception) { assertBulk(true,'Single-unit parser refuses multisheet'); return; }
    throw new RuntimeException('Single upload accepted all-unit workbook');
});
// Uppercase names and shuffled tabs map by name, not by worksheet order.
$shuffled = array_reverse(array_map('strtoupper', RkaCalculator::UNITS));
fixture($shuffled, static function ($path) use ($parser): void {
    $parsed = $parser->parseAll($path);
    assertBulk($parsed['Banyuwangi']['inputs']['C11'] === '100.10', 'First shuffled tab mapped to wrong unit');
    assertBulk($parsed['Korporat Kanwil']['inputs']['C11'] === '2100.60', 'Corporate must sum source tabs, never its cached values');
});
$unknown = RkaCalculator::UNITS; $unknown[2] = 'Surabayaa'; invalid($unknown, 'Surabayaa');
invalid(array_slice(RkaCalculator::UNITS,0,6), 'Banyuwangi');
invalid([...RkaCalculator::UNITS,'Sheet1'], 'Sheet1');
$duplicate = RkaCalculator::UNITS; $duplicate[2] = 'KANWIL'; invalid($duplicate, 'duplikat');
invalid(RkaCalculator::UNITS, 'Rumus C14', static function ($xml,$number): string {
    if ($number !== 7) return $xml;
    $doc = new DOMDocument();$doc->loadXML($xml);$xpath = new DOMXPath($doc);$xpath->registerNamespace('s','http://schemas.openxmlformats.org/spreadsheetml/2006/main');
    $cell = $xpath->query('//s:c[@r="C14"]')->item(0);
    $cell->appendChild($doc->createElementNS('http://schemas.openxmlformats.org/spreadsheetml/2006/main','f','C11+C12+C13'));
    return $doc->saveXML();
});
// Change heading text on one sheet while retaining every monetary field.
invalid(RkaCalculator::UNITS, 'Tahun pada A2', static function ($xml,$number): string {
    if ($number !== 7) return $xml;
    $doc = new DOMDocument();$doc->loadXML($xml);$xpath = new DOMXPath($doc);$xpath->registerNamespace('s','http://schemas.openxmlformats.org/spreadsheetml/2006/main');
    $cell = $xpath->query('//s:c[@r="A2"]')->item(0);while ($cell->firstChild) $cell->removeChild($cell->firstChild);
    $cell->setAttribute('t','inlineStr');$inline=$doc->createElementNS('http://schemas.openxmlformats.org/spreadsheetml/2006/main','is');$inline->appendChild($doc->createElementNS('http://schemas.openxmlformats.org/spreadsheetml/2006/main','t','LABA RUGI RKA TAHUN 2027'));$cell->appendChild($inline);
    return $doc->saveXML();
});
// Synthetic F:K report: source metadata is not money; corporate formula caches
// deliberately disagree with source units and must never drive the result.
function shiftedFixture(string $xml, int $number, bool $badCorporate = false, bool $oneRowUp = false): string {
    $ns = 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';
    $doc = new DOMDocument(); $doc->loadXML($xml, LIBXML_NONET);
    $xpath = new DOMXPath($doc); $xpath->registerNamespace('s', $ns);
    foreach (iterator_to_array($xpath->query('//s:c')) as $cell) {
        preg_match('/^([A-H])(\d+)$/', $cell->getAttribute('r'), $parts);
        $row = $parts[2];
        if ((int)$row === 6 && $parts[1] >= 'C') { $cell->parentNode->removeChild($cell); continue; }
        if ($oneRowUp && (int)$row === 7) { $cell->parentNode->removeChild($cell); continue; }
        if (!$oneRowUp && (int)$row === 5 && $parts[1] >= 'C') $row = 6;
        if ($oneRowUp && (int)$row >= 8) $row--;
        $cell->setAttribute('r', chr(ord($parts[1])+3).$row);
    }
    foreach (RkaCalculator::schema()['formulas'] as $address => $formula) {
        if ($number === 2 && in_array((int)substr($address,1),[14,21,23],true)) continue;
        $sourceRow = (int) substr($address,1);
        $targetRow = $oneRowUp && $sourceRow >= 8 ? $sourceRow - 1 : $sourceRow;
        $target = chr(ord($address[0])+3).$targetRow;
        $cell = $xpath->query('//s:c[@r="'.$target.'"]')->item(0);
        if (!$cell) throw new RuntimeException('Missing fixture cell '.$target);
        $formula = preg_replace_callback('/([C-H])(\d+)/', static fn($match) => chr(ord($match[1])+3)
            . ($oneRowUp && (int)$match[2] >= 8 ? (int)$match[2] - 1 : $match[2]), $formula);
        $cell->appendChild($doc->createElementNS($ns,'f',$formula));
    }
    if ($number === 1) foreach (RkaCalculator::schema()['input_rows'] as $row) foreach (range('F','J') as $column) {
        $address = $column.($oneRowUp && $row >= 8 ? $row - 1 : $row);
        $cell = $xpath->query('//s:c[@r="'.$address.'"]')->item(0);
        while ($cell->firstChild) $cell->removeChild($cell->firstChild);
        $cell->removeAttribute('t');
        $formula = implode('+',array_map(static fn($unit)=>strtoupper($unit).'!'.$address,RkaCalculator::SOURCE_UNITS));
        if ($badCorporate && $address === 'F11') $formula = str_replace('KANWIL!F11','KANWIL!F12',$formula);
        $cell->appendChild($doc->createElementNS($ns,'f',$formula));
        $cell->appendChild($doc->createElementNS($ns,'v','999.99'));
    }
    return $doc->saveXML();
}
fixture(RkaCalculator::UNITS, static function($path) use ($parser): void {
    $zip = new ZipArchive(); $zip->open($path); $zip->addFromString('xl/printerSettings/printerSettings1.bin','safe-test-printer-settings'); $zip->close();
    $parsed = $parser->parseAll($path);
    assertBulk($parsed['Surabaya']['inputs']['C11']==='300.10','F:K columns must map correctly');
    assertBulk($parsed['Korporat Kanwil']['inputs']['C11']==='2700.60','Corporate formula cache was trusted');
    assertBulk($parsed['Korporat Kanwil']['calculated']['H166']==='2550.30','Corporate rollups changed');
    assertBulk($parsed['Kanwil']['calculated']['C14']==='170.04','Missing Kanwil formulas must calculate on server');
    $zip->open($path); $zip->addFromString('xl/vbaProject.bin','test-macro'); $zip->close();
    try { $parser->parseAll($path); } catch (RuntimeException $exception) { assertBulk(str_contains($exception->getMessage(),'makro'),'Macro rejection regressed'); return; }
    throw new RuntimeException('Macro workbook accepted');
}, 'shiftedFixture');
invalid(RkaCalculator::UNITS,'Rumus konsolidasi F11',static fn($xml,$number)=>shiftedFixture($xml,$number,true));
fixture(RkaCalculator::UNITS, static function($path) use ($parser): void {
    $parsed = $parser->parseAll($path);
    assertBulk($parsed['Surabaya']['inputs']['C11']==='300.10','F5:K5 one-row-up layout must map to the existing schema');
    assertBulk($parsed['Korporat Kanwil']['inputs']['C11']==='2700.60','One-row-up corporate consolidation changed');
    assertBulk($parsed['Korporat Kanwil']['calculated']['H166']==='2550.30','One-row-up rollups changed');
}, static fn($xml,$number)=>shiftedFixture($xml,$number,false,true));
echo "All-unit workbook checks: $checks OK; layouts, exact source consolidation, safe printer settings and invalid sheet/formula rejection.\n";
