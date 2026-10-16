<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

final class CreateAccountingBuktiBayar extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'nomor_memo' => ['type' => 'VARCHAR', 'constraint' => 120],
            'nomor_voucher' => ['type' => 'VARCHAR', 'constraint' => 120],
            'tanggal' => ['type' => 'DATE'],
            'nominal' => ['type' => 'DECIMAL', 'constraint' => '20,2'],
            'kategori' => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'created_by' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'created_by_name' => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'deleted_by_role' => ['type' => 'VARCHAR', 'constraint' => 80, 'null' => true],
            'deleted_by_name' => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
            'deleted_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('tanggal');
        $this->forge->addKey('nomor_memo');
        $this->forge->addKey('nomor_voucher');
        $this->forge->createTable('accounting_bukti_bayar', true, ['ENGINE' => 'InnoDB']);
    }

    public function down(): void
    {
        $this->forge->dropTable('accounting_bukti_bayar', true);
    }
}
