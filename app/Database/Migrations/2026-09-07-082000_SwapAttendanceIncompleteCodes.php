<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

final class SwapAttendanceIncompleteCodes extends Migration
{
    public function up(): void
    {
        $this->applyCodes('TAP', 'TAM');
    }

    public function down(): void
    {
        $this->applyCodes('TAM', 'TAP');
    }

    private function applyCodes(string $checkinOnlyCode, string $checkoutOnlyCode): void
    {
        $this->db->table('sdm_attendance_records')
            ->whereIn('recap_code', ['TA', 'TAM', 'TAP'])
            ->where('actual_in IS NOT NULL', null, false)
            ->where('actual_out IS NULL', null, false)
            ->update(['recap_code' => $checkinOnlyCode]);

        $this->db->table('sdm_attendance_records')
            ->whereIn('recap_code', ['TA', 'TAM', 'TAP'])
            ->where('actual_in IS NULL', null, false)
            ->where('actual_out IS NOT NULL', null, false)
            ->update(['recap_code' => $checkoutOnlyCode]);
    }
}
