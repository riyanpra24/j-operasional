<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddBerkasToAgendaris extends Migration
{
    public function up()
    {
        $this->forge->addColumn('agendaris', [
            'berkas_nama' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
                'after'      => 'perihal_surat',
            ],
            'berkas_nama_asli' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
                'after'      => 'berkas_nama',
            ],
            'berkas_mime' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
                'null'       => true,
                'after'      => 'berkas_nama_asli',
            ],
            'berkas_ukuran' => [
                'type'       => 'BIGINT',
                'unsigned'   => true,
                'null'       => true,
                'after'      => 'berkas_mime',
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('agendaris', [
            'berkas_nama',
            'berkas_nama_asli',
            'berkas_mime',
            'berkas_ukuran',
        ]);
    }
}
