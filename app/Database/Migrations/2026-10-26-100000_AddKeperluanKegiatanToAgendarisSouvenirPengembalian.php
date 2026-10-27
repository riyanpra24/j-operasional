<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddKeperluanKegiatanToAgendarisSouvenirPengembalian extends Migration
{
    public function up()
    {
        if ($this->db->fieldExists('keperluan_kegiatan', 'agendaris_souvenir_pengembalian')) return;

        $this->forge->addColumn('agendaris_souvenir_pengembalian', [
            'keperluan_kegiatan' => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true, 'after' => 'divisi_bagian'],
        ]);
    }

    public function down()
    {
        if ($this->db->fieldExists('keperluan_kegiatan', 'agendaris_souvenir_pengembalian')) {
            $this->forge->dropColumn('agendaris_souvenir_pengembalian', 'keperluan_kegiatan');
        }
    }
}
