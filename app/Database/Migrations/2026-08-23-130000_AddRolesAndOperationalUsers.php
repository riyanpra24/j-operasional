<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddRolesAndOperationalUsers extends Migration
{
    public function up()
    {
        if (! $this->db->fieldExists('role', 'users')) {
            $this->forge->addColumn('users', [
                'role' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 20,
                    'null'       => false,
                    'default'    => 'admin',
                    'after'      => 'display_name',
                ],
            ]);
        }

        $now      = date('Y-m-d H:i:s');
        $accounts = [
            [
                'username'     => 'admin',
                'password'     => 'Admin@12345',
                'display_name' => 'Administrator',
                'role'         => 'admin',
            ],
            [
                'username'     => 'security',
                'password'     => 'Security@12345',
                'display_name' => 'Petugas Security',
                'role'         => 'security',
            ],
            [
                'username'     => 'agendaris',
                'password'     => 'Agendaris@12345',
                'display_name' => 'Petugas Agendaris',
                'role'         => 'agendaris',
            ],
        ];

        $builder = $this->db->table('users');

        foreach ($accounts as $account) {
            $data = [
                'password_hash' => password_hash($account['password'], PASSWORD_DEFAULT),
                'display_name'  => $account['display_name'],
                'role'          => $account['role'],
                'updated_at'    => $now,
            ];

            $existing = $builder->where('username', $account['username'])->get()->getRowArray();

            if ($existing !== null) {
                $builder->where('id', $existing['id'])->update($data);
                continue;
            }

            $builder->insert([
                'username'   => $account['username'],
                'created_at' => $now,
                ...$data,
            ]);
        }
    }

    public function down()
    {
        $this->db->table('users')->whereIn('username', ['security', 'agendaris'])->delete();

        if ($this->db->fieldExists('role', 'users')) {
            $this->forge->dropColumn('users', 'role');
        }
    }
}
