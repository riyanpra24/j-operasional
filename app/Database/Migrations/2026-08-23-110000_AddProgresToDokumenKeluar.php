<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddProgresToDokumenKeluar extends Migration
{
    public function up()
    {
        $this->forge->addColumn('dokumen_keluar', [
            'progres' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => false,
                'default'    => 'Belum Diambil',
                'after'      => 'tanggal_security',
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('dokumen_keluar', 'progres');
    }
}
