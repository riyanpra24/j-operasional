<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class RemoveCatatanFromDokumenSpk extends Migration
{
    public function up()
    {
        if ($this->db->fieldExists('catatan', 'dokumen_spk')) {
            $this->forge->dropColumn('dokumen_spk', 'catatan');
        }
    }

    public function down()
    {
        if (! $this->db->fieldExists('catatan', 'dokumen_spk')) {
            $this->forge->addColumn('dokumen_spk', [
                'catatan' => ['type' => 'TEXT', 'null' => true, 'after' => 'link_berkas'],
            ]);
        }
    }
}
