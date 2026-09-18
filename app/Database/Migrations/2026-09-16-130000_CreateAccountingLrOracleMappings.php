<?php

namespace App\Database\Migrations;

use App\Libraries\OracleLrMappingService;
use CodeIgniter\Database\Migration;

final class CreateAccountingLrOracleMappings extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'source_lob' => ['type' => 'VARCHAR', 'constraint' => 80],
            'description_lob_contains' => ['type' => 'VARCHAR', 'constraint' => 160, 'null' => true],
            'target_column' => ['type' => 'VARCHAR', 'constraint' => 40],
            'priority' => ['type' => 'SMALLINT', 'unsigned' => true, 'default' => 100],
            'is_active' => ['type' => 'TINYINT', 'unsigned' => true, 'default' => 1],
            'updated_by_name' => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'created_at' => ['type' => 'DATETIME'],
            'updated_at' => ['type' => 'DATETIME'],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['source_lob', 'priority']);
        $this->forge->createTable('accounting_lr_lob_mappings', true, ['ENGINE' => 'InnoDB']);

        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'unit_scope' => ['type' => 'VARCHAR', 'constraint' => 20],
            'source_description' => ['type' => 'VARCHAR', 'constraint' => 255],
            'source_description_normalized' => ['type' => 'VARCHAR', 'constraint' => 255],
            'report_label' => ['type' => 'VARCHAR', 'constraint' => 255],
            'sign_mode' => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'keep'],
            'is_active' => ['type' => 'TINYINT', 'unsigned' => true, 'default' => 1],
            'updated_by_name' => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'created_at' => ['type' => 'DATETIME'],
            'updated_at' => ['type' => 'DATETIME'],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['unit_scope', 'source_description_normalized'], 'lr_account_mapping_scope_source');
        $this->forge->addKey(['report_label', 'is_active']);
        $this->forge->createTable('accounting_lr_account_mappings', true, ['ENGINE' => 'InnoDB']);

        $now = date('Y-m-d H:i:s');
        $lobRows = array_map(static fn (array $row): array => $row + ['created_at' => $now, 'updated_at' => $now], OracleLrMappingService::defaultLobMappings());
        $accountRows = array_map(static fn (array $row): array => $row + ['created_at' => $now, 'updated_at' => $now], OracleLrMappingService::defaultAccountMappings());
        if ($lobRows) $this->db->table('accounting_lr_lob_mappings')->insertBatch($lobRows);
        if ($accountRows) $this->db->table('accounting_lr_account_mappings')->insertBatch($accountRows);
    }

    public function down(): void
    {
        $this->forge->dropTable('accounting_lr_account_mappings', true);
        $this->forge->dropTable('accounting_lr_lob_mappings', true);
    }
}
