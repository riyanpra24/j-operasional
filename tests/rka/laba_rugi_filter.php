<?php
/** Read-only controller/render check for the profit-and-loss unit filter. */
ob_start();
define('FCPATH', realpath(__DIR__ . '/../../public') . DIRECTORY_SEPARATOR);
define('ENVIRONMENT', 'development');
require __DIR__ . '/../../app/Config/Paths.php';
$paths = new Config\Paths(); require $paths->systemDirectory . '/Boot.php';
CodeIgniter\Boot::bootConsole($paths);
$request = Config\Services::incomingrequest(new Config\App(), false);
Config\Services::injectMock('request', $request);
$controller = new App\Controllers\Akutansi();
$controller->initController($request, Config\Services::response(null, false), Config\Services::logger());
$cases = [[], ['unit_kerja'=>'unknown'], ['unit_kerja'=>['Surabaya']]];
foreach (App\Libraries\RkaCalculator::UNITS as $unit) $cases[] = ['unit_kerja'=>$unit];
foreach (['2027','2000','2100','1999','2101','invalid',['2027']] as $year) $cases[] = ['unit_kerja'=>'Kediri','tahun'=>$year];
foreach ($cases as $query) {
    $request->setGlobal('get', $query);
    $html = $controller->labaRugi();
    $dom = new DOMDocument(); @$dom->loadHTML('<?xml encoding="UTF-8">'.$html); $xpath = new DOMXPath($dom);
    $selected = $xpath->query('//select[@id="lrFilterUnit"]/option[@selected]');
    $unit = $query['unit_kerja'] ?? null;
    $expected = is_string($unit) && in_array($unit, App\Libraries\RkaCalculator::UNITS, true) ? $unit : 'Korporat Kanwil';
    $year = $query['tahun'] ?? null;
    $expectedYear = is_string($year) && preg_match('/^\d{4}$/D', $year) && (int)$year >= 2000 && (int)$year <= 2100 ? (int)$year : 2026;
    if ($selected->length !== 1 || $selected->item(0)->getAttribute('value') !== $expected
        || $xpath->query('//select[@id="lrFilterUnit"]/option')->length !== 7
        || $xpath->query('//form[@method="get"]//select[@name="unit_kerja" and @id="lrFilterUnit"]')->length !== 1
        || !str_contains($html, '· '.esc($expected))) throw new RuntimeException('Incorrect profit-and-loss unit filter.');
    $yearInput = $xpath->query('//form[@method="get"]//input[@id="lrFilterYear" and @name="tahun" and @min="2000" and @max="2100"]');
    if ($yearInput->length !== 1 || $yearInput->item(0)->getAttribute('value') !== (string)$expectedYear
        || trim($xpath->query('//span[contains(@class,"lr-report-year")]')->item(0)->textContent) !== (string)$expectedYear
        || !str_contains($html, 'Laba Rugi Tahun '.$expectedYear)) throw new RuntimeException('Incorrect profit-and-loss year filter.');
    if (str_contains($html, 'Laporan laba rugi tahun '.$expectedYear)
        || $xpath->query('//section[contains(@class,"lr-page-heading")]//div[contains(@class,"lr-heading-actions")]/button[@data-lr-upload-open]')->length !== 1
        || $xpath->query('//section[contains(@class,"lr-page-heading")]//div[contains(@class,"lr-heading-actions")]/button[@data-lr-delete-open]')->length !== 1
        || $xpath->query('//form[@method="get"]//button[@data-lr-delete-open]')->length !== 0) {
        throw new RuntimeException('The heading must use compact copy and place deletion beside the upload action.');
    }
    $uploadMonth = $xpath->query('//form[@data-lr-upload-form]//select[@id="lrUploadMonth" and @name="bulan" and @required]');
    if ($uploadMonth->length !== 1
        || $xpath->query('//select[@id="lrUploadMonth"]/option')->length !== 12
        || $xpath->query('//select[@id="lrUploadMonth"]/option[@selected]')->length !== 1) {
        throw new RuntimeException('The Oracle upload must require one explicitly selected reporting month.');
    }
    $sourceUnit = in_array($expected, App\Libraries\OracleLrSalaryParser::IMPORT_UNITS, true);
    if ($xpath->query('//dialog[@id="lrDeleteDialog"]')->length !== ($sourceUnit ? 1 : 0)
        || $xpath->query('//form[@data-lr-delete-form]//select[@id="lrDeleteMonth" and @name="bulan" and @required]')->length !== ($sourceUnit ? 1 : 0)
        || $xpath->query('//form[@data-lr-delete-form]//input[@id="lrDeleteYear" and @name="tahun" and @required]')->length !== ($sourceUnit ? 1 : 0)) {
        throw new RuntimeException('Source units must receive a month/year deletion dialog; corporate consolidation must remain protected.');
    }
}
$request->setGlobal('post', ['unit_kerja'=>'Kanwil', 'tahun'=>'2026']);
$controller->importLabaRugi();
if (session()->getFlashdata('lr_upload_error') !== 'Pilih bulan laporan yang valid.') {
    throw new RuntimeException('The server must reject an Oracle upload without an explicit reporting month.');
}
$request->setGlobal('post', ['unit_kerja'=>'Kanwil', 'tahun'=>'2026', 'bulan'=>'13']);
$controller->importLabaRugi();
if (session()->getFlashdata('lr_upload_error') !== 'Pilih bulan laporan yang valid.') {
    throw new RuntimeException('The server must reject a reporting month outside January through December.');
}
echo "Profit-and-loss filters: seven units, corporate/2026 defaults, explicit years and units, year limits, required upload month and matching report heading OK; read-only.\n";
