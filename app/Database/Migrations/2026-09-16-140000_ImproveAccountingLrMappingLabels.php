<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

final class ImproveAccountingLrMappingLabels extends Migration
{
    public function up(): void
    {
        if (!$this->db->tableExists('accounting_lr_account_mappings')) return;
        $rows=$this->db->table('accounting_lr_account_mappings')->select('id, source_description_normalized, report_label')->get()->getResultArray();
        foreach ($rows as $row) {
            $labelNormalized=\App\Libraries\OracleLrSalaryParser::normalizeLabel((string)$row['report_label']);
            if ($labelNormalized!==(string)$row['source_description_normalized']) continue;
            $this->db->table('accounting_lr_account_mappings')->where('id',(int)$row['id'])->update(['source_description'=>$row['report_label']]);
        }
    }

    public function down(): void
    {
        // Display capitalization has no effect on matching and does not need reversal.
    }
}
