<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateSecurityHandoverHistory extends Migration
{
    public function up()
    {
        $this->forge->addColumn('dokumen_masuk', [
            'security_penanggung_jawab' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
                'null'       => true,
                'after'      => 'serah_terima_shift_oleh',
            ],
        ]);

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
            'security_dari' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
                'null'       => true,
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
        $this->forge->addKey('dokumen_masuk_id');
        $this->forge->addKey('diserahkan_at');
        $this->forge->addForeignKey('dokumen_masuk_id', 'dokumen_masuk', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('security_handover_history', true, ['ENGINE' => 'InnoDB']);
    }

    public function down()
    {
        $this->forge->dropTable('security_handover_history', true);
        $this->forge->dropColumn('dokumen_masuk', 'security_penanggung_jawab');
    }
}
