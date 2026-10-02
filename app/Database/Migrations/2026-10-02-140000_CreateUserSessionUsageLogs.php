<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use RuntimeException;

final class CreateUserSessionUsageLogs extends Migration
{
    public function up(): void
    {
        if (! $this->db->tableExists('user_session_usage_logs')) {
            $this->forge->addField([
                'id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
                'user_id' => $this->userIdFieldDefinition(),
                'started_at' => ['type' => 'DATETIME'],
                'last_seen_at' => ['type' => 'DATETIME'],
                'ended_at' => ['type' => 'DATETIME', 'null' => true],
                'created_at' => ['type' => 'DATETIME', 'null' => true],
                'updated_at' => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addKey('user_id');
            $this->forge->addKey('last_seen_at');
            $this->forge->addForeignKey('user_id', 'users', 'id', 'CASCADE', 'CASCADE');
            $this->forge->createTable('user_session_usage_logs', true, ['ENGINE' => 'InnoDB']);
        }

        if (! $this->db->tableExists('user_sessions')) {
            return;
        }

        $sessions = $this->db->table('user_sessions')->get()->getResultArray();
        foreach ($sessions as $session) {
            $exists = $this->db->table('user_session_usage_logs')
                ->where('user_id', (int) $session['user_id'])
                ->where('ended_at IS NULL', null, false)
                ->countAllResults() > 0;
            if ($exists) {
                continue;
            }

            $this->db->table('user_session_usage_logs')->insert([
                'user_id'      => (int) $session['user_id'],
                'started_at'   => (string) $session['created_at'],
                'last_seen_at' => (string) $session['last_seen_at'],
                'ended_at'     => null,
                'created_at'   => (string) $session['created_at'],
                'updated_at'   => (string) $session['updated_at'],
            ]);
        }
    }

    public function down(): void
    {
        $this->forge->dropTable('user_session_usage_logs', true);
    }

    private function userIdFieldDefinition(): array
    {
        $usersTable = $this->db->escapeIdentifiers($this->db->prefixTable('users'));
        $column = $this->db->query("SHOW COLUMNS FROM {$usersTable} WHERE Field = 'id'")->getRowArray();
        if ($column === null || ! isset($column['Type'])) {
            throw new RuntimeException('Kolom users.id tidak ditemukan.');
        }

        $type = strtolower((string) $column['Type']);
        if (! preg_match('/^(tinyint|smallint|mediumint|int|bigint)(?:\((\d+)\))?/', $type, $matches)) {
            throw new RuntimeException('Tipe kolom users.id tidak didukung: ' . $type);
        }

        $definition = ['type' => strtoupper($matches[1]), 'unsigned' => str_contains($type, 'unsigned')];
        if (isset($matches[2]) && $matches[2] !== '') {
            $definition['constraint'] = (int) $matches[2];
        }

        return $definition;
    }
}
