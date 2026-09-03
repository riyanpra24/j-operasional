<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddSdmProcessingLockToAgendaris extends Migration
{
    public function up(): void
    {
        $fields = [];

        if (! $this->db->fieldExists('sdm_processed_at', 'agendaris')) {
            $fields['sdm_processed_at'] = [
                'type'  => 'DATETIME',
                'null'  => true,
                'after' => 'progres',
            ];
        }

        if (! $this->db->fieldExists('sdm_processed_by', 'agendaris')) {
            $fields['sdm_processed_by'] = [
                'type'       => 'VARCHAR',
                'constraint' => 150,
                'null'       => true,
                'after'      => 'sdm_processed_at',
            ];
        }

        if ($fields !== []) {
            $this->forge->addColumn('agendaris', $fields);
        }

        // Tahap 4 dan 5 hanya dapat dibuat oleh SDM & Teller. Data lama yang
        // sudah memiliki tahap tersebut langsung dianggap telah diproses.
        $this->db->query(
            "UPDATE agendaris SET sdm_processed_at = COALESCE(disposisi_5_waktu, disposisi_4_waktu, updated_at, NOW()), "
            . "sdm_processed_by = COALESCE(NULLIF(TRIM(sdm_processed_by), ''), 'SDM & Teller') "
            . "WHERE sdm_processed_at IS NULL AND (TRIM(COALESCE(disposisi_4, '')) <> '' OR TRIM(COALESCE(disposisi_5, '')) <> '')"
        );
    }

    public function down(): void
    {
        $fields = [];

        if ($this->db->fieldExists('sdm_processed_by', 'agendaris')) {
            $fields[] = 'sdm_processed_by';
        }

        if ($this->db->fieldExists('sdm_processed_at', 'agendaris')) {
            $fields[] = 'sdm_processed_at';
        }

        if ($fields !== []) {
            $this->forge->dropColumn('agendaris', $fields);
        }
    }
}
