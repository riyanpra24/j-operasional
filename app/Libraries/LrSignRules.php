<?php
namespace App\Libraries;

/** Explicit Kanwil-only accounting sign rules. Source values remain untouched. */
final class LrSignRules
{
    private const KANWIL_ONLY=['pendapatan jasa giro'=>true,'pendapatan lainnya'=>true,'laba sebelum pajak'=>true];
    private const ALL_SHEETS=['laba tahun berjalan'=>true,'jumlah laba komprehensif tahun berjalan'=>true];

    public static function isInverted(string $description,string $sheetName='KANWIL'): bool
    {
        $label=OracleLrSalaryParser::normalizeLabel($description);
        return isset(self::ALL_SHEETS[$label]) || (OracleLrSalaryParser::normalizeLabel($sheetName)==='kanwil' && isset(self::KANWIL_ONLY[$label]));
    }

    public static function calculationValue(string $description,string $sourceAmount,string $sheetName='KANWIL'): string
    {
        $value=LrMoney::decimal($sourceAmount);
        if (!self::isInverted($description,$sheetName) || !preg_match('/[1-9]/',$value)) return $value;
        return str_starts_with($value,'-') ? substr($value,1) : '-'.$value;
    }

    public static function isAllSheets(string $description): bool
    {
        return isset(self::ALL_SHEETS[OracleLrSalaryParser::normalizeLabel($description)]);
    }
}
