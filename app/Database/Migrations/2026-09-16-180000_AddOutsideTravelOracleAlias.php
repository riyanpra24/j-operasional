<?php

namespace App\Database\Migrations;

use App\Libraries\OracleLrSalaryParser;
use CodeIgniter\Database\Migration;

final class AddOutsideTravelOracleAlias extends Migration
{
    public function up(): void
    {
        if (!$this->db->tableExists('accounting_lr_account_mappings')) return;
        $source = 'Tranportasi dinas luar negeri';
        $normalized = OracleLrSalaryParser::normalizeLabel($source);
        $existing = $this->db->table('accounting_lr_account_mappings')->select('id')
            ->where('unit_scope', 'all')->where('source_description_normalized', $normalized)->get()->getRowArray();
        $values = [
            'source_description' => $source,
            'source_description_normalized' => $normalized,
            'report_label' => 'Transportasi dinas luar negeri',
            'sign_mode' => 'keep',
            'is_active' => 1,
            'updated_by_name' => 'System',
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        if ($existing === null) {
            $this->db->table('accounting_lr_account_mappings')->insert($values + [
                'unit_scope' => 'all',
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        } else {
            $this->db->table('accounting_lr_account_mappings')->where('id', (int) $existing['id'])->update($values);
        }
    }

    public function down(): void
    {
        if (!$this->db->tableExists('accounting_lr_account_mappings')) return;
        $this->db->table('accounting_lr_account_mappings')
            ->where('unit_scope', 'all')
            ->where('source_description_normalized', OracleLrSalaryParser::normalizeLabel('Tranportasi dinas luar negeri'))
            ->delete();
    }
}
