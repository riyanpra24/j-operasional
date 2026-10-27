<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateAgendarisSouvenirPengembalian extends Migration
{
    public function up()
    {
        if ($this->db->tableExists('agendaris_souvenir_pengembalian')) return;

        $this->forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'nama_pengembali' => ['type' => 'VARCHAR', 'constraint' => 150],
            'divisi_bagian' => ['type' => 'VARCHAR', 'constraint' => 150],
            'souvenir_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'keterangan' => ['type' => 'TEXT', 'null' => true],
            'tanggal_pengembalian' => ['type' => 'DATE'],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
            'deleted_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('souvenir_id');
        $this->forge->addKey('tanggal_pengembalian');
        $this->forge->createTable('agendaris_souvenir_pengembalian', true);
    }

    public function down()
    {
        $this->forge->dropTable('agendaris_souvenir_pengembalian', true);
    }
}
