<?php

namespace App\Libraries;

use RuntimeException;
use Throwable;

/** Configurable, auditable Oracle LOB and COA mappings used by LR imports. */
final class OracleLrMappingService
{
    public const TARGET_COLUMNS = ['KUR', 'NON KUR', 'PEN'];
    public const UNIT_SCOPES = ['all', 'kanwil', 'branch'];
    public const SIGN_MODES = ['keep', 'invert', 'invert_kanwil'];

    private const LOB_TABLE = 'accounting_lr_lob_mappings';
    private const ACCOUNT_TABLE = 'accounting_lr_account_mappings';

    public static function defaultLobMappings(): array
    {
        return [
            ['source_lob' => 'KUR', 'description_lob_contains' => null, 'target_column' => 'KUR', 'priority' => 10, 'is_active' => 1],
            ['source_lob' => 'NON KUR', 'description_lob_contains' => null, 'target_column' => 'NON KUR', 'priority' => 10, 'is_active' => 1],
            ['source_lob' => 'PEN', 'description_lob_contains' => null, 'target_column' => 'PEN', 'priority' => 10, 'is_active' => 1],
        ];
    }

    public static function defaultAccountMappings(): array
    {
        $branch = LrReportRows::branchSourceMap();
        $kanwil = LrReportRows::sourceMap();
        $rows = [];
        foreach ($branch as $source => $label) {
            $rows[] = self::defaultAccountRow($source, $label, 'all');
        }
        foreach (array_diff_key($kanwil, $branch) as $source => $label) {
            $rows[] = self::defaultAccountRow($source, $label, 'kanwil');
        }
        foreach (LrReportRows::branchFormulaSourceMappings() as $mapping) {
            $source = (string) $mapping['source_description'];
            $rows[] = [
                'unit_scope' => 'branch',
                'source_description' => $source,
                'source_description_normalized' => OracleLrSalaryParser::normalizeLabel($source),
                'report_label' => (string) $mapping['report_label'],
                'sign_mode' => (string) $mapping['sign_mode'],
                'is_active' => 1,
            ];
        }
        return $rows;
    }

    private static function defaultAccountRow(string $source, string $label, string $scope): array
    {
        $normalized = OracleLrSalaryParser::normalizeLabel($source);
        $displaySource = $normalized === OracleLrSalaryParser::normalizeLabel($label) ? $label : $source;
        return [
            'unit_scope' => $scope,
            'source_description' => $displaySource,
            'source_description_normalized' => $normalized,
            'report_label' => $label,
            'sign_mode' => in_array(OracleLrSalaryParser::normalizeLabel($label), ['pendapatan jasa giro', 'pendapatan lainnya'], true)
                ? 'invert' : 'keep',
            'is_active' => 1,
        ];
    }

    public function lobMappings(bool $activeOnly = true): array
    {
        $rows = $this->tableRows(self::LOB_TABLE, self::defaultLobMappings());
        if ($activeOnly) $rows = array_values(array_filter($rows, static fn (array $row): bool => (int) ($row['is_active'] ?? 0) === 1));
        usort($rows, static fn (array $a, array $b): int => [(int) ($a['priority'] ?? 999), (int) ($a['id'] ?? 0)] <=> [(int) ($b['priority'] ?? 999), (int) ($b['id'] ?? 0)]);
        return $rows;
    }

    public function accountMappings(bool $activeOnly = true): array
    {
        $rows = $this->tableRows(self::ACCOUNT_TABLE, self::defaultAccountMappings());
        if ($activeOnly) $rows = array_values(array_filter($rows, static fn (array $row): bool => (int) ($row['is_active'] ?? 0) === 1));
        usort($rows, static fn (array $a, array $b): int => [(string) ($a['report_label'] ?? ''), (int) ($a['id'] ?? 0)] <=> [(string) ($b['report_label'] ?? ''), (int) ($b['id'] ?? 0)]);
        return $rows;
    }

    public function accountMap(string $unit): array
    {
        $wantedScope = OracleLrSalaryParser::normalizeLabel($unit) === 'kanwil' ? 'kanwil' : 'branch';
        $map = [];
        $rows = $this->accountMappings();
        usort($rows, static function (array $a, array $b) use ($wantedScope): int {
            $aSpecific = ($a['unit_scope'] ?? '') === $wantedScope ? 1 : 0;
            $bSpecific = ($b['unit_scope'] ?? '') === $wantedScope ? 1 : 0;
            return [$aSpecific, (int) ($a['id'] ?? 0)] <=> [$bSpecific, (int) ($b['id'] ?? 0)];
        });
        foreach ($rows as $row) {
            if (!in_array($row['unit_scope'] ?? '', ['all', $wantedScope], true)) continue;
            $key = OracleLrSalaryParser::normalizeLabel((string) ($row['source_description_normalized'] ?? $row['source_description'] ?? ''));
            if ($key === '') continue;
            $map[$key] = [
                'id' => isset($row['id']) ? (int) $row['id'] : null,
                'source_description' => (string) ($row['source_description'] ?? $key),
                'report_label' => (string) ($row['report_label'] ?? ''),
                'sign_mode' => (string) ($row['sign_mode'] ?? 'keep'),
                'unit_scope' => (string) ($row['unit_scope'] ?? 'all'),
            ];
        }
        return $map;
    }

