<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddNomorTanggalAgendarisToAgendaris extends Migration
{
    public function up()
    {
        $this->forge->addColumn('agendaris', [
            'nomor_agendaris' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => true,
                'after'      => 'nomor_surat',
            ],
            'tanggal_agendaris' => [
                'type'  => 'DATE',
                'null'  => true,
                'after' => 'nomor_agendaris',
            ],
        ]);

        $this->forge->addUniqueKey('nomor_agendaris', 'agendaris_nomor_agendaris_unique');
        $this->forge->addKey('tanggal_agendaris', false, false, 'agendaris_tanggal_agendaris_index');
        $this->forge->processIndexes('agendaris');
    }

    public function down()
    {
        $this->forge->dropKey('agendaris', 'agendaris_nomor_agendaris_unique');
        $this->forge->dropKey('agendaris', 'agendaris_tanggal_agendaris_index');
        $this->forge->dropColumn('agendaris', ['nomor_agendaris', 'tanggal_agendaris']);
    }
}
