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
        foreach ($latest as $unit => $row) {
            $results[$unit] = json_decode($row['result_json'], true, 512, JSON_THROW_ON_ERROR);
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
        $valuesByUnit = (new LrRealizationCalculator())->calculate($results, $rka);
        $sourceReportEdited = false;
        foreach ($results as $unit => $result) {
            if (! is_array($result) || ! isset($valuesByUnit[$unit])) {
                continue;
            }
            $manualReport = $result['manual_report_override']['values'] ?? null;
            if (is_array($manualReport)) {
                foreach ($manualReport as $key => $override) {
                    if (is_string($key) && is_array($override)) {
                        $valuesByUnit[$unit][$key] = array_merge((array) ($valuesByUnit[$unit][$key] ?? []), $override);
                    }
                }
                $valuesByUnit[$unit] = $this->refreshManualReportPercentages($valuesByUnit[$unit], $rka[$unit] ?? null);
                $sourceReportEdited = $sourceReportEdited || in_array($unit, RkaCalculator::SOURCE_UNITS, true);
                continue;
            }
            if (isset($result['manual_volume_override'])) {
                $valuesByUnit[$unit] = $this->refreshManualReportPercentages($valuesByUnit[$unit], $rka[$unit] ?? null);
                $sourceReportEdited = $sourceReportEdited || in_array($unit, RkaCalculator::SOURCE_UNITS, true);
            }
        }
        if ($sourceReportEdited && isset($valuesByUnit['Korporat Kanwil'])) {
            $corporateValues = [];
            foreach (RkaCalculator::SOURCE_UNITS as $source) {
                foreach ((array) ($valuesByUnit[$source] ?? []) as $key => $row) {
                    if (! is_string($key) || ! is_array($row)) continue;
                    foreach (LrRealizationCalculator::VALUE_COLUMNS as $column) {
                        $corporateValues[$key][$column] = \App\Libraries\LrMoney::add(
                            (string) ($corporateValues[$key][$column] ?? '0.00'),
                            (string) ($row[$column] ?? '0.00'),
                        );
                    }
                }
            }
            foreach ($corporateValues as $key => $row) {
                $valuesByUnit['Korporat Kanwil'][$key] = array_merge((array) ($valuesByUnit['Korporat Kanwil'][$key] ?? []), $row);
            }
            $valuesByUnit['Korporat Kanwil'] = $this->refreshManualReportPercentages(
                $valuesByUnit['Korporat Kanwil'],
                $rka['Korporat Kanwil'] ?? null,
            );
        }

        return [
            'import' => $latest[$selectedUnit] ?? null,
            'result' => $results[$selectedUnit] ?? null,
            'values' => $valuesByUnit[$selectedUnit] ?? [],
            'values_by_unit' => $valuesByUnit,
            'basis' => $basis,
            'month' => $month,
        ];
    }

    /** Recalculate percentage fields after a manual Laba/Rugi adjustment. */
    private function refreshManualReportPercentages(array $values, ?array $rkaCalculated): array
    {
        if ($rkaCalculated === null) {
            return $values;
        }
        foreach (RkaCalculator::schema()['rows'] as $rowNumber => $definition) {
            $key = OracleLrSalaryParser::normalizeLabel((string) $definition['label']);
            $reportRow = $values[$key] ?? null;
            if (! is_array($reportRow)) continue;
            $budgets = [
                'KUR' => (string) ($rkaCalculated['C' . $rowNumber] ?? '0.00'),
                'PEN' => (string) ($rkaCalculated['D' . $rowNumber] ?? '0.00'),
                'KBG/SURETYSHIP' => (string) ($rkaCalculated['E' . $rowNumber] ?? '0.00'),
                'KONSUMTIF' => (string) ($rkaCalculated['F' . $rowNumber] ?? '0.00'),
                'PRODUKTIF' => (string) ($rkaCalculated['G' . $rowNumber] ?? '0.00'),
                'TOTAL' => (string) ($rkaCalculated['H' . $rowNumber] ?? '0.00'),
            ];
            $total = (string) ($reportRow['TOTAL'] ?? '0.00');
            $values[$key]['%'] = \App\Libraries\LrMoney::isZero($budgets['TOTAL']) ? '0.00'
                : (\App\Libraries\LrMoney::divide($total, $budgets['TOTAL'], 18) ?? '0.00');
            $details = [];
            foreach (['KUR', 'PEN', 'KBG/SURETYSHIP', 'KONSUMTIF', 'PRODUKTIF'] as $column) {
                $details[$column] = \App\Libraries\LrMoney::isZero($budgets[$column]) ? '0.00'
                    : (\App\Libraries\LrMoney::divide((string) ($reportRow[$column] ?? '0.00'), $budgets[$column], 18) ?? '0.00');
            }
            $details['TOTAL'] = $values[$key]['%'];
            $values[$key]['percentage_details'] = $details;
        }
        return $values;
    }

    /**
     * Returns the BOPO source values stored in the selected YTD workpaper.
     * Old imports are read again from their original workpaper so a re-upload
     * is not required when the BOPO section is introduced.
     *
     * @return array<string,array{realisasi:?string,target:?string,pencapaian:?string}>
     */
    public function bopoYtd(int $year, int $month): array
    {
        return $this->bopo($year, $month, 'YTD');
    }

    /** @return array<string,array{realisasi:?string,target:?string,pencapaian:?string}> */
    public function bopoPtd(int $year, int $month): array
    {
        return $this->bopo($year, $month, 'PTD');
    }

    /** @return array<string,array{realisasi:?string,target:?string,pencapaian:?string}> */
    private function bopo(int $year, int $month, string $basis): array
    {
        if ($month < 1 || $month > 12 || !in_array($basis, self::BASES, true)) return [];
        $rows = (new AccountingLrImportModel())
            ->where('report_year', $year)
            ->where('report_basis', $basis)
            ->where('report_month', $month)
            ->orderBy('id', 'DESC')
            ->findAll();

        $latest = [];
        foreach ($rows as $row) {
            $unit = (string) ($row['unit_name'] ?? '');
            $isSourceUnit = in_array($unit, RkaCalculator::SOURCE_UNITS, true);
            $isWorkpaperCorporate = $unit === 'Korporat Kanwil'
                && (string) ($row['rule_version'] ?? '') === LrWorkpaperImportParser::RULE;
            if (($isSourceUnit || $isWorkpaperCorporate) && !isset($latest[$unit])) $latest[$unit] = $row;
        }

        $values = [];
        $reparsedByPath = [];
        foreach ($latest as $unit => $row) {
            $result = null;
            try { $result = json_decode((string) $row['result_json'], true, 512, JSON_THROW_ON_ERROR); }
            catch (\JsonException) { $result = null; }
            $bopo = is_array($result) && is_array($result['bopo'] ?? null) ? $result['bopo'] : null;

            // Imports created before the per-unit S168 fallback was added need
            // a one-time read from their original YTD workpaper.
            if ($bopo === null || !$this->hasBopoPencapaian($bopo)) {
                $path = $this->sourcePath($row);
                if ($path !== null) {
                    if (!array_key_exists($path, $reparsedByPath)) {
                        try {
                            $reparsedByPath[$path] = (new LrWorkpaperImportParser())->parse(
                                $path,
                                (int) $row['report_year'],
                                (int) $row['report_month'],
                                (string) $row['report_basis'],
                                is_array($result) && ($result['manual_adjustment_approved'] ?? false) === true,
                            );
                        } catch (\Throwable $exception) {
                            log_message('notice', 'Sumber BOPO belum dapat dibaca ulang: {message}', ['message' => $exception->getMessage()]);
                            $reparsedByPath[$path] = [];
                        }
                    }
                    $bopo = $reparsedByPath[$path][$unit]['bopo'] ?? null;
                }
            }

            $values[$unit] = $this->normalizeBopo($bopo);
        }
        return $values;
    }

    /** @param mixed $raw @return array{realisasi:?string,target:?string,pencapaian:?string} */
    private function normalizeBopo(mixed $raw): array
    {
        $result = ['realisasi' => null, 'target' => null, 'pencapaian' => null];
        if (!is_array($raw)) return $result;
        foreach (array_keys($result) as $key) {
            if (!is_string($raw[$key] ?? null)) continue;
            try { $result[$key] = LrMoney::decimal($raw[$key]); }
            catch (\InvalidArgumentException) { /* An unreadable optional control cell stays empty. */ }
        }
        return $result;
    }

    /** @param mixed $bopo */
    private function hasBopoPencapaian(mixed $bopo): bool
    {
        return is_array($bopo) && is_string($bopo['pencapaian'] ?? null) && $bopo['pencapaian'] !== '';
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
