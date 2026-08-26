<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddOutgoingWorkflowTimestamps extends Migration
{
    public function up()
    {
        $this->forge->addColumn('dokumen_keluar', [
            'diterima_security_at' => [
                'type'  => 'DATETIME',
                'null'  => true,
                'after' => 'tanggal_security',
            ],
            'diambil_ekspedisi_at' => [
                'type'  => 'DATETIME',
                'null'  => true,
                'after' => 'progres',
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('dokumen_keluar', [
            'diterima_security_at',
            'diambil_ekspedisi_at',
        ]);
    }
}
