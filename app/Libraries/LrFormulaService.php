<?php

namespace App\Libraries;

use RuntimeException;
use Throwable;

/** Administrator-managed, unit- and LOB-scoped formulas for Laba & Rugi realization rows. */
final class LrFormulaService
{
    public const TABLE = 'accounting_lr_formula_rules';
    public const SCOPES = ['all', 'kanwil', 'branch', 'surabaya', 'kediri', 'malang', 'madiun', 'banyuwangi'];
    public const COLUMN_SCOPES = ['all', 'kur', 'pen', 'non-kur', 'kbg-suretyship', 'konsumtif', 'produktif'];

    private ?array $rows = null;

    public static function scopeOptions(): array
    {
        return [
            'all' => 'Semua unit sumber',
            'kanwil' => 'Kanwil',
            'branch' => 'Seluruh cabang',
            'surabaya' => 'Surabaya',
            'kediri' => 'Kediri',
            'malang' => 'Malang',
            'madiun' => 'Madiun',
            'banyuwangi' => 'Banyuwangi',
        ];
    }

    public static function columnScopeOptions(): array
    {
        return [
            'all' => 'Semua kolom / LOB',
            'kur' => 'KUR',
            'pen' => 'PEN',
            'non-kur' => 'NON KUR',
            'kbg-suretyship' => 'KBG/Suretyship',
            'konsumtif' => 'Konsumtif',
            'produktif' => 'Produktif',
        ];
    }

    public static function columnScopeKey(string $column): ?string
    {
        return match (OracleLrSalaryParser::normalizeLabel($column)) {
            'kur' => 'kur',
            'pen' => 'pen',
            'non kur' => 'non-kur',
            'kbg/suretyship', 'kbg suretyship' => 'kbg-suretyship',
            'konsumtif' => 'konsumtif',
            'produktif' => 'produktif',
            default => null,
        };
    }

    /** @return array<int,array{unit_scope:string,target_label:string,priority:int,is_active:int,terms:array}> */
    public static function defaultRules(): array
    {
        return [
            self::rule('IMBAL JASA PENJAMINAN BERSIH', 100, [
                ['Imbal Jasa Penjaminan Bruto', 1],
                ['Restitusi penjaminan kredit', -1],
                ['Premi Penjaminan Ulang', -1],
            ]),
            self::rule('JUMLAH BEBAN KLAIM', 200, [
                ['Beban Klaim', 1],
                ['Kenaikan (Penurunan) Cadangan Klaim', 1],
                ['Pendapatan Subrogasi', -1],
                ['Beban Komisi Netto', -1],
            ]),
            self::rule('PENJAMINAN BERSIH', 300, [
                ['IMBAL JASA PENJAMINAN BERSIH', 1],
                ['JUMLAH BEBAN KLAIM', -1],
            ]),
            self::rule('Total Beban Karyawan', 400, self::detailTerms('beban-karyawan')),
            self::rule('Total Beban Administrasi & Umum', 500, self::detailTerms('beban-administrasi-umum')),
            self::rule('Total Beban Penyusutan & Amortisasi', 600, self::detailTerms('beban-penyusutan-amortisasi')),
            self::rule('TOTAL BEBAN USAHA', 700, [
                ['Total Beban Karyawan', 1],
                ['Total Beban Administrasi & Umum', 1],
                ['Total Beban Penyusutan & Amortisasi', 1],
            ]),
            self::rule('PENDAPATAN (BEBAN) LAIN-LAIN BERSIH', 800, [
                ['Pendapatan jasa giro', 1],
                ['Pendapatan lainnya', 1],
            ]),
            self::rule('LABA SEBELUM PAJAK', 900, [
                ['PENJAMINAN BERSIH', 1],
                ['PENDAPATAN INVESTASI BERSIH', 1],
                ['TOTAL BEBAN USAHA', -1],
                ['PENDAPATAN (BEBAN) LAIN-LAIN BERSIH', 1],
            ]),
        ];
    }

