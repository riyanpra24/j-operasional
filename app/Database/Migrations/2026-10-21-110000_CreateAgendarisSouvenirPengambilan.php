<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

final class CreateAgendarisSouvenirPengambilan extends Migration
{
    public function up(): void
    {
        if ($this->db->tableExists('agendaris_souvenir_pengambilan')) return;

        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'nama_pengambil' => ['type' => 'VARCHAR', 'constraint' => 150],
            'divisi_bagian' => ['type' => 'VARCHAR', 'constraint' => 150],
            'souvenir_id' => ['type' => 'INT', 'unsigned' => true],
            'tanggal_pengambilan' => ['type' => 'DATE'],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
            'deleted_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('souvenir_id');
        $this->forge->addKey('tanggal_pengambilan');
        $this->forge->createTable('agendaris_souvenir_pengambilan');
    }

    public function down(): void
    {
        if ($this->db->tableExists('agendaris_souvenir_pengambilan')) {
            $this->forge->dropTable('agendaris_souvenir_pengambilan');
        }
    }
}
