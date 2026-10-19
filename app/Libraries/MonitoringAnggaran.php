<?php

namespace App\Libraries;

/** The row selection and order follow the KONSOL monitoring worksheet. */
final class MonitoringAnggaran
{
    private const GROUPS = [
        'BEBAN SDM' => [29, 30, 31, 34, 42, 43, 44, 45, 48, 50, 51],
        'BEBAN ADMINISTRASI & UMUM' => [59, 60, 61, 62, 70, 71, 72, 76, 77, 78, 79, 80, 86, 87, 88, 89, 90, 97, 98, 99, 101, 102, 104, 109, 110, 111, 117, 122, 124, 125, 128, 129, 130, 131, 133, 135, 137],
        'BEBAN PENYUSUTAN & AMORTISASI' => [145, 146, 147, 148, 149, 152, 154, 155, 156, 158],
    ];

    /** @param array<string, string>|null $values Calculated RKA cells, or null if no RKA exists. */
    public function rows(?array $values, string $search = '', array $realizationValues = []): array
    {
        $schema = RkaCalculator::schema()['rows'];
        $result = [];
        foreach (self::GROUPS as $heading => $sourceRows) {
            $details = [];
            $total = $values === null ? null : '0.00';
            $realizationTotal = '0.00';
            $matchesGroup = $search !== '' && mb_stripos($heading, $search) !== false;
            foreach ($sourceRows as $sourceRow) {
                $label = $schema[$sourceRow]['label'];
                // This spelling differs between the RKA and the Laba/Rugi report.
                $realizationLabel = $sourceRow === 71 ? 'Transportasi dinas dalam negeri' : $label;
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
                    $details[] = ['label' => $label, 'amount' => $amount, 'realization' => $realization, 'realization_ratio' => $this->realizationRatio($amount, $realization), 'group' => false];
                }
            }
            if ($details !== []) {
                $result[] = ['label' => $heading, 'amount' => $total, 'realization' => $realizationTotal, 'realization_ratio' => $this->realizationRatio($total, $realizationTotal), 'group' => true];
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

    /** Deviation is the unrounded elapsed-year proportion minus the realization ratio. */
    public static function deviation(?string $realizationRatio, int $month): ?string
    {
        if ($realizationRatio === null || $month < 1 || $month > 12) return null;
        return LrMoney::subtract(LrMoney::divide((string) $month, '12'), $realizationRatio);
    }
}
