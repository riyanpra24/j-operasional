<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

final class NormalizeAdditionalEssLeaveCodes extends Migration
{
    private const LEAVE_CODES = ['CS', 'CBR', 'CK', 'SD'];

    public function up(): void
    {
        if (! $this->db->tableExists('sdm_attendance_records')) {
            return;
        }

        // Variasi kode cuti dari ESS disimpan dengan satu kode rekap yang seragam.
        foreach (self::LEAVE_CODES as $code) {
            $this->db->table('sdm_attendance_records')
                ->where("UPPER(raw_status) = " . $this->db->escape($code), null, false)
                ->update(['recap_code' => 'CT']);
        }

        // Hari libur tidak dihitung sebagai cuti, meskipun file ESS membawa kode cuti.
        $this->db->table('sdm_attendance_records')
            ->whereIn('day_type', ['OFF', 'PHOFF'])
            ->update(['recap_code' => 'OFF']);

        $this->refreshImportSummaries();
    }

    public function down(): void
    {
        // Kode asal tidak dipulihkan karena semua variasi telah diseragamkan menjadi CT.
    }

    private function refreshImportSummaries(): void
    {
        if (! $this->db->tableExists('sdm_attendance_imports')) {
            return;
        }

        $imports = $this->db->table('sdm_attendance_imports')->select('id')->get()->getResultArray();
        foreach ($imports as $import) {
            $rows = $this->db->table('sdm_attendance_records')
                ->select('employee_key, recap_code, day_type')
                ->where('import_id', (int) $import['id'])
                ->get()
                ->getResultArray();
            $employees = [];
            $counts = array_fill_keys(['H', 'TLBT', 'TLTAP', 'ODR', 'IZ', 'CT', 'A', 'TA', 'TAM', 'TAP', 'TPA', 'OFF', 'OTHER'], 0);

            foreach ($rows as $row) {
                $employees[$row['employee_key']] = true;
                $isNonWorkingDay = in_array(strtoupper((string) ($row['day_type'] ?? '')), ['OFF', 'PHOFF'], true);
                $code = $isNonWorkingDay ? 'OFF' : (string) $row['recap_code'];
                isset($counts[$code]) ? $counts[$code]++ : $counts['OTHER']++;
            }

            $this->db->table('sdm_attendance_imports')->where('id', (int) $import['id'])->update([
                'employee_count'   => count($employees),
                'row_count'        => count($rows),
                'hadir_count'      => $counts['H'] + $counts['TLBT'] + $counts['ODR'],
                'izin_count'       => $counts['IZ'] + $counts['CT'],
                'alpa_count'       => $counts['A'],
                'incomplete_count' => $counts['TA'] + $counts['TAM'] + $counts['TAP'] + $counts['TLTAP'] + $counts['TPA'],
                'off_count'        => $counts['OFF'],
                'other_count'      => $counts['OTHER'],
            ]);
        }
    }
}
