<?php

namespace App\Database\Migrations;

use App\Libraries\LrSourceAdjustmentService;
use CodeIgniter\Database\Migration;

final class CreateAccountingLrSourceAdjustmentRequests extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'action' => ['type' => 'VARCHAR', 'constraint' => 20],
            'rule_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'payload_json' => ['type' => 'LONGTEXT'],
            'base_updated_at' => ['type' => 'DATETIME', 'null' => true],
            'status' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'pending'],
            'requested_by_name' => ['type' => 'VARCHAR', 'constraint' => 150],
            'requested_by_role' => ['type' => 'VARCHAR', 'constraint' => 30],
            'requested_at' => ['type' => 'DATETIME'],
            'reviewed_by_name' => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'reviewed_at' => ['type' => 'DATETIME', 'null' => true],
            'review_note' => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['status', 'requested_at'], false, false, 'lr_source_request_status');
        $this->forge->addKey(['rule_id', 'status'], false, false, 'lr_source_request_rule');
        $this->forge->createTable(LrSourceAdjustmentService::REQUEST_TABLE, true, ['ENGINE' => 'InnoDB']);
    }

    public function down(): void
    {
        $this->forge->dropTable(LrSourceAdjustmentService::REQUEST_TABLE, true);
    }
}
