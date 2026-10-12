<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

final class AddWorkUnitToDokumenKeluar extends Migration
{
    public function up(): void
    {
        if ($this->db->fieldExists('unit_kerja', 'dokumen_keluar')) {
            return;
        }

        $this->forge->addColumn('dokumen_keluar', [
            'unit_kerja' => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true, 'after' => 'nama_ekspedisi'],
        ]);
        $this->forge->addKey('unit_kerja');
        $this->forge->processIndexes('dokumen_keluar');
    }

    public function down(): void
    {
        if ($this->db->fieldExists('unit_kerja', 'dokumen_keluar')) {
            $this->forge->dropColumn('dokumen_keluar', 'unit_kerja');
        }
    }
}
