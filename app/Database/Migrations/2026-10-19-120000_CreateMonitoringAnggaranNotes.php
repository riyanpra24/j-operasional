<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

final class CreateMonitoringAnggaranNotes extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'unit_name' => ['type' => 'VARCHAR', 'constraint' => 80],
            'report_year' => ['type' => 'SMALLINT', 'unsigned' => true],
            'report_month' => ['type' => 'TINYINT', 'unsigned' => true],
            'row_key' => ['type' => 'VARCHAR', 'constraint' => 24],
            'explanation' => ['type' => 'TEXT'],
            'updated_by_name' => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'updated_at' => ['type' => 'DATETIME'],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['unit_name', 'report_year', 'report_month', 'row_key'], 'monitoring_note_period_row');
        $this->forge->createTable('accounting_monitoring_notes', true, ['ENGINE' => 'InnoDB']);
    }

    public function down(): void
    {
        $this->forge->dropTable('accounting_monitoring_notes', true);
    }
}
