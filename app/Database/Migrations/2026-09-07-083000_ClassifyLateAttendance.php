<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

final class ClassifyLateAttendance extends Migration
{
    public function up(): void
    {
        if (! $this->db->tableExists('sdm_attendance_records')) {
            return;
        }

        $this->db->table('sdm_attendance_records')
            ->where('recap_code', 'H')
            ->where('actual_in >', '08:00:00')
            ->where('actual_out IS NOT NULL', null, false)
            ->update(['recap_code' => 'TLBT']);
    }

    public function down(): void
    {
        if (! $this->db->tableExists('sdm_attendance_records')) {
            return;
        }

        $this->db->table('sdm_attendance_records')
            ->where('recap_code', 'TLBT')
            ->update(['recap_code' => 'H']);
    }
}
