<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

final class AddJenisToAgendarisSouvenirInventaris extends Migration
{
    public function up(): void
    {
        if (! $this->db->fieldExists('jenis', 'agendaris_souvenir_inventaris')) {
            $this->forge->addColumn('agendaris_souvenir_inventaris', [
                'jenis' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'Souvenir', 'after' => 'nama'],
            ]);
        }
    }

    public function down(): void
    {
        if ($this->db->fieldExists('jenis', 'agendaris_souvenir_inventaris')) {
            $this->forge->dropColumn('agendaris_souvenir_inventaris', 'jenis');
        }
    }
}
