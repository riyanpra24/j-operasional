<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddJangkaWaktuToPksDokumen extends Migration
{
    public function up()
    {
        $this->forge->addColumn('pks_dokumen_kerjasama', [
            'jangka_waktu_bulan' => [
                'type'       => 'INT',
                'constraint' => 10,
                'unsigned'   => true,
                'null'       => true,
                'after'      => 'periode_mulai',
            ],
        ]);

        $this->db->query(
            'UPDATE pks_dokumen_kerjasama
             SET jangka_waktu_bulan = GREATEST(1, TIMESTAMPDIFF(MONTH, periode_mulai, periode_selesai))
             WHERE jangka_waktu_bulan IS NULL'
        );

        $this->forge->modifyColumn('pks_dokumen_kerjasama', [
            'jangka_waktu_bulan' => [
                'name'       => 'jangka_waktu_bulan',
                'type'       => 'INT',
                'constraint' => 10,
                'unsigned'   => true,
                'default'    => 1,
                'null'       => false,
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('pks_dokumen_kerjasama', 'jangka_waktu_bulan');
    }
}
