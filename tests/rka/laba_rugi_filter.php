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
$cases = [[], ['unit_kerja'=>'unknown'], ['unit_kerja'=>['Surabaya']], ['jenis_laporan'=>'PTD'], ['jenis_laporan'=>'ptd','unit_kerja'=>'Surabaya'], ['jenis_laporan'=>'unknown'], ['bulan'=>'8'], ['bulan'=>'12'], ['bulan'=>'0'], ['bulan'=>'13'], ['bulan'=>'invalid'], ['lob'=>['KUR','PEN']], ['lob'=>'KUR'], ['lob'=>['PRODUKTIF']], ['lob'=>['unknown']], ['lob'=>['KUR','KUR','PEN']]];
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
    $basis = $query['jenis_laporan'] ?? null;
    $expectedBasis = is_string($basis) && in_array(strtoupper($basis), App\Libraries\LrRealizationService::BASES, true) ? strtoupper($basis) : 'YTD';
    $month = $query['bulan'] ?? null;
    $expectedRequestedMonth = is_string($month) && preg_match('/^(?:[1-9]|1[0-2])$/D', $month) ? (int)$month : null;
    $requestedLobs=$query['lob']??null; if (is_string($requestedLobs)) $requestedLobs=[$requestedLobs];
    $expectedLobs=[]; if (is_array($requestedLobs)) foreach ($requestedLobs as $lob) if (is_string($lob) && in_array($lob,App\Libraries\LrRealizationService::LOB_COLUMNS,true) && !in_array($lob,$expectedLobs,true)) $expectedLobs[]=$lob;
    if (!$expectedLobs) $expectedLobs=App\Libraries\LrRealizationService::LOB_COLUMNS;
    if ($selected->length !== 1 || $selected->item(0)->getAttribute('value') !== $expected
        || $xpath->query('//select[@id="lrFilterUnit"]/option')->length !== 7
        || $xpath->query('//form[@method="get"]//select[@name="unit_kerja" and @id="lrFilterUnit"]')->length !== 1
        || !str_contains($html, '· '.esc($expected))) throw new RuntimeException('Incorrect profit-and-loss unit filter.');
    $yearInput = $xpath->query('//form[@method="get"]//input[@id="lrFilterYear" and @name="tahun" and @min="2000" and @max="2100"]');
    $selectedMonth = $xpath->query('//select[@id="lrFilterMonth"]/option[@selected]');
    if ($yearInput->length !== 1 || $yearInput->item(0)->getAttribute('value') !== (string)$expectedYear
        || trim($xpath->query('//span[contains(@class,"lr-report-year")]')->item(0)->textContent) !== (string)$expectedYear
        || $xpath->query('//select[@id="lrFilterMonth" and @name="bulan" and @required]/option')->length !== 12
        || $selectedMonth->length !== 1
        || ($expectedRequestedMonth !== null && $selectedMonth->item(0)->getAttribute('value') !== (string)$expectedRequestedMonth)
        || !str_contains($html, 'Laba / Rugi ('.$expectedBasis.') '.trim($selectedMonth->item(0)->textContent).' '.$expectedYear)) throw new RuntimeException('Incorrect profit-and-loss month/year filter.');
    if ($xpath->query('//nav[contains(@class,"lr-report-tabs")]/a')->length !== 2
        || $xpath->query('//nav[contains(@class,"lr-report-tabs")]/a[@aria-current="page" and contains(normalize-space(.),"('.$expectedBasis.')")]')->length !== 1
        || $xpath->query('//form[@method="get"]/input[@name="jenis_laporan" and @value="'.$expectedBasis.'"]')->length !== 1
        || $xpath->query('//form[@data-lr-upload-form]//input[@name="jenis_laporan" and @value="'.$expectedBasis.'"]')->length !== 1
        || $xpath->query('//form[@data-lr-delete-form]/input[@name="jenis_laporan" and @value="'.$expectedBasis.'"]')->length !== 1) {
        throw new RuntimeException('YTD/PTD tabs and form scope must remain synchronized.');
    }
    foreach ($xpath->query('//nav[contains(@class,"lr-report-tabs")]/a') as $tab) {
        parse_str((string)parse_url(html_entity_decode($tab->getAttribute('href')),PHP_URL_QUERY),$tabQuery);
        if (($tabQuery['bulan']??null)!==$selectedMonth->item(0)->getAttribute('value') || ($tabQuery['lob']??[])!==$expectedLobs) throw new RuntimeException('Tab navigation must preserve the selected month and LOB columns.');
    }
    $checkedLobs=$xpath->query('//form[@method="get"]//input[@type="checkbox" and @name="lob[]" and @checked]');
    $tableColumns=[]; foreach ($xpath->query('//table[contains(@class,"lr-profitloss-table")]/thead/tr/th[position()>1]') as $heading) $tableColumns[]=trim($heading->textContent);
    if ($xpath->query('//form[@method="get"]//input[@type="checkbox" and @name="lob[]"]')->length!==count(App\Libraries\LrRealizationService::LOB_COLUMNS)
        || $checkedLobs->length!==count($expectedLobs)
        || $tableColumns!==array_merge($expectedLobs,['TOTAL','%'])
        || $xpath->query('//form[@data-lr-upload-form]//input[@type="hidden" and @name="lob[]"]')->length!==count($expectedLobs)
        || $xpath->query('//form[@data-lr-delete-form]//input[@type="hidden" and @name="lob[]"]')->length!==count($expectedLobs)) {
        throw new RuntimeException('Multi-LOB selection must control visible columns and persist through report actions.');
    }
    if (str_contains($html, 'Laporan laba rugi tahun '.$expectedYear)
        || !str_contains($html, 'Laporan Laba / Rugi')
        || $xpath->query('//section[contains(@class,"lr-page-heading")]//div[contains(@class,"lr-heading-actions")]/button[@data-lr-upload-open]')->length !== 1
        || $xpath->query('//section[contains(@class,"lr-page-heading")]//div[contains(@class,"lr-heading-actions")]/button[@data-lr-delete-open]')->length !== 1
        || $xpath->query('//form[@method="get"]//button[@data-lr-delete-open]')->length !== 0
        || $xpath->query('//form[@method="get"]//a[contains(@class,"btn-ghost") and normalize-space(.)="Reset" and not(contains(@href,"?"))]')->length !== 1) {
        throw new RuntimeException('The heading must use compact copy and place deletion beside the upload action.');
    }
    $uploadMonth = $xpath->query('//form[@data-lr-upload-form]//select[@id="lrUploadMonth" and @name="bulan" and @required]');
    if ($uploadMonth->length !== 1
        || $xpath->query('//select[@id="lrUploadMonth"]/option')->length !== 12
        || $xpath->query('//select[@id="lrUploadMonth"]/option[@selected]')->length !== 1) {
        throw new RuntimeException('The Oracle upload must require one explicitly selected reporting month.');
    }
    if ($xpath->query('//button[@data-lr-delete-open and not(@disabled)]')->length !== 1
        || $xpath->query('//dialog[@id="lrDeleteDialog"]')->length !== 1
        || $xpath->query('//form[@data-lr-delete-form]//select[@id="lrDeleteUnit" and @name="unit_kerja" and @required]')->length !== 1
        || $xpath->query('//select[@id="lrDeleteUnit"]/option[@value="all" and normalize-space(.)="Seluruh Unit Kerja"]')->length !== 1
        || $xpath->query('//form[@data-lr-delete-form]//select[@id="lrDeleteMonth" and @name="bulan" and @required]')->length !== 1
        || $xpath->query('//form[@data-lr-delete-form]//input[@id="lrDeleteYear" and @name="tahun" and @required]')->length !== 1) {
        throw new RuntimeException('Delete button and unit/month/year dialog must be available from every report filter.');
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
$request->setGlobal('post', ['unit_kerja'=>'Kanwil', 'tahun'=>'2026', 'bulan'=>'8']);
$controller->importLabaRugi();
if (session()->getFlashdata('lr_upload_error') !== 'Pilih jenis laporan YTD atau PTD yang valid.') {
    throw new RuntimeException('The server must reject an upload without an explicit YTD/PTD report basis.');
}
echo "Profit-and-loss filters: multi-LOB columns, isolated YTD/PTD tabs, twelve exact months, seven units, defaults, limits, and matching report heading OK; read-only.\n";
