<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class SplitUmumUserRole extends Migration
{
    public function up()
    {
        $this->db->table('users')
            ->where('role', 'umum')
            ->update(['role' => 'umum_1']);
    }

    public function down()
    {
        $this->db->table('users')
            ->whereIn('role', ['umum_1', 'umum_2'])
            ->update(['role' => 'umum']);
    }
}