    public function rules(bool $activeOnly = false): array
    {
        $rows = $this->loadRows();
        if ($activeOnly) {
            $rows = array_values(array_filter($rows, static fn (array $row): bool => (int) ($row['is_active'] ?? 0) === 1));
        }
        usort($rows, static fn (array $a, array $b): int => [
            (int) ($a['priority'] ?? 999), (int) ($a['id'] ?? 0),
        ] <=> [
            (int) ($b['priority'] ?? 999), (int) ($b['id'] ?? 0),
        ]);
        return $rows;
    }

    /** Return effective formulas for one source unit and one report column in dependency-safe order. */
    public function rulesForUnit(string $unit, string $columnScope = 'all'): array
    {
        $unitKey = OracleLrSalaryParser::normalizeLabel($unit);
        if (!in_array($unitKey, ['kanwil', 'surabaya', 'kediri', 'malang', 'madiun', 'banyuwangi'], true)) {
            throw new RuntimeException('Unit kerja untuk rumus tidak valid.');
        }
        $columnScope = strtolower(trim($columnScope));
        if (!in_array($columnScope, self::COLUMN_SCOPES, true)) throw new RuntimeException('Kolom/LOB rumus tidak valid.');
        $general = $unitKey === 'kanwil' ? 'kanwil' : 'branch';
        $selected = [];
        foreach ($this->rules(true) as $row) {
            $scope = (string) ($row['unit_scope'] ?? '');
            if (!in_array($scope, ['all', $general, $unitKey], true)) continue;
            $rowColumnScope = (string) ($row['column_scope'] ?? 'all');
            if ($columnScope === 'all' ? $rowColumnScope !== 'all' : !in_array($rowColumnScope, ['all', $columnScope], true)) continue;
            $targetKey = OracleLrSalaryParser::normalizeLabel((string) ($row['target_label'] ?? ''));
            if ($targetKey === '') continue;
            $unitSpecificity = $scope === $unitKey ? 2 : ($scope === $general ? 1 : 0);
            $columnSpecificity = $columnScope !== 'all' && $rowColumnScope === $columnScope ? 1 : 0;
            $specificity = ($unitSpecificity * 2) + $columnSpecificity;
            if (!isset($selected[$targetKey]) || $specificity > $selected[$targetKey]['specificity']) {
                $selected[$targetKey] = ['specificity' => $specificity, 'rule' => $row];
            }
        }
        $effective = array_map(static fn (array $item): array => $item['rule'], $selected);
        return $this->dependencyOrder($effective);
    }

