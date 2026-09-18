<?php

namespace App\Libraries;

use RuntimeException;
use Throwable;

/** Period-bound overrides that recalculate a report detail from raw Oracle descriptions. */
final class LrSourceAdjustmentService
{
    public const TABLE = 'accounting_lr_source_adjustments';
    public const AUDIT_TABLE = 'accounting_lr_source_adjustment_audits';
    public const REQUEST_TABLE = 'accounting_lr_source_adjustment_requests';

    private ?array $rows = null;
    private ?array $knownDescriptions = null;

    public static function targetOptions(): array
    {
        $labels = [];
        foreach (LrReportRows::rows() as $row) {
            foreach ($row['details'] ?? [] as $detail) {
                if (($detail['type'] ?? '') === 'detail') $labels[] = (string) $detail['label'];
            }
        }
        return array_values(array_unique($labels));
    }

    public function rules(): array
    {
        if ($this->rows !== null) return $this->rows;
        $db = $this->database();
        $rows = $db->table(self::TABLE)->orderBy('effective_from', 'DESC')->orderBy('id', 'DESC')->get()->getResultArray();
        foreach ($rows as &$row) {
            $terms = json_decode((string) ($row['terms_json'] ?? '[]'), true);
            if (!is_array($terms)) throw new RuntimeException('Data komponen penyesuaian sumber tidak valid.');
            $row['terms'] = $terms;
        }
        unset($row);
        return $this->rows = $rows;
    }

    /** @return array<int,array<string,mixed>> */
    public function requests(int $limit = 50): array
    {
        $rows = $this->database()->table(self::REQUEST_TABLE)
            ->orderBy("CASE WHEN status = 'pending' THEN 0 ELSE 1 END", '', false)
            ->orderBy('id', 'DESC')->limit(max(1, min($limit, 100)))->get()->getResultArray();
        foreach ($rows as &$row) {
            try { $payload = json_decode((string) ($row['payload_json'] ?? '{}'), true, 64, JSON_THROW_ON_ERROR); }
            catch (Throwable) { $payload = []; }
            $row['payload'] = is_array($payload) ? $payload : [];
        }
        unset($row);
        return $rows;
    }

    public function formulaLines(array $terms): string
    {
        return implode("\n", array_map(static fn (array $term): string =>
            self::termOperator($term) . ' ' . (string) ($term['source_description'] ?? ''), $terms));
    }

    public function knownSourceDescriptions(): array
    {
        if ($this->knownDescriptions !== null) return $this->knownDescriptions;
        $descriptions = [];
        foreach (LrReportRows::branchFormulaSourceMappings() as $mapping) {
            $this->rememberDescription($descriptions, (string) ($mapping['source_description'] ?? ''));
        }
        $db = db_connect();
        if ($db->tableExists('accounting_lr_imports')) {
            $rows = $db->table('accounting_lr_imports')->select('result_json')->where('deleted_at', null)
                ->orderBy('id', 'DESC')->limit(30)->get()->getResultArray();
            foreach ($rows as $row) {
                try { $result = json_decode((string) $row['result_json'], true, 512, JSON_THROW_ON_ERROR); }
                catch (Throwable) { continue; }
                foreach (array_merge($result['matches'] ?? [], $result['unmapped'] ?? []) as $source) {
                    $this->rememberDescription($descriptions, (string) ($source['description'] ?? ''));
                }
            }
        }
        natcasesort($descriptions);
        return $this->knownDescriptions = array_values($descriptions);
    }

    public function save(array $input, string $actor): int
    {
        $db = $this->database();
        $id = $this->id($input['id'] ?? null);
        $payload = $this->validatedPayload($input, $id, $actor);
        $db->transBegin();
        try {
            if ($id === null) {
                $payload['created_at'] = date('Y-m-d H:i:s');
                if (!$db->table(self::TABLE)->insert($payload)) throw new RuntimeException('Penyesuaian sumber belum berhasil ditambahkan.');
                $id = (int) $db->insertID();
                $this->audit($id, 'create', $payload, $actor);
            } else {
                $old = $db->table(self::TABLE)->where('id', $id)->get()->getRowArray();
                if ($old === null) throw new RuntimeException('Penyesuaian sumber tidak ditemukan.');
                $this->audit($id, 'before_update', $old, $actor);
                if (!$db->table(self::TABLE)->where('id', $id)->update($payload)) throw new RuntimeException('Penyesuaian sumber belum berhasil disimpan.');
                $this->audit($id, 'after_update', $payload, $actor);
            }
            if (!$db->transStatus() || !$db->transCommit()) throw new RuntimeException('Penyesuaian sumber belum berhasil disimpan.');
            $this->rows = null;
        } catch (Throwable $exception) {
            $db->transRollback();
            throw $exception;
        }
        return $id;
    }