    public function reportLabels(string $unit): array
    {
        return array_values(array_unique(array_column($this->accountMap($unit), 'report_label')));
    }

    public function classify(string $sourceLob, string $descriptionLob): ?array
    {
        $source = OracleLrSalaryParser::normalizeLabel($sourceLob);
        foreach ($this->lobMappings() as $row) {
            if ($source !== OracleLrSalaryParser::normalizeLabel((string) ($row['source_lob'] ?? ''))) continue;
            return [
                'id' => isset($row['id']) ? (int) $row['id'] : null,
                'target_column' => (string) $row['target_column'],
                'priority' => (int) ($row['priority'] ?? 999),
            ];
        }
        return null;
    }

    public function calculationValue(string $mode, string $sourceAmount, string $sheetName): string
    {
        $value = LrMoney::decimal($sourceAmount);
        $invert = $mode === 'invert'
            || ($mode === 'invert_kanwil' && OracleLrSalaryParser::normalizeLabel($sheetName) === 'kanwil');
        if (!$invert || !preg_match('/[1-9]/', $value)) return $value;
        return str_starts_with($value, '-') ? substr($value, 1) : '-' . $value;
    }

    public function version(): string
    {
        return substr(hash('sha256', json_encode([
            'lob' => $this->lobMappings(), 'account' => $this->accountMappings(),
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)), 0, 16);
    }

    public function saveAccount(array $input, string $actor): void
    {
        $db = $this->database();
        $id = $this->id($input['id'] ?? null);
        $scope = strtolower(trim((string) ($input['unit_scope'] ?? '')));
        $source = trim((string) ($input['source_description'] ?? ''));
        $label = trim((string) ($input['report_label'] ?? ''));
        $mode = strtolower(trim((string) ($input['sign_mode'] ?? '')));
        if (!in_array($scope, self::UNIT_SCOPES, true)) throw new RuntimeException('Cakupan unit tidak valid.');
        if ($source === '' || mb_strlen($source) > 255) throw new RuntimeException('Description COA sumber wajib diisi, maksimal 255 karakter.');
        if (!in_array($label, LrReportRows::mappableLabels(), true)) throw new RuntimeException('Uraian tujuan tidak termasuk rincian Laba & Rugi.');
        if (!in_array($mode, self::SIGN_MODES, true)) throw new RuntimeException('Perlakuan tanda nominal tidak valid.');
        $normalized = OracleLrSalaryParser::normalizeLabel($source);
        $duplicate = $db->table(self::ACCOUNT_TABLE)->select('id')->where('unit_scope', $scope)
            ->where('source_description_normalized', $normalized);
        if ($id !== null) $duplicate->where('id !=', $id);
        if ($duplicate->get()->getRowArray() !== null) throw new RuntimeException('Description COA dengan cakupan tersebut sudah memiliki mapping.');
        $payload = [
            'unit_scope' => $scope,
            'source_description' => $source,
            'source_description_normalized' => $normalized,
            'report_label' => $label,
            'sign_mode' => $mode,
            'is_active' => ($input['is_active'] ?? null) === '1' ? 1 : 0,
            'updated_by_name' => mb_substr($actor, 0, 150),
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        if ($id === null) {
            $payload['created_at'] = date('Y-m-d H:i:s');
            if (!$db->table(self::ACCOUNT_TABLE)->insert($payload)) throw new RuntimeException('Mapping COA belum berhasil ditambahkan.');
            return;
        }
        if ($db->table(self::ACCOUNT_TABLE)->select('id')->where('id',$id)->get()->getRowArray()===null) throw new RuntimeException('Mapping COA tidak ditemukan.');
        if (!$db->table(self::ACCOUNT_TABLE)->where('id', $id)->update($payload)) throw new RuntimeException('Mapping COA belum berhasil disimpan.');
    }

    public function deleteAccount(mixed $rawId): void
    {
        $id = $this->id($rawId);
        $db=$this->database();
        if ($id === null || $db->table(self::ACCOUNT_TABLE)->select('id')->where('id',$id)->get()->getRowArray()===null
            || !$db->table(self::ACCOUNT_TABLE)->where('id', $id)->delete()) throw new RuntimeException('Mapping COA belum berhasil dihapus.');
    }

    private function tableRows(string $table, array $fallback): array
    {
        try {
            $db = db_connect();
            if (!$db->tableExists($table)) return $fallback;
            return $db->table($table)->orderBy('id', 'ASC')->get()->getResultArray();
        } catch (Throwable) {
            return $fallback;
        }
    }

    private function database()
    {
        $db = db_connect();
        if (!$db->tableExists(self::LOB_TABLE) || !$db->tableExists(self::ACCOUNT_TABLE)) {
            throw new RuntimeException('Tabel mapping Oracle belum tersedia. Jalankan migrasi database.');
        }
        return $db;
    }

    private function id(mixed $value): ?int
    {
        if ($value === null || $value === '') return null;
        if (!is_string($value) || !preg_match('/^[1-9]\d{0,18}$/D', $value)) throw new RuntimeException('Identitas mapping tidak valid.');
        return (int) $value;
    }
}
