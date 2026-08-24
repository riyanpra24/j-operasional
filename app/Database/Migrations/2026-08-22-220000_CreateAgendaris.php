<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateAgendaris extends Migration
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
            'dokumen_masuk_id' => [
                'type'       => 'BIGINT',
                'constraint' => 20,
                'unsigned'   => true,
            ],
            'tanggal_surat' => [
                'type' => 'DATE',
            ],
            'nomor_surat' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
            ],
            'perihal_surat' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('dokumen_masuk_id', 'agendaris_dokumen_unique');
        $this->forge->addKey('tanggal_surat');
        $this->forge->addKey('nomor_surat');
        $this->forge->addForeignKey('dokumen_masuk_id', 'dokumen_masuk', 'id', 'CASCADE', 'CASCADE', 'agendaris_dokumen_masuk_fk');
        $this->forge->createTable('agendaris', true, ['ENGINE' => 'InnoDB']);
    }

    public function down()
    {
        $this->forge->dropTable('agendaris', true);
    }
}
