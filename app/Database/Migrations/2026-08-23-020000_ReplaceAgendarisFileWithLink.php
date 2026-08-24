<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class ReplaceAgendarisFileWithLink extends Migration
{
    public function up()
    {
        $this->forge->addColumn('agendaris', [
            'berkas_link' => [
                'type'       => 'VARCHAR',
                'constraint' => 2048,
                'null'       => true,
                'after'      => 'perihal_surat',
            ],
        ]);

        $this->forge->dropColumn('agendaris', [
            'berkas_nama',
            'berkas_nama_asli',
            'berkas_mime',
            'berkas_ukuran',
        ]);
    }

    public function down()
    {
        $this->forge->addColumn('agendaris', [
            'berkas_nama' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'berkas_nama_asli' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'berkas_mime' => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'berkas_ukuran' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
        ]);

        $this->forge->dropColumn('agendaris', 'berkas_link');
    }
}
