<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateDokumenKeluar extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'BIGINT',
                'constraint'     => 20,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'nomor_surat' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
            ],
            'jenis_surat' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
            ],
            'tanggal_pengiriman' => [
                'type' => 'DATE',
            ],
            'nomor_resi' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
            ],
            'tanggal_diterima' => [
                'type' => 'DATE',
                'null' => true,
            ],
            'nama_penerima' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
            ],
            'alamat_penerima' => [
                'type' => 'TEXT',
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('nomor_surat');
        $this->forge->addKey('nomor_resi');
        $this->forge->addKey('tanggal_pengiriman');
        $this->forge->addKey('tanggal_diterima');
        $this->forge->createTable('dokumen_keluar', true, ['ENGINE' => 'InnoDB']);
    }

    public function down()
    {
        $this->forge->dropTable('dokumen_keluar', true);
    }
}
