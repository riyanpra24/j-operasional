<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

final class AddBerkasToAccountingBuktiBayar extends Migration
{
    public function up(): void
    {
        $columns = [
            'berkas_path' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true, 'after' => 'kategori'],
            'berkas_nama' => ['type' => 'VARCHAR', 'constraint' => 200, 'null' => true, 'after' => 'berkas_path'],
            'berkas_mime' => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true, 'after' => 'berkas_nama'],
        ];

        foreach ($columns as $name => $definition) {
            if (! $this->db->fieldExists($name, 'accounting_bukti_bayar')) {
                $this->forge->addColumn('accounting_bukti_bayar', [$name => $definition]);
            }
        }
    }

    public function down(): void
    {
        $this->forge->dropColumn('accounting_bukti_bayar', ['berkas_path', 'berkas_nama', 'berkas_mime']);
    }
}
