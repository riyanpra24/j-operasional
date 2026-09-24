<?php
/** Regression: page, source grouping, and exact-template output. */
ob_start();
define('FCPATH', realpath(__DIR__ . '/../../public') . DIRECTORY_SEPARATOR);
define('ENVIRONMENT', getenv('SIMULATION_PRODUCTION_READS') === '1' ? 'production' : 'testing');
require __DIR__ . '/../../app/Config/Paths.php';
$paths = new Config\Paths();
require $paths->systemDirectory . '/Boot.php';
CodeIgniter\Boot::bootConsole($paths);

$routes = file_get_contents(__DIR__ . '/../../app/Config/Routes.php');
foreach (['simulasiHitung', 'uploadSimulasiHitung', 'downloadSimulasiHitung', 'deleteSimulasiHitung'] as $method) {
    if (!is_string($routes) || !str_contains($routes, 'Akutansi::' . $method)) throw new RuntimeException('Missing route: ' . $method);
}
session()->set(['auth_user_id' => 1, 'auth_role' => 'akutansi', 'auth_display_name' => 'User Akutansi', 'auth_username' => 'akutansi']);
$request = Config\Services::incomingrequest(new Config\App(), false);
Config\Services::injectMock('request', $request);
$controller = new App\Controllers\Akutansi();
$controller->initController($request, Config\Services::response(null, false), Config\Services::logger());
$html = $controller->simulasiHitung();
$dom = new DOMDocument();
@$dom->loadHTML('<?xml encoding="UTF-8">' . $html);
$xpath = new DOMXPath($dom);
if ($xpath->query('//h1[normalize-space(.)="Simulasi Hitung"]')->length !== 1
    || $xpath->query('//button[@data-simulation-upload-open and normalize-space(.)="Isi Kertas Kerja"]')->length !== 1
    || $xpath->query('//dialog[@id="simulationUploadDialog"]')->length !== 1
    || $xpath->query('//form[contains(@action,"simulasi-hitung") and @enctype="multipart/form-data"]')->length !== 1
    || $xpath->query('//input[@name="oracle_excel" and @type="file"]')->length !== 1
    || $xpath->query('//h2[normalize-space(.)="Unduh Kertas Kerja"]')->length !== 1) {
    throw new RuntimeException('Simulasi Hitung page is incomplete.');
}
$view = file_get_contents(__DIR__ . '/../../app/Views/akutansi/simulasi_hitung.php');
if (!is_string($view) || !str_contains($view, 'data-simulation-delete')
    || !str_contains($view, 'data-simulation-upload-open')
    || !str_contains($view, 'simulation-upload-card')
    || !str_contains($view, 'simulation-period-badge')
    || !str_contains($view, 'simulation-stat-success')) {
    throw new RuntimeException('Daftar hasil simulasi belum memiliki status visual dan aksi hapus.');
}
$deleteId = bin2hex(random_bytes(16));
$deleteBase = WRITEPATH . 'uploads/lr_simulations/' . $deleteId;
if (!is_dir(dirname($deleteBase))) mkdir(dirname($deleteBase), 0770, true);
file_put_contents($deleteBase . '.xlsx', 'fixture');
file_put_contents($deleteBase . '.json', json_encode(['id' => $deleteId, 'created_by_id' => 1], JSON_THROW_ON_ERROR));
try {
    (new App\Libraries\LrWorkpaperSimulationService())->delete($deleteId, 1, false);
    if (is_file($deleteBase . '.xlsx') || is_file($deleteBase . '.json')) {
        throw new RuntimeException('Hasil simulasi belum dihapus sepenuhnya.');
    }
} finally {
    foreach (['.xlsx', '.json', '.xlsx.deleting', '.json.deleting'] as $suffix) {
        if (is_file($deleteBase . $suffix)) unlink($deleteBase . $suffix);
    }
}

