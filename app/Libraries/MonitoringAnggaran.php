<?php

namespace App\Libraries;

/** Monitoring groups follow the expense details of the RKA report. */
final class MonitoringAnggaran
{
    private const GROUPS = [
        'beban-karyawan' => ['BEBAN SDM', 'group_29'],
        'beban-administrasi-umum' => ['BEBAN ADMINISTRASI & UMUM', 'group_59'],
        'beban-penyusutan-amortisasi' => ['BEBAN PENYUSUTAN & AMORTISASI', 'group_145'],
    ];

    /** @param array<string, string>|null $values Calculated RKA cells, or null if no RKA exists. */
    public function rows(?array $values, string $search = '', array $realizationValues = []): array
    {
        $rkaSchema = RkaCalculator::schema();
        $schema = $rkaSchema['rows'];
        $result = [];
        foreach ($rkaSchema['groups'] as $group) {
            if (!isset(self::GROUPS[$group['key'] ?? ''])) continue;
            [$heading, $groupKey] = self::GROUPS[$group['key']];
            // Exclude the RKA subtotal: the monitoring heading already sums its details.
            $sourceRows = array_values(array_filter($group['details'], static fn ($row) => $schema[$row]['terms'] === null));
            $details = [];
            $total = $values === null ? null : '0.00';
            $realizationTotal = '0.00';
            $matchesGroup = $search !== '' && mb_stripos($heading, $search) !== false;
            foreach ($sourceRows as $sourceRow) {
                $label = $schema[$sourceRow]['label'];
                // This spelling differs between the RKA and the Laba/Rugi report.
                $realizationLabel = match ($sourceRow) {
                    57 => 'Imbal jasa profesional - konsultan manajemen',
                    71 => 'Transportasi dinas dalam negeri',
                    74 => 'Transportasi dinas luar negeri',
                    default => $label,
                };
                $realizationKey = OracleLrSalaryParser::normalizeLabel($realizationLabel);
                $realization = $realizationValues[$realizationKey]['TOTAL'] ?? null;
                if (! is_string($realization)) {
                    $realization = null;
                    $realizationTotal = null;
                } else {
                    $realization = LrMoney::decimal($realization);
                    if ($realizationTotal !== null) $realizationTotal = LrMoney::add($realizationTotal, $realization);
                }
                $amount = $values['H' . $sourceRow] ?? null;
                if (! is_string($amount)) {
                    $total = null;
                } else {
                    $amount = RkaMoney::decimal($amount);
                    if ($total !== null) $total = bcadd($total, $amount, 2);
                }
                if ($search === '' || $matchesGroup || mb_stripos($label, $search) !== false) {
                    $details[] = ['key' => 'row_' . $sourceRow, 'label' => $label, 'amount' => $amount, 'realization' => $realization, 'realization_ratio' => $this->realizationRatio($amount, $realization), 'remaining_budget' => $this->remainingBudget($amount, $realization), 'group' => false];
                }
            }
            if ($details !== []) {
                $result[] = ['key' => $groupKey, 'label' => $heading, 'amount' => $total, 'realization' => $realizationTotal, 'realization_ratio' => $this->realizationRatio($total, $realizationTotal), 'remaining_budget' => $this->remainingBudget($total, $realizationTotal), 'group' => true];
                array_push($result, ...$details);
            }
        }
        return $result;
    }

    /** Excel's IFERROR returns zero for a zero budget; unavailable source data stays unavailable. */
    private function realizationRatio(?string $budget, ?string $realization): ?string
    {
        if ($budget === null || $realization === null) return null;
        return LrMoney::divide($realization, $budget) ?? '0.00';
    }

    private function remainingBudget(?string $budget, ?string $realization): ?string
    {
        if ($budget === null || $realization === null) return null;
        return LrMoney::subtract($budget, $realization);
    }

    /** Deviation is the unrounded elapsed-year proportion minus the realization ratio. */
    public static function deviation(?string $realizationRatio, int $month): ?string
    {
        if ($realizationRatio === null || $month < 1 || $month > 12) return null;
        return LrMoney::subtract(LrMoney::divide((string) $month, '12'), $realizationRatio);
    }

    /**
     * Keeps deviation-matched details while retaining their group as context.
     * A group is included when it matches itself or owns at least one matching detail.
     *
     * @param list<array<string,mixed>> $rows
     * @return list<array<string,mixed>>
     */
    public static function filterDeviationRows(array $rows, int $month, string $filter): array
    {
        if ($filter === 'all') return $rows;
        if ($month < 1 || $month > 12 || ! in_array($filter, ['normal', 'negative'], true)) return [];

        $filtered = [];
        $group = null;
        $details = [];
        $matchesDeviation = static function (array $row) use ($month, $filter): bool {
            $deviation = self::deviation(is_string($row['realization_ratio'] ?? null) ? $row['realization_ratio'] : null, $month);
            if ($deviation === null) return false;
            $isNegative = str_starts_with(LrMoney::decimal($deviation), '-');
            return $filter === 'negative' ? $isNegative : ! $isNegative;
        };
        $appendGroup = static function () use (&$filtered, &$group, &$details, $matchesDeviation): void {
            if (! is_array($group)) return;
            if ($matchesDeviation($group) || $details !== []) {
                $filtered[] = $group;
                array_push($filtered, ...$details);
            }
        };

        foreach ($rows as $row) {
            if (($row['group'] ?? false) === true) {
                $appendGroup();
                $group = $row;
                $details = [];
                continue;
            }
            if ($matchesDeviation($row)) $details[] = $row;
        }
        $appendGroup();

        return $filtered;
    }

    /** @param list<array<string,mixed>> $rows @return list<array<string,mixed>> */
    public static function negativeDeviationRows(array $rows, int $month): array
    {
        return self::filterDeviationRows($rows, $month, 'negative');
    }
}
