<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateDokumenSpk extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'nomor_urut' => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true],
            'jenis_dokumen' => ['type' => 'ENUM', 'constraint' => ['SPK'], 'default' => 'SPK'],
            'nomor_dokumen' => ['type' => 'VARCHAR', 'constraint' => 200],
            'tanggal_dokumen' => ['type' => 'DATE', 'null' => true],
            'tahun' => ['type' => 'SMALLINT', 'constraint' => 4, 'unsigned' => true],
            'perihal' => ['type' => 'TEXT'],
            'link_berkas' => ['type' => 'VARCHAR', 'constraint' => 2048, 'null' => true],
            'created_by' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'created_by_name' => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('nomor_dokumen');
        $this->forge->addUniqueKey(['tahun', 'nomor_urut']);
        $this->forge->addKey('jenis_dokumen');
        $this->forge->addKey('tanggal_dokumen');
        $this->forge->createTable('dokumen_spk', true, ['ENGINE' => 'InnoDB']);
    }

    public function down()
    {
        $this->forge->dropTable('dokumen_spk', true);
    }
}
