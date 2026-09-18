<?php

namespace App\Libraries;

use InvalidArgumentException;
use RuntimeException;

final class RkaCalculator
{
    public const UNITS = ['Korporat Kanwil', 'Kanwil', 'Surabaya', 'Kediri', 'Malang', 'Madiun', 'Banyuwangi'];
    public const SOURCE_UNITS = ['Kanwil', 'Surabaya', 'Kediri', 'Malang', 'Madiun', 'Banyuwangi'];

    /** Consolidate inputs, never include the corporate result in its own sources. */
    public function consolidate(array $units): array
    {
        $inputs = $this->zeros();
        foreach ($units as $unit => $values) {
            if (!in_array($unit, self::SOURCE_UNITS, true)) {
                throw new InvalidArgumentException('Sumber konsolidasi RKA tidak valid.');
            }
            foreach ($this->normalizeInputs($values) as $cell => $amount) {
                $inputs[$cell] = RkaMoney::decimal(bcadd($inputs[$cell], $amount, 2));
            }
        }
        return $inputs;
    }

    public static function schema(): array
    {
        static $schema;
        return $schema ??= json_decode(file_get_contents(__DIR__ . '/../Config/RkaTemplate2026.json'), true, 512, JSON_THROW_ON_ERROR);
    }

    public function normalizeInputs(array $inputs, bool $localized = false): array
    {
        $normalized = [];
        foreach (self::schema()['input_rows'] as $row) {
            foreach (['C', 'D', 'E', 'F', 'G'] as $column) {
                $cell = $column . $row;
                if (! array_key_exists($cell, $inputs) || ! is_string($inputs[$cell])) {
                    throw new InvalidArgumentException('Nominal tidak lengkap atau tidak valid pada ' . $cell . '.');
                }
                try {
                    $normalized[$cell] = $localized ? RkaMoney::localized($inputs[$cell]) : RkaMoney::decimal($inputs[$cell]);
                } catch (InvalidArgumentException $exception) {
                    throw new InvalidArgumentException($cell . ': ' . $exception->getMessage());
                }
            }
        }
        if (count($normalized) !== count($inputs)) {
            throw new InvalidArgumentException('Terdapat kolom yang bukan isian Template RKA.');
        }
        return $normalized;
    }

    public function calculate(array $inputs): array
    {
        if (! extension_loaded('bcmath')) {
            throw new RuntimeException('Ekstensi BCMath diperlukan untuk perhitungan nominal RKA yang presisi.');
        }
        $inputs = $this->normalizeInputs($inputs);
        $result = [];
        foreach (self::schema()['rows'] as $row => $definition) {
            foreach (['C', 'D', 'E', 'F', 'G'] as $column) {
                $cell = $column . $row;
                if ($definition['terms'] === null) {
                    $result[$cell] = $inputs[$cell];
                    continue;
                }
                $amount = '0.00';
                foreach ($definition['terms'] as $term) {
                    $reference = $column . $term['row'];
                    if (! isset($result[$reference])) {
                        throw new RuntimeException('Referensi rumus tidak tersedia: ' . $reference);
                    }
                    $amount = $term['coefficient'] === 1
                        ? bcadd($amount, $result[$reference], 2)
                        : bcsub($amount, $result[$reference], 2);
                }
                $result[$cell] = RkaMoney::decimal($amount);
            }
            $total = '0.00';
            foreach (['C', 'D', 'E', 'F', 'G'] as $column) {
                $total = bcadd($total, $result[$column . $row], 2);
            }
            $result['H' . $row] = RkaMoney::decimal($total);
        }
        return $result;
    }

    public function zeros(): array
    {
        $inputs = [];
        foreach (self::schema()['input_rows'] as $row) {
            foreach (['C', 'D', 'E', 'F', 'G'] as $column) {
                $inputs[$column . $row] = '0.00';
            }
        }
        return $inputs;
    }
}
