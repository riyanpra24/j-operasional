<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

final class SeparateAttendanceIncompleteCodes extends Migration
{
    public function up(): void
    {
        $records = $this->db->table('sdm_attendance_records');
        $records
            ->where('recap_code', 'TA')
            ->where('actual_in IS NOT NULL', null, false)
            ->where('actual_out IS NULL', null, false)
            ->update(['recap_code' => 'TAM']);

        $records
            ->where('recap_code', 'TA')
            ->where('actual_in IS NULL', null, false)
            ->where('actual_out IS NOT NULL', null, false)
            ->update(['recap_code' => 'TAP']);

        $this->refreshIncompleteCounts();
    }

    public function down(): void
    {
        $this->db->table('sdm_attendance_records')
            ->whereIn('recap_code', ['TAM', 'TAP'])
            ->update(['recap_code' => 'TA']);

        $this->refreshIncompleteCounts();
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
