<?php
namespace App\Libraries;
use InvalidArgumentException;

/** LR source decimals are exact strings. Rounding belongs only to presentation. */
final class LrMoney
{
    public static function decimal(string $raw): string
    {
        $raw=trim($raw);
        if (strlen($raw)>180 || !preg_match('/^([+-]?)(\d+)(?:\.(\d+))?(?:[eE]([+-]?\d+))?$/D',$raw,$parts)) throw new InvalidArgumentException('Nominal harus berupa angka yang valid.');
        $exponent=(int)($parts[4]??0);
        if (abs($exponent)>30) throw new InvalidArgumentException('Nominal melampaui batas yang didukung.');
        $fraction=$parts[3]??''; $digits=$parts[2].$fraction; $point=strlen($parts[2])+$exponent;
        if ($point<0) { $digits=str_repeat('0',-$point).$digits; $point=0; }
        if ($point>strlen($digits)) $digits.=str_repeat('0',$point-strlen($digits));
        $whole=ltrim(substr($digits,0,$point),'0')?:'0'; $fraction=substr($digits,$point);
        if (strlen($whole)>22 || strlen($fraction)>100) throw new InvalidArgumentException('Nominal maksimal 22 digit utama dan 100 angka desimal; angka tidak dipotong.');
        $fraction=str_pad($fraction,2,'0');
        $negative=$parts[1]==='-' && ($whole!=='0' || preg_match('/[1-9]/',$fraction));
        return ($negative?'-':'').$whole.'.'.$fraction;
    }

    public static function add(string $left,string $right): string
    {
        $left=self::decimal($left); $right=self::decimal($right);
        $scale=max(strlen(explode('.',$left)[1]),strlen(explode('.',$right)[1]));
        return self::decimal(bcadd($left,$right,$scale));
    }

    public static function subtract(string $left,string $right): string
    {
        $left=self::decimal($left); $right=self::decimal($right);
        $scale=max(strlen(explode('.',$left)[1]),strlen(explode('.',$right)[1]));
        return self::decimal(bcsub($left,$right,$scale));
    }

    public static function negate(string $raw): string
    {
        $value=self::decimal($raw);
        if (!preg_match('/[1-9]/',$value)) return $value;
        return str_starts_with($value,'-') ? substr($value,1) : '-'.$value;
    }

    /** Derived allocations retain 30 decimal places; source amounts are never rounded. */
    public static function multiply(string $left,string $right,int $scale=30): string
    {
        return self::decimal(bcmul(self::decimal($left),self::decimal($right),$scale));
    }

    public static function divide(string $numerator,string $denominator,int $scale=30): ?string
    {
        $numerator=self::decimal($numerator); $denominator=self::decimal($denominator);
        if (bccomp($denominator,'0',max(2,strlen(explode('.',$denominator)[1])))===0) return null;
        return self::decimal(bcdiv($numerator,$denominator,$scale));
    }

    public static function isZero(string $raw): bool
    {
        $value=self::decimal($raw);
        return bccomp($value,'0',max(2,strlen(explode('.',$value)[1])))===0;
    }

    public static function exactFormat(string $raw): string
    {
        [$whole,$fraction]=explode('.',self::decimal($raw));
        return preg_replace('/\B(?=(\d{3})+(?!\d))/', '.', $whole).','.$fraction;
    }

    /** Nearest whole Rupiah, halves away from zero. Never used in arithmetic. */
    public static function roundedFormat(string $raw): string
    {
        $value=self::decimal($raw); $negative=str_starts_with($value,'-');
        $whole=bcadd(ltrim($value,'-'),'0.5',0);
        return ($negative && $whole!=='0'?'-':'').preg_replace('/\B(?=(\d{3})+(?!\d))/', '.', $whole);
    }

    public static function reportDisplay(string $raw): string
    {
        $value=self::decimal($raw);
        return (str_starts_with($value,'-')?'*':'').self::roundedFormat(ltrim($value,'-'));
    }

    /** Ratio is stored as 1.00 for 100%; display follows the workbook's whole-percent view. */
    public static function percentageDisplay(string $ratio): string
    {
        $percent=self::multiply($ratio,'100',12);
        return self::reportDisplay($percent).'%';
    }
}
