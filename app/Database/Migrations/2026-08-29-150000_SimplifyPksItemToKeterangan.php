<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class SimplifyPksItemToKeterangan extends Migration
{
    public function up()
    {
        // Pertahankan informasi nama lama di dalam keterangan sebelum kolom dihapus.
        $this->db->query("UPDATE pks_item_kerjasama
            SET keterangan = CASE
                WHEN TRIM(COALESCE(keterangan, '')) = '' THEN nama_item
                WHEN TRIM(COALESCE(nama_item, '')) = '' THEN keterangan
                ELSE CONCAT(nama_item, ' — ', keterangan)
            END");

        $this->forge->dropColumn('pks_item_kerjasama', ['nama_item', 'jumlah', 'satuan']);
    }

    public function down()
    {
        $this->forge->addColumn('pks_item_kerjasama', [
            'nama_item' => [
                'type' => 'VARCHAR',
                'constraint' => 250,
                'default' => '',
                'after' => 'kerjasama_id',
            ],
            'jumlah' => [
                'type' => 'DECIMAL',
                'constraint' => '14,2',
                'null' => true,
                'after' => 'nama_item',
            ],
            'satuan' => [
                'type' => 'VARCHAR',
                'constraint' => 80,
                'null' => true,
                'after' => 'jumlah',
            ],
        ]);
    }
}
