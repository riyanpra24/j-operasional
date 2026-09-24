<?php

namespace App\Libraries;

use RuntimeException;

/** Formula engine translated from the supplied realization workpaper. */
final class LrRealizationCalculator
{
    public const WORKPAPER_RULE = 'simulasi_kertas_kerja_v1';
    public const SOURCE_COLUMNS = ['KUR', 'PEN', 'NON KUR'];
    public const PRODUCT_COLUMNS = ['KBG/SURETYSHIP', 'KONSUMTIF', 'PRODUKTIF'];
    public const VALUE_COLUMNS = ['KUR', 'PEN', 'NON KUR', 'KBG/SURETYSHIP', 'KONSUMTIF', 'PRODUKTIF', 'TOTAL'];

    private LrSourceAdjustmentService $sourceAdjustmentService;

    public function __construct(?LrSourceAdjustmentService $sourceAdjustmentService = null)
    {
        $this->sourceAdjustmentService = $sourceAdjustmentService ?? new LrSourceAdjustmentService();
    }

    /**
     * @param array<string,array> $resultsByUnit Parsed Oracle result by source unit.
     * @param array<string,array|null> $rkaCalculatedByUnit RKA calculated_json by unit.
     * @return array<string,array<string,array<string,string>>>
     */
    public function calculate(array $resultsByUnit, array $rkaCalculatedByUnit = [], array $periodByUnit = []): array
    {
        if (!extension_loaded('bcmath')) throw new RuntimeException('BCMath diperlukan untuk menghitung realisasi secara presisi.');

        // Simulasi Hitung has already applied the approved workbook formulas.  Do not
        // derive, allocate, or recompute those figures again when showing Laba/Rugi.
        if ($this->containsWorkpaperResult($resultsByUnit)) {
            $valuesByUnit = [];
            foreach (RkaCalculator::UNITS as $unit) {
                $result = $resultsByUnit[$unit] ?? null;
                if (!is_array($result) || ($result['rule'] ?? '') !== self::WORKPAPER_RULE || !is_array($result['values'] ?? null)) {
                    // A report may be uploaded progressively. Keep every valid unit
                    // available instead of failing the entire Laba/Rugi page because
                    // another unit has not been uploaded for the selected period.
                    continue;
                }
                $values = $result['values'];
                // Corporate total % is the X-column value under "% Pencapaian"
                // in the workpaper. Older uploads did not persist that cache, so
                // only those records retain the safe R/K fallback.
                $valuesByUnit[$unit] = $unit === 'Korporat Kanwil' && ($result['percentage_column'] ?? null) === 'X'
                    ? $values
                    : $this->applyPercentages($values, $rkaCalculatedByUnit[$unit] ?? null);
            }
            return $valuesByUnit;
        }

        $states = [];
        foreach (RkaCalculator::SOURCE_UNITS as $unit) {
            if (!isset($resultsByUnit[$unit])) continue;
            $period = $periodByUnit[$unit] ?? null;
            $states[$unit] = $this->sourceState(
                $resultsByUnit[$unit], $unit,
                is_array($period) ? (int) ($period['year'] ?? 0) : 0,
                is_array($period) ? (int) ($period['month'] ?? 0) : 0
            );
        }

        $valuesByUnit = [];
        foreach (RkaCalculator::SOURCE_UNITS as $unit) {
            if ($unit === 'Kanwil' || !isset($states[$unit])) continue;
            $valuesByUnit[$unit] = $this->completeUnit(
                $states[$unit], $this->premiumMix($states[$unit]['values']), $unit
            );
        }

        // The workpaper allocates Kanwil NON KUR using the consolidated premium mix.
        $branchPremium = [];
        foreach ($valuesByUnit as $unit => $values) {
            if ($unit === 'Kanwil') continue;
            $branchPremium = $this->addReport($branchPremium, [
                $this->key('Imbal Jasa Penjaminan Bruto') => $values[$this->key('Imbal Jasa Penjaminan Bruto')] ?? [],
            ]);
        }
        $corporateMix = $this->premiumMix($branchPremium);
        if (isset($states['Kanwil'])) {
            $valuesByUnit['Kanwil'] = $this->completeUnit(
                $states['Kanwil'], $corporateMix, 'Kanwil'
            );
        }

        $corporate = [];
        foreach (RkaCalculator::SOURCE_UNITS as $unit) {
            if (isset($valuesByUnit[$unit])) $corporate = $this->addReport($corporate, $valuesByUnit[$unit]);
        }
        if ($corporate) $valuesByUnit['Korporat Kanwil'] = $corporate;

        foreach ($valuesByUnit as $unit => $values) {
            $valuesByUnit[$unit] = $this->applyPercentages($values, $rkaCalculatedByUnit[$unit] ?? null);
        }
        return $valuesByUnit;
    }

