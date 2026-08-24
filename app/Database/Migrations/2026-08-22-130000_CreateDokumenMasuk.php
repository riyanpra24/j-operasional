<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateDokumenMasuk extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'BIGINT',
                'constraint'     => 20,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'pengirim' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
            ],
            'hari' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
            ],
            'tanggal' => [
                'type' => 'DATE',
            ],
            'jenis' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
            ],
            'jumlah' => [
                'type'       => 'INT',
                'constraint' => 10,
                'unsigned'   => true,
                'default'    => 1,
            ],
            'ekspedisi' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
                'null'       => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'deleted_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('tanggal');
        $this->forge->addKey('pengirim');
        $this->forge->addKey('jenis');
        $this->forge->addKey('ekspedisi');
        $this->forge->createTable('dokumen_masuk', true);
    }

    public function down()
    {
        $this->forge->dropTable('dokumen_masuk', true);
    }
}
