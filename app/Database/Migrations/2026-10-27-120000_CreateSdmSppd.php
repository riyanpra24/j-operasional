<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

final class CreateSdmSppd extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'nomor_sppd' => ['type' => 'VARCHAR', 'constraint' => 100],
            'tanggal_sppd' => ['type' => 'DATE'],
            'nama' => ['type' => 'VARCHAR', 'constraint' => 150],
            'jabatan' => ['type' => 'VARCHAR', 'constraint' => 150],
            'tujuan' => ['type' => 'VARCHAR', 'constraint' => 200],
            'keperluan' => ['type' => 'VARCHAR', 'constraint' => 500],
            'tanggal_berangkat' => ['type' => 'DATE'],
            'tanggal_kembali' => ['type' => 'DATE'],
            'total_hari' => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true],
            'created_by' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'created_by_name' => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'deleted_by_role' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'deleted_by_name' => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
            'deleted_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('nomor_sppd');
        $this->forge->addKey('tanggal_sppd');
        $this->forge->addKey('tujuan');
        $this->forge->addKey(['tanggal_berangkat', 'tanggal_kembali']);
        $this->forge->createTable('sdm_sppd', true, ['ENGINE' => 'InnoDB']);
    }

    public function down(): void
    {
        $this->forge->dropTable('sdm_sppd', true);
    }
}
