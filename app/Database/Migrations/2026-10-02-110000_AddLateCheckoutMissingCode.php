<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

final class AddLateCheckoutMissingCode extends Migration
{
    public function up(): void
    {
        if (! $this->db->tableExists('sdm_attendance_records')) {
            return;
        }

        $this->db->table('sdm_attendance_records')
            ->where('recap_code', 'TAP')
            ->where('actual_in >=', '08:01:00')
            ->where('actual_out IS NULL', null, false)
            ->update(['recap_code' => 'TLTAP']);

        $this->refreshImportSummaries();
    }

    public function down(): void
    {
        if (! $this->db->tableExists('sdm_attendance_records')) {
            return;
        }

        $this->db->table('sdm_attendance_records')
            ->where('recap_code', 'TLTAP')
            ->update(['recap_code' => 'TAP']);

        $this->refreshImportSummaries();
    }

    private function refreshImportSummaries(): void
    {
        if (! $this->db->tableExists('sdm_attendance_imports')) {
            return;
        }

        $imports = $this->db->table('sdm_attendance_imports')->select('id')->get()->getResultArray();
        foreach ($imports as $import) {
            $rows = $this->db->table('sdm_attendance_records')
                ->select('employee_key, recap_code')
                ->where('import_id', (int) $import['id'])
                ->get()
                ->getResultArray();
            $employees = [];
            $counts = array_fill_keys(['H', 'TLBT', 'TLTAP', 'IZ', 'CT', 'A', 'TA', 'TAM', 'TAP', 'TPA', 'OFF', 'OTHER'], 0);

            foreach ($rows as $row) {
                $employees[$row['employee_key']] = true;
                $code = (string) $row['recap_code'];
                isset($counts[$code]) ? $counts[$code]++ : $counts['OTHER']++;
            }

            $this->db->table('sdm_attendance_imports')->where('id', (int) $import['id'])->update([
                'employee_count'   => count($employees),
                'row_count'        => count($rows),
                'hadir_count'      => $counts['H'] + $counts['TLBT'],
                'izin_count'       => $counts['IZ'] + $counts['CT'],
                'alpa_count'       => $counts['A'],
                'incomplete_count' => $counts['TA'] + $counts['TAM'] + $counts['TAP'] + $counts['TLTAP'] + $counts['TPA'],
                'off_count'        => $counts['OFF'],
                'other_count'      => $counts['OTHER'],
            ]);
        }
    }
}
