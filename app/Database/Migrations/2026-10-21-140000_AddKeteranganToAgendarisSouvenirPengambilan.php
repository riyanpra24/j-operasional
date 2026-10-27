<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

final class AddKeteranganToAgendarisSouvenirPengambilan extends Migration
{
    public function up(): void
    {
        if (! $this->db->fieldExists('keterangan', 'agendaris_souvenir_pengambilan')) {
            $this->forge->addColumn('agendaris_souvenir_pengambilan', [
                'keterangan' => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true, 'after' => 'divisi_bagian'],
            ]);
        }
    }

    public function down(): void
    {
        if ($this->db->fieldExists('keterangan', 'agendaris_souvenir_pengambilan')) {
            $this->forge->dropColumn('agendaris_souvenir_pengambilan', 'keterangan');
        }
    }
}
