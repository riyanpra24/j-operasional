<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateAgendarisSouvenirInventaris extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'nama_item' => ['type' => 'VARCHAR', 'constraint' => 150],
            'persediaan_awal' => ['type' => 'INT', 'unsigned' => true],
            'persediaan_akhir' => ['type' => 'INT', 'unsigned' => true],
            'terpakai' => ['type' => 'INT', 'unsigned' => true],
            'keterangan' => ['type' => 'TEXT', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
            'deleted_at' => ['type' => 'DATETIME', 'null' => true],
            'deleted_by_role' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'deleted_by_name' => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('nama_item');
        $this->forge->addKey('persediaan_akhir');
        $this->forge->createTable('agendaris_souvenir_inventaris');
    }

    public function down()
    {
        $this->forge->dropTable('agendaris_souvenir_inventaris');
    }
}
