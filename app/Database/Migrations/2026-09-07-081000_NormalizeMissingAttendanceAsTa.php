<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

final class NormalizeMissingAttendanceAsTa extends Migration
{
    public function up(): void
    {
        $this->db->table('sdm_attendance_records')
            ->where('recap_code', '-')
            ->where('actual_in IS NULL', null, false)
            ->where('actual_out IS NULL', null, false)
            ->update(['recap_code' => 'TA']);

        $this->refreshIncompleteCounts();
    }

    public function down(): void
    {
        // TA dapat berasal dari beberapa kondisi berbeda sehingga pengembalian
        // otomatis ke kode lama berisiko mengubah data yang memang valid.
    }

    private function refreshIncompleteCounts(): void
    {
        $this->db->query(
            "UPDATE sdm_attendance_imports AS imports
             SET incomplete_count = (
                 SELECT COUNT(*)
                 FROM sdm_attendance_records AS records
                 WHERE records.import_id = imports.id
                   AND records.recap_code IN ('TA', 'TAM', 'TAP')
             )",
        );
    }
}
