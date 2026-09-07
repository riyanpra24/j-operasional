<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use RuntimeException;

final class CreateSdmAttendanceRecap extends Migration
{
    public function up(): void
    {
        $importedByField = $this->userIdFieldDefinition();
        $importedByField['null'] = true;

        $this->forge->addField([
            'id'               => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'source_name'      => ['type' => 'VARCHAR', 'constraint' => 255],
            'source_hash'      => ['type' => 'CHAR', 'constraint' => 64],
            'period_start'     => ['type' => 'DATE'],
            'period_end'       => ['type' => 'DATE'],
            'employee_count'   => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'default' => 0],
            'row_count'        => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'default' => 0],
            'hadir_count'      => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'default' => 0],
            'izin_count'       => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'default' => 0],
            'alpa_count'       => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'default' => 0],
            'incomplete_count' => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'default' => 0],
            'off_count'        => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'default' => 0],
            'other_count'      => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'default' => 0],
            'warnings_json'    => ['type' => 'TEXT', 'null' => true],
            'imported_by'      => $importedByField,
            'imported_by_name' => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'created_at'       => ['type' => 'DATETIME', 'null' => true],
            'updated_at'       => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('source_hash');
        $this->forge->addKey(['period_start', 'created_at']);
        $this->forge->addForeignKey('imported_by', 'users', 'id', 'CASCADE', 'SET NULL');
        $this->forge->createTable('sdm_attendance_imports', true, ['ENGINE' => 'InnoDB']);

        $this->forge->addField([
            'id'              => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'import_id'       => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
            'employee_key'    => ['type' => 'VARCHAR', 'constraint' => 190],
            'employee_no'     => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true],
            'employee_name'   => ['type' => 'VARCHAR', 'constraint' => 150],
            'position'        => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'organization'    => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'attendance_date' => ['type' => 'DATE'],
            'shift'           => ['type' => 'VARCHAR', 'constraint' => 80, 'null' => true],
            'actual_in'       => ['type' => 'TIME', 'null' => true],
            'actual_out'      => ['type' => 'TIME', 'null' => true],
            'day_type'        => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'raw_status'      => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true],
            'other_status'    => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'remark'          => ['type' => 'TEXT', 'null' => true],
            'recap_code'      => ['type' => 'VARCHAR', 'constraint' => 10],
            'created_at'      => ['type' => 'DATETIME', 'null' => true],
            'updated_at'      => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['import_id', 'employee_key', 'attendance_date']);
        $this->forge->addKey(['import_id', 'employee_name', 'attendance_date']);
        $this->forge->addKey(['employee_no', 'attendance_date']);
        $this->forge->addForeignKey('import_id', 'sdm_attendance_imports', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('sdm_attendance_records', true, ['ENGINE' => 'InnoDB']);
    }

    public function down(): void
    {
        $this->forge->dropTable('sdm_attendance_records', true);
        $this->forge->dropTable('sdm_attendance_imports', true);
    }

    /**
     * Samakan tipe foreign key dengan users.id pada setiap lingkungan.
     * Database lama dapat memakai INT, sedangkan instalasi lain memakai BIGINT.
     *
     * @return array{type: string, unsigned: bool, constraint?: int}
     */
    private function userIdFieldDefinition(): array
    {
        $usersTable = $this->db->escapeIdentifiers($this->db->prefixTable('users'));
        $column = $this->db
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
            'type' => strtoupper($matches[1]),
            'unsigned' => str_contains($columnType, 'unsigned'),
        ];

        if (isset($matches[2]) && $matches[2] !== '') {
            $definition['constraint'] = (int) $matches[2];
        }

        return $definition;
    }
}
