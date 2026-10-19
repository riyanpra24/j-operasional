<?php

namespace App\Libraries;

use DateTimeImmutable;
use DateTimeZone;

class AssetPeriod
{
    public static function months(?string $start, ?string $end): ?int
    {
        $from = self::date($start);
        $to = self::date($end);
        if ($from === null || $to === null || $to < $from) {
            return null;
        }
        $months = ((int) $to->format('Y') - (int) $from->format('Y')) * 12
            + (int) $to->format('n') - (int) $from->format('n');
        return max(0, $months - ((int) $to->format('j') < (int) $from->format('j') ? 1 : 0));
    }

    public static function remaining(?string $end, ?string $today = null): ?int
    {
        $to = self::date($end);
        $from = self::date($today ?? (new DateTimeImmutable('now', new DateTimeZone('Asia/Jakarta')))->format('Y-m-d'));
        if ($from === null || $to === null) { return null; }
        return $to <= $from ? 0 : self::months($from->format('Y-m-d'), $to->format('Y-m-d'));
    }

    public static function forRecord(array $record, ?string $today = null): array
    {
        $record['masa_manfaat'] = self::months($record['tanggal_perolehan'] ?? null, $record['tanggal_habis_manfaat'] ?? null);
        $record['sisa_masa_manfaat'] = self::remaining($record['tanggal_habis_manfaat'] ?? null, $today);
        return $record;
    }

    private static function date(?string $value): ?DateTimeImmutable
    {
        if ($value === null || $value === '') { return null; }
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value, new DateTimeZone('Asia/Jakarta'));
        return $date !== false && $date->format('Y-m-d') === $value ? $date : null;
    }
}
