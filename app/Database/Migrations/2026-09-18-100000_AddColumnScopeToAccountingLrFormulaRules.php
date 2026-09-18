<?php

namespace App\Database\Migrations;

use App\Libraries\LrFormulaService;
use CodeIgniter\Database\Migration;

final class AddColumnScopeToAccountingLrFormulaRules extends Migration
{
    public function up(): void
    {
        if (!$this->db->tableExists(LrFormulaService::TABLE)) return;
        if (!$this->db->fieldExists('column_scope', LrFormulaService::TABLE)) {
            $this->forge->addColumn(LrFormulaService::TABLE, [
                'column_scope' => [
                    'type' => 'VARCHAR',
                    'constraint' => 30,
                    'default' => 'all',
                    'after' => 'unit_scope',
                ],
            ]);
        }
        $indexes = $this->db->getIndexData(LrFormulaService::TABLE);
        if (isset($indexes['lr_formula_scope_target'])) {
            $this->forge->dropKey(LrFormulaService::TABLE, 'lr_formula_scope_target');
        }
        if (!isset($indexes['lr_formula_unit_column_target'])) {
            $this->db->query('ALTER TABLE `' . LrFormulaService::TABLE . '` ADD UNIQUE KEY `lr_formula_unit_column_target` (`unit_scope`,`column_scope`,`target_label_normalized`)');
        }
    }

    public function down(): void
    {
        if (!$this->db->tableExists(LrFormulaService::TABLE) || !$this->db->fieldExists('column_scope', LrFormulaService::TABLE)) return;
        $indexes = $this->db->getIndexData(LrFormulaService::TABLE);
        if (isset($indexes['lr_formula_unit_column_target'])) {
            $this->forge->dropKey(LrFormulaService::TABLE, 'lr_formula_unit_column_target');
        }
        $this->db->query('ALTER TABLE `' . LrFormulaService::TABLE . '` ADD UNIQUE KEY `lr_formula_scope_target` (`unit_scope`,`target_label_normalized`)');
        $this->forge->dropColumn(LrFormulaService::TABLE, 'column_scope');
    }
}
