<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

final class AddBerkasUrlToAccountingBuktiBayar extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('accounting_bukti_bayar', [
            'berkas_url' => ['type' => 'VARCHAR', 'constraint' => 2048, 'null' => true, 'after' => 'berkas_mime'],
        ]);
    }

    public function down(): void
    {
        $this->forge->dropColumn('accounting_bukti_bayar', 'berkas_url');
    }
}
