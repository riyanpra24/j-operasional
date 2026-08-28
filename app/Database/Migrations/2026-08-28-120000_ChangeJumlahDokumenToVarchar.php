<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class ChangeJumlahDokumenToVarchar extends Migration
{
    public function up()
    {
        $this->forge->modifyColumn('dokumen_keluar', [
            'jumlah_dokumen' => [
                'name'       => 'jumlah_dokumen',
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'default'    => '1',
                'null'       => false,
            ],
        ]);
    }

    public function down()
    {
        $this->forge->modifyColumn('dokumen_keluar', [
            'jumlah_dokumen' => [
                'name'       => 'jumlah_dokumen',
                'type'       => 'INT',
                'constraint' => 10,
                'unsigned'   => true,
                'default'    => 1,
            ],
        ]);
    }
}
