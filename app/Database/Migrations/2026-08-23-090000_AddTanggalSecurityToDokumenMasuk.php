<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddTanggalSecurityToDokumenMasuk extends Migration
{
    public function up()
    {
        $this->forge->addColumn('dokumen_masuk', [
            'tanggal_security' => [
                'type'  => 'DATE',
                'null'  => true,
                'after' => 'pengambilan',
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('dokumen_masuk', 'tanggal_security');
    }
}
