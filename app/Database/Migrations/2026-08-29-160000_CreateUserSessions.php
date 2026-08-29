<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use RuntimeException;

class CreateUserSessions extends Migration
{
    public function up()
    {
        $userIdField = $this->userIdFieldDefinition();

        $this->forge->addField([
            'id' => [
                'type'           => 'BIGINT',
                'constraint'     => 20,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'user_id' => $userIdField,
            'token_hash' => [
                'type'       => 'CHAR',
                'constraint' => 64,
            ],
            'ip_address' => [
                'type'       => 'VARCHAR',
                'constraint' => 45,
                'null'       => true,
            ],
            'user_agent' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'last_seen_at' => [
                'type' => 'DATETIME',
            ],
            'expires_at' => [
                'type' => 'DATETIME',
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('user_id');
        $this->forge->addKey('expires_at');
        $this->forge->addForeignKey('user_id', 'users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('user_sessions', true, ['ENGINE' => 'InnoDB']);
    }

    public function down()
    {
        $this->forge->dropTable('user_sessions', true);
    }

    /**
     * Samakan tipe foreign key dengan users.id pada setiap lingkungan.
     * Database lama dapat memakai INT, sedangkan instalasi lokal tertentu memakai BIGINT.
     *
     * @return array{type: string, unsigned: bool, constraint?: int}
     */
    private function userIdFieldDefinition(): array
    {
        $usersTable = $this->db->escapeIdentifiers($this->db->prefixTable('users'));
        $column     = $this->db
            ->query("SHOW COLUMNS FROM {$usersTable} WHERE Field = 'id'")
            ->getRowArray();

        if ($column === null || ! isset($column['Type'])) {
            throw new RuntimeException('Kolom users.id tidak ditemukan.');
        }

        $columnType = strtolower((string) $column['Type']);

        if (! preg_match('/^(tinyint|smallint|mediumint|int|bigint)(?:\((\d+)\))?/', $columnType, $matches)) {
            throw new RuntimeException('Tipe kolom users.id tidak didukung: ' . $columnType);
        }

        $definition = [
            'type'     => strtoupper($matches[1]),
            'unsigned' => str_contains($columnType, 'unsigned'),
        ];

        if (isset($matches[2]) && $matches[2] !== '') {
            $definition['constraint'] = (int) $matches[2];
        }

        return $definition;
    }
}
