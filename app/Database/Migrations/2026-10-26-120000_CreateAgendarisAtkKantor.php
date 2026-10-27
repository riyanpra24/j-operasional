<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateAgendarisAtkKantor extends Migration
{
    public function up(): void
    {
        if ($this->db->tableExists('agendaris_atk_kantor')) return;

        $this->forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'nama_barang' => ['type' => 'VARCHAR', 'constraint' => 150],
            'merek' => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'stok_barang' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'default' => 0],
            'jumlah' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'default' => 0],
            'kondisi' => ['type' => 'VARCHAR', 'constraint' => 100],
            'keterangan' => ['type' => 'TEXT', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
            'deleted_at' => ['type' => 'DATETIME', 'null' => true],
            'deleted_by_role' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'deleted_by_name' => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('nama_barang');
        $this->forge->addKey('kondisi');
        $this->forge->createTable('agendaris_atk_kantor', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('agendaris_atk_kantor', true);
    }
}