    public function deactivate(mixed $rawId, string $actor): void
    {
        $db = $this->database();
        $id = $this->id($rawId);
        if ($id === null) throw new RuntimeException('Identitas penyesuaian sumber tidak valid.');
        $row = $db->table(self::TABLE)->where('id', $id)->get()->getRowArray();
        if ($row === null) throw new RuntimeException('Penyesuaian sumber tidak ditemukan.');
        $db->transBegin();
        try {
            $this->audit($id, 'deactivate', $row, $actor);
            if (!$db->table(self::TABLE)->where('id', $id)->update([
                'is_active' => 0, 'updated_by_name' => mb_substr($actor, 0, 150), 'updated_at' => date('Y-m-d H:i:s'),
            ])) throw new RuntimeException('Penyesuaian sumber belum berhasil dinonaktifkan.');
            if (!$db->transStatus() || !$db->transCommit()) throw new RuntimeException('Penyesuaian sumber belum berhasil dinonaktifkan.');
            $this->rows = null;
        } catch (Throwable $exception) {
            $db->transRollback();
            throw $exception;
        }
    }

    public function deleteInactive(mixed $rawId, string $actor, ?int $approvedRequestId = null): void
    {
        $db = $this->database();
        $id = $this->id($rawId);
        if ($id === null) throw new RuntimeException('Identitas penyesuaian sumber tidak valid.');
        $current = $db->table(self::TABLE)->where('id', $id)->get()->getRowArray();
        if ($current === null) throw new RuntimeException('Penyesuaian sumber tidak ditemukan.');
        if ((int) ($current['is_active'] ?? 0) !== 0) throw new RuntimeException('Hanya penyesuaian yang sudah nonaktif yang dapat dihapus.');
        $db->transBegin();
        try {
            $row = $db->query('SELECT * FROM ' . self::TABLE . ' WHERE id = ? FOR UPDATE', [$id])->getRowArray();
            if ($row === null) throw new RuntimeException('Penyesuaian sumber tidak ditemukan.');
            if ((int) ($row['is_active'] ?? 0) !== 0) throw new RuntimeException('Hanya penyesuaian yang sudah nonaktif yang dapat dihapus.');
            $pending = $db->table(self::REQUEST_TABLE)->where('rule_id', $id)->where('status', 'pending');
            if ($approvedRequestId !== null) $pending->where('id !=', $approvedRequestId);
            if ($pending->countAllResults() > 0) throw new RuntimeException('Masih ada pengajuan untuk aturan ini yang menunggu persetujuan Administrator.');
            $this->audit($id, 'delete', $row, $actor);
            if (!$db->table(self::TABLE)->where('id', $id)->delete() || $db->affectedRows() !== 1) {
                throw new RuntimeException('Penyesuaian sumber belum berhasil dihapus.');
            }
            if (!$db->transStatus() || !$db->transCommit()) throw new RuntimeException('Penyesuaian sumber belum berhasil dihapus.');
            $this->rows = null;
        } catch (Throwable $exception) {
            $db->transRollback();
            throw $exception;
        }
    }

