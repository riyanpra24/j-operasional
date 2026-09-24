<?php

namespace App\Libraries;

use App\Models\AccountingLrImportModel;

/** Loads the latest source per unit and report basis, then calculates one consistent report view. */
final class LrRealizationService
{
    public const BASES = ['YTD', 'PTD'];
    public const LOB_COLUMNS = ['KUR', 'PEN', 'NON KUR', 'KBG/SURETYSHIP', 'KONSUMTIF', 'PRODUKTIF'];

    public function view(string $selectedUnit, int $year, string $basis = 'YTD', ?int $month = null): array
    {
        if (!in_array($basis, self::BASES, true)) $basis = 'YTD';
        if ($month !== null && ($month < 1 || $month > 12)) $month = null;
        if ($month === null) {
            $periodQuery=(new AccountingLrImportModel())->selectMax('report_month')->where('report_year',$year)->where('report_basis',$basis);
            if (in_array($selectedUnit,RkaCalculator::SOURCE_UNITS,true)) $periodQuery->where('unit_name',$selectedUnit);
            $latestPeriod=$periodQuery->first();
            $month=isset($latestPeriod['report_month']) && (int)$latestPeriod['report_month']>=1
                ? (int)$latestPeriod['report_month'] : (int)date('n');
        }
        $rows = (new AccountingLrImportModel())->where('report_year', $year)->where('report_basis', $basis)->where('report_month',$month)
            ->orderBy('id', 'DESC')->findAll();
        $latest = [];
        foreach ($rows as $row) {
            $unit = (string) $row['unit_name'];
            $isSourceUnit = in_array($unit, RkaCalculator::SOURCE_UNITS, true);
            $isWorkpaperCorporate = $unit === 'Korporat Kanwil' && (string) ($row['rule_version'] ?? '') === LrRealizationCalculator::WORKPAPER_RULE;
            if ((!$isSourceUnit && !$isWorkpaperCorporate) || isset($latest[$unit])) continue;
            $latest[$unit] = $row;
        }

        $results = [];
        $periods = [];
        foreach ($latest as $unit => $row) {
            $results[$unit] = json_decode($row['result_json'], true, 512, JSON_THROW_ON_ERROR);
            $periods[$unit] = ['year' => (int) $row['report_year'], 'month' => (int) $row['report_month']];
        }
        // Imports created before the LOB percentage detail was introduced still
        // retain their original workbook. Re-read it only when needed so the
        // Corporate screen can show the same % Pencapaian detail immediately.
        $corporate = $results['Korporat Kanwil'] ?? null;
        $corporateImport = $latest['Korporat Kanwil'] ?? null;
        if (is_array($corporate) && is_array($corporateImport) && ! $this->hasPercentageDetails($corporate)) {
            $sourcePath = $this->sourcePath($corporateImport);
            if ($sourcePath !== null) {
                try {
                    $reparsed = (new LrWorkpaperImportParser())->parse(
                        $sourcePath,
                        (int) $corporateImport['report_year'],
                        (int) $corporateImport['report_month'],
                        (string) $corporateImport['report_basis'],
                    );
                    if (isset($reparsed['Korporat Kanwil'])) $results['Korporat Kanwil'] = $reparsed['Korporat Kanwil'];
                } catch (\Throwable $exception) {
                    log_message('notice', 'Rincian persentase Korporat belum dapat dibaca ulang: {message}', ['message' => $exception->getMessage()]);
                }
            }
        }
        $rka = [];
        $budgetService = new RkaBudgetService();
        foreach (array_merge(RkaCalculator::SOURCE_UNITS, ['Korporat Kanwil']) as $unit) {
            $record = $budgetService->find($unit, $year);
            $rka[$unit] = $record === null ? null : json_decode($record['calculated_json'], true, 512, JSON_THROW_ON_ERROR);
        }
        $valuesByUnit = (new LrRealizationCalculator())->calculate($results, $rka, $periods);

        return [
            'import' => $latest[$selectedUnit] ?? null,
            'result' => $results[$selectedUnit] ?? null,
            'values' => $valuesByUnit[$selectedUnit] ?? [],
            'values_by_unit' => $valuesByUnit,
            'basis' => $basis,
            'month' => $month,
        ];
    }

    /** @param array<string,mixed> $result */
    private function hasPercentageDetails(array $result): bool
    {
        foreach ((array) ($result['values'] ?? []) as $row) {
            if (is_array($row) && is_array($row['percentage_details'] ?? null) && $row['percentage_details'] !== []) return true;
        }
        return false;
    }

    /** @param array<string,mixed> $import */
    private function sourcePath(array $import): ?string
    {
        $relative = str_replace('\\', '/', trim((string) ($import['source_path'] ?? '')));
        if (!preg_match('~^uploads/laba_rugi/[a-f0-9]{32}\.xlsx$~D', $relative)) return null;
        $path = WRITEPATH . $relative;
        return is_file($path) ? $path : null;
    }
}
