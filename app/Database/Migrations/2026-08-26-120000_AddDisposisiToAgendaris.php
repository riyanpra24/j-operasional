<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddDisposisiToAgendaris extends Migration
{
    public function up()
    {
        $this->forge->addColumn('agendaris', [
            'disposisi_1' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
                'after'      => 'berkas_link',
            ],
            'disposisi_2' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
                'after'      => 'disposisi_1',
            ],
            'disposisi_3' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
                'after'      => 'disposisi_2',
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('agendaris', ['disposisi_1', 'disposisi_2', 'disposisi_3']);
    }
}