    private function containsWorkpaperResult(array $resultsByUnit): bool
    {
        foreach ($resultsByUnit as $result) if (($result['rule'] ?? '') === self::WORKPAPER_RULE) return true;
        return false;
    }

    private function sourceState(array $result, string $unit, int $year = 0, int $month = 0): array
    {
        $values = [];
        $productZeros = [];
        $specialMap = LrReportRows::branchFormulaSourceMap();
        $fallbackMap = $unit === 'Kanwil' ? LrReportRows::sourceMap() : LrReportRows::branchSourceMap();
        $seenRows = [];

        foreach ($result['matches'] ?? [] as $row) {
            $rowId = (string) ($row['row'] ?? 'm'.count($seenRows));
            $seenRows[$rowId] = true;
            $this->applySourceRow($values, $productZeros, $row, $specialMap, $fallbackMap, $unit, true);
        }
        foreach ($result['unmapped'] ?? [] as $row) {
            $rowId = (string) ($row['row'] ?? 'u'.count($seenRows));
            if (isset($seenRows[$rowId])) continue;
            $this->applySourceRow($values, $productZeros, $row, $specialMap, $fallbackMap, $unit, false);
        }
        $sourceOverrides = $year >= 2000 && $month >= 1
            ? $this->sourceAdjustmentService->calculateOverrides($result, $unit, $year, $month)
            : [];
        return ['values' => $values, 'product_zeros' => $productZeros, 'source_overrides' => $sourceOverrides];
    }

    private function applySourceRow(array &$values, array &$productZeros, array $row, array $specialMap, array $fallbackMap, string $unit, bool $mapped): void
    {
        $description = OracleLrSalaryParser::normalizeLabel((string) ($row['description'] ?? ''));
        $special = $specialMap[$description] ?? null;
        $segment = OracleLrSalaryParser::columnForLob((string) ($row['lob'] ?? $row['source_lob'] ?? ''));
        if ($segment === null) return;

        if ($special !== null) {
            if ($unit === 'Kanwil' || !in_array($segment, $special['segments'] ?? [], true)) return;
            $label = (string) $special['report_label'];
            $amount = $this->signed((string) ($row['source_amount'] ?? '0'), (string) $special['sign_mode']);
            $this->addValue($values, $label, $segment, $amount);
            if (!empty($special['product_zero'])) $productZeros[$this->key($label)] = true;
            if ($segment === 'NON KUR' && isset($special['product_sign_mode'])) {
                $product = $this->productColumn((string) ($row['description_lob'] ?? ''));
                if ($product !== null) {
                    $this->addValue($values, $label, $product,
                        $this->signed((string) ($row['source_amount'] ?? '0'), (string) $special['product_sign_mode']));
                }
            }
            return;
        }
        $label = $mapped ? (string) ($row['report_label'] ?? '') : (string) ($fallbackMap[$description] ?? '');
        if ($label === '') return;
        $labelKey = $this->key($label);
        // The realization workpaper reverses both other-income source balances in every unit.
        $amount = in_array($labelKey, [$this->key('Pendapatan jasa giro'), $this->key('Pendapatan lainnya')], true)
            ? LrMoney::negate((string) ($row['source_amount'] ?? '0'))
            : (string) ($mapped ? ($row['calculation_amount'] ?? $row['source_amount'] ?? '0') : ($row['source_amount'] ?? '0'));
        $this->addValue($values, $label, $segment, $amount);
    }

