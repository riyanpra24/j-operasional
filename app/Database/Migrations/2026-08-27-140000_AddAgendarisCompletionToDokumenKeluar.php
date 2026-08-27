<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddAgendarisCompletionToDokumenKeluar extends Migration
{
    public function up()
    {
        $this->forge->addColumn('dokumen_keluar', [
            'status_agendaris' => [
                'type'       => 'VARCHAR',
                'constraint' => 40,
                'default'    => 'Menunggu Penyelesaian',
                'after'      => 'diambil_ekspedisi_at',
            ],
            'selesai_agendaris_at' => [
                'type'  => 'DATETIME',
                'null'  => true,
                'after' => 'status_agendaris',
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('dokumen_keluar', [
            'status_agendaris',
            'selesai_agendaris_at',
        ]);
    }
}
