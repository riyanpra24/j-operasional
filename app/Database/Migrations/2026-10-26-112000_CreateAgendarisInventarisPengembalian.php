<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateAgendarisInventarisPengembalian extends Migration
{
    public function up()
    {
        if ($this->db->tableExists('agendaris_inventaris_pengembalian')) return;
        $this->forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'nama_pengembali' => ['type' => 'VARCHAR', 'constraint' => 150], 'divisi_bagian' => ['type' => 'VARCHAR', 'constraint' => 150], 'keperluan_kegiatan' => ['type' => 'VARCHAR', 'constraint' => 500],
            'inventaris_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true], 'keterangan' => ['type' => 'TEXT', 'null' => true], 'tanggal_pengembalian' => ['type' => 'DATE'],
            'created_at' => ['type' => 'DATETIME', 'null' => true], 'updated_at' => ['type' => 'DATETIME', 'null' => true], 'deleted_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true); $this->forge->addKey('inventaris_id'); $this->forge->addKey('tanggal_pengembalian');
        $this->forge->createTable('agendaris_inventaris_pengembalian', true);
    }
    public function down() { $this->forge->dropTable('agendaris_inventaris_pengembalian', true); }
}
