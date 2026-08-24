<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateDistribusiDokumen extends Migration
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
            'dokumen_keluar_id' => [
                'type'       => 'BIGINT',
                'constraint' => 20,
                'unsigned'   => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('dokumen_keluar_id', 'distribusi_dokumen_keluar_unique');
        $this->forge->addForeignKey('dokumen_keluar_id', 'dokumen_keluar', 'id', 'CASCADE', 'CASCADE', 'distribusi_dokumen_keluar_fk');
        $this->forge->createTable('distribusi_dokumen', true, ['ENGINE' => 'InnoDB']);

        $this->db->query(
            'INSERT INTO distribusi_dokumen (dokumen_keluar_id) '
            . 'SELECT id FROM dokumen_keluar'
        );
    }

    public function down()
    {
        $this->forge->dropTable('distribusi_dokumen', true);
    }
}
