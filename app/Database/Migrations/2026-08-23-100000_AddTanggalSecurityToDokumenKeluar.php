<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddTanggalSecurityToDokumenKeluar extends Migration
{
    public function up()
    {
        $this->forge->addColumn('dokumen_keluar', [
            'tanggal_security' => [
                'type'  => 'DATE',
                'null'  => true,
                'after' => 'security',
            ],
        ]);

        $this->db->query(
            'UPDATE dokumen_keluar SET tanggal_security = tanggal_diterima '
            . 'WHERE tanggal_security IS NULL AND tanggal_diterima IS NOT NULL'
        );
    }

    public function down()
    {
        $this->forge->dropColumn('dokumen_keluar', 'tanggal_security');
    }
}