    private function completeUnit(array $state, array $mix, string $unit): array
    {
        $values = $state['values'];
        foreach (LrReportRows::allocatedProductLabels() as $label) {
            $key = $this->key($label);
            if (!isset($values[$key]['NON KUR'])) continue;
            foreach (self::PRODUCT_COLUMNS as $column) {
                if (isset($mix[$column])) $values[$key][$column] = LrMoney::multiply($values[$key]['NON KUR'], $mix[$column]);
            }
        }
        foreach (array_keys($state['product_zeros']) as $key) {
            if (!isset($values[$key])) continue;
            foreach (self::PRODUCT_COLUMNS as $column) $values[$key][$column] = '0.00';
        }
        foreach ($state['source_overrides'] ?? [] as $targetKey => $columns) {
            foreach ($columns as $column => $amount) {
                if ($amount === null) unset($values[$targetKey][$column]);
                else $values[$targetKey][$column] = (string) $amount;
            }
        }

        foreach (array_merge(self::SOURCE_COLUMNS, self::PRODUCT_COLUMNS) as $column) {
            $columnScope = LrFormulaService::columnScopeKey($column);
            if ($columnScope === null) continue;
            // Result-row formulas are part of the calculation engine and cannot be changed from the UI.
            foreach (LrFormulaService::defaultRules() as $formula) {
                $this->deriveColumn($values, (string) $formula['target_label'], (array) $formula['terms'], $column);
            }
        }

        foreach ($values as $key => $columns) {
            $present = false; $total = '0.00';
            foreach (self::SOURCE_COLUMNS as $column) {
                if (!array_key_exists($column, $columns)) continue;
                $present = true; $total = LrMoney::add($total, $columns[$column]);
            }
            if ($present) $values[$key]['TOTAL'] = $total;
        }
        if (isset($values[$this->key('TOTAL BEBAN USAHA')])) {
            $values[$this->key('BEBAN USAHA')] = $values[$this->key('TOTAL BEBAN USAHA')];
        }
        return $values;
    }

    private function deriveColumn(array &$values, string $target, array $terms, string $column): void
    {
        $targetKey = $this->key($target);
        $present = false; $amount = '0.00';
        foreach ($terms as $term) {
            $source = (string) ($term['label'] ?? $term[0] ?? '');
            $coefficient = (int) ($term['coefficient'] ?? $term[1] ?? 0);
            if ($source === '' || !in_array($coefficient, [-1, 1], true)) continue;
            $sourceKey = $this->key($source);
            if (!isset($values[$sourceKey][$column])) continue;
            $present = true;
            $amount = $coefficient === 1
                ? LrMoney::add($amount, $values[$sourceKey][$column])
                : LrMoney::subtract($amount, $values[$sourceKey][$column]);
        }
        if ($present) $values[$targetKey][$column] = $amount;
    }

    private function premiumMix(array $values): array
    {
        $premium = $values[$this->key('Imbal Jasa Penjaminan Bruto')] ?? [];
        if (!isset($premium['NON KUR']) || LrMoney::isZero($premium['NON KUR'])) return [];
        $mix = [];
        foreach (self::PRODUCT_COLUMNS as $column) {
            if (!isset($premium[$column])) continue;
            $ratio = LrMoney::divide($premium[$column], $premium['NON KUR']);
            if ($ratio !== null) $mix[$column] = $ratio;
        }
        return $mix;
    }

