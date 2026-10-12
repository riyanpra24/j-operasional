<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

final class CreateAccountingLrRecapOverrides extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'unit_name' => ['type' => 'VARCHAR', 'constraint' => 80],
            'report_year' => ['type' => 'SMALLINT', 'unsigned' => true],
            'report_month' => ['type' => 'TINYINT', 'unsigned' => true],
            'values_json' => ['type' => 'LONGTEXT'],
            'updated_by_name' => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['unit_name', 'report_year', 'report_month'], 'lr_recap_unit_period');
        $this->forge->createTable('accounting_lr_recap_overrides', true, ['ENGINE' => 'InnoDB']);
    }

    public function down(): void
    {
        $this->forge->dropTable('accounting_lr_recap_overrides', true);
    }
}
