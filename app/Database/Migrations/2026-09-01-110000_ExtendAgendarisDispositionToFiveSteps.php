<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class ExtendAgendarisDispositionToFiveSteps extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('agendaris', [
            'disposisi_4' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
                'after'      => 'disposisi_3_catatan',
            ],
            'disposisi_4_status' => [
                'type'       => 'VARCHAR',
                'constraint' => 30,
                'null'       => true,
                'after'      => 'disposisi_4',
            ],
            'disposisi_4_waktu' => [
                'type'  => 'DATETIME',
                'null'  => true,
                'after' => 'disposisi_4_status',
            ],
            'disposisi_4_catatan' => [
                'type'  => 'TEXT',
                'null'  => true,
                'after' => 'disposisi_4_waktu',
            ],
            'disposisi_5' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
                'after'      => 'disposisi_4_catatan',
            ],
            'disposisi_5_status' => [
                'type'       => 'VARCHAR',
                'constraint' => 30,
                'null'       => true,
                'after'      => 'disposisi_5',
            ],
            'disposisi_5_waktu' => [
                'type'  => 'DATETIME',
                'null'  => true,
                'after' => 'disposisi_5_status',
            ],
            'disposisi_5_catatan' => [
                'type'  => 'TEXT',
                'null'  => true,
                'after' => 'disposisi_5_waktu',
            ],
        ]);
    }

    public function down(): void
    {
        $this->forge->dropColumn('agendaris', [
            'disposisi_4',
            'disposisi_4_status',
            'disposisi_4_waktu',
            'disposisi_4_catatan',
            'disposisi_5',
            'disposisi_5_status',
            'disposisi_5_waktu',
            'disposisi_5_catatan',
        ]);
    }
}
