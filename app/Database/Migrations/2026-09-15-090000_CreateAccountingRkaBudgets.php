<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

final class CreateAccountingRkaBudgets extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'unit_name' => ['type' => 'VARCHAR', 'constraint' => 80],
            'budget_year' => ['type' => 'SMALLINT', 'unsigned' => true],
            'template_version' => ['type' => 'VARCHAR', 'constraint' => 40],
            'inputs_json' => ['type' => 'LONGTEXT'],
            'calculated_json' => ['type' => 'LONGTEXT'],
            'source_type' => ['type' => 'VARCHAR', 'constraint' => 20],
            'source_name' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'source_hash' => ['type' => 'CHAR', 'constraint' => 64, 'null' => true],
            'revision' => ['type' => 'INT', 'unsigned' => true, 'default' => 1],
            'updated_by' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'updated_by_name' => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'created_at' => ['type' => 'DATETIME'],
            'updated_at' => ['type' => 'DATETIME'],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['unit_name', 'budget_year']);
        $this->forge->createTable('accounting_rka_budgets', true, ['ENGINE' => 'InnoDB']);
    }

    public function down(): void
    {
        $this->forge->dropTable('accounting_rka_budgets', true);
    }
}
