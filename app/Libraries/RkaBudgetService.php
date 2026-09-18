<?php

namespace App\Libraries;

use App\Models\AccountingRkaBudgetModel;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

final class RkaBudgetService
{
    public function find(string $unit, int $year): ?array
    {
        $record = $this->findStored($unit, $year);
        if ($unit !== 'Korporat Kanwil') return $record;
        // Read the six sources in one query: the aggregate always reflects current
        // active data, including edits, deletion and administrator restoration.
        $sources = (new AccountingRkaBudgetModel())->where('budget_year', $year)
            ->whereIn('unit_name', RkaCalculator::SOURCE_UNITS)->findAll();
        if (!$sources && $record === null) return null;
        $inputs = []; $updatedAt = '';
        foreach ($sources as $source) {
            if ($source['template_version'] !== RkaCalculator::schema()['version']) {
                throw new RuntimeException('Versi RKA unit sumber berbeda dengan template aktif.');
            }
            $inputs[$source['unit_name']] = json_decode($source['inputs_json'], true, 512, JSON_THROW_ON_ERROR);
            $updatedAt = max($updatedAt, $source['updated_at']);
        }
        $calculator = new RkaCalculator();
        $values = $calculator->consolidate($inputs);
        $record ??= ['id' => 0, 'revision' => 0, 'unit_name' => $unit, 'budget_year' => $year,
            'template_version' => RkaCalculator::schema()['version']];
        $record['inputs_json'] = json_encode($values, JSON_THROW_ON_ERROR);
        $record['calculated_json'] = json_encode($calculator->calculate($values), JSON_THROW_ON_ERROR);
        $record['source_type'] = 'consolidated';
        $record['updated_at'] = $updatedAt ?: ($record['updated_at'] ?? '');
        return $record;
    }

    /** Actual persisted identity for optimistic concurrency, not a derived view. */
    public function findStored(string $unit, int $year): ?array
    {
        $this->validateSelection($unit, $year);
        return (new AccountingRkaBudgetModel())->where('unit_name', $unit)->where('budget_year', $year)->first();
    }

    public function revisions(): array
    {
        $revisions = [];
        foreach ((new AccountingRkaBudgetModel())->select('unit_name,budget_year,revision')->findAll() as $record) {
            $revisions[$record['unit_name'] . ':' . $record['budget_year']] = (int) $record['revision'];
        }
        return $revisions;
    }

    public function validateSelection(string $unit, int $year): void
    {
        if (! in_array($unit, RkaCalculator::UNITS, true) || $year < 2000 || $year > 2100) {
            throw new InvalidArgumentException('Pilih unit kerja dan tahun RKA yang valid (2000–2100).');
        }
    }

