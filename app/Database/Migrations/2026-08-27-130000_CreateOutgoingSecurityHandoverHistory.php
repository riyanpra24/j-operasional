<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateOutgoingSecurityHandoverHistory extends Migration
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
            'security_dari' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
            ],
            'security_ke' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
            ],
            'dicatat_oleh' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
            ],
            'diserahkan_at' => [
                'type' => 'DATETIME',
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('dokumen_keluar_id');
        $this->forge->addKey('diserahkan_at');
        $this->forge->addForeignKey('dokumen_keluar_id', 'dokumen_keluar', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('outgoing_security_handover_history', true, ['ENGINE' => 'InnoDB']);
    }

    public function down()
    {
        $this->forge->dropTable('outgoing_security_handover_history', true);
    }
}
