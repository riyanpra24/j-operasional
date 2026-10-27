<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

final class RenameAgendarisSouvenirNamaToNamaItem extends Migration
{
    public function up(): void
    {
        if ($this->db->fieldExists('nama', 'agendaris_souvenir_inventaris') && ! $this->db->fieldExists('nama_item', 'agendaris_souvenir_inventaris')) {
            $this->forge->modifyColumn('agendaris_souvenir_inventaris', [
                'nama' => ['name' => 'nama_item', 'type' => 'VARCHAR', 'constraint' => 150],
            ]);
        }
    }

    public function down(): void
    {
        if ($this->db->fieldExists('nama_item', 'agendaris_souvenir_inventaris') && ! $this->db->fieldExists('nama', 'agendaris_souvenir_inventaris')) {
            $this->forge->modifyColumn('agendaris_souvenir_inventaris', [
                'nama_item' => ['name' => 'nama', 'type' => 'VARCHAR', 'constraint' => 150],
            ]);
        }
    }
}
