<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

final class CreateSdmAttendanceCalendar extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'              => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'calendar_date'   => ['type' => 'DATE'],
            'is_holiday'      => ['type' => 'TINYINT', 'constraint' => 1, 'unsigned' => true, 'default' => 1],
            'label'           => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'updated_by'      => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'null' => true],
            'updated_by_name' => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'updated_by_role' => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true],
            'created_at'      => ['type' => 'DATETIME', 'null' => true],
            'updated_at'      => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('calendar_date');
        $this->forge->addKey(['is_holiday', 'calendar_date']);
        $this->forge->createTable('sdm_attendance_calendar', true, ['ENGINE' => 'InnoDB']);
    }

    public function down(): void
    {
        $this->forge->dropTable('sdm_attendance_calendar', true);
    }
}
