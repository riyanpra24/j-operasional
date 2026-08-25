<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddPenyerahanAtToDokumenMasuk extends Migration
{
    public function up()
    {
        $this->forge->addColumn('dokumen_masuk', [
            'penyerahan_at' => [
                'type'  => 'DATETIME',
                'null'  => true,
                'after' => 'tanggal_security',
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('dokumen_masuk', 'penyerahan_at');
    }
}
