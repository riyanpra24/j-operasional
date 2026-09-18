<?php
require __DIR__ . '/../../app/Libraries/RkaMoney.php';
require __DIR__ . '/../../app/Libraries/RkaCalculator.php';
require __DIR__ . '/../../app/Libraries/RkaWorkbookParser.php';
$parsed = (new App\Libraries\RkaWorkbookParser())->parse(__DIR__ . '/../../writable/templates/rka/Template RKA.xlsx');
$expected = json_decode(file_get_contents(__DIR__ . '/expected.json'), true, 512, JSON_THROW_ON_ERROR);
foreach ($expected as $cell => $amount) {
    if ($parsed['calculated'][$cell] !== $amount) {
        throw new RuntimeException($cell . ': ' . $parsed['calculated'][$cell] . ' != ' . $amount);
    }
}
file_put_contents(__DIR__ . '/parsed.json', json_encode($parsed, JSON_THROW_ON_ERROR));
echo count($expected) . " amounts reconcile with independent Decimal calculation.\n";
