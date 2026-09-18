<?php
/** All-unit atomicity checks on an explicitly unused test year. */
define('FCPATH', realpath(__DIR__ . '/../../public') . DIRECTORY_SEPARATOR);
define('ENVIRONMENT', 'development');
require __DIR__ . '/../../app/Config/Paths.php';
$paths = new Config\Paths(); require $paths->systemDirectory . '/Boot.php';
CodeIgniter\Boot::bootConsole($paths);
$db = db_connect(); $service = new App\Libraries\RkaBudgetService();
$units = App\Libraries\RkaCalculator::UNITS; $year = 2098;
if ((new App\Models\AccountingRkaBudgetModel())->withDeleted()->where('budget_year',$year)->countAllResults() !== 0) {
    throw new RuntimeException('Test skipped: year 2098 already contains user data.');
}
$budgets = []; $snapshots = [];
foreach ($units as $index => $unit) {
    $inputs = (new App\Libraries\RkaCalculator())->zeros(); $inputs['C11'] = (($index + 1) * 100) . '.01';
    $budgets[$unit] = ['inputs'=>$inputs]; $snapshots[$unit] = ['id'=>null,'revision'=>0];
}
// No outer test transaction here: verify that the service really rolls back units
// already written when the last unit fails. The test year was verified empty.
foreach (['stale','invalid-amount'] as $case) {
    $badSnapshots = $snapshots; $badBudgets = $budgets;
    if ($case === 'stale') $badSnapshots['Banyuwangi']['id'] = 999999;
    else $badBudgets['Banyuwangi']['inputs']['C11'] = '0.001';
    $rejected = false;
    try { $service->saveAll($year,$badBudgets,$badSnapshots,false,'Test.xlsx',null); }
    catch (RuntimeException|InvalidArgumentException $exception) { $rejected = true; }
    if (!$rejected) throw new RuntimeException('Bad bulk upload unexpectedly committed.');
    if ((new App\Models\AccountingRkaBudgetModel())->withDeleted()->where('budget_year',$year)->countAllResults() !== 0) {
        throw new RuntimeException('Atomicity failure: some units were persisted after rejected all-unit upload.');
    }
}
$db->transBegin();
try {
    $service->saveAll($year,$budgets,$snapshots,false,'AllUnits.xlsx','test-hash');
    foreach ($units as $index => $unit) {
        $record = $service->find($unit,$year);
        $amount = $unit === 'Korporat Kanwil' ? '2700.06' : (($index + 1) * 100) . '.01';
        if ($record === null || json_decode($record['inputs_json'],true)['C11'] !== $amount) throw new RuntimeException('Wrong per-unit bulk inputs.');
        $snapshots[$unit] = ['id'=>(int)$record['id'],'revision'=>(int)$record['revision']];
    }
    $rejected = false;
    try { $service->saveAll($year,$budgets,$snapshots,false,'AllUnits.xlsx',null); }
    catch (RuntimeException $exception) { $rejected = str_contains($exception->getMessage(),'Konfirmasi'); }
    if (!$rejected) throw new RuntimeException('Replacement was allowed without explicit confirmation.');
    foreach ($units as $unit) $budgets[$unit]['inputs']['C11'] = '321.09';
    $service->saveAll($year,$budgets,$snapshots,true,'AllUnits.xlsx',null);
    foreach ($units as $unit) {
        $record = $service->find($unit,$year);
        $amount = $unit === 'Korporat Kanwil' ? '1926.54' : '321.09';
        if ((int)$record['revision'] !== 2 || json_decode($record['calculated_json'],true)['H166'] !== $amount) throw new RuntimeException('All-unit replacement failed to preserve exact amounts.');
    }
    $child = $service->find('Kanwil', $year);
    $edited = json_decode($child['inputs_json'], true); $edited['C11'] = '-100.01';
    $service->save('Kanwil', $year, $edited, (int)$child['revision'], 'manual', null, null, (int)$child['id']);
    if (json_decode($service->find('Korporat Kanwil', $year)['calculated_json'],true)['H166'] !== '1505.44') throw new RuntimeException('Stored corporate cache overrode live child update.');
} finally { $db->transRollback(); }
if ((new App\Models\AccountingRkaBudgetModel())->withDeleted()->where('budget_year',$year)->countAllResults() !== 0) throw new RuntimeException('Bulk test records were not rolled back.');
echo "All-unit DB checks OK: atomic rollback on last-unit failure, seven distinct budgets, explicit replacement and revisions; no test records retained.\n";
