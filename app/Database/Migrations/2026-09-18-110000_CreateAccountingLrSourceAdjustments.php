<?php

namespace App\Database\Migrations;

use App\Libraries\LrSourceAdjustmentService;
use CodeIgniter\Database\Migration;

final class CreateAccountingLrSourceAdjustments extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'unit_scope' => ['type' => 'VARCHAR', 'constraint' => 30],
            'column_scope' => ['type' => 'VARCHAR', 'constraint' => 30],
            'target_label' => ['type' => 'VARCHAR', 'constraint' => 255],
            'target_label_normalized' => ['type' => 'VARCHAR', 'constraint' => 255],
            'terms_json' => ['type' => 'TEXT'],
            'effective_from' => ['type' => 'DATE'],
            'effective_to' => ['type' => 'DATE'],
            'reason' => ['type' => 'VARCHAR', 'constraint' => 500],
            'is_active' => ['type' => 'TINYINT', 'unsigned' => true, 'default' => 1],
            'updated_by_name' => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'created_at' => ['type' => 'DATETIME'],
            'updated_at' => ['type' => 'DATETIME'],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['is_active', 'effective_from', 'effective_to'], false, false, 'lr_source_adjustment_period');
        $this->forge->addKey(['unit_scope', 'column_scope', 'target_label_normalized'], false, false, 'lr_source_adjustment_target');
        $this->forge->createTable(LrSourceAdjustmentService::TABLE, true, ['ENGINE' => 'InnoDB']);

        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'rule_id' => ['type' => 'BIGINT', 'unsigned' => true],
            'action' => ['type' => 'VARCHAR', 'constraint' => 30],
            'snapshot_json' => ['type' => 'LONGTEXT'],
            'actor_name' => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'created_at' => ['type' => 'DATETIME'],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['rule_id', 'created_at']);
        $this->forge->createTable(LrSourceAdjustmentService::AUDIT_TABLE, true, ['ENGINE' => 'InnoDB']);
    }

    public function down(): void
    {
        $this->forge->dropTable(LrSourceAdjustmentService::AUDIT_TABLE, true);
        $this->forge->dropTable(LrSourceAdjustmentService::TABLE, true);
    }
}
