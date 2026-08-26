<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddSatuanJumlahToDokumenMasuk extends Migration
{
    public function up()
    {
        $this->forge->addColumn('dokumen_masuk', [
            'satuan_jumlah' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => true,
                'after'      => 'jumlah',
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('dokumen_masuk', 'satuan_jumlah');
    }
}
