<?php
/** Transactional checks for period-bound Oracle source adjustments. */
ob_start();
define('FCPATH', realpath(__DIR__ . '/../../public') . DIRECTORY_SEPARATOR);
define('ENVIRONMENT', 'development');
require __DIR__ . '/../../app/Config/Paths.php';
$paths = new Config\Paths();
require $paths->systemDirectory . '/Boot.php';
CodeIgniter\Boot::bootConsole($paths);

$db = db_connect(); $checks = 0;
$assert = static function (bool $condition, string $message) use (&$checks): void {
    if (!$condition) throw new RuntimeException($message);
    $checks++;
};
$rejects = static function (callable $operation, string $message) use ($assert): void {
    try { $operation(); } catch (RuntimeException) { $assert(true, $message); return; }
    throw new RuntimeException($message);
};

$service = new App\Libraries\LrSourceAdjustmentService();
$known = $service->knownSourceDescriptions();
$assert(in_array('Kenaikan/penurunan estimasi liabilitas klaim - penjaminan', $known, true)
    && in_array('Penerimaan klaim penjaminan ulang', $known, true),
    'The source editor must offer both standard reserve and reinsurance descriptions.');
$assert(in_array('Kenaikan (Penurunan) Cadangan Klaim', App\Libraries\LrSourceAdjustmentService::targetOptions(), true),
    'Reserve must be an allowed source-adjustment target.');

