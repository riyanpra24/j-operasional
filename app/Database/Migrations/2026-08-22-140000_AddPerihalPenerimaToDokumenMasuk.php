<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddPerihalPenerimaToDokumenMasuk extends Migration
{
    public function up()
    {
        $this->forge->addColumn('dokumen_masuk', [
            'perihal' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'default'    => '',
                'after'      => 'pengirim',
            ],
            'penerima' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'default'    => '',
                'after'      => 'perihal',
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('dokumen_masuk', ['perihal', 'penerima']);
    }
}
