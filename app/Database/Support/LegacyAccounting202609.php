<?php

namespace App\Database\Support;

/**
 * Frozen migration dependencies from 62c7a2b7^ (September 2026).
 * Migration-only: deliberately provides no runtime service, routes or CRUD API.
 */
final class LegacyAccounting202609
{
    public const TABLE = 'accounting_lr_source_adjustments';
    public const AUDIT_TABLE = 'accounting_lr_source_adjustment_audits';
    public const REQUEST_TABLE = 'accounting_lr_source_adjustment_requests';

    public static function normalizeLabel(string $text): string
    {
        return mb_strtolower(trim(preg_replace('/[\s\x{00a0}]+/u', ' ', $text)));
    }

    public static function defaultLobMappings(): array
    {
        return self::snapshot()['lob'];
    }

    public static function defaultAccountMappings(): array
    {
        return self::snapshot()['accounts'];
    }

    public static function branchFormulaSourceMappings(): array
    {
        return self::snapshot()['branch_formula'];
    }

    private static function snapshot(): array
    {
        static $snapshot;
        return $snapshot ??= json_decode(
            file_get_contents(__DIR__ . '/legacy_accounting_202609.json'),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );
    }
}
