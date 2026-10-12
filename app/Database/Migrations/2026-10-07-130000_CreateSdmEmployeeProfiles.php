<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

final class CreateSdmEmployeeProfiles extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'employee_no' => ['type' => 'VARCHAR', 'constraint' => 30],
            'employee_name' => ['type' => 'VARCHAR', 'constraint' => 150],
            'gender' => ['type' => 'VARCHAR', 'constraint' => 1, 'null' => true],
            'organization' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'position' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'employment_status' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'employment_date' => ['type' => 'DATE', 'null' => true],
            'phone_number' => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true],
            'corporate_email' => ['type' => 'VARCHAR', 'constraint' => 190, 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('employee_no', 'sdm_employee_profiles_npp');
        $this->forge->addKey('employee_name');
        $this->forge->createTable('sdm_employee_profiles', true, ['ENGINE' => 'InnoDB']);

        $this->db->query("INSERT IGNORE INTO sdm_employee_profiles (employee_no, employee_name, organization, position, created_at, updated_at)
            SELECT employee_no, MAX(employee_name), MAX(organization), MAX(position), NOW(), NOW()
            FROM sdm_attendance_records
            WHERE employee_no IS NOT NULL AND employee_no <> ''
            GROUP BY employee_no");
    }

    public function down(): void
    {
        $this->forge->dropTable('sdm_employee_profiles', true);
    }
}
