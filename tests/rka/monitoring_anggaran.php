<?php

require __DIR__ . '/../../app/Libraries/RkaMoney.php';
require __DIR__ . '/../../app/Libraries/RkaCalculator.php';
require __DIR__ . '/../../app/Libraries/LrMoney.php';
require __DIR__ . '/../../app/Libraries/OracleLrSalaryParser.php';
require __DIR__ . '/../../app/Libraries/MonitoringAnggaran.php';

$calculator = new App\Libraries\RkaCalculator();
$inputs = $calculator->zeros();
$inputs['C29'] = '100.25';
$inputs['D29'] = '10.75';
$inputs['C32'] = '999.00'; // Outside the monitoring worksheet's SDM detail.
$inputs['C59'] = '25.00';
$values = $calculator->calculate($inputs);
$monitoring = new App\Libraries\MonitoringAnggaran();
$rows = $monitoring->rows($values);
if (count($rows) !== 61 || $rows[0]['amount'] !== '111.00' || $rows[1]['amount'] !== '111.00'
    || $rows[12]['amount'] !== '25.00' || $rows[13]['label'] !== 'Imbal jasa profesional - lainnya') {
    throw new RuntimeException('RKA detail or worksheet subtotal mapping is incorrect.');
}
$filtered = $monitoring->rows($values, 'gaji');
if (count($filtered) !== 2 || $filtered[0]['amount'] !== '111.00' || $filtered[1]['label'] !== 'Beban gaji karyawan') {
    throw new RuntimeException('Search changed the group total or returned the wrong detail.');
}
$missing = $monitoring->rows(null);
if ($missing[0]['amount'] !== null || $missing[1]['amount'] !== null) {
    throw new RuntimeException('Missing RKA was displayed as zero.');
}
unset($values['H29']);
if ($monitoring->rows($values)[0]['amount'] !== null) {
    throw new RuntimeException('Incomplete RKA was displayed as a complete subtotal.');
}
$realizations = [];
foreach ($rows as $row) {
    if ($row['group']) continue;
    $label = $row['label'] === 'Tranportasi dinas dalam negeri' ? 'Transportasi dinas dalam negeri' : $row['label'];
    $realizations[App\Libraries\OracleLrSalaryParser::normalizeLabel($label)] = ['TOTAL' => '1.001'];
}
$realizations['beban gaji karyawan']['TOTAL'] = '2.005';
$realizations['transportasi dinas dalam negeri']['TOTAL'] = '-7.75';
$actualRows = $monitoring->rows(null, '', $realizations);
if ($actualRows[0]['realization'] !== '12.015' || $actualRows[1]['realization'] !== '2.005' || $actualRows[1]['amount'] !== null) {
    throw new RuntimeException('YTD values or full-precision group total not mapped independently of RKA.');
}
$transport = $monitoring->rows(null, 'Tranportasi', $realizations);
if (count($transport) !== 2 || $transport[1]['realization'] !== '-7.75' || $transport[0]['realization'] !== $actualRows[12]['realization']) {
    throw new RuntimeException('Spelling alias or filtered group subtotal incorrect.');
}
$realizations['beban gaji karyawan']['TOTAL'] = '0.00';
if ($monitoring->rows(null, '', $realizations)[1]['realization'] !== '0.00') throw new RuntimeException('Real zero was treated as missing.');
unset($realizations['beban gaji karyawan']);
$missingActual = $monitoring->rows(null, '', $realizations);
if ($missingActual[0]['realization'] !== null || $missingActual[1]['realization'] !== null) throw new RuntimeException('Missing YTD amounts must not become zero or a partial group total.');
$fullRka = $calculator->calculate($inputs);
$realizations['beban gaji karyawan']['TOTAL'] = '103.23';
$ratios = $monitoring->rows($fullRka, '', $realizations);
if ($ratios[1]['remaining_budget'] !== '7.77' || $ratios[0]['remaining_budget'] !== '-2.240') throw new RuntimeException('Remaining budget must subtract the full-precision YTD realization from RKA.');
if ($monitoring->rows($fullRka, 'gaji', $realizations)[0]['remaining_budget'] !== $ratios[0]['remaining_budget']) throw new RuntimeException('Search must not change group remaining budget.');
if (App\Libraries\LrMoney::percentageDisplay($ratios[1]['realization_ratio']) !== '93%') throw new RuntimeException('Realization ratio must divide YTD by annual RKA.');
if (App\Libraries\LrMoney::percentageDisplay($ratios[0]['realization_ratio']) !== '102%') throw new RuntimeException('Group percentage must use group totals.');
$realizations['beban gaji karyawan']['TOTAL'] = '-103.23';
if (App\Libraries\LrMoney::percentageDisplay($monitoring->rows($fullRka, '', $realizations)[1]['realization_ratio']) !== '*93%') throw new RuntimeException('Negative percentages must retain the report marker.');
if ($monitoring->rows($fullRka, '', $realizations)[2]['realization_ratio'] !== '0.00') throw new RuntimeException('Excel IFERROR behavior for zero RKA is required.');
if ($monitoring->rows(null, '', $realizations)[1]['realization_ratio'] !== null) throw new RuntimeException('Missing RKA must not be treated as zero.');
if ($monitoring->rows(null, '', $realizations)[1]['remaining_budget'] !== null) throw new RuntimeException('Remaining budget must be unavailable when RKA is missing.');
unset($realizations['beban gaji karyawan']);
if ($monitoring->rows($fullRka, '', $realizations)[1]['realization_ratio'] !== null || $monitoring->rows($fullRka, '', $realizations)[0]['realization_ratio'] !== null) throw new RuntimeException('Missing realization must not be treated as zero.');
if ($monitoring->rows($fullRka, '', $realizations)[1]['remaining_budget'] !== null || $monitoring->rows($fullRka, '', $realizations)[0]['remaining_budget'] !== null) throw new RuntimeException('Missing realization must not produce a partial remaining budget.');
foreach ([[9, '0.93', '*18%'], [9, '0.65', '10%'], [9, '0.75', '0%'], [8, '0.662', '0%'], [12, '0.00', '100%']] as [$month, $ratio, $expected]) {
    $deviation = App\Libraries\MonitoringAnggaran::deviation($ratio, $month);
    if (App\Libraries\LrMoney::percentageDisplay($deviation) !== $expected) throw new RuntimeException("Incorrect deviation for month $month and realization $ratio.");
}
if (App\Libraries\MonitoringAnggaran::deviation(null, 9) !== null || App\Libraries\MonitoringAnggaran::deviation('0', 13) !== null) throw new RuntimeException('Missing realization or invalid month must not produce a deviation.');
echo "Monitoring anggaran mapping: passed\n";