    public function save(array $input, string $actor): void
    {
        $db = $this->database();
        $id = $this->id($input['id'] ?? null);
        $scope = strtolower(trim((string) ($input['unit_scope'] ?? '')));
        $columnScope = strtolower(trim((string) ($input['column_scope'] ?? 'all')));
        $target = $this->canonicalLabel((string) ($input['target_label'] ?? ''));
        $priorityText = trim((string) ($input['priority'] ?? ''));
        if (!in_array($scope, self::SCOPES, true)) throw new RuntimeException('Cakupan unit rumus tidak valid.');
        if (!in_array($columnScope, self::COLUMN_SCOPES, true)) throw new RuntimeException('Kolom/LOB rumus tidak valid.');
        if ($target === null) throw new RuntimeException('Baris hasil rumus tidak valid.');
        if (!preg_match('/^\d{1,3}$/D', $priorityText) || (int) $priorityText < 1 || (int) $priorityText > 999) {
            throw new RuntimeException('Urutan hitung harus antara 1 sampai 999.');
        }
        $terms = $this->parseFormulaLines((string) ($input['formula_lines'] ?? ''), $target);
        $targetNormalized = OracleLrSalaryParser::normalizeLabel($target);
        $duplicate = $db->table(self::TABLE)->select('id')->where('unit_scope', $scope)
            ->where('column_scope', $columnScope)
            ->where('target_label_normalized', $targetNormalized);
        if ($id !== null) $duplicate->where('id !=', $id);
        if ($duplicate->get()->getRowArray() !== null) {
            throw new RuntimeException('Baris hasil tersebut sudah memiliki rumus pada cakupan unit dan kolom/LOB yang sama.');
        }
        $payload = [
            'unit_scope' => $scope,
            'column_scope' => $columnScope,
            'target_label' => $target,
            'target_label_normalized' => $targetNormalized,
            'terms_json' => json_encode($terms, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR),
            'priority' => (int) $priorityText,
            'is_active' => ($input['is_active'] ?? null) === '1' ? 1 : 0,
            'updated_by_name' => mb_substr($actor, 0, 150),
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        $db->transBegin();
        try {
            if ($id === null) {
                $payload['created_at'] = date('Y-m-d H:i:s');
                if (!$db->table(self::TABLE)->insert($payload)) throw new RuntimeException('Rumus belum berhasil ditambahkan.');
            } else {
                if ($db->table(self::TABLE)->select('id')->where('id', $id)->get()->getRowArray() === null) {
                    throw new RuntimeException('Rumus tidak ditemukan.');
                }
                if (!$db->table(self::TABLE)->where('id', $id)->update($payload)) {
                    throw new RuntimeException('Rumus belum berhasil disimpan.');
                }
            }
            $this->rows = null;
            $this->assertAllGraphs();
            if (!$db->transStatus() || !$db->transCommit()) throw new RuntimeException('Rumus belum berhasil disimpan.');
        } catch (Throwable $exception) {
            $db->transRollback();
            $this->rows = null;
            throw $exception;
        }
    }

    public function delete(mixed $rawId): void
    {
        $db = $this->database();
        $id = $this->id($rawId);
        if ($id === null) throw new RuntimeException('Identitas rumus tidak valid.');
        $db->transBegin();
        try {
            if ($db->table(self::TABLE)->select('id')->where('id', $id)->get()->getRowArray() === null
                || !$db->table(self::TABLE)->where('id', $id)->delete()) {
                throw new RuntimeException('Rumus belum berhasil dihapus.');
            }
            $this->rows = null;
            $this->assertAllGraphs();
            if (!$db->transStatus() || !$db->transCommit()) throw new RuntimeException('Rumus belum berhasil dihapus.');
        } catch (Throwable $exception) {
            $db->transRollback();
            $this->rows = null;
            throw $exception;
        }
    }

    public function formulaLines(array $terms): string
    {
        return implode("\n", array_map(static fn (array $term): string =>
            ((int) ($term['coefficient'] ?? 0) === -1 ? '- ' : '+ ') . (string) ($term['label'] ?? ''), $terms));
    }

    /** @return array<int,array{label:string,coefficient:int}> */
    public function parseFormulaLines(string $text, ?string $target = null): array
    {
        if (mb_strlen($text) > 12000) throw new RuntimeException('Rumus terlalu panjang.');
        $labels = $this->labelMap();
        $terms = []; $seen = [];
        foreach (preg_split('/\R/u', $text) ?: [] as $index => $line) {
            $line = trim($line);
            if ($line === '') continue;
            if (!preg_match('/^([+-])\s*(.+)$/u', $line, $match)) {
                throw new RuntimeException('Baris rumus ke-' . ($index + 1) . ' harus diawali tanda + atau -.');
            }
            $key = OracleLrSalaryParser::normalizeLabel(trim($match[2]));
            if (!isset($labels[$key])) throw new RuntimeException('Komponen rumus tidak dikenal: ' . trim($match[2]) . '.');
            if (isset($seen[$key])) throw new RuntimeException('Komponen rumus tidak boleh digunakan dua kali: ' . $labels[$key] . '.');
            if ($target !== null && $key === OracleLrSalaryParser::normalizeLabel($target)) {
                throw new RuntimeException('Baris hasil tidak boleh menghitung dirinya sendiri.');
            }
            $seen[$key] = true;
            $terms[] = ['label' => $labels[$key], 'coefficient' => $match[1] === '-' ? -1 : 1];
        }
        if (!$terms) throw new RuntimeException('Tambahkan minimal satu komponen rumus.');
        if (count($terms) > 150) throw new RuntimeException('Rumus maksimal terdiri dari 150 komponen.');
        return $terms;
    }

    private function loadRows(): array
    {
        if ($this->rows !== null) return $this->rows;
        try {
            if (!function_exists('db_connect')) return $this->rows = self::defaultRules();
            $db = db_connect();
            if (!$db->tableExists(self::TABLE)) return $this->rows = self::defaultRules();
            $rows = $db->table(self::TABLE)->orderBy('priority', 'ASC')->orderBy('id', 'ASC')->get()->getResultArray();
            foreach ($rows as &$row) {
                $row['column_scope'] = (string) ($row['column_scope'] ?? 'all');
                $terms = json_decode((string) ($row['terms_json'] ?? '[]'), true);
                if (!is_array($terms)) throw new RuntimeException('Data komponen rumus tidak valid.');
                $row['terms'] = $terms;
            }
            unset($row);
            return $this->rows = $rows;
        } catch (RuntimeException $exception) {
            throw $exception;
        } catch (Throwable) {
            return $this->rows = self::defaultRules();
        }
    }

    private function dependencyOrder(array $rules): array
    {
        $byTarget = [];
        foreach ($rules as $rule) {
            $key = OracleLrSalaryParser::normalizeLabel((string) ($rule['target_label'] ?? ''));
            if ($key !== '') $byTarget[$key] = $rule;
        }
        uasort($byTarget, static fn (array $a, array $b): int => [
            (int) ($a['priority'] ?? 999), (string) ($a['target_label'] ?? ''),
        ] <=> [
            (int) ($b['priority'] ?? 999), (string) ($b['target_label'] ?? ''),
        ]);
        $visiting = []; $visited = []; $ordered = [];
        $visit = function (string $key) use (&$visit, &$visiting, &$visited, &$ordered, $byTarget): void {
            if (isset($visited[$key])) return;
            if (isset($visiting[$key])) throw new RuntimeException('Rumus membentuk siklus perhitungan pada ' . ($byTarget[$key]['target_label'] ?? $key) . '.');
            $visiting[$key] = true;
            foreach ($byTarget[$key]['terms'] ?? [] as $term) {
                $sourceKey = OracleLrSalaryParser::normalizeLabel((string) ($term['label'] ?? ''));
                if (isset($byTarget[$sourceKey])) $visit($sourceKey);
            }
            unset($visiting[$key]); $visited[$key] = true; $ordered[] = $byTarget[$key];
        };
        foreach (array_keys($byTarget) as $key) $visit($key);
        return $ordered;
    }

    private function assertAllGraphs(): void
    {
        foreach (['Kanwil', 'Surabaya', 'Kediri', 'Malang', 'Madiun', 'Banyuwangi'] as $unit) {
            foreach (self::COLUMN_SCOPES as $columnScope) $this->rulesForUnit($unit, $columnScope);
        }
    }

    private function canonicalLabel(string $label): ?string
    {
        return $this->labelMap()[OracleLrSalaryParser::normalizeLabel($label)] ?? null;
    }

    private function labelMap(): array
    {
        $map = [];
        foreach (LrReportRows::valueLabels() as $label) {
            $map[OracleLrSalaryParser::normalizeLabel($label)] = $label;
        }
        return $map;
    }

    private function database()
    {
        $db = db_connect();
        if (!$db->tableExists(self::TABLE)) throw new RuntimeException('Tabel Seting Rumus belum tersedia. Jalankan migrasi database.');
        return $db;
    }

    private function id(mixed $value): ?int
    {
        if ($value === null || $value === '') return null;
        if (!is_string($value) || !preg_match('/^[1-9]\d{0,18}$/D', $value)) throw new RuntimeException('Identitas rumus tidak valid.');
        return (int) $value;
    }

    private static function rule(string $target, int $priority, array $pairs): array
    {
        return [
            'unit_scope' => 'all', 'column_scope' => 'all', 'target_label' => $target, 'priority' => $priority, 'is_active' => 1,
            'terms' => array_map(static fn (array $pair): array => ['label' => $pair[0], 'coefficient' => $pair[1]], $pairs),
        ];
    }

    private static function detailTerms(string $groupKey): array
    {
        foreach (LrReportRows::rows() as $row) {
            if (($row['key'] ?? '') !== $groupKey) continue;
            $terms = [];
            foreach ($row['details'] ?? [] as $detail) {
                if (($detail['type'] ?? '') === 'detail') $terms[] = [(string) $detail['label'], 1];
            }
            return $terms;
        }
        throw new RuntimeException('Kelompok rincian rumus tidak ditemukan: ' . $groupKey);
    }
}