    private function addReport(array $left, array $right): array
    {
        foreach ($right as $key => $columns) {
            foreach ($columns as $column => $amount) {
                if (!in_array($column, self::VALUE_COLUMNS, true)) continue;
                $left[$key][$column] = LrMoney::add($left[$key][$column] ?? '0.00', $amount);
            }
        }
        return $left;
    }

    private function applyPercentages(array $values, ?array $rkaCalculated): array
    {
        if ($rkaCalculated === null) return $values;
        $budgetByLabel = [];
        foreach (RkaCalculator::schema()['rows'] as $row => $definition) {
            $budgetCell = 'H'.$row;
            if (!isset($rkaCalculated[$budgetCell])) continue;
            $budgetByLabel[$this->canonical((string) $definition['label'])] = [
                'KUR' => (string) ($rkaCalculated['C'.$row] ?? '0.00'),
                'PEN' => (string) ($rkaCalculated['D'.$row] ?? '0.00'),
                'KBG/SURETYSHIP' => (string) ($rkaCalculated['E'.$row] ?? '0.00'),
                'KONSUMTIF' => (string) ($rkaCalculated['F'.$row] ?? '0.00'),
                'PRODUKTIF' => (string) ($rkaCalculated['G'.$row] ?? '0.00'),
                'TOTAL' => (string) $rkaCalculated[$budgetCell],
            ];
        }
        $budgetByLabel[$this->canonical('BEBAN USAHA')] = $budgetByLabel[$this->canonical('TOTAL BEBAN USAHA')] ?? [
            'KUR' => '0.00', 'PEN' => '0.00', 'KBG/SURETYSHIP' => '0.00',
            'KONSUMTIF' => '0.00', 'PRODUKTIF' => '0.00', 'TOTAL' => '0.00',
        ];
        foreach ($values as $key => $columns) {
            if (!isset($columns['TOTAL'])) continue;
            $budgets = $budgetByLabel[$this->canonical($key)] ?? null;
            if (!is_array($budgets)) continue;
            $values[$key]['%'] = LrMoney::isZero($budgets['TOTAL']) ? '0.00' : (LrMoney::divide($columns['TOTAL'], $budgets['TOTAL'], 18) ?? '0.00');
            $percentageDetails = [];
            foreach (['KUR', 'PEN', 'KBG/SURETYSHIP', 'KONSUMTIF', 'PRODUKTIF'] as $column) {
                $actual = (string) ($columns[$column] ?? '0.00');
                $budget = $budgets[$column];
                $percentageDetails[$column] = LrMoney::isZero($budget) ? '0.00' : (LrMoney::divide($actual, $budget, 18) ?? '0.00');
            }
            $percentageDetails['TOTAL'] = $values[$key]['%'];
            $values[$key]['percentage_details'] = $percentageDetails;
        }
        return $values;
    }

    private function canonical(string $label): string
    {
        $label = $this->key($label);
        $label = str_replace('tranportasi dinas', 'transportasi dinas', $label);
        $label = str_replace(' - konsultan manajemen (sdm, sop, akuntansi dll)', ' - konsultan manajemen', $label);
        return $label;
    }

    private function productColumn(string $descriptionLob): ?string
    {
        $description = OracleLrSalaryParser::normalizeLabel($descriptionLob);
        if (str_contains($description, 'kbg') || str_contains($description, 'suretyship')) return 'KBG/SURETYSHIP';
        if (str_contains($description, 'konsumtif')) return 'KONSUMTIF';
        if (str_contains($description, 'produktif')) return 'PRODUKTIF';
        return null;
    }

    private function signed(string $amount, string $mode): string
    {
        return $mode === 'invert' ? LrMoney::negate($amount) : LrMoney::decimal($amount);
    }

    private function addValue(array &$values, string $label, string $column, string $amount): void
    {
        $key = $this->key($label);
        $values[$key][$column] = LrMoney::add($values[$key][$column] ?? '0.00', $amount);
    }

    private function key(string $label): string
    {
        return OracleLrSalaryParser::normalizeLabel($label);
    }
}
