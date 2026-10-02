<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

final class NormalizeCbLeaveCodes extends Migration
{
    public function up(): void
    {
        if (! $this->db->tableExists('sdm_attendance_records')) {
            return;
        }

        // File ESS menggunakan CB1, CB2, CB3, dan seterusnya sebagai variasi cuti.
        // Data impor lama dapat masih menyimpan kode mentah tersebut sebagai recap_code.
        $records = $this->db->table('sdm_attendance_records')
            ->select('id, raw_status')
            ->get()
            ->getResultArray();

        foreach ($records as $record) {
            $rawStatus = strtoupper(trim((string) ($record['raw_status'] ?? '')));
            if (preg_match('/^CB\d+$/', $rawStatus) !== 1) {
                continue;
            }

            $this->db->table('sdm_attendance_records')
                ->where('id', (int) $record['id'])
                ->update(['recap_code' => 'CT']);
        }

        // Hari libur tetap bukan hari kehadiran, termasuk jika data ESS memuat kode lain.
        $this->db->table('sdm_attendance_records')
            ->whereIn('day_type', ['OFF', 'PHOFF'])
            ->update(['recap_code' => 'OFF']);

        $this->refreshImportSummaries();
    }

    public function down(): void
    {
        // Kode asal data lama tidak dapat dipulihkan dengan pasti.
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
                $code = (string) $row['recap_code'];
                $isNonWorkingDay = in_array(strtoupper((string) ($row['day_type'] ?? '')), ['OFF', 'PHOFF'], true);

                if ($isNonWorkingDay || ($code === 'ODR' && $isNonWorkingDay)) {
                    $counts['OFF']++;
                } elseif (isset($counts[$code])) {
                    $counts[$code]++;
                } else {
                    $counts['OTHER']++;
                }
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
