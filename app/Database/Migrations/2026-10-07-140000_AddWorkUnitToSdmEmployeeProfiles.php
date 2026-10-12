<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

final class AddWorkUnitToSdmEmployeeProfiles extends Migration
{
    public function up(): void
    {
        if (! $this->db->fieldExists('work_unit', 'sdm_employee_profiles')) {
            $this->forge->addColumn('sdm_employee_profiles', [
                'work_unit' => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true, 'after' => 'gender'],
            ]);
            $this->forge->addKey('work_unit');
            $this->forge->processIndexes('sdm_employee_profiles');
        }

        $this->db->query("UPDATE sdm_employee_profiles AS profiles
            INNER JOIN (
                SELECT records.employee_no, MAX(imports.work_unit) AS work_unit
                FROM sdm_attendance_records AS records
                INNER JOIN sdm_attendance_imports AS imports ON imports.id = records.import_id
                WHERE records.employee_no IS NOT NULL AND records.employee_no <> ''
                  AND imports.work_unit IS NOT NULL AND imports.work_unit <> ''
                GROUP BY records.employee_no
            ) AS attendance ON attendance.employee_no = profiles.employee_no
            SET profiles.work_unit = attendance.work_unit
            WHERE profiles.work_unit IS NULL OR profiles.work_unit = ''");
    }

    public function down(): void
    {
        if ($this->db->fieldExists('work_unit', 'sdm_employee_profiles')) {
            $this->forge->dropColumn('sdm_employee_profiles', 'work_unit');
        }
    }
}