    public function requestSave(array $input, string $actor): int
    {
        $db = $this->database();
        $id = $this->id($input['id'] ?? null);
        $payload = $this->validatedPayload($input, $id, $actor);
        $current = null;
        if ($id !== null) {
            $current = $db->table(self::TABLE)->where('id', $id)->get()->getRowArray();
            if ($current === null) throw new RuntimeException('Penyesuaian sumber tidak ditemukan.');
        }
        $terms = json_decode((string) $payload['terms_json'], true, 64, JSON_THROW_ON_ERROR);
        $normalizedInput = [
            'id' => $id === null ? '' : (string) $id,
            'unit_scope' => (string) $payload['unit_scope'],
            'column_scope' => (string) $payload['column_scope'],
            'target_label' => (string) $payload['target_label'],
            'effective_from' => substr((string) $payload['effective_from'], 0, 7),
            'effective_to' => substr((string) $payload['effective_to'], 0, 7),
            'formula_lines' => $this->formulaLines(is_array($terms) ? $terms : []),
            'reason' => (string) $payload['reason'],
            'is_active' => (int) $payload['is_active'] === 1 ? '1' : '0',
        ];
        $requestPayload = ['input' => $normalizedInput, 'summary' => $this->requestSummary($normalizedInput)];
        $this->assertNoPendingRequest($id, $normalizedInput);
        if (!$db->table(self::REQUEST_TABLE)->insert([
            'action' => $id === null ? 'create' : 'update',
            'rule_id' => $id,
            'payload_json' => json_encode($requestPayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR),
            'base_updated_at' => $current['updated_at'] ?? null,
            'status' => 'pending',
            'requested_by_name' => mb_substr($actor, 0, 150),
            'requested_by_role' => 'akutansi',
            'requested_at' => date('Y-m-d H:i:s'),
        ])) throw new RuntimeException('Pengajuan penyesuaian sumber belum berhasil disimpan.');
        return (int) $db->insertID();
    }

    public function requestDeactivation(mixed $rawId, string $actor): int
    {
        $db = $this->database();
        $id = $this->id($rawId);
        if ($id === null) throw new RuntimeException('Identitas penyesuaian sumber tidak valid.');
        $row = $db->table(self::TABLE)->where('id', $id)->get()->getRowArray();
        if ($row === null) throw new RuntimeException('Penyesuaian sumber tidak ditemukan.');
        if ((int) ($row['is_active'] ?? 0) !== 1) throw new RuntimeException('Penyesuaian sumber tersebut sudah nonaktif.');
        $this->assertNoPendingRequest($id, null);
        $summary = [
            'unit_scope' => (string) $row['unit_scope'], 'column_scope' => (string) $row['column_scope'],
            'target_label' => (string) $row['target_label'],
            'effective_from' => substr((string) $row['effective_from'], 0, 7),
            'effective_to' => substr((string) $row['effective_to'], 0, 7),
        ];
        if (!$db->table(self::REQUEST_TABLE)->insert([
            'action' => 'deactivate', 'rule_id' => $id,
            'payload_json' => json_encode(['summary' => $summary], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR),
            'base_updated_at' => $row['updated_at'] ?? null, 'status' => 'pending',
            'requested_by_name' => mb_substr($actor, 0, 150), 'requested_by_role' => 'akutansi',
            'requested_at' => date('Y-m-d H:i:s'),
        ])) throw new RuntimeException('Pengajuan penonaktifan belum berhasil disimpan.');
        return (int) $db->insertID();
    }

    public function requestDeletion(mixed $rawId, string $actor): int
    {
        $db = $this->database();
        $id = $this->id($rawId);
        if ($id === null) throw new RuntimeException('Identitas penyesuaian sumber tidak valid.');
        $row = $db->table(self::TABLE)->where('id', $id)->get()->getRowArray();
        if ($row === null) throw new RuntimeException('Penyesuaian sumber tidak ditemukan.');
        if ((int) ($row['is_active'] ?? 0) !== 0) throw new RuntimeException('Hanya penyesuaian yang sudah nonaktif yang dapat dihapus.');
        $this->assertNoPendingRequest($id, null);
        $summary = [
            'unit_scope' => (string) $row['unit_scope'], 'column_scope' => (string) $row['column_scope'],
            'target_label' => (string) $row['target_label'],
            'effective_from' => substr((string) $row['effective_from'], 0, 7),
            'effective_to' => substr((string) $row['effective_to'], 0, 7),
        ];
        if (!$db->table(self::REQUEST_TABLE)->insert([
            'action' => 'delete', 'rule_id' => $id,
            'payload_json' => json_encode(['summary' => $summary], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR),
            'base_updated_at' => $row['updated_at'] ?? null, 'status' => 'pending',
            'requested_by_name' => mb_substr($actor, 0, 150), 'requested_by_role' => 'akutansi',
            'requested_at' => date('Y-m-d H:i:s'),
        ])) throw new RuntimeException('Pengajuan penghapusan belum berhasil disimpan.');
        return (int) $db->insertID();
    }

