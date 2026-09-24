<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

final class CreateSdmMagang extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'nomor' => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'null' => true],
            'nama_magang' => ['type' => 'VARCHAR', 'constraint' => 200],
            'nomor_kontrak_kerja' => ['type' => 'VARCHAR', 'constraint' => 200],
            'unit_kerja' => ['type' => 'VARCHAR', 'constraint' => 150],
            'jenis_magang' => ['type' => 'VARCHAR', 'constraint' => 100],
            'tanggal_mulai' => ['type' => 'DATE', 'null' => true],
            'tanggal_selesai' => ['type' => 'DATE', 'null' => true],
            'link_pkk' => ['type' => 'VARCHAR', 'constraint' => 2048, 'null' => true],
            'keterangan' => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'created_by' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'created_by_name' => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'deleted_by_role' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'deleted_by_name' => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
            'deleted_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('nomor_kontrak_kerja');
        $this->forge->addKey(['unit_kerja', 'tanggal_selesai']);
        $this->forge->addKey('jenis_magang');
        $this->forge->createTable('sdm_magang', true, ['ENGINE' => 'InnoDB']);
    }

    public function down(): void
    {
        $this->forge->dropTable('sdm_magang', true);
    }
}