    public function save(string $unit, int $year, array $inputs, int $revision, string $sourceType, ?string $sourceName = null, ?string $sourceHash = null, ?int $expectedId = null): void
    {
        $this->validateSelection($unit, $year);
        $calculator = new RkaCalculator();
        if ($unit === 'Korporat Kanwil' && $sourceType !== 'consolidated') {
            throw new RuntimeException('Korporat Kanwil dihitung otomatis. Ubah RKA pada enam unit sumbernya.');
        }
        $inputs = $calculator->normalizeInputs($inputs);
        $calculated = $calculator->calculate($inputs);
        $db = db_connect();
        $db->transBegin();
        try {
            $existing = $db->query('SELECT id,revision FROM accounting_rka_budgets WHERE unit_name = ? AND budget_year = ? AND deleted_at IS NULL FOR UPDATE', [$unit, $year])->getRowArray();
            if ($expectedId !== null && ($existing === null || (int) $existing['id'] !== $expectedId)) {
                throw new RuntimeException('RKA yang akan diedit sudah dihapus atau diganti. Muat ulang halaman sebelum menyimpan.');
            }
            if (($existing !== null ? (int) $existing['revision'] : 0) !== $revision) {
                throw new RuntimeException('RKA sudah diubah oleh pengguna lain. Muat ulang halaman dan periksa data terbaru sebelum menyimpan.');
            }
            $now = date('Y-m-d H:i:s');
            $nextRevision = $revision + 1;
            if ($existing === null) {
                $last = $db->query('SELECT revision FROM accounting_rka_budgets WHERE unit_name = ? AND budget_year = ? ORDER BY revision DESC LIMIT 1 FOR UPDATE', [$unit, $year])->getRowArray();
                $nextRevision = ($last !== null ? (int) $last['revision'] : 0) + 1;
            }
            $data = [
                'unit_name' => $unit, 'budget_year' => $year,
                'template_version' => RkaCalculator::schema()['version'],
                'inputs_json' => json_encode($inputs, JSON_THROW_ON_ERROR),
                'calculated_json' => json_encode($calculated, JSON_THROW_ON_ERROR),
                'source_type' => $sourceType, 'source_name' => $sourceName, 'source_hash' => $sourceHash,
                'revision' => $nextRevision,
                'updated_by' => session()->get('auth_user_id') ?: null,
                'updated_by_name' => session()->get('auth_display_name') ?: null,
                'updated_at' => $now,
            ];
            $builder = $db->table('accounting_rka_budgets');
            $ok = $existing === null ? $builder->insert($data + ['created_at' => $now]) : $builder->where('id', $existing['id'])->update($data);
            if (! $ok || ! $db->transStatus()) {
                throw new RuntimeException('RKA belum berhasil disimpan. Tidak ada perubahan yang diterapkan.');
            }
            if (! $db->transCommit()) {
                throw new RuntimeException('Transaksi penyimpanan RKA gagal. Silakan muat ulang halaman.');
            }
        } catch (Throwable $exception) {
            $db->transRollback();
            throw $exception;
        }
    }

    /** One transaction: a stale/deleted/replaced unit rejects the entire upload. */
    public function saveAll(int $year, array $budgets, array $snapshots, bool $replaceConfirmed, ?string $sourceName, ?string $sourceHash): void
    {
        $expected = RkaCalculator::UNITS;
        if (count($budgets) !== count($expected) || count($snapshots) !== count($expected)
            || array_diff($expected, array_keys($budgets)) || array_diff($expected, array_keys($snapshots))) {
            throw new RuntimeException('Data upload harus mencakup seluruh unit kerja. Tidak ada RKA yang disimpan.');
        }
        $db = db_connect();
        $db->transBegin();
        try {
            $sources = [];
            foreach (RkaCalculator::SOURCE_UNITS as $source) $sources[$source] = $budgets[$source]['inputs'];
            $budgets['Korporat Kanwil']['inputs'] = (new RkaCalculator())->consolidate($sources);
            foreach ($expected as $unit) {
                $this->validateSelection($unit, $year);
                $snapshot = $snapshots[$unit];
                if (!is_array($snapshot) || !isset($snapshot['revision']) || !is_int($snapshot['revision'])
                    || $snapshot['revision'] < 0 || $snapshot['revision'] > 999999999
                    || !array_key_exists('id', $snapshot)
                    || ($snapshot['id'] !== null && (!is_int($snapshot['id']) || $snapshot['id'] <= 0))) {
                    throw new RuntimeException('Versi RKA seluruh unit tidak valid. Klik Next untuk memeriksa kembali.');
                }
                $current = $db->query('SELECT id,revision FROM accounting_rka_budgets WHERE unit_name = ? AND budget_year = ? AND deleted_at IS NULL FOR UPDATE', [$unit, $year])->getRowArray();
                if (($current !== null ? (int) $current['id'] : null) !== $snapshot['id']
                    || ($current !== null ? (int) $current['revision'] : 0) !== $snapshot['revision']) {
                    throw new RuntimeException('RKA ' . $unit . ' sudah berubah. Klik Next dan periksa kembali. Tidak ada RKA yang disimpan.');
                }
                if ($current !== null && !$replaceConfirmed) {
                    throw new RuntimeException('RKA sudah diseting. Konfirmasi Seting Ulang sebelum mengganti RKA seluruh unit.');
                }
                $this->save($unit, $year, $budgets[$unit]['inputs'], $snapshot['revision'],
                    $unit === 'Korporat Kanwil' ? 'consolidated' : 'excel', $sourceName, $sourceHash, $snapshot['id']);
            }
            if (!$db->transStatus() || !$db->transCommit()) {
                throw new RuntimeException('Upload seluruh unit gagal disimpan. Tidak ada perubahan yang diterapkan.');
            }
        } catch (Throwable $exception) {
            $db->transRollback();
            throw $exception;
        }
    }

