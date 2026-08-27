<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddShiftHandoverToDokumenMasuk extends Migration
{
    public function up()
    {
        $this->forge->addColumn('dokumen_masuk', [
            'serah_terima_shift_at' => [
                'type'  => 'DATETIME',
                'null'  => true,
                'after' => 'penyerahan_at',
            ],
            'serah_terima_shift_oleh' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
                'null'       => true,
                'after'      => 'serah_terima_shift_at',
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('dokumen_masuk', [
            'serah_terima_shift_at',
            'serah_terima_shift_oleh',
        ]);
    }
}
