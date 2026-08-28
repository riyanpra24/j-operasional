<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddJumlahDanEkspedisiToDokumenKeluar extends Migration
{
    public function up()
    {
        $this->forge->addColumn('dokumen_keluar', [
            'jumlah_dokumen' => [
                'type'       => 'INT',
                'constraint' => 10,
                'unsigned'   => true,
                'default'    => 1,
                'after'      => 'jenis_surat',
            ],
            'nama_ekspedisi' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
                'null'       => true,
                'after'      => 'jumlah_dokumen',
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('dokumen_keluar', ['jumlah_dokumen', 'nama_ekspedisi']);
    }
}
