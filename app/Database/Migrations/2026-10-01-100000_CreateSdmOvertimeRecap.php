<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

final class CreateSdmOvertimeRecap extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'source_name' => ['type' => 'VARCHAR', 'constraint' => 255],
            'source_hash' => ['type' => 'CHAR', 'constraint' => 64],
            'period_start' => ['type' => 'DATE'], 'period_end' => ['type' => 'DATE'],
            'employee_count' => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'default' => 0],
            'row_count' => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'default' => 0],
            'total_minutes' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'default' => 0],
            'imported_by' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'null' => true],
            'imported_by_name' => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true], 'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true); $this->forge->addUniqueKey('source_hash'); $this->forge->addKey(['period_start', 'created_at']);
        $this->forge->createTable('sdm_overtime_imports', true, ['ENGINE' => 'InnoDB']);

        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'import_id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
            'employee_key' => ['type' => 'VARCHAR', 'constraint' => 190], 'employee_no' => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true],
            'employee_name' => ['type' => 'VARCHAR', 'constraint' => 150], 'organization' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'overtime_date' => ['type' => 'DATE'], 'shift' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'actual_in' => ['type' => 'TIME', 'null' => true], 'actual_out' => ['type' => 'TIME', 'null' => true],
            'overtime_start' => ['type' => 'TIME', 'null' => true], 'overtime_end' => ['type' => 'TIME', 'null' => true],
            'overtime_minutes' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'default' => 0], 'overtime_hours' => ['type' => 'DECIMAL', 'constraint' => '10,2', 'null' => true],
            'request_no' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true], 'request_date' => ['type' => 'DATE', 'null' => true],
            'overtime_type' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true], 'reason' => ['type' => 'TEXT', 'null' => true], 'remark' => ['type' => 'TEXT', 'null' => true], 'hour_type' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'request_start' => ['type' => 'DATETIME', 'null' => true], 'request_end' => ['type' => 'DATETIME', 'null' => true], 'request_status' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true], 'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true); $this->forge->addUniqueKey(['import_id', 'employee_key', 'overtime_date', 'request_no']); $this->forge->addKey(['import_id', 'overtime_date', 'employee_name']);
        $this->forge->addForeignKey('import_id', 'sdm_overtime_imports', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('sdm_overtime_records', true, ['ENGINE' => 'InnoDB']);
    }

    public function down(): void
    {
        $this->forge->dropTable('sdm_overtime_records', true); $this->forge->dropTable('sdm_overtime_imports', true);
    }
}
