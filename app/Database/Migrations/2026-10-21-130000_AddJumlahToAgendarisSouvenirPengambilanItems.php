<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

final class AddJumlahToAgendarisSouvenirPengambilanItems extends Migration
{
    public function up(): void
    {
        if (! $this->db->fieldExists('jumlah_diambil', 'agendaris_souvenir_pengambilan_item')) {
            $this->forge->addColumn('agendaris_souvenir_pengambilan_item', [
                'jumlah_diambil' => ['type' => 'INT', 'unsigned' => true, 'default' => 1, 'after' => 'souvenir_id'],
            ]);
        }
    }

    public function down(): void
    {
        if ($this->db->fieldExists('jumlah_diambil', 'agendaris_souvenir_pengambilan_item')) {
            $this->forge->dropColumn('agendaris_souvenir_pengambilan_item', 'jumlah_diambil');
        }
    }
}
