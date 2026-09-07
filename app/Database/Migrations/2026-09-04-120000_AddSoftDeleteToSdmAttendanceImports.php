<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

final class AddSoftDeleteToSdmAttendanceImports extends Migration
{
    public function up(): void
    {
        $fields = [];
        if (! $this->db->fieldExists('deleted_at', 'sdm_attendance_imports')) {
            $fields['deleted_at'] = ['type' => 'DATETIME', 'null' => true, 'after' => 'updated_at'];
        }
        if (! $this->db->fieldExists('deleted_by_role', 'sdm_attendance_imports')) {
            $fields['deleted_by_role'] = ['type' => 'VARCHAR', 'constraint' => 40, 'null' => true, 'after' => 'deleted_at'];
        }
        if (! $this->db->fieldExists('deleted_by_name', 'sdm_attendance_imports')) {
            $fields['deleted_by_name'] = ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true, 'after' => 'deleted_by_role'];
        }

        if ($fields !== []) {
            $this->forge->addColumn('sdm_attendance_imports', $fields);
        }
    }

    public function down(): void
    {
        foreach (['deleted_by_name', 'deleted_by_role', 'deleted_at'] as $field) {
            if ($this->db->fieldExists($field, 'sdm_attendance_imports')) {
                $this->forge->dropColumn('sdm_attendance_imports', $field);
            }
        }
    }
}
