<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddVehicleInternalPic extends Migration
{
    public function up(): void
    {
        if (! $this->db->tableExists('vehicles')) {
            return;
        }

        if (! $this->db->fieldExists('pic_internal', 'vehicles')) {
            $this->forge->addColumn('vehicles', [
                'pic_internal' => [
                    'type' => 'VARCHAR',
                    'constraint' => 150,
                    'null' => true,
                    'after' => 'pic',
                ],
            ]);
        }

        $this->db->table('vehicles')->where('pic', 'Bagian Umum 1')->update(['pic_internal' => 'Angger Wicaksono']);
        $this->db->table('vehicles')->where('pic', 'Bagian Umum 2')->update(['pic_internal' => 'Agil Halis Kesawa']);
    }

    public function down(): void
    {
        if ($this->db->tableExists('vehicles') && $this->db->fieldExists('pic_internal', 'vehicles')) {
            $this->forge->dropColumn('vehicles', 'pic_internal');
        }
    }
}
