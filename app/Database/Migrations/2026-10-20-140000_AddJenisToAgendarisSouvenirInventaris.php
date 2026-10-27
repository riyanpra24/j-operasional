<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

final class AddJenisToAgendarisSouvenirInventaris extends Migration
{
    public function up(): void
    {
        $table = 'agendaris_souvenir_inventaris';

        if (! $this->db->fieldExists('jenis', $table)) {
            // Fresh installs use nama_item. Keep nama as a fallback for
            // databases created with the legacy schema.
            $after = $this->db->fieldExists('nama_item', $table) ? 'nama_item' : 'nama';

            $this->forge->addColumn('agendaris_souvenir_inventaris', [
                'jenis' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'Souvenir', 'after' => $after],
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
