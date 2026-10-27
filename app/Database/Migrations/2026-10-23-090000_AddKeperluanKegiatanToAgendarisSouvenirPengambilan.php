<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

final class AddKeperluanKegiatanToAgendarisSouvenirPengambilan extends Migration
{
    public function up(): void
    {
        if (! $this->db->fieldExists('keperluan_kegiatan', 'agendaris_souvenir_pengambilan')) {
            $this->forge->addColumn('agendaris_souvenir_pengambilan', [
                'keperluan_kegiatan' => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true, 'after' => 'tanggal_pengambilan'],
            ]);
        }
    }

    public function down(): void
    {
        if ($this->db->fieldExists('keperluan_kegiatan', 'agendaris_souvenir_pengambilan')) {
            $this->forge->dropColumn('agendaris_souvenir_pengambilan', 'keperluan_kegiatan');
        }
    }
}
