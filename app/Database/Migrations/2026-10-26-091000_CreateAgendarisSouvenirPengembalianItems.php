<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateAgendarisSouvenirPengembalianItems extends Migration
{
    public function up()
    {
        if ($this->db->tableExists('agendaris_souvenir_pengembalian_item')) return;

        $this->forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'pengembalian_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'souvenir_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'jumlah_dikembalikan' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('pengembalian_id');
        $this->forge->addKey('souvenir_id');
        $this->forge->createTable('agendaris_souvenir_pengembalian_item', true);
    }

    public function down()
    {
        $this->forge->dropTable('agendaris_souvenir_pengembalian_item', true);
    }
}
