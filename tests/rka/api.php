<?php
ob_start();
define('FCPATH', realpath(__DIR__ . '/../../public') . DIRECTORY_SEPARATOR);
define('ENVIRONMENT', 'development');
require __DIR__ . '/../../app/Config/Paths.php';
$paths = new Config\Paths(); require $paths->systemDirectory . '/Boot.php';
CodeIgniter\Boot::bootConsole($paths);
$request = Config\Services::incomingrequest(new Config\App(), false);
Config\Services::injectMock('request', $request);
$response = Config\Services::response(null, false);
$controller = new App\Controllers\Akutansi();
$controller->initController($request, $response, Config\Services::logger());
$selection = new ReflectionMethod($controller, 'selection');
$request->setGlobal('get', []);
if ($selection->invoke($controller) !== ['Korporat Kanwil', 2026]) throw new RuntimeException('RKA opening filter must default to Korporat Kanwil.');
$request->setGlobal('get', ['unit_kerja'=>'invalid','tahun'=>'2026']);
if ($selection->invoke($controller) !== ['Korporat Kanwil', 2026]) throw new RuntimeException('Invalid filter must fall back to Korporat Kanwil.');
$request->setGlobal('get', ['unit_kerja'=>'Surabaya','tahun'=>'2027']);
if ($selection->invoke($controller) !== ['Surabaya', 2027]) throw new RuntimeException('Explicit user filter must be preserved.');
$request->setGlobal('get', ['scope'=>'all','tahun'=>'2026']);
$result = $controller->rkaManualData();
$data = json_decode($result->getBody(),true,32,JSON_THROW_ON_ERROR);
if ($result->getStatusCode() !== 200 || $data['scope'] !== 'all' || $data['year'] !== 2026
    || array_keys($data['records']) !== App\Libraries\RkaCalculator::UNITS) throw new RuntimeException('All-unit snapshot endpoint returned incorrect data.');
$service = new App\Libraries\RkaBudgetService();
foreach ($data['records'] as $unit=>$snapshot) {
    $record = $service->findStored($unit,2026);
    if ($snapshot !== ['id'=>$record !== null ? (int)$record['id'] : null,'revision'=>$record !== null ? (int)$record['revision'] : 0]) throw new RuntimeException('Incorrect unit identity or revision.');
}
$request->setGlobal('get',['unit_kerja'=>'Surabaya','tahun'=>'2026']);
$single = json_decode($controller->rkaManualData()->getBody(),true,512,JSON_THROW_ON_ERROR);
if ($single['unit'] !== 'Surabaya' || count($single['inputs']) !== 675) throw new RuntimeException('Single-unit data endpoint regressed.');
$request->setGlobal('get',['scope'=>'all','tahun'=>'2200']);
if ($controller->rkaManualData()->getStatusCode() !== 400) throw new RuntimeException('Invalid all-unit year was accepted.');
$request->setGlobal('get',['unit_kerja'=>'Korporat Kanwil','tahun'=>'2026']);
if ($controller->rkaManualData()->getStatusCode() !== 400) throw new RuntimeException('Corporate direct input was allowed.');
echo "RKA snapshot API: seven exact active identities/revisions, single-unit compatibility and invalid-year rejection OK; read-only.\n";