    public function approveRequest(mixed $rawId, string $reviewer): void
    {
        $db = $this->database();
        $id = $this->id($rawId);
        if ($id === null) throw new RuntimeException('Identitas pengajuan tidak valid.');
        $db->transBegin();
        try {
            $request = $db->table(self::REQUEST_TABLE)->where('id', $id)->get()->getRowArray();
            if ($request === null || (string) ($request['status'] ?? '') !== 'pending') throw new RuntimeException('Pengajuan tidak ditemukan atau sudah diperiksa.');
            $this->assertRequestStillCurrent($request);
            $payload = json_decode((string) $request['payload_json'], true, 64, JSON_THROW_ON_ERROR);
            $appliedRuleId = $request['rule_id'] === null ? null : (int) $request['rule_id'];
            if ((string) $request['action'] === 'deactivate') {
                $this->deactivate((string) $request['rule_id'], $reviewer);
            } elseif ((string) $request['action'] === 'delete') {
                $this->deleteInactive((string) $request['rule_id'], $reviewer, $id);
            } else {
                $input = is_array($payload['input'] ?? null) ? $payload['input'] : null;
                if ($input === null) throw new RuntimeException('Isi pengajuan tidak valid.');
                $appliedRuleId = $this->save($input, $reviewer);
            }
            if (!$db->table(self::REQUEST_TABLE)->where('id', $id)->where('status', 'pending')->update([
                'status' => 'approved', 'reviewed_by_name' => mb_substr($reviewer, 0, 150),
                'reviewed_at' => date('Y-m-d H:i:s'), 'review_note' => 'Disetujui Administrator', 'rule_id' => $appliedRuleId,
            ])) throw new RuntimeException('Status persetujuan belum berhasil disimpan.');
            if (!$db->transStatus() || !$db->transCommit()) throw new RuntimeException('Persetujuan belum berhasil diterapkan.');
        } catch (Throwable $exception) {
            $db->transRollback();
            throw $exception;
        }
    }

    public function rejectRequest(mixed $rawId, string $reviewer, string $note = ''): void
    {
        $db = $this->database();
        $id = $this->id($rawId);
        if ($id === null) throw new RuntimeException('Identitas pengajuan tidak valid.');
        $note = trim($note);
        if (mb_strlen($note) > 500) throw new RuntimeException('Catatan penolakan maksimal 500 karakter.');
        $updated = $db->table(self::REQUEST_TABLE)->where('id', $id)->where('status', 'pending')->update([
            'status' => 'rejected', 'reviewed_by_name' => mb_substr($reviewer, 0, 150),
            'reviewed_at' => date('Y-m-d H:i:s'), 'review_note' => $note !== '' ? $note : 'Ditolak Administrator',
        ]);
        if (!$updated || $db->affectedRows() !== 1) throw new RuntimeException('Pengajuan tidak ditemukan atau sudah diperiksa.');
    }

    /** @return array<string,array<string,string|null>> target-key => report-column => overridden value */
    public function calculateOverrides(array $result, string $unit, int $year, int $month): array
    {
        if ($month < 1 || $month > 12) return [];
        $overrides = [];
        foreach (array_merge(LrRealizationCalculator::SOURCE_COLUMNS, LrRealizationCalculator::PRODUCT_COLUMNS) as $column) {
            $columnScope = LrFormulaService::columnScopeKey($column);
            if ($columnScope === null) continue;
            foreach ($this->effectiveRules($unit, $columnScope, sprintf('%04d-%02d-01', $year, $month)) as $rule) {
                $present = false; $amount = null; $valid = true;
                $terms = []; $sourceAmounts = [];
                foreach ($rule['terms'] ?? [] as $term) {
                    $terms[OracleLrSalaryParser::normalizeLabel((string) ($term['source_description'] ?? ''))] = [
                        'operator' => self::termOperator((array) $term),
                        'source_description' => (string) ($term['source_description'] ?? ''),
                    ];
                }
                $seen = [];
                foreach (array_merge($result['matches'] ?? [], $result['unmapped'] ?? []) as $row) {
                    $rowId = (string) ($row['row'] ?? 'row-' . count($seen));
                    if (isset($seen[$rowId])) continue;
                    $seen[$rowId] = true;
                    if (!$this->rowMatchesColumn($row, $column)) continue;
                    $key = OracleLrSalaryParser::normalizeLabel((string) ($row['description'] ?? ''));
                    if (!isset($terms[$key])) continue;
                    $sourceAmounts[$key] = LrMoney::add($sourceAmounts[$key] ?? '0.00', (string) ($row['source_amount'] ?? '0'));
                }
                foreach ($terms as $key => $term) {
                    if (!array_key_exists($key, $sourceAmounts)) continue;
                    $raw = $sourceAmounts[$key]; $operator = (string) $term['operator'];
                    if ($amount === null) {
                        if (!in_array($operator, ['+', '-'], true)) { $valid = false; break; }
                        $amount = $operator === '-' ? LrMoney::negate($raw) : LrMoney::decimal($raw);
                    } else {
                        try {
                            $amount = match ($operator) {
                                '+' => LrMoney::add($amount, $raw),
                                '-' => LrMoney::subtract($amount, $raw),
                                '*' => LrMoney::multiply($amount, $raw),
                                '/' => LrMoney::divide($amount, $raw),
                                default => null,
                            };
                        } catch (Throwable $error) {
                            log_message('warning', 'Penyesuaian sumber Oracle dilewati karena operasi nominal tidak valid: {message}', ['message' => $error->getMessage()]);
                            $valid = false;
                            break;
                        }
                        if ($amount === null) { $valid = false; break; }
                    }
                    $present = true;
                }
                $targetKey = OracleLrSalaryParser::normalizeLabel((string) $rule['target_label']);
                if ($valid) $overrides[$targetKey][$column] = $present ? $amount : null;
            }
        }
        return $overrides;
    }

