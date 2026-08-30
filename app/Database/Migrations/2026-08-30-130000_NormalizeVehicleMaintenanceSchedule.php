<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class NormalizeVehicleMaintenanceSchedule extends Migration
{
    public function up()
    {
        $this->db->query(
            'UPDATE vehicle_maintenance '
            . 'SET servis_berikutnya_tanggal = DATE_ADD(tanggal_servis, INTERVAL 6 MONTH) '
            . 'WHERE tanggal_servis IS NOT NULL AND deleted_at IS NULL',
        );
    }

    public function down()
    {
        // Jadwal lama tidak dapat dipulihkan karena tidak memiliki tabel audit nilai sebelumnya.
    }
}
