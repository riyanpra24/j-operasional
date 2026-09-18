<?php

require __DIR__.'/../../app/Libraries/RkaMoney.php';
require __DIR__.'/../../app/Libraries/LrMoney.php';
require __DIR__.'/../../app/Libraries/LrSignRules.php';
require __DIR__.'/../../app/Libraries/OracleLrSalaryParser.php';
require __DIR__.'/../../app/Libraries/LrReportRows.php';
require __DIR__.'/../../app/Libraries/RkaCalculator.php';
require __DIR__.'/../../app/Libraries/LrFormulaService.php';
require __DIR__.'/../../app/Libraries/LrSourceAdjustmentService.php';
require __DIR__.'/../../app/Libraries/LrRealizationCalculator.php';

use App\Libraries\LrRealizationCalculator;
use App\Libraries\OracleLrSalaryParser;

$checks = 0;
$assert = static function (bool $condition, string $message) use (&$checks): void {
    if (!$condition) throw new RuntimeException($message);
    $checks++;
};
$row = static function (int $number, string $lob, string $description, string $amount, string $descriptionLob = '', ?string $reportLabel = null): array {
    $value = [
        'row' => $number, 'lob' => $lob, 'source_lob' => $lob,
        'description' => $description, 'description_lob' => $descriptionLob,
        'source_amount' => $amount, 'calculation_amount' => $amount,
    ];
    if ($reportLabel !== null) $value['report_label'] = $reportLabel;
    return $value;
};

$matches = [
    $row(1, 'KUR', 'Pendapatan premi penjaminan kredit', '-100'),
    $row(2, 'NON KUR', 'Pendapatan premi penjaminan kredit', '-60', 'Penjaminan KBG'),
    $row(3, 'NON KUR', 'Pendapatan premi penjaminan kredit', '-40', 'Penjaminan Produktif'),
    $row(4, 'KUR', 'Pembayaran premi penjaminan ulang keluar', '10'),
    $row(5, 'NON KUR', 'Pembayaran premi penjaminan ulang keluar', '20', 'Penjaminan Produktif'),
    $row(6, 'KUR', 'Beban klaim penjaminan kredit bruto', '30'),
    $row(7, 'NON KUR', 'Beban klaim penjaminan kredit bruto', '40', 'Penjaminan KBG'),
    $row(8, 'KUR', 'Kenaikan/penurunan estimasi liabilitas klaim - penjaminan', '4'),
    $row(9, 'NON KUR', 'Kenaikan/penurunan estimasi liabilitas klaim - penjaminan', '6'),
    $row(10, 'KUR', 'Hak subrogasi penjaminan kredit - bruto', '-3'),
    $row(11, 'NON KUR', 'Hak subrogasi penjaminan kredit - bruto', '-5', 'Penjaminan KBG'),
    $row(12, 'NON KUR', 'Beban penagihan subrogasi', '1', 'Penjaminan KBG'),
    $row(13, 'NON KUR', 'Beban Pajak PPh 21 Non Karywan', '2'),
    $row(14, 'KUR', 'Pendapatan komisi penjaminan ulang diterima', '-2'),
    $row(15, 'KUR', 'Komisi penjaminan kredit dibayar', '-1'),
    $row(16, 'KUR', 'Beban gaji karyawan', '10', '', 'Beban gaji karyawan'),
    $row(17, 'NON KUR', 'Beban gaji karyawan', '20', '', 'Beban gaji karyawan'),
    $row(18, 'KUR', 'Pendapatan jasa giro', '-5', '', 'Pendapatan jasa giro'),
    $row(19, 'NON KUR', 'Pendapatan lainnya', '-10', '', 'Pendapatan lainnya'),
];
$unmapped = [
    $row(20, 'KUR', 'Tranportasi dinas luar negeri', '1'),
    $row(21, 'NON KUR', 'Tranportasi dinas luar negeri', '-1'),
];
$result = [
    'rule' => OracleLrSalaryParser::RULE,
    'unit' => 'Surabaya',
    'matches' => $matches,
    'unmapped' => $unmapped,
];
$rka = ['H29' => '60.00', 'H52' => '60.00'];
$valuesByUnit = (new LrRealizationCalculator())->calculate(['Surabaya' => $result], ['Surabaya' => $rka]);
$values = $valuesByUnit['Surabaya'];
$get = static fn (string $label, string $column): ?string => $values[OracleLrSalaryParser::normalizeLabel($label)][$column] ?? null;

$assert($get('Imbal Jasa Penjaminan Bruto', 'KUR') === '100.00' && $get('Imbal Jasa Penjaminan Bruto', 'NON KUR') === '100.00', 'Premium source signs must be inverted exactly.');
$assert($get('Imbal Jasa Penjaminan Bruto', 'KBG/SURETYSHIP') === '60.00' && $get('Imbal Jasa Penjaminan Bruto', 'PRODUKTIF') === '40.00', 'Product premium must follow Description LOB directly.');
$assert($get('IMBAL JASA PENJAMINAN BERSIH', 'TOTAL') === '170.00', 'Net premium formula must subtract reinsurance premium.');
$assert($get('Pendapatan Subrogasi', 'NON KUR') === '2.00' && $get('Pendapatan Subrogasi', 'KBG/SURETYSHIP') === '6.00', 'Subrogation must preserve the different base and product signs.');
$assert($get('Kenaikan (Penurunan) Cadangan Klaim', 'KBG/SURETYSHIP') === '0.00', 'Reserve product result must be literal zero.');
$assert($get('Beban gaji karyawan', 'KBG/SURETYSHIP') === '12.000000000000000000000000000000' && $get('Beban gaji karyawan', 'PRODUKTIF') === '8.000000000000000000000000000000', 'Generic NON KUR expense must use the premium mix.');
$assert($get('Transportasi dinas luar negeri', 'TOTAL') === '0.00', 'A verified Oracle spelling in an older unmapped upload must be recalculated without re-upload.');
$assert($get('Total Beban Karyawan', 'TOTAL') === '30.00' && $get('TOTAL BEBAN USAHA', 'TOTAL') === '30.00', 'Expense subtotals must be formula results.');
$assert($get('Total Beban Karyawan', '%') === '0.500000000000000000', 'Percentage must compare total actual with the matching total RKA.');
$assert($get('Beban gaji karyawan', '%') === '0.500000000000000000', 'Detail percentage must use its matching RKA row.');
$assert($get('PENDAPATAN (BEBAN) LAIN-LAIN BERSIH', 'TOTAL') === '15.00', 'Other income must reverse source signs and total automatically.');
$assert($get('LABA SEBELUM PAJAK', 'TOTAL') !== null, 'Pre-tax profit must be calculated from all upstream formula rows.');
$assert($get('Imbal Jasa Penjaminan Bruto', 'PEN') === null, 'A missing PEN source must remain empty, not fabricated as zero.');
$assert(($valuesByUnit['Korporat Kanwil'][OracleLrSalaryParser::normalizeLabel('LABA SEBELUM PAJAK')]['TOTAL'] ?? null) === $get('LABA SEBELUM PAJAK', 'TOTAL'), 'Corporate result must consolidate the available source units.');

echo "Realization formula engine: {$checks} exact checks OK; sources, products, subtotals, profit, percentages, consolidation and missing values are automatic.\n";
