<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddVehicleCustomStatus extends Migration
{
    public function up(): void
    {
        if ($this->db->tableExists('vehicles') && ! $this->db->fieldExists('status_lainnya', 'vehicles')) {
            $this->forge->addColumn('vehicles', [
                'status_lainnya' => [
                    'type' => 'VARCHAR',
                    'constraint' => 100,
                    'null' => true,
                    'after' => 'status',
                ],
            ]);
        }
    }

    public function down(): void
    {
        if ($this->db->tableExists('vehicles') && $this->db->fieldExists('status_lainnya', 'vehicles')) {
            $this->forge->dropColumn('vehicles', 'status_lainnya');
        }
    }
}
