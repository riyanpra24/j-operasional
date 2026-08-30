<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class FixVehicleActivityLogForeignKey extends Migration
{
    public function up()
    {
        $this->forge->dropForeignKey('vehicle_activity_logs', 'vehicle_activity_logs_vehicle_id_foreign');
        $this->forge->addForeignKey('vehicle_id', 'vehicles', 'id', 'CASCADE', 'SET NULL', 'vehicle_activity_logs_vehicle_id_foreign');
        $this->forge->processIndexes('vehicle_activity_logs');
    }

    public function down()
    {
        $this->forge->dropForeignKey('vehicle_activity_logs', 'vehicle_activity_logs_vehicle_id_foreign');
        $this->forge->addForeignKey('vehicle_id', 'vehicles', 'id', 'SET NULL', 'CASCADE', 'vehicle_activity_logs_vehicle_id_foreign');
        $this->forge->processIndexes('vehicle_activity_logs');
    }
}
