<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

final class CreateSdmAttendanceRecap extends Migration
{
    public function up(): void
    {
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
            'imported_by'      => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'null' => true],
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
}
