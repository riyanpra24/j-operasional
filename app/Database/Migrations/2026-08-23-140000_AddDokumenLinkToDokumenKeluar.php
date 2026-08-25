<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddDokumenLinkToDokumenKeluar extends Migration
{
    public function up()
    {
        $this->forge->addColumn('dokumen_keluar', [
            'dokumen_link' => [
                'type'       => 'VARCHAR',
                'constraint' => 2048,
                'null'       => true,
                'after'      => 'alamat_penerima',
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('dokumen_keluar', 'dokumen_link');
    }
}