    private function effectiveRules(string $unit, string $columnScope, string $period): array
    {
        $unitKey = OracleLrSalaryParser::normalizeLabel($unit);
        $general = $unitKey === 'kanwil' ? 'kanwil' : 'branch';
        $selected = [];
        foreach ($this->rules() as $row) {
            if ((int) ($row['is_active'] ?? 0) !== 1 || $period < (string) $row['effective_from'] || $period > (string) $row['effective_to']) continue;
            $scope = (string) $row['unit_scope'];
            if (!in_array($scope, ['all', $general, $unitKey], true)) continue;
            $rowColumn = (string) $row['column_scope'];
            if (!in_array($rowColumn, ['all', $columnScope], true)) continue;
            $target = (string) $row['target_label_normalized'];
            $specificity = (($scope === $unitKey ? 2 : ($scope === $general ? 1 : 0)) * 2) + ($rowColumn === $columnScope ? 1 : 0);
            if (!isset($selected[$target]) || $specificity > $selected[$target]['specificity']) {
                $selected[$target] = ['specificity' => $specificity, 'rule' => $row];
            }
        }
        return array_values(array_map(static fn (array $item): array => $item['rule'], $selected));
    }

    private function validatedPayload(array $input, ?int $id, string $actor): array
    {
        $unitScope = strtolower(trim((string) ($input['unit_scope'] ?? '')));
        $columnScope = strtolower(trim((string) ($input['column_scope'] ?? '')));
        if (!in_array($unitScope, LrFormulaService::SCOPES, true)) throw new RuntimeException('Cakupan unit penyesuaian tidak valid.');
        if (!in_array($columnScope, LrFormulaService::COLUMN_SCOPES, true)) throw new RuntimeException('Kolom/LOB penyesuaian tidak valid.');
        $target = $this->canonicalTarget((string) ($input['target_label'] ?? ''));
        if ($target === null) throw new RuntimeException('Baris tujuan penyesuaian harus berupa baris rincian laporan.');
        $from = $this->month((string) ($input['effective_from'] ?? ''), 'Periode mulai');
        $to = $this->month((string) ($input['effective_to'] ?? ''), 'Periode selesai');
        if ($from > $to) throw new RuntimeException('Periode selesai tidak boleh lebih awal dari periode mulai.');
        $months = (((int) substr($to, 0, 4) - (int) substr($from, 0, 4)) * 12)
            + ((int) substr($to, 5, 2) - (int) substr($from, 5, 2)) + 1;
        if ($months > 24) throw new RuntimeException('Penyesuaian sementara maksimal berlaku selama 24 bulan.');
        $reason = trim((string) ($input['reason'] ?? ''));
        if (mb_strlen($reason) < 10 || mb_strlen($reason) > 500) throw new RuntimeException('Alasan penyesuaian harus berisi 10 sampai 500 karakter.');
        $terms = $this->parseFormulaLines((string) ($input['formula_lines'] ?? ''));
        $targetNormalized = OracleLrSalaryParser::normalizeLabel($target);
        $active = ($input['is_active'] ?? null) === '1' ? 1 : 0;
        if ($active === 1) $this->assertNoOverlap($unitScope, $columnScope, $targetNormalized, $from, $to, $id);
        return [
            'unit_scope' => $unitScope, 'column_scope' => $columnScope,
            'target_label' => $target, 'target_label_normalized' => $targetNormalized,
            'terms_json' => json_encode($terms, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR),
            'effective_from' => $from . '-01', 'effective_to' => $to . '-01',
            'reason' => $reason, 'is_active' => $active,
            'updated_by_name' => mb_substr($actor, 0, 150), 'updated_at' => date('Y-m-d H:i:s'),
        ];
    }

