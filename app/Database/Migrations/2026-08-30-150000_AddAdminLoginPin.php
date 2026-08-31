<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddAdminLoginPin extends Migration
{
    public function up(): void
    {
        if ($this->db->fieldExists('admin_login_pin_hash', 'users')) {
            return;
        }

        $this->forge->addColumn('users', [
            'admin_login_pin_hash' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
                'after' => 'password_hash',
            ],
        ]);
    }

    public function down(): void
    {
        if ($this->db->fieldExists('admin_login_pin_hash', 'users')) {
            $this->forge->dropColumn('users', 'admin_login_pin_hash');
        }
    }
}
