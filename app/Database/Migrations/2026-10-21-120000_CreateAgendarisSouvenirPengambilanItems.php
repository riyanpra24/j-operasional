<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

final class CreateAgendarisSouvenirPengambilanItems extends Migration
{
    public function up(): void
    {
        if (! $this->db->tableExists('agendaris_souvenir_pengambilan_item')) {
            $this->forge->addField([
                'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
                'pengambilan_id' => ['type' => 'INT', 'unsigned' => true],
                'souvenir_id' => ['type' => 'INT', 'unsigned' => true],
                'created_at' => ['type' => 'DATETIME', 'null' => true],
                'updated_at' => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addKey('pengambilan_id');
            $this->forge->addKey('souvenir_id');
            $this->forge->createTable('agendaris_souvenir_pengambilan_item');
        }

        if (! $this->db->tableExists('agendaris_souvenir_pengambilan')) return;

        $rows = $this->db->table('agendaris_souvenir_pengambilan')
            ->select('id, souvenir_id')
            ->where('deleted_at', null)
            ->get()
            ->getResultArray();
        foreach ($rows as $row) {
            $exists = $this->db->table('agendaris_souvenir_pengambilan_item')
                ->where('pengambilan_id', (int) $row['id'])
                ->where('souvenir_id', (int) $row['souvenir_id'])
                ->countAllResults() > 0;
            if (! $exists) {
                $this->db->table('agendaris_souvenir_pengambilan_item')->insert([
                    'pengambilan_id' => (int) $row['id'],
                    'souvenir_id' => (int) $row['souvenir_id'],
                ]);
            }
        }
    }

    public function down(): void
    {
        if ($this->db->tableExists('agendaris_souvenir_pengambilan_item')) {
            $this->forge->dropTable('agendaris_souvenir_pengambilan_item');
        }
    }
}
