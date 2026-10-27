<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateAgendarisAtkPengambilan extends Migration
{
    public function up(): void
    {
        if (! $this->db->tableExists('agendaris_atk_pengambilan')) {
            $this->forge->addField([
                'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'nama_pengambil' => ['type' => 'VARCHAR', 'constraint' => 150],
                'divisi_bagian' => ['type' => 'VARCHAR', 'constraint' => 150],
                'keperluan_kegiatan' => ['type' => 'VARCHAR', 'constraint' => 500],
                'keterangan' => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
                'tanggal_pengambilan' => ['type' => 'DATE'],
                'created_at' => ['type' => 'DATETIME', 'null' => true],
                'updated_at' => ['type' => 'DATETIME', 'null' => true],
                'deleted_at' => ['type' => 'DATETIME', 'null' => true],
                'deleted_by_role' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
                'deleted_by_name' => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addKey('tanggal_pengambilan');
            $this->forge->addKey('nama_pengambil');
            $this->forge->createTable('agendaris_atk_pengambilan', true);
        }

        if ($this->db->tableExists('agendaris_atk_pengambilan_item')) return;
        $this->forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'pengambilan_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'atk_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'jumlah_diambil' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('pengambilan_id');
        $this->forge->addKey('atk_id');
        $this->forge->createTable('agendaris_atk_pengambilan_item', true);
    }

    public function down(): void
    {
        if ($this->db->tableExists('agendaris_atk_pengambilan_item')) $this->forge->dropTable('agendaris_atk_pengambilan_item', true);
        if ($this->db->tableExists('agendaris_atk_pengambilan')) $this->forge->dropTable('agendaris_atk_pengambilan', true);
    }
}