$source = 'C:\\Users\\Jamkrindo\\Downloads\\LR SEKANWIL JANUARI 2026.xlsx';
$template = APPPATH . 'Resources/Templates/kertas_kerja_realisasi_rka_2026.xlsx';
if (!is_file($source)) { fwrite(STDOUT, "Simulation page: passed; Oracle fixture unavailable\n"); exit(0); }
$parser = new App\Libraries\OracleLrSalaryParser();
$parsed = []; $rka = [];
$calculator = new App\Libraries\RkaCalculator();
$budgetService = new App\Libraries\RkaBudgetService();
foreach (App\Libraries\OracleLrSalaryParser::IMPORT_UNITS as $unit) {
    $parsed[$unit] = $parser->parse($source, $unit, true);
    if ($parsed[$unit]['year'] !== 2026 || $parsed[$unit]['month'] !== 1) throw new RuntimeException('Fixture period mismatch.');
    $record = getenv('SIMULATION_PRODUCTION_READS') === '1' ? $budgetService->find($unit, 2026) : null;
    $rka[$unit] = $record === null ? $calculator->calculate($calculator->zeros()) : json_decode($record['calculated_json'], true, 512, JSON_THROW_ON_ERROR);
}
$periods = getenv('SIMULATION_PRODUCTION_READS') === '1'
    ? array_fill_keys(App\Libraries\RkaCalculator::SOURCE_UNITS, ['year' => 2026, 'month' => 1]) : [];
$realization = (new App\Libraries\LrRealizationCalculator())->calculate($parsed, $rka, $periods);
$output = tempnam(sys_get_temp_dir(), 'lr-simulation-');
if ($output === false || !copy($template, $output)) throw new RuntimeException('Cannot copy simulation template.');
try {
    (new App\Libraries\LrWorkpaperSimulationService())->populate($output, $parsed, $rka, $realization, 2026);
    $before = new ZipArchive(); $after = new ZipArchive();
    if ($before->open($template) !== true || $after->open($output) !== true) throw new RuntimeException('Simulation workbook invalid.');
    try {
        foreach (range(1, 7) as $index) {
            $name = 'xl/worksheets/sheet' . $index . '.xml';
            $old = simplexml_load_string((string) $before->getFromName($name));
            $new = simplexml_load_string((string) $after->getFromName($name));
            $old->registerXPathNamespace('s', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
            $new->registerXPathNamespace('s', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
            $formulaMap = static function (SimpleXMLElement $sheet): array {
                $formulas = [];
                foreach ($sheet->xpath('//s:sheetData/s:row/s:c[s:f]') as $cell) $formulas[(string) $cell['r']] = (string) $cell->children('http://schemas.openxmlformats.org/spreadsheetml/2006/main')->f;
                return $formulas;
            };
            if ($formulaMap($old) !== $formulaMap($new)) throw new RuntimeException('Template formula changed in sheet ' . $index);
            if ($index === 3) {
                $cells = [];
                foreach ($new->xpath('//s:sheetData/s:row/s:c') as $cell) $cells[(string) $cell['r']] = $cell;
                if ((string) $cells['Y8']->children('http://schemas.openxmlformats.org/spreadsheetml/2006/main')->v !== (string) $parsed['Surabaya']['helper_rows']['KUR'][0]['amount']) throw new RuntimeException('KUR helper balance mismatch.');
                $cells['W1']->registerXPathNamespace('s', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
                if (implode('', array_map('strval', $cells['W1']->xpath('.//s:t'))) !== 'SIMULASI_LR|YTD|2026|1') throw new RuntimeException('Penanda asal Simulasi Hitung tidak tersedia.');
                if ($parsed['Surabaya']['helper_rows']['KUR'][0]['description'] !== 'Pendapatan premi penjaminan kredit') throw new RuntimeException('Oracle indentation was not normalized for SUMIF.');
                if (isset($cells['L7']->children('http://schemas.openxmlformats.org/spreadsheetml/2006/main')->v)) throw new RuntimeException('Old volume was retained.');
                if ((string) $cells['F7']->children('http://schemas.openxmlformats.org/spreadsheetml/2006/main')->v !== (string) $rka['Surabaya']['C8']) throw new RuntimeException('RKA did not come from system data.');
                foreach (['D85' => 'D84', 'E85' => 'E84', 'F85' => 'F84', 'G85' => 'G84', 'H85' => 'H84', 'I85' => 'I84', 'J85' => 'J84', 'M85' => 'M84'] as $highlighted => $normal) {
                    if ((string) $cells[$highlighted]['s'] !== (string) $cells[$normal]['s']) {
                        throw new RuntimeException('Penanda merah pada hasil simulasi belum dihapus: ' . $highlighted);
                    }
                }
            }
        }
    } finally { $before->close(); $after->close(); }
} finally {
    if (getenv('SIMULATION_KEEP_OUTPUT') === '1') fwrite(STDOUT, 'WORKBOOK=' . $output . "\n");
    else unlink($output);
}
fwrite(STDOUT, "Simulation workpaper: passed\n");
