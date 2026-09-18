<?php

namespace App\Database\Migrations;

use App\Libraries\LrFormulaService;
use App\Libraries\OracleLrSalaryParser;
use CodeIgniter\Database\Migration;

final class CreateAccountingLrFormulaRules extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'unit_scope' => ['type' => 'VARCHAR', 'constraint' => 30],
            'column_scope' => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'all'],
            'target_label' => ['type' => 'VARCHAR', 'constraint' => 255],
            'target_label_normalized' => ['type' => 'VARCHAR', 'constraint' => 255],
            'terms_json' => ['type' => 'TEXT'],
            'priority' => ['type' => 'SMALLINT', 'unsigned' => true, 'default' => 500],
            'is_active' => ['type' => 'TINYINT', 'unsigned' => true, 'default' => 1],
            'updated_by_name' => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'created_at' => ['type' => 'DATETIME'],
            'updated_at' => ['type' => 'DATETIME'],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['unit_scope', 'column_scope', 'target_label_normalized'], 'lr_formula_unit_column_target');
        $this->forge->addKey(['is_active', 'priority']);
        $this->forge->createTable(LrFormulaService::TABLE, true, ['ENGINE' => 'InnoDB']);

        $now = date('Y-m-d H:i:s');
        $rows = [];
        foreach (LrFormulaService::defaultRules() as $rule) {
            $rows[] = [
                'unit_scope' => $rule['unit_scope'],
                'column_scope' => $rule['column_scope'],
                'target_label' => $rule['target_label'],
                'target_label_normalized' => OracleLrSalaryParser::normalizeLabel($rule['target_label']),
                'terms_json' => json_encode($rule['terms'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR),
                'priority' => $rule['priority'],
                'is_active' => $rule['is_active'],
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
        if ($rows) $this->db->table(LrFormulaService::TABLE)->insertBatch($rows);
    }

    public function down(): void
    {
        $this->forge->dropTable(LrFormulaService::TABLE, true);
    }
}
