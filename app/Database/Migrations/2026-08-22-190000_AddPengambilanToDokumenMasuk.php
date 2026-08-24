<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddPengambilanToDokumenMasuk extends Migration
{
    public function up()
    {
        $this->forge->addColumn('dokumen_masuk', [
            'pengambilan' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
                'after'      => 'ekspedisi',
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('dokumen_masuk', 'pengambilan');
    }
}
