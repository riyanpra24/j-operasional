<?php

declare(strict_types=1);

require __DIR__ . '/../app/Libraries/OracleLrSalaryParser.php';
require __DIR__ . '/../app/Libraries/LrReportRows.php';

use App\Libraries\OracleLrSalaryParser;
use App\Libraries\LrReportRows;

/**
 * Inserts clearly marked, reversible Laba/Rugi data for local UI testing.
 * Existing imports are preserved; only prior rows produced by this script are
 * soft-deleted before the refreshed dummy values are inserted.
 */

const DUMMY_SOURCE = 'Data Dummy Laba/Rugi 2026';
const DUMMY_RULE = 'simulasi_kertas_kerja_v1';

function environmentValue(string $key): string
{
    foreach (file(__DIR__ . '/../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
        if (preg_match('/^' . preg_quote($key, '/') . '\\s*=\\s*(.*)$/', trim($line), $matches)) {
            return trim(trim((string) $matches[1]), "\\\"'");
        }
    }
    return '';
}

/** @return array<string,string> */
function reportColumns(int $amount): array
{
    $kur = intdiv($amount * 48, 100);
    $pen = intdiv($amount * 12, 100);
    $nonKur = $amount - $kur - $pen;
    $kbg = intdiv($nonKur * 50, 100);
    $konsumtif = intdiv($nonKur * 25, 100);
    $produktif = $nonKur - $kbg - $konsumtif;

    return [
        'KUR' => (string) $kur,
        'PEN' => (string) $pen,
        'NON KUR' => (string) $nonKur,
        'KBG/SURETYSHIP' => (string) $kbg,
        'KONSUMTIF' => (string) $konsumtif,
        'PRODUKTIF' => (string) $produktif,
        'TOTAL' => (string) $amount,
    ];
}

/** @return list<string> */
function detailLabels(string $key): array
{
    foreach (LrReportRows::rows() as $row) {
        if (($row['key'] ?? null) === $key) {
            return array_map(static fn (array $detail): string => (string) $detail['label'], $row['details'] ?? []);
        }
    }
    return [];
}

/** @param list<string> $labels @return array<string,int> */
function distributeAcrossLabels(array $labels, int $total): array
{
    $result = [];
    $remaining = $total;
    $count = count($labels);
    foreach ($labels as $index => $label) {
        $amount = $index === $count - 1 ? $remaining : intdiv($total * ($index + 1), $count * ($index + 2));
        $result[$label] = $amount;
        $remaining -= $amount;
    }
    return $result;
}

/** @return list<string> */
function displayedValueLabels(): array
{
    $labels = [];
    foreach (LrReportRows::rows() as $row) {
        if (in_array($row['label'], ['PENDAPATAN INVESTASI BERSIH', 'BEBAN USAHA'], true)) {
            continue; // These two entries are section titles, not numeric rows.
        }
        $labels[] = (string) ($row['value_label'] ?? $row['label']);
        foreach ($row['details'] ?? [] as $detail) $labels[] = (string) $detail['label'];
    }
    return array_values(array_unique($labels));
}

/** @param array<string,array<string,string>> $values */
function assertEveryDisplayedRowHasDummyValue(array $values): void
{
    $missing = [];
    foreach (displayedValueLabels() as $label) {
        if (!isset($values[OracleLrSalaryParser::normalizeLabel($label)])) $missing[] = $label;
    }
    if ($missing !== []) {
        throw new RuntimeException('Baris tabel dummy belum terisi: ' . implode(', ', $missing));
    }
}

/** @return array<string,array<string,string>> */
function dummyValues(int $volume): array
{
    $premiumGross = intdiv($volume * 15, 100);
    $restitution = intdiv($volume, 100);
    $reinsurancePremium = -intdiv($volume * 2, 100);
    $premiumNet = $premiumGross + $restitution + $reinsurancePremium;
    $claim = -intdiv($volume * 5, 100);
    $reserve = -intdiv($volume * 7, 1000);
    $subrogation = intdiv($volume * 15, 1000);
    $commission = -intdiv($volume, 1000);
    $claimsTotal = $claim + $reserve + $subrogation + $commission;
    $netGuarantee = $premiumNet + $claimsTotal;
    $employeeExpense = -intdiv($volume * 16, 1000);
    $administrationExpense = -intdiv($volume * 18, 1000);
    $amortizationExpense = -intdiv($volume * 6, 1000);
    $operatingExpense = $employeeExpense + $administrationExpense + $amortizationExpense;
    $otherIncome = intdiv($volume * 5, 1000);
    $profit = $netGuarantee + $operatingExpense + $otherIncome;

    $raw = [
        'VOLUME' => $volume,
        'Imbal Jasa Penjaminan Bruto' => $premiumGross,
        'Restitusi penjaminan kredit' => $restitution,
        'Premi Penjaminan Ulang' => $reinsurancePremium,
        'IMBAL JASA PENJAMINAN BERSIH' => $premiumNet,
        'Beban Klaim' => $claim,
        'Kenaikan (Penurunan) Cadangan Klaim' => $reserve,
        'Pendapatan Subrogasi' => $subrogation,
        'Beban Komisi Netto' => $commission,
        'JUMLAH BEBAN KLAIM' => $claimsTotal,
        'PENJAMINAN BERSIH' => $netGuarantee,
        'Total Beban Karyawan' => $employeeExpense,
        'Total Beban Administrasi & Umum' => $administrationExpense,
        'Total Beban Penyusutan & Amortisasi' => $amortizationExpense,
        'TOTAL BEBAN USAHA' => $operatingExpense,
        'Pendapatan jasa giro' => intdiv($otherIncome * 3, 5),
        'Pendapatan lainnya' => $otherIncome - intdiv($otherIncome * 3, 5),
        'PENDAPATAN (BEBAN) LAIN-LAIN BERSIH' => $otherIncome,
        'LABA SEBELUM PAJAK' => $profit,
    ];

    foreach ([
        'beban-karyawan' => $employeeExpense,
        'beban-administrasi-umum' => $administrationExpense,
        'beban-penyusutan-amortisasi' => $amortizationExpense,
    ] as $group => $total) {
        $labels = detailLabels($group);
        array_pop($labels); // The final detail row is the group total, populated above.
        $raw += distributeAcrossLabels($labels, $total);
    }

    $values = [];
    foreach ($raw as $label => $amount) {
        $values[OracleLrSalaryParser::normalizeLabel($label)] = reportColumns($amount);
    }
    return $values;
}

/** @param list<array<string,array<string,string>>> $reports
 *  @return array<string,array<string,string>>
 */
function sumReports(array $reports): array
{
    $sum = [];
    foreach ($reports as $report) {
        foreach ($report as $label => $columns) {
            foreach ($columns as $column => $value) {
                $sum[$label][$column] = (string) ((int) ($sum[$label][$column] ?? 0) + (int) $value);
            }
        }
    }
    return $sum;
}

$connection = new mysqli(
    environmentValue('database.default.hostname') ?: '127.0.0.1',
    environmentValue('database.default.username'),
    environmentValue('database.default.password'),
    environmentValue('database.default.database'),
);
if ($connection->connect_errno !== 0) {
    throw new RuntimeException('Basis data lokal tidak dapat dihubungkan: ' . $connection->connect_error);
}
$connection->set_charset('utf8mb4');

$periods = [
    ['basis' => 'YTD', 'month' => 7, 'volume' => 11200000000],
    ['basis' => 'YTD', 'month' => 8, 'volume' => 13800000000],
    ['basis' => 'PTD', 'month' => 7, 'volume' => 2100000000],
    ['basis' => 'PTD', 'month' => 8, 'volume' => 2600000000],
];
$weights = ['Kanwil' => 34, 'Surabaya' => 26, 'Kediri' => 18, 'Malang' => 12, 'Madiun' => 6, 'Banyuwangi' => 4];
$insert = $connection->prepare(
    'INSERT INTO accounting_lr_imports '
    . '(unit_name, report_year, report_month, report_basis, rule_version, result_json, source_name, source_hash, source_path, created_by_name, created_at) '
    . 'VALUES (?, 2026, ?, ?, ?, ?, ?, ?, ?, ?, NOW())'
);
if ($insert === false) {
    throw new RuntimeException('Pernyataan simpan data dummy tidak dapat dibuat.');
}

$connection->begin_transaction();
try {
    $source = DUMMY_SOURCE;
    $cleanup = $connection->prepare(
        'UPDATE accounting_lr_imports SET deleted_at = NOW(), deleted_by_role = ?, deleted_by_name = ? '
        . 'WHERE source_name = ? AND deleted_at IS NULL'
    );
    if ($cleanup === false) throw new RuntimeException('Pembersihan data dummy lama tidak dapat disiapkan.');
    $role = 'system';
    $actor = 'Seed Data Dummy';
    $cleanup->bind_param('sss', $role, $actor, $source);
    if (! $cleanup->execute()) throw new RuntimeException('Data dummy lama tidak dapat dibersihkan.');
    $cleanup->close();

    foreach ($periods as $period) {
        $sourceReports = [];
        foreach ($weights as $unit => $weight) {
            $sourceReports[$unit] = dummyValues(intdiv($period['volume'] * $weight, 100));
            assertEveryDisplayedRowHasDummyValue($sourceReports[$unit]);
        }
        $sourceReports['Korporat Kanwil'] = sumReports(array_values($sourceReports));
        assertEveryDisplayedRowHasDummyValue($sourceReports['Korporat Kanwil']);

        foreach ($sourceReports as $unit => $values) {
            $result = [
                'rule' => DUMMY_RULE,
                'unit' => $unit,
                'sheet' => strtoupper($unit),
                'year' => 2026,
                'month' => $period['month'],
                'basis' => $period['basis'],
                'period' => $period['basis'] . ' · ' . $period['month'] . ' 2026',
                'values' => $values,
                'percentage_column' => null,
                'bopo' => ['realisasi' => null, 'target' => null, 'pencapaian' => null],
                'read_rows' => count($values),
                'calculated_cells' => 0,
                'is_dummy' => true,
            ];
            $month = $period['month'];
            $basis = $period['basis'];
            $rule = DUMMY_RULE;
            $resultJson = json_encode($result, JSON_THROW_ON_ERROR);
            $hash = hash('sha256', $source . '|' . $basis . '|' . $month . '|2026');
            $path = 'uploads/laba_rugi/data-dummy-' . strtolower($basis) . '-' . $month . '-2026.xlsx';
            $createdBy = 'Seed Data Dummy';
            $insert->bind_param('sisssssss', $unit, $month, $basis, $rule, $resultJson, $source, $hash, $path, $createdBy);
            if (! $insert->execute()) throw new RuntimeException('Data dummy gagal disimpan untuk ' . $unit . ' ' . $basis . ' bulan ' . $month . '.');
        }
    }
    $connection->commit();
    echo "28 data dummy Laba/Rugi dibuat untuk YTD Juli–Agustus dan PTD Juli–Agustus 2026.\n";
} catch (Throwable $exception) {
    $connection->rollback();
    throw $exception;
} finally {
    $insert->close();
    $connection->close();
}
