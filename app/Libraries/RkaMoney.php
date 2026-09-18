<?php

namespace App\Libraries;

use InvalidArgumentException;

/** Financial amounts are decimal strings, never binary floating-point values. */
final class RkaMoney
{
    public static function decimal(string $value): string
    {
        $value = trim($value);
        if (! preg_match('/^([+-]?)(\d+)(?:\.(\d+))?(?:[eE]([+-]?\d+))?$/D', $value, $parts)) {
            throw new InvalidArgumentException('Nominal harus berupa angka yang valid.');
        }
        $exponent = (int) ($parts[4] ?? 0);
        if (abs($exponent) > 30) {
            throw new InvalidArgumentException('Nominal melampaui batas yang didukung.');
        }
        $fraction = $parts[3] ?? '';
        $digits = $parts[2] . $fraction;
        $point = strlen($parts[2]) + $exponent;
        if ($point < 0) {
            $digits = str_repeat('0', -$point) . $digits;
            $point = 0;
        }
        if ($point > strlen($digits)) {
            $digits .= str_repeat('0', $point - strlen($digits));
        }
        $whole = ltrim(substr($digits, 0, $point), '0') ?: '0';
        $fraction = substr($digits, $point);
        if (strlen($whole) > 22 || preg_match('/[1-9]/', substr($fraction, 2))) {
            throw new InvalidArgumentException('Nominal maksimal 22 digit dan 2 angka desimal; angka tidak dibulatkan otomatis.');
        }
        $fraction = str_pad(substr($fraction, 0, 2), 2, '0');
        return ($parts[1] === '-' && ($whole !== '0' || $fraction !== '00') ? '-' : '') . $whole . '.' . $fraction;
    }

    public static function localized(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '0.00';
        }
        if (preg_match('/^-?(?:\d+|\d{1,3}(?:\.\d{3})+)(?:,\d{1,2})?$/D', $value)) {
            return self::decimal(str_replace(',', '.', str_replace('.', '', $value)));
        }
        if (preg_match('/^-?\d+\.\d{1,2}$/D', $value)) {
            return self::decimal($value);
        }
        throw new InvalidArgumentException('Gunakan nominal seperti 1.234.567,89 (maksimal 2 angka desimal).');
    }

    public static function format(string $value): string
    {
        $value = self::decimal($value);
        [$whole, $fraction] = explode('.', $value);
        return preg_replace('/\B(?=(\d{3})+(?!\d))/', '.', $whole) . ',' . $fraction;
    }

    /** Display-only: retain nonzero cents; never change the stored decimal amount. */
    public static function display(string $value): string
    {
        return preg_replace('/,00$/D', '', self::format($value));
    }

    /** Report notation only. Inputs and stored values retain their minus sign. */
    public static function reportDisplay(string $value): string
    {
        $formatted = self::display($value);
        return str_starts_with($formatted, '-') ? '*' . substr($formatted, 1) : $formatted;
    }
}
