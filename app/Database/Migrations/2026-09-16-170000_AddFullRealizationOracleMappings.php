<?php

namespace App\Database\Migrations;

use App\Libraries\LrReportRows;
use App\Libraries\OracleLrSalaryParser;
use CodeIgniter\Database\Migration;

final class AddFullRealizationOracleMappings extends Migration
{
    public function up(): void
    {
        if (!$this->db->tableExists('accounting_lr_account_mappings')) return;
        $now = date('Y-m-d H:i:s');
        foreach (LrReportRows::branchFormulaSourceMappings() as $mapping) {
            $source = (string) $mapping['source_description'];
            $normalized = OracleLrSalaryParser::normalizeLabel($source);
            $existing = $this->db->table('accounting_lr_account_mappings')->select('id')
                ->where('unit_scope', 'branch')->where('source_description_normalized', $normalized)->get()->getRowArray();
            $values = [
                'source_description' => $source,
                'source_description_normalized' => $normalized,
                'report_label' => (string) $mapping['report_label'],
                'sign_mode' => (string) $mapping['sign_mode'],
                'is_active' => 1,
                'updated_by_name' => 'System',
                'updated_at' => $now,
            ];
            if ($existing === null) {
                $this->db->table('accounting_lr_account_mappings')->insert($values + ['unit_scope' => 'branch', 'created_at' => $now]);
            } else {
                $this->db->table('accounting_lr_account_mappings')->where('id', (int) $existing['id'])->update($values);
            }
        }
        foreach (['Pendapatan jasa giro', 'Pendapatan lainnya'] as $source) {
            $this->db->table('accounting_lr_account_mappings')
                ->where('source_description_normalized', OracleLrSalaryParser::normalizeLabel($source))
                ->update(['sign_mode' => 'invert', 'updated_by_name' => 'System', 'updated_at' => $now]);
        }
    }

    public function down(): void
    {
        if (!$this->db->tableExists('accounting_lr_account_mappings')) return;
        $keep = [
            OracleLrSalaryParser::normalizeLabel('Pendapatan premi penjaminan kredit'),
            OracleLrSalaryParser::normalizeLabel('Beban Pajak PPh 21 Non Karywan'),
        ];
        foreach (LrReportRows::branchFormulaSourceMappings() as $mapping) {
            $normalized = OracleLrSalaryParser::normalizeLabel((string) $mapping['source_description']);
            if (in_array($normalized, $keep, true)) continue;
            $this->db->table('accounting_lr_account_mappings')->where('unit_scope', 'branch')
                ->where('source_description_normalized', $normalized)->delete();
        }
        foreach (['Pendapatan jasa giro', 'Pendapatan lainnya'] as $source) {
            $this->db->table('accounting_lr_account_mappings')
                ->where('source_description_normalized', OracleLrSalaryParser::normalizeLabel($source))
                ->update(['sign_mode' => 'invert_kanwil', 'updated_at' => date('Y-m-d H:i:s')]);
        }
    }
}
