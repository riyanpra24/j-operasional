<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class SetVehicleMaintenanceToThreeMonths extends Migration
{
    public function up()
    {
        $this->db->query(
            'UPDATE vehicle_maintenance '
            . 'SET servis_berikutnya_tanggal = DATE_ADD(tanggal_servis, INTERVAL 3 MONTH) '
            . 'WHERE tanggal_servis IS NOT NULL AND deleted_at IS NULL',
        );
    }

    public function down()
    {
        $this->db->query(
            'UPDATE vehicle_maintenance '
            . 'SET servis_berikutnya_tanggal = DATE_ADD(tanggal_servis, INTERVAL 6 MONTH) '
            . 'WHERE tanggal_servis IS NOT NULL AND deleted_at IS NULL',
        );
    }
}
