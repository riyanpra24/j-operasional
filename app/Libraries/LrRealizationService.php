<?php

namespace App\Libraries;

use App\Models\AccountingLrImportModel;

/** Loads the latest YTD source per unit and calculates one consistent report view. */
final class LrRealizationService
{
    public function view(string $selectedUnit, int $year): array
    {
        $rows = (new AccountingLrImportModel())->where('report_year', $year)
            ->orderBy('report_month', 'DESC')->orderBy('id', 'DESC')->findAll();
        $latest = [];
        foreach ($rows as $row) {
            $unit = (string) $row['unit_name'];
            if (!in_array($unit, RkaCalculator::SOURCE_UNITS, true) || isset($latest[$unit])) continue;
            $latest[$unit] = $row;
        }

        $results = [];
        $periods = [];
        foreach ($latest as $unit => $row) {
            $results[$unit] = json_decode($row['result_json'], true, 512, JSON_THROW_ON_ERROR);
            $periods[$unit] = ['year' => (int) $row['report_year'], 'month' => (int) $row['report_month']];
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
        ];
    }
}
