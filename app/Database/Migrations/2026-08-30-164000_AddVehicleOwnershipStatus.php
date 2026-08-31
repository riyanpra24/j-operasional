<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddVehicleOwnershipStatus extends Migration
{
    public function up(): void
    {
        if ($this->db->tableExists('vehicles') && ! $this->db->fieldExists('status_kendaraan', 'vehicles')) {
            $this->forge->addColumn('vehicles', [
                'status_kendaraan' => [
                    'type' => 'ENUM',
                    'constraint' => ['Kendaraan Aset', 'Kendaraan Sewa'],
                    'default' => 'Kendaraan Aset',
                    'null' => false,
                    'after' => 'jenis',
                ],
            ]);
        }
    }

    public function down(): void
    {
        if ($this->db->tableExists('vehicles') && $this->db->fieldExists('status_kendaraan', 'vehicles')) {
            $this->forge->dropColumn('vehicles', 'status_kendaraan');
        }
    }
}
