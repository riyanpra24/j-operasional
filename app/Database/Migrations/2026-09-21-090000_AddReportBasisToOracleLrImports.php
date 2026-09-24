<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

final class AddReportBasisToOracleLrImports extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('accounting_lr_imports', [
            'report_basis' => [
                'type' => 'VARCHAR',
                'constraint' => 3,
                'default' => 'YTD',
                'after' => 'report_month',
            ],
        ]);
        $this->db->query('CREATE INDEX accounting_lr_import_basis_period ON accounting_lr_imports (report_basis, report_year, report_month, unit_name)');
    }

    public function down(): void
    {
        $this->db->query('DROP INDEX accounting_lr_import_basis_period ON accounting_lr_imports');
        $this->forge->dropColumn('accounting_lr_imports', 'report_basis');
    }
}
