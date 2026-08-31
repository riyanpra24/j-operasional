<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddVehicleCustomDriver extends Migration
{
    public function up(): void
    {
        if ($this->db->tableExists('vehicles')
            && ! $this->db->fieldExists('unit_pengguna_lainnya', 'vehicles')) {
            $this->forge->addColumn('vehicles', [
                'unit_pengguna_lainnya' => [
                    'type' => 'VARCHAR',
                    'constraint' => 150,
                    'null' => true,
                    'after' => 'unit_pengguna',
                ],
            ]);
        }
    }

    public function down(): void
    {
        if ($this->db->tableExists('vehicles')
            && $this->db->fieldExists('unit_pengguna_lainnya', 'vehicles')) {
            $this->forge->dropColumn('vehicles', 'unit_pengguna_lainnya');
        }
    }
}
