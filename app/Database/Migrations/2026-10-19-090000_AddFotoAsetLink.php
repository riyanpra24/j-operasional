<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddFotoAsetLink extends Migration
{
    public function up()
    {
        if (! $this->db->fieldExists('foto_aset', 'data_aset')) {
            $this->forge->addColumn('data_aset', ['foto_aset' => ['type' => 'TEXT', 'null' => true]]);
        }
    }

    public function down()
    {
        if ($this->db->fieldExists('foto_aset', 'data_aset')) {
            $this->forge->dropColumn('data_aset', 'foto_aset');
        }
    }
}