$db->transBegin();
try {
    $service->save([
        'unit_scope' => 'surabaya', 'column_scope' => 'kur',
        'target_label' => 'Kenaikan (Penurunan) Cadangan Klaim',
        'effective_from' => '2099-11', 'effective_to' => '2099-11',
        'formula_lines' => "+ Kenaikan/penurunan estimasi liabilitas klaim - penjaminan\n+ Penerimaan klaim penjaminan ulang",
        'reason' => 'Penyesuaian salah pencatatan Surabaya Agustus 2026.', 'is_active' => '1',
    ], 'Adjustment Test');
    $row = $db->table(App\Libraries\LrSourceAdjustmentService::TABLE)->where('unit_scope', 'surabaya')
        ->where('column_scope', 'kur')->orderBy('id', 'DESC')->get()->getRowArray();
    $assert($row !== null && (int) $row['is_active'] === 1, 'A period-bound KUR adjustment must be stored as active.');
    $assert($db->table(App\Libraries\LrSourceAdjustmentService::AUDIT_TABLE)->where('rule_id', (int) $row['id'])->countAllResults() === 1,
        'Creation must produce an immutable audit snapshot.');
    $rejects(static fn () => (new App\Libraries\LrSourceAdjustmentService())->save([
        'unit_scope' => 'surabaya', 'column_scope' => 'kur',
        'target_label' => 'Kenaikan (Penurunan) Cadangan Klaim',
        'effective_from' => '2099-11', 'effective_to' => '2099-12',
        'formula_lines' => '+ Kenaikan/penurunan estimasi liabilitas klaim - penjaminan',
        'reason' => 'Aturan ini sengaja bertumpang tindih untuk pengujian.', 'is_active' => '1',
    ], 'Adjustment Test'), 'Overlapping active adjustments must be rejected.');
    $rejects(static fn () => (new App\Libraries\LrSourceAdjustmentService())->save([
        'unit_scope' => 'surabaya', 'column_scope' => 'non-kur',
        'target_label' => 'Kenaikan (Penurunan) Cadangan Klaim',
        'effective_from' => '2099-11', 'effective_to' => '2099-11',
        'formula_lines' => '+ Uraian Oracle Salah Ketik',
        'reason' => 'Uraian salah ketik harus ditolak oleh sistem.', 'is_active' => '1',
    ], 'Adjustment Test'), 'Unknown Oracle descriptions must be rejected.');

    $source = ['matches' => [
        ['row' => 1, 'lob' => 'KUR', 'description' => 'Kenaikan/penurunan estimasi liabilitas klaim - penjaminan', 'source_amount' => '10'],
        ['row' => 2, 'lob' => 'KUR', 'description' => 'Penerimaan klaim penjaminan ulang', 'source_amount' => '5'],
        ['row' => 3, 'lob' => 'NON KUR', 'description' => 'Kenaikan/penurunan estimasi liabilitas klaim - penjaminan', 'source_amount' => '20'],
    ], 'unmapped' => []];
    $overrides = (new App\Libraries\LrSourceAdjustmentService())->calculateOverrides($source, 'Surabaya', 2099, 11);
    $reserveKey = App\Libraries\OracleLrSalaryParser::normalizeLabel('Kenaikan (Penurunan) Cadangan Klaim');
    $assert(($overrides[$reserveKey]['KUR'] ?? null) === '15.00' && !array_key_exists('NON KUR', $overrides[$reserveKey] ?? []),
        'A Surabaya KUR override must sum its raw sources without affecting NON KUR.');
    $assert((new App\Libraries\LrSourceAdjustmentService())->calculateOverrides($source, 'Surabaya', 2099, 12) === [],
        'An expired adjustment must not affect the following month.');

    $service->save([
        'unit_scope' => 'malang', 'column_scope' => 'kur',
        'target_label' => 'Pendapatan Subrogasi',
        'effective_from' => '2097-01', 'effective_to' => '2097-01',
        'formula_lines' => "+ Kenaikan/penurunan estimasi liabilitas klaim - penjaminan\n* Penerimaan klaim penjaminan ulang",
        'reason' => 'Pengujian operator kali pada penyesuaian sumber Oracle.', 'is_active' => '1',
    ], 'Adjustment Test');
    $service->save([
        'unit_scope' => 'malang', 'column_scope' => 'kur',
        'target_label' => 'Beban Komisi Netto',
        'effective_from' => '2097-01', 'effective_to' => '2097-01',
        'formula_lines' => "+ Kenaikan/penurunan estimasi liabilitas klaim - penjaminan\n/ Penerimaan klaim penjaminan ulang",
        'reason' => 'Pengujian operator bagi pada penyesuaian sumber Oracle.', 'is_active' => '1',
    ], 'Adjustment Test');
    $mathOverrides = (new App\Libraries\LrSourceAdjustmentService())->calculateOverrides($source, 'Malang', 2097, 1);
    $subrogationKey = App\Libraries\OracleLrSalaryParser::normalizeLabel('Pendapatan Subrogasi');
    $commissionKey = App\Libraries\OracleLrSalaryParser::normalizeLabel('Beban Komisi Netto');
    $assert(($mathOverrides[$subrogationKey]['KUR'] ?? null) === '50.000000000000000000000000000000'
        && ($mathOverrides[$commissionKey]['KUR'] ?? null) === '2.000000000000000000000000000000',
        'Multiply and divide operators must be evaluated sequentially using exact decimal values.');
    $zeroSource = $source;
    $zeroSource['matches'][1]['source_amount'] = '0';
    $zeroDivision = (new App\Libraries\LrSourceAdjustmentService())->calculateOverrides($zeroSource, 'Malang', 2097, 1);
    $assert(!array_key_exists('KUR', $zeroDivision[$commissionKey] ?? []),
        'Division by zero must skip the override so the standard report calculation remains available.');
    $rejects(static fn () => (new App\Libraries\LrSourceAdjustmentService())->save([
        'unit_scope' => 'malang', 'column_scope' => 'pen',
        'target_label' => 'Pendapatan Subrogasi',
        'effective_from' => '2097-02', 'effective_to' => '2097-02',
        'formula_lines' => '* Kenaikan/penurunan estimasi liabilitas klaim - penjaminan',
        'reason' => 'Operator pertama kali sengaja diuji agar ditolak.', 'is_active' => '1',
    ], 'Adjustment Test'), 'The first source description must not use multiply or divide.');

    $activePeriod = (new App\Libraries\LrRealizationCalculator())->calculate(
        ['Surabaya' => $source], [], ['Surabaya' => ['year' => 2099, 'month' => 11]]
    )['Surabaya'];
    $nextPeriod = (new App\Libraries\LrRealizationCalculator())->calculate(
        ['Surabaya' => $source], [], ['Surabaya' => ['year' => 2099, 'month' => 12]]
    )['Surabaya'];
    $assert(($activePeriod[$reserveKey]['KUR'] ?? null) === '15.00' && ($nextPeriod[$reserveKey]['KUR'] ?? null) === '10.00',
        'The report engine must apply the override only to its selected reporting period.');

    $rejects(static fn () => (new App\Libraries\LrSourceAdjustmentService())->deleteInactive((string) $row['id'], 'Adjustment Test'),
        'An active source adjustment must never be deleted directly.');
    (new App\Libraries\LrSourceAdjustmentService())->deactivate((string) $row['id'], 'Adjustment Test');
    $deactivated = $db->table(App\Libraries\LrSourceAdjustmentService::TABLE)->where('id', (int) $row['id'])->get()->getRowArray();
    $assert((int) ($deactivated['is_active'] ?? 1) === 0
        && $db->table(App\Libraries\LrSourceAdjustmentService::AUDIT_TABLE)->where('rule_id', (int) $row['id'])->countAllResults() === 2,
        'Deactivation must preserve the rule and append an audit snapshot.');
    (new App\Libraries\LrSourceAdjustmentService())->deleteInactive((string) $row['id'], 'Adjustment Test');
    $assert($db->table(App\Libraries\LrSourceAdjustmentService::TABLE)->where('id', (int) $row['id'])->get()->getRowArray() === null
        && $db->table(App\Libraries\LrSourceAdjustmentService::AUDIT_TABLE)->where('rule_id', (int) $row['id'])->where('action', 'delete')->countAllResults() === 1,
        'An inactive source adjustment may be deleted while its immutable audit snapshot remains available.');

    $approvalService = new App\Libraries\LrSourceAdjustmentService();
    $requestId = $approvalService->requestSave([
        'unit_scope' => 'banyuwangi', 'column_scope' => 'pen',
        'target_label' => 'Kenaikan (Penurunan) Cadangan Klaim',
        'effective_from' => '2098-10', 'effective_to' => '2098-10',
        'formula_lines' => '+ Kenaikan/penurunan estimasi liabilitas klaim - penjaminan',
        'reason' => 'Pengajuan baru untuk menguji alur persetujuan Administrator.', 'is_active' => '1',
    ], 'User Akuntansi Test');
    $pending = $db->table(App\Libraries\LrSourceAdjustmentService::REQUEST_TABLE)->where('id', $requestId)->get()->getRowArray();
    $beforeApproval = $db->table(App\Libraries\LrSourceAdjustmentService::TABLE)->where('unit_scope', 'banyuwangi')
        ->where('column_scope', 'pen')->where('effective_from', '2098-10-01')->countAllResults();
    $assert(($pending['status'] ?? '') === 'pending' && $beforeApproval === 0,
        'An Accounting create request must remain pending and must not affect calculations before approval.');
    $approvalService->approveRequest((string) $requestId, 'Administrator Test');
    $approved = $db->table(App\Libraries\LrSourceAdjustmentService::REQUEST_TABLE)->where('id', $requestId)->get()->getRowArray();
    $approvedRule = $db->table(App\Libraries\LrSourceAdjustmentService::TABLE)->where('unit_scope', 'banyuwangi')
        ->where('column_scope', 'pen')->where('effective_from', '2098-10-01')->get()->getRowArray();
    $assert(($approved['status'] ?? '') === 'approved' && $approvedRule !== null && (int) $approvedRule['is_active'] === 1,
        'Administrator approval must atomically activate the requested source adjustment.');

    $updateRequestId = $approvalService->requestSave([
        'id' => (string) $approvedRule['id'], 'unit_scope' => 'banyuwangi', 'column_scope' => 'pen',
        'target_label' => 'Kenaikan (Penurunan) Cadangan Klaim',
        'effective_from' => '2098-10', 'effective_to' => '2098-10',
        'formula_lines' => "+ Kenaikan/penurunan estimasi liabilitas klaim - penjaminan\n+ Penerimaan klaim penjaminan ulang",
        'reason' => 'Perubahan pengajuan yang baru berlaku setelah disetujui.', 'is_active' => '1',
    ], 'User Akuntansi Test');
    $unchangedRule = $db->table(App\Libraries\LrSourceAdjustmentService::TABLE)->where('id', (int) $approvedRule['id'])->get()->getRowArray();
    $assert(($unchangedRule['reason'] ?? '') === $approvedRule['reason'],
        'An Accounting update request must not overwrite the active rule while pending.');
    $approvalService->approveRequest((string) $updateRequestId, 'Administrator Test');
    $changedRule = $db->table(App\Libraries\LrSourceAdjustmentService::TABLE)->where('id', (int) $approvedRule['id'])->get()->getRowArray();
    $assert(($changedRule['reason'] ?? '') === 'Perubahan pengajuan yang baru berlaku setelah disetujui.',
        'Administrator approval must apply the requested update.');

    $deactivateRequestId = $approvalService->requestDeactivation((string) $approvedRule['id'], 'User Akuntansi Test');
    $stillActive = $db->table(App\Libraries\LrSourceAdjustmentService::TABLE)->where('id', (int) $approvedRule['id'])->get()->getRowArray();
    $assert((int) ($stillActive['is_active'] ?? 0) === 1,
        'A deactivation request must leave the rule active while waiting for approval.');
    $approvalService->approveRequest((string) $deactivateRequestId, 'Administrator Test');
    $inactiveAfterApproval = $db->table(App\Libraries\LrSourceAdjustmentService::TABLE)->where('id', (int) $approvedRule['id'])->get()->getRowArray();
    $assert((int) ($inactiveAfterApproval['is_active'] ?? 1) === 0,
        'Administrator approval must apply a requested deactivation.');
    $deleteRequestId = $approvalService->requestDeletion((string) $approvedRule['id'], 'User Akuntansi Test');
    $pendingDelete = $db->table(App\Libraries\LrSourceAdjustmentService::REQUEST_TABLE)->where('id', $deleteRequestId)->get()->getRowArray();
    $assert(($pendingDelete['status'] ?? '') === 'pending'
        && $db->table(App\Libraries\LrSourceAdjustmentService::TABLE)->where('id', (int) $approvedRule['id'])->countAllResults() === 1,
        'An Accounting deletion request must leave the inactive rule stored until Administrator approval.');
    $approvalService->approveRequest((string) $deleteRequestId, 'Administrator Test');
    $approvedDelete = $db->table(App\Libraries\LrSourceAdjustmentService::REQUEST_TABLE)->where('id', $deleteRequestId)->get()->getRowArray();
    $assert(($approvedDelete['status'] ?? '') === 'approved'
        && $db->table(App\Libraries\LrSourceAdjustmentService::TABLE)->where('id', (int) $approvedRule['id'])->countAllResults() === 0
        && $db->table(App\Libraries\LrSourceAdjustmentService::AUDIT_TABLE)->where('rule_id', (int) $approvedRule['id'])->where('action', 'delete')->countAllResults() === 1,
        'Administrator approval must delete the inactive rule while retaining its audit history.');

    $rejectedRequestId = $approvalService->requestSave([
        'unit_scope' => 'banyuwangi', 'column_scope' => 'pen',
        'target_label' => 'Kenaikan (Penurunan) Cadangan Klaim',
        'effective_from' => '2098-11', 'effective_to' => '2098-11',
        'formula_lines' => '+ Kenaikan/penurunan estimasi liabilitas klaim - penjaminan',
        'reason' => 'Pengajuan ini sengaja ditolak dalam pengujian persetujuan.', 'is_active' => '1',
    ], 'User Akuntansi Test');
    $approvalService->rejectRequest((string) $rejectedRequestId, 'Administrator Test', 'Tidak sesuai dokumen pendukung.');
    $rejected = $db->table(App\Libraries\LrSourceAdjustmentService::REQUEST_TABLE)->where('id', $rejectedRequestId)->get()->getRowArray();
    $rejectedRuleCount = $db->table(App\Libraries\LrSourceAdjustmentService::TABLE)->where('unit_scope', 'banyuwangi')
        ->where('column_scope', 'pen')->where('effective_from', '2098-11-01')->countAllResults();
    $assert(($rejected['status'] ?? '') === 'rejected' && $rejectedRuleCount === 0,
        'A rejected request must keep the calculation unchanged.');
} finally {
    $db->transRollback();
}

echo "LR source adjustments: {$checks} checks OK; period isolation, searchable sources, Accounting requests, Administrator approval/rejection and audit history are protected.\n";
