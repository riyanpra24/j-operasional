<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

final class AddSatuanToAgendarisSouvenirInventaris extends Migration
{
    public function up(): void
    {
        if (! $this->db->fieldExists('satuan', 'agendaris_souvenir_inventaris')) {
            $this->forge->addColumn('agendaris_souvenir_inventaris', [
                'satuan' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'pcs', 'after' => 'jenis'],
            ]);
        }
    }

    public function down(): void
    {
        if ($this->db->fieldExists('satuan', 'agendaris_souvenir_inventaris')) {
            $this->forge->dropColumn('agendaris_souvenir_inventaris', 'satuan');
        }
    }
}
