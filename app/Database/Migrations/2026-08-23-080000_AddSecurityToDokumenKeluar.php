<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddSecurityToDokumenKeluar extends Migration
{
    public function up()
    {
        $this->forge->addColumn('dokumen_keluar', [
            'security' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
                'after'      => 'tanggal_diterima',
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('dokumen_keluar', 'security');
    }
}
