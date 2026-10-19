<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateDataAset extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'kategori' => ['type' => 'VARCHAR', 'constraint' => 100],
            'kelompok_aset' => ['type' => 'VARCHAR', 'constraint' => 255],
            'kodefikasi_aset' => ['type' => 'VARCHAR', 'constraint' => 100],
            'nomor_aset' => ['type' => 'VARCHAR', 'constraint' => 100],
            'nama_aset' => ['type' => 'VARCHAR', 'constraint' => 150],
            'status_aset' => ['type' => 'VARCHAR', 'constraint' => 100],
            'tanggal_perolehan' => ['type' => 'DATE', 'null' => true],
            'tanggal_habis_manfaat' => ['type' => 'DATE', 'null' => true],
            'masa_manfaat' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'sisa_masa_manfaat' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'lokasi' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
            'deleted_at' => ['type' => 'DATETIME', 'null' => true],
            'deleted_by_role' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'deleted_by_name' => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('nomor_aset');
        $this->forge->addKey('kategori');
        $this->forge->createTable('data_aset');
    }

    public function down()
    {
        $this->forge->dropTable('data_aset');
    }
}