    public function delete(string $unit, int $year, int $revision, int $recordId): void
    {
        $this->validateSelection($unit, $year);
        if ($unit === 'Korporat Kanwil') throw new RuntimeException('Korporat Kanwil adalah hasil otomatis. Hapus atau ubah RKA unit sumbernya.');
        $db = db_connect();
        $db->transBegin();
        try {
            $record = $db->query('SELECT id,revision FROM accounting_rka_budgets WHERE unit_name = ? AND budget_year = ? AND deleted_at IS NULL FOR UPDATE', [$unit, $year])->getRowArray();
            if ($record === null) throw new RuntimeException('RKA tidak ditemukan atau sudah dihapus.');
            if ((int) $record['revision'] !== $revision || (int) $record['id'] !== $recordId) throw new RuntimeException('RKA sudah diubah oleh pengguna lain. Muat ulang halaman sebelum menghapus.');
            $ok = $db->table('accounting_rka_budgets')->where('id', $record['id'])->update([
                'deleted_at' => date('Y-m-d H:i:s'), 'active_slot' => null,
                'deleted_by_role' => session()->get('auth_role') ?: null,
                'deleted_by_name' => session()->get('auth_display_name') ?: null,
                'revision' => $revision + 1, 'updated_at' => date('Y-m-d H:i:s'),
            ]);
            if (! $ok || ! $db->transStatus() || ! $db->transCommit()) throw new RuntimeException('RKA belum berhasil dihapus. Silakan coba kembali.');
        } catch (Throwable $exception) { $db->transRollback(); throw $exception; }
    }

    public function restore(int $id): void
    {
        if (session()->get('auth_role') !== 'admin') throw new RuntimeException('Pemulihan RKA hanya dapat dilakukan oleh Administrator.');
        $db = db_connect();
        $db->transBegin();
        try {
            $record = $db->query('SELECT * FROM accounting_rka_budgets WHERE id = ? FOR UPDATE', [$id])->getRowArray();
            if ($record === null || empty($record['deleted_at'])) throw new RuntimeException('Arsip RKA tidak ditemukan atau sudah dipulihkan.');
            $active = $db->query('SELECT id FROM accounting_rka_budgets WHERE unit_name = ? AND budget_year = ? AND deleted_at IS NULL FOR UPDATE', [$record['unit_name'], $record['budget_year']])->getRowArray();
            if ($active !== null) throw new RuntimeException('RKA aktif untuk unit dan tahun ini sudah ada. Hapus RKA aktif terlebih dahulu sebelum memulihkan arsip.');
            $last = $db->query('SELECT revision FROM accounting_rka_budgets WHERE unit_name = ? AND budget_year = ? ORDER BY revision DESC LIMIT 1 FOR UPDATE', [$record['unit_name'], $record['budget_year']])->getRowArray();
            $ok = $db->table('accounting_rka_budgets')->where('id', $id)->update([
                'deleted_at' => null, 'active_slot' => 1, 'deleted_by_role' => null, 'deleted_by_name' => null,
                'revision' => (int) $last['revision'] + 1, 'updated_at' => date('Y-m-d H:i:s'),
            ]);
            if (! $ok || ! $db->transStatus() || ! $db->transCommit()) throw new RuntimeException('Arsip RKA belum berhasil dipulihkan.');
        } catch (Throwable $exception) { $db->transRollback(); throw $exception; }
    }
}
