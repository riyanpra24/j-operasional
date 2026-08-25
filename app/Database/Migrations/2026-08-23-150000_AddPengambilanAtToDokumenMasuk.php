<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddPengambilanAtToDokumenMasuk extends Migration
{
    public function up()
    {
        $this->forge->addColumn('dokumen_masuk', [
            'pengambilan_at' => [
                'type'  => 'DATETIME',
                'null'  => true,
                'after' => 'pengambilan',
            ],
        ]);

        $this->db->query(
            "UPDATE dokumen_masuk
             SET pengambilan_at = COALESCE(updated_at, created_at, CURRENT_TIMESTAMP)
             WHERE pengambilan IS NOT NULL AND TRIM(pengambilan) != ''"
        );
    }

    public function down()
    {
        $this->forge->dropColumn('dokumen_masuk', 'pengambilan_at');
    }
}
