<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class NormalizeVehicleStatusOptions extends Migration
{
    public function up(): void
    {
        if ($this->db->tableExists('vehicles')) {
            // Expand the ENUM first so legacy values can be normalized safely.
            $this->forge->modifyColumn('vehicles', [
                'status' => [
                    'type' => 'ENUM',
                    'constraint' => ['Tersedia', 'Digunakan', 'Perawatan', 'Tidak Aktif', 'Lainnya'],
                    'default' => 'Digunakan',
                    'null' => false,
                ],
            ]);
            $this->db->table('vehicles')
                ->whereIn('status', ['Tersedia', ''])
                ->update(['status' => 'Lainnya']);
            $this->forge->modifyColumn('vehicles', [
                'status' => [
                    'type' => 'ENUM',
                    'constraint' => ['Digunakan', 'Perawatan', 'Tidak Aktif', 'Lainnya'],
                    'default' => 'Digunakan',
                    'null' => false,
                ],
            ]);
        }
    }

    public function down(): void
    {
        if ($this->db->tableExists('vehicles')) {
            $this->forge->modifyColumn('vehicles', [
                'status' => [
                    'type' => 'ENUM',
                    'constraint' => ['Tersedia', 'Digunakan', 'Perawatan', 'Tidak Aktif', 'Lainnya'],
                    'default' => 'Tersedia',
                    'null' => false,
                ],
            ]);
            $this->db->table('vehicles')
                ->where('status', 'Lainnya')
                ->update(['status' => 'Tersedia']);
            $this->forge->modifyColumn('vehicles', [
                'status' => [
                    'type' => 'ENUM',
                    'constraint' => ['Tersedia', 'Digunakan', 'Perawatan', 'Tidak Aktif'],
                    'default' => 'Tersedia',
                    'null' => false,
                ],
            ]);
        }
    }
}
