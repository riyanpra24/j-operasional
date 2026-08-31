<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class RemoveVehicleNextServiceKilometer extends Migration
{
    public function up(): void
    {
        if ($this->db->tableExists('vehicle_maintenance')
            && $this->db->fieldExists('servis_berikutnya_km', 'vehicle_maintenance')) {
            $this->forge->dropColumn('vehicle_maintenance', 'servis_berikutnya_km');
        }
    }

    public function down(): void
    {
        if ($this->db->tableExists('vehicle_maintenance')
            && ! $this->db->fieldExists('servis_berikutnya_km', 'vehicle_maintenance')) {
            $this->forge->addColumn('vehicle_maintenance', [
                'servis_berikutnya_km' => [
                    'type' => 'INT',
                    'constraint' => 10,
                    'unsigned' => true,
                    'null' => true,
                    'after' => 'servis_berikutnya_tanggal',
                ],
            ]);
        }
    }
}
