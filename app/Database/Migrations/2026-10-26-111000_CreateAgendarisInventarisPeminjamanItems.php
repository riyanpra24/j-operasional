<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateAgendarisInventarisPeminjamanItems extends Migration
{
    public function up()
    {
        if ($this->db->tableExists('agendaris_inventaris_peminjaman_item')) return;
        $this->forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'peminjaman_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true], 'inventaris_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true], 'jumlah_dipinjam' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true], 'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true); $this->forge->addKey('peminjaman_id'); $this->forge->addKey('inventaris_id');
        $this->forge->createTable('agendaris_inventaris_peminjaman_item', true);
    }
    public function down() { $this->forge->dropTable('agendaris_inventaris_peminjaman_item', true); }
}
