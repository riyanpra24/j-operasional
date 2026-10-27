<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddSatuanToAgendarisAtkKantor extends Migration
{
    public function up(): void
    {
        if ($this->db->fieldExists('satuan', 'agendaris_atk_kantor')) return;
        $this->forge->addColumn('agendaris_atk_kantor', [
            'satuan' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'Pcs', 'after' => 'jumlah'],
        ]);
    }

    public function down(): void
    {
        if ($this->db->fieldExists('satuan', 'agendaris_atk_kantor')) $this->forge->dropColumn('agendaris_atk_kantor', 'satuan');
    }
}
