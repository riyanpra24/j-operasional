<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddDispositionTrackingToAgendaris extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('agendaris', [
            'disposisi_1_status' => [
                'type'       => 'VARCHAR',
                'constraint' => 30,
                'null'       => true,
                'after'      => 'disposisi_1',
            ],
            'disposisi_1_waktu' => [
                'type'  => 'DATETIME',
                'null'  => true,
                'after' => 'disposisi_1_status',
            ],
            'disposisi_1_catatan' => [
                'type'  => 'TEXT',
                'null'  => true,
                'after' => 'disposisi_1_waktu',
            ],
            'disposisi_2_status' => [
                'type'       => 'VARCHAR',
                'constraint' => 30,
                'null'       => true,
                'after'      => 'disposisi_2',
            ],
            'disposisi_2_waktu' => [
                'type'  => 'DATETIME',
                'null'  => true,
                'after' => 'disposisi_2_status',
            ],
            'disposisi_2_catatan' => [
                'type'  => 'TEXT',
                'null'  => true,
                'after' => 'disposisi_2_waktu',
            ],
            'disposisi_3_status' => [
                'type'       => 'VARCHAR',
                'constraint' => 30,
                'null'       => true,
                'after'      => 'disposisi_3',
            ],
            'disposisi_3_waktu' => [
                'type'  => 'DATETIME',
                'null'  => true,
                'after' => 'disposisi_3_status',
            ],
            'disposisi_3_catatan' => [
                'type'  => 'TEXT',
                'null'  => true,
                'after' => 'disposisi_3_waktu',
            ],
        ]);

        // Beri status awal pada data lama tanpa menghilangkan nilai disposisinya.
        for ($step = 1; $step <= 3; $step++) {
            $nextConditions = [];
            for ($next = $step + 1; $next <= 3; $next++) {
                $nextConditions[] = "TRIM(COALESCE(disposisi_{$next}, '')) <> ''";
            }

            $status = $nextConditions === []
                ? "'Diproses'"
                : "IF(" . implode(' OR ', $nextConditions) . ", 'Diteruskan', 'Diproses')";

            $this->db->query(
                "UPDATE agendaris SET disposisi_{$step}_status = {$status}, "
                . "disposisi_{$step}_waktu = COALESCE(updated_at, created_at) "
                . "WHERE TRIM(COALESCE(disposisi_{$step}, '')) <> ''"
            );
        }
    }

    public function down(): void
    {
        $this->forge->dropColumn('agendaris', [
            'disposisi_1_status',
            'disposisi_1_waktu',
            'disposisi_1_catatan',
            'disposisi_2_status',
            'disposisi_2_waktu',
            'disposisi_2_catatan',
            'disposisi_3_status',
            'disposisi_3_waktu',
            'disposisi_3_catatan',
        ]);
    }
}
