<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class NormalizeVehicleManagementUnit extends Migration
{
    public function up(): void
    {
        if (! $this->db->tableExists('vehicles')) {
            return;
        }

        $this->db->table('vehicles')
            ->where('pic IS NOT NULL', null, false)
            ->whereNotIn('pic', ['Bagian Umum 1', 'Bagian Umum 2'])
            ->update(['pic' => 'Bagian Umum 2']);
    }

    public function down(): void
    {
        // Nilai PIC lama tidak dapat direkonstruksi setelah dinormalisasi.
    }
}