    private function requestSummary(array $input): array
    {
        return [
            'unit_scope' => (string) ($input['unit_scope'] ?? ''),
            'column_scope' => (string) ($input['column_scope'] ?? ''),
            'target_label' => (string) ($input['target_label'] ?? ''),
            'effective_from' => (string) ($input['effective_from'] ?? ''),
            'effective_to' => (string) ($input['effective_to'] ?? ''),
        ];
    }

    private function assertNoPendingRequest(?int $ruleId, ?array $input): void
    {
        foreach ($this->database()->table(self::REQUEST_TABLE)->where('status', 'pending')->get()->getResultArray() as $request) {
            if ($ruleId !== null && (int) ($request['rule_id'] ?? 0) === $ruleId) {
                throw new RuntimeException('Masih ada pengajuan untuk aturan ini yang menunggu persetujuan Administrator.');
            }
            if ($input === null || (string) ($request['action'] ?? '') === 'deactivate') continue;
            try { $payload = json_decode((string) $request['payload_json'], true, 64, JSON_THROW_ON_ERROR); }
            catch (Throwable) { continue; }
            $pending = $payload['input'] ?? null;
            if (!is_array($pending)) continue;
            if (($pending['unit_scope'] ?? null) !== ($input['unit_scope'] ?? null)
                || ($pending['column_scope'] ?? null) !== ($input['column_scope'] ?? null)
                || OracleLrSalaryParser::normalizeLabel((string) ($pending['target_label'] ?? ''))
                    !== OracleLrSalaryParser::normalizeLabel((string) ($input['target_label'] ?? ''))) continue;
            if ((string) ($pending['effective_from'] ?? '') <= (string) ($input['effective_to'] ?? '')
                && (string) ($pending['effective_to'] ?? '') >= (string) ($input['effective_from'] ?? '')) {
                throw new RuntimeException('Sudah ada pengajuan dengan unit, LOB, baris tujuan, dan periode yang bertumpang tindih.');
            }
        }
    }

    private function assertRequestStillCurrent(array $request): void
    {
        $ruleId = $request['rule_id'] ?? null;
        if ($ruleId === null || $ruleId === '') return;
        $current = $this->database()->table(self::TABLE)->where('id', (int) $ruleId)->get()->getRowArray();
        if ($current === null) throw new RuntimeException('Aturan sumber sudah tidak tersedia. Tolak pengajuan dan buat pengajuan baru.');
        if ((string) ($current['updated_at'] ?? '') !== (string) ($request['base_updated_at'] ?? '')) {
            throw new RuntimeException('Aturan sumber berubah setelah pengajuan dibuat. Tolak pengajuan ini dan minta pengajuan baru.');
        }
    }

    private function parseFormulaLines(string $text): array
    {
        if (mb_strlen($text) > 12000) throw new RuntimeException('Komponen penyesuaian terlalu panjang.');
        $known = [];
        foreach ($this->knownSourceDescriptions() as $description) $known[OracleLrSalaryParser::normalizeLabel($description)] = $description;
        $terms = []; $seen = [];
        foreach (preg_split('/\R/u', $text) ?: [] as $index => $line) {
            $line = trim($line);
            if ($line === '') continue;
            if (!preg_match('/^([+\-*\/×÷])\s*(.+)$/u', $line, $match)) throw new RuntimeException('Komponen sumber ke-' . ($index + 1) . ' harus diawali operator +, -, ×, atau ÷.');
            $key = OracleLrSalaryParser::normalizeLabel(trim($match[2]));
            if (!isset($known[$key])) throw new RuntimeException('Uraian Oracle tidak ditemukan pada mapping atau impor tersimpan: ' . trim($match[2]) . '.');
            if (isset($seen[$key])) throw new RuntimeException('Uraian Oracle tidak boleh digunakan dua kali: ' . $known[$key] . '.');
            $seen[$key] = true;
            $operator = match ($match[1]) { '×' => '*', '÷' => '/', default => $match[1] };
            if ($terms === [] && in_array($operator, ['*', '/'], true)) throw new RuntimeException('Uraian Oracle pertama harus menggunakan operator + atau -.');
            $terms[] = ['source_description' => $known[$key], 'operator' => $operator];
        }
        if (!$terms) throw new RuntimeException('Tambahkan minimal satu uraian Oracle.');
        if (count($terms) > 50) throw new RuntimeException('Penyesuaian maksimal terdiri dari 50 uraian Oracle.');
        return $terms;
    }

