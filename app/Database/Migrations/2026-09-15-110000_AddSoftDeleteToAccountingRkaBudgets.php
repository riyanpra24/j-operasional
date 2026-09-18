<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use RuntimeException;

final class AddSoftDeleteToAccountingRkaBudgets extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('accounting_rka_budgets', [
            'deleted_at' => ['type' => 'DATETIME', 'null' => true],
            'deleted_by_role' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'deleted_by_name' => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            // NULL permits multiple archived budgets; 1 permits only one active budget.
            'active_slot' => ['type' => 'TINYINT', 'unsigned' => true, 'null' => true, 'default' => 1],
        ]);
        $this->forge->dropKey('accounting_rka_budgets', 'unit_name_budget_year');
        $this->forge->addUniqueKey(['unit_name', 'budget_year', 'active_slot'], 'rka_unit_year_active');
        $this->forge->processIndexes('accounting_rka_budgets');
    }

    public function down(): void
    {
        if ($this->db->query('SELECT unit_name FROM accounting_rka_budgets GROUP BY unit_name,budget_year HAVING COUNT(*) > 1 LIMIT 1')->getRowArray() !== null) {
            throw new RuntimeException('Rollback RKA dibatalkan: ada beberapa arsip unit/tahun yang sama. Cadangkan dan selesaikan arsip sebelum rollback.');
        }
        $this->forge->dropKey('accounting_rka_budgets', 'rka_unit_year_active');
        $this->forge->dropColumn('accounting_rka_budgets', ['deleted_at', 'deleted_by_role', 'deleted_by_name', 'active_slot']);
        $this->forge->addUniqueKey(['unit_name', 'budget_year']);
        $this->forge->processIndexes('accounting_rka_budgets');
    }
}
