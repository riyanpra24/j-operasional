<?php

namespace App\Database\Migrations;

use App\Libraries\OracleLrSalaryParser;
use CodeIgniter\Database\Migration;

final class AddBranchSubrogationTaxOracleMapping extends Migration
{
    private const SOURCE = 'Beban Pajak PPh 21 Non Karywan';
    private const TARGET = 'Pendapatan Subrogasi';

    public function up(): void
    {
        if (!$this->db->tableExists('accounting_lr_account_mappings')) return;

        $normalized = OracleLrSalaryParser::normalizeLabel(self::SOURCE);
        $existing = $this->db->table('accounting_lr_account_mappings')
            ->select('id')
            ->where('unit_scope', 'branch')
            ->where('source_description_normalized', $normalized)
            ->get()->getRowArray();
        $values = [
            'source_description' => self::SOURCE,
            'source_description_normalized' => $normalized,
            'report_label' => self::TARGET,
            'sign_mode' => 'invert',
            'is_active' => 1,
            'updated_by_name' => 'System',
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        if ($existing !== null) {
            $this->db->table('accounting_lr_account_mappings')->where('id', (int) $existing['id'])->update($values);
            return;
        }
        $values['unit_scope'] = 'branch';
        $values['created_at'] = date('Y-m-d H:i:s');
        $this->db->table('accounting_lr_account_mappings')->insert($values);
    }

    public function down(): void
    {
        if (!$this->db->tableExists('accounting_lr_account_mappings')) return;
        $this->db->table('accounting_lr_account_mappings')
            ->where('unit_scope', 'branch')
            ->where('source_description_normalized', OracleLrSalaryParser::normalizeLabel(self::SOURCE))
            ->where('report_label', self::TARGET)
            ->where('sign_mode', 'invert')
            ->delete();
    }
}