    private static function termOperator(array $term): string
    {
        $operator = (string) ($term['operator'] ?? '');
        if (in_array($operator, ['+', '-', '*', '/'], true)) return $operator;
        return (int) ($term['coefficient'] ?? 1) === -1 ? '-' : '+';
    }

    private function assertNoOverlap(string $unit, string $column, string $target, string $from, string $to, ?int $id): void
    {
        $query = $this->database()->table(self::TABLE)->select('id')->where('unit_scope', $unit)->where('column_scope', $column)
            ->where('target_label_normalized', $target)->where('is_active', 1)
            ->where('effective_from <=', $to . '-01')->where('effective_to >=', $from . '-01');
        if ($id !== null) $query->where('id !=', $id);
        if ($query->get()->getRowArray() !== null) throw new RuntimeException('Sudah ada penyesuaian aktif untuk unit, LOB, baris tujuan, dan periode yang bertumpang tindih.');
    }

    private function rowMatchesColumn(array $row, string $column): bool
    {
        $segment = OracleLrSalaryParser::columnForLob((string) ($row['lob'] ?? $row['source_lob'] ?? ''));
        if (in_array($column, LrRealizationCalculator::SOURCE_COLUMNS, true)) return $segment === $column;
        if ($segment !== 'NON KUR') return false;
        $lob = OracleLrSalaryParser::normalizeLabel((string) ($row['description_lob'] ?? ''));
        return match ($column) {
            'KBG/SURETYSHIP' => str_contains($lob, 'kbg') || str_contains($lob, 'surety'),
            'KONSUMTIF' => str_contains($lob, 'konsumtif'),
            'PRODUKTIF' => str_contains($lob, 'produktif'),
            default => false,
        };
    }

    private function canonicalTarget(string $label): ?string
    {
        $key = OracleLrSalaryParser::normalizeLabel($label);
        foreach (self::targetOptions() as $option) if (OracleLrSalaryParser::normalizeLabel($option) === $key) return $option;
        return null;
    }

    private function month(string $value, string $label): string
    {
        $value = trim($value);
        if (!preg_match('/^(20\d{2})-(0[1-9]|1[0-2])$/D', $value)) throw new RuntimeException($label . ' tidak valid.');
        return $value;
    }

    private function id(mixed $value): ?int
    {
        if ($value === null || $value === '') return null;
        if (!is_string($value) || !preg_match('/^[1-9]\d{0,18}$/D', $value)) throw new RuntimeException('Identitas penyesuaian sumber tidak valid.');
        return (int) $value;
    }

    private function audit(int $id, string $action, array $snapshot, string $actor): void
    {
        $this->database()->table(self::AUDIT_TABLE)->insert([
            'rule_id' => $id, 'action' => $action,
            'snapshot_json' => json_encode($snapshot, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR),
            'actor_name' => mb_substr($actor, 0, 150), 'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    private function rememberDescription(array &$descriptions, string $description): void
    {
        $description = trim((string) preg_replace('/[\s\x{00a0}]+/u', ' ', $description));
        if ($description === '') return;
        $descriptions[OracleLrSalaryParser::normalizeLabel($description)] = $description;
    }

    private function database()
    {
        $db = db_connect();
        if (!$db->tableExists(self::TABLE) || !$db->tableExists(self::AUDIT_TABLE) || !$db->tableExists(self::REQUEST_TABLE)) {
            throw new RuntimeException('Tabel Penyesuaian Sumber Oracle dan persetujuannya belum tersedia. Jalankan migrasi database.');
        }
        return $db;
    }
}
