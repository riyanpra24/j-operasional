<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

final class AddAttendanceWorkUnitAndReportingPeriod extends Migration
{
    public function up(): void
    {
        if (! $this->db->tableExists('sdm_attendance_imports')) {
            return;
        }

        $fields = [];
        if (! $this->db->fieldExists('work_unit', 'sdm_attendance_imports')) {
            $fields['work_unit'] = [
                'type'       => 'VARCHAR',
                'constraint' => 150,
                'default'    => 'Kantor Wilayah Surabaya',
                'after'      => 'source_hash',
            ];
        }
        if (! $this->db->fieldExists('report_month', 'sdm_attendance_imports')) {
            $fields['report_month'] = [
                'type'       => 'TINYINT',
                'constraint' => 2,
                'unsigned'   => true,
                'default'    => 1,
                'after'      => 'period_end',
            ];
        }
        if (! $this->db->fieldExists('report_year', 'sdm_attendance_imports')) {
            $fields['report_year'] = [
                'type'       => 'SMALLINT',
                'constraint' => 4,
                'unsigned'   => true,
                'default'    => 2026,
                'after'      => 'report_month',
            ];
        }
        if ($fields !== []) {
            $this->forge->addColumn('sdm_attendance_imports', $fields);
        }

        $this->db->query(
            'UPDATE sdm_attendance_imports
             SET report_month = MONTH(period_start), report_year = YEAR(period_start)'
        );
        $this->forge->addKey(['work_unit', 'report_year', 'report_month']);
        $this->forge->processIndexes('sdm_attendance_imports');
    }

    public function down(): void
    {
        if (! $this->db->tableExists('sdm_attendance_imports')) {
            return;
        }

        foreach (['work_unit', 'report_month', 'report_year'] as $field) {
            if ($this->db->fieldExists($field, 'sdm_attendance_imports')) {
                $this->forge->dropColumn('sdm_attendance_imports', $field);
            }
        }
    }
}
