<?php
/** Optional local DB integration test; all test writes are rolled back. */
define('FCPATH', realpath(__DIR__ . '/../../public') . DIRECTORY_SEPARATOR);
define('ENVIRONMENT', 'development');
require __DIR__ . '/../../app/Config/Paths.php';
$paths = new Config\Paths();
require $paths->systemDirectory . '/Boot.php';
CodeIgniter\Boot::bootConsole($paths);

$db = db_connect();
$service = new App\Libraries\RkaBudgetService();
$unit = 'Surabaya'; $year = 2099;
if ((new App\Models\AccountingRkaBudgetModel())->withDeleted()->where('budget_year', $year)->first() !== null) { throw new RuntimeException('Test skipped: year 2099 already contains active/archived data.'); }
$previousRole = session()->get('auth_role');
$db->transBegin();
try {
    $inputs = (new App\Libraries\RkaCalculator())->zeros();
    $inputs['C11'] = '100.01';
    $service->save($unit, $year, $inputs, 0, 'manual');
    if (json_decode($service->find('Korporat Kanwil', $year)['inputs_json'], true)['C11'] !== '100.01'
        || $service->findStored('Korporat Kanwil', $year) !== null) throw new RuntimeException('Corporate must appear automatically without a separate corporate upload.');
    $record = $service->find($unit, $year);
    if ((int) $record['revision'] !== 1) { throw new RuntimeException('Insert failed.'); }
    $values = json_decode($record['calculated_json'], true, 512, JSON_THROW_ON_ERROR);
    if ($values['H166'] !== '100.01') { throw new RuntimeException('Stored financial amount differs.'); }
    $inputs['C11'] = '200.02';
    $service->save($unit, $year, $inputs, 1, 'excel', 'Test.xlsx', null, (int) $record['id']);
    if (json_decode($service->find('Korporat Kanwil', $year)['calculated_json'], true)['H166'] !== '200.02') throw new RuntimeException('Corporate did not reflect an Excel-origin edit.');
    if ((int) $service->find($unit, $year)['revision'] !== 2) { throw new RuntimeException('Update failed.'); }
    $rejected = false;
    try { $service->save($unit, $year, $inputs, 1, 'manual'); }
    catch (RuntimeException $exception) { $rejected = str_contains($exception->getMessage(), 'pengguna lain'); }
    if (!$rejected) { throw new RuntimeException('Stale revision was not rejected.'); }
    if ((int) $service->find($unit, $year)['revision'] !== 2) { throw new RuntimeException('Rejected update changed data.'); }
    $oldId = (int) $record['id'];
    $staleDeleteRejected = false;
    try { $service->delete($unit, $year, 1, $oldId); } catch (RuntimeException $exception) { $staleDeleteRejected = true; }
    if (! $staleDeleteRejected || $service->find($unit, $year) === null) throw new RuntimeException('Stale delete changed active RKA.');
    $service->delete($unit, $year, 2, $oldId);
    if ($service->find('Korporat Kanwil', $year) !== null) throw new RuntimeException('Deleted source remained in corporate consolidation.');
    if ($service->find($unit, $year) !== null || isset($service->revisions()[$unit . ':' . $year])) throw new RuntimeException('Deleted RKA still active.');
    $archive = (new App\Models\AccountingRkaBudgetModel())->withDeleted()->find($oldId);
    if (empty($archive['deleted_at']) || $archive['active_slot'] !== null || json_decode($archive['inputs_json'], true)['C11'] !== '200.02') throw new RuntimeException('Soft delete did not preserve exact inputs.');
    $inputs['C11'] = '300.03'; $service->save($unit, $year, $inputs, 0, 'manual');
    $newRecord = $service->find($unit, $year);
    if ((int) $newRecord['id'] === $oldId) throw new RuntimeException('New RKA overwrote archived RKA.');
    $oldEditRejected = false;
    try { $service->save($unit, $year, $inputs, (int) $newRecord['revision'], 'manual', null, null, $oldId); } catch (RuntimeException $exception) { $oldEditRejected = true; }
    if (! $oldEditRejected || json_decode($service->find($unit, $year)['inputs_json'], true)['C11'] !== '300.03') throw new RuntimeException('Stale edit identity changed replacement RKA.');
    $wrongId = false;
    try { $service->delete($unit, $year, (int) $newRecord['revision'], $oldId); } catch (RuntimeException $exception) { $wrongId = true; }
    if (! $wrongId || $service->find($unit, $year) === null) throw new RuntimeException('Old delete dialog removed a replacement RKA.');
    session()->set('auth_role', 'akutansi');
    $unauthorized = false;
    try { $service->restore($oldId); } catch (RuntimeException $exception) { $unauthorized = true; }
    if (! $unauthorized) throw new RuntimeException('Non-admin restore allowed.');
    session()->set('auth_role', 'admin');
    $conflict = false;
    try { $service->restore($oldId); } catch (RuntimeException $exception) { $conflict = str_contains($exception->getMessage(), 'aktif'); }
    if (! $conflict) throw new RuntimeException('Restore overwrote a new active budget.');
    $service->delete($unit, $year, (int) $newRecord['revision'], (int) $newRecord['id']); $service->restore($oldId);
    $restored = $service->find($unit, $year);
    if (json_decode($service->find('Korporat Kanwil', $year)['inputs_json'], true)['C11'] !== '200.02') throw new RuntimeException('Corporate did not reflect restored source amounts.');
    $corporateRejected = false;
    try { $service->save('Korporat Kanwil', $year, $inputs, 0, 'manual'); } catch (RuntimeException $exception) { $corporateRejected = true; }
    if (!$corporateRejected) throw new RuntimeException('Direct corporate override was allowed.');
    if ((int) $restored['id'] !== $oldId || (int) $restored['revision'] !== 6 || json_decode($restored['inputs_json'], true)['C11'] !== '200.02') throw new RuntimeException('Restore altered amounts or failed revision protection.');
    echo "Database save/delete/archive/new-budget/admin-restore checks OK; test writes rolled back.\n";
} finally { $db->transRollback(); session()->set('auth_role', $previousRole); }
if ($service->find($unit, $year) !== null) { throw new RuntimeException('Test data was not rolled back.'); }
