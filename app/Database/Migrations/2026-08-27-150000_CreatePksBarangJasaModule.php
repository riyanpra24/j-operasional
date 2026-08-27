<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePksBarangJasaModule extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'nama_mitra' => ['type' => 'VARCHAR', 'constraint' => 200],
            'alamat' => ['type' => 'TEXT', 'null' => true],
            'nama_kontak' => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'jabatan_kontak' => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'telepon' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'email' => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('nama_mitra');
        $this->forge->createTable('pks_mitra', true, ['ENGINE' => 'InnoDB']);

        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'mitra_id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
            'kode_internal' => ['type' => 'VARCHAR', 'constraint' => 80],
            'nama_kerjasama' => ['type' => 'VARCHAR', 'constraint' => 250],
            'unit_pengelola' => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'pic_internal' => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'ruang_lingkup' => ['type' => 'TEXT', 'null' => true],
            'keterangan' => ['type' => 'TEXT', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('kode_internal');
        $this->forge->addKey('mitra_id');
        $this->forge->addForeignKey('mitra_id', 'pks_mitra', 'id', 'RESTRICT', 'CASCADE');
        $this->forge->createTable('pks_kerjasama', true, ['ENGINE' => 'InnoDB']);

        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'kerjasama_id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
            'jenis_dokumen' => ['type' => 'VARCHAR', 'constraint' => 30],
            'urutan' => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true],
            'nomor_dokumen' => ['type' => 'VARCHAR', 'constraint' => 200],
            'tanggal_dokumen' => ['type' => 'DATE'],
            'periode_mulai' => ['type' => 'DATE'],
            'periode_selesai' => ['type' => 'DATE'],
            'nilai' => ['type' => 'DECIMAL', 'constraint' => '18,2', 'default' => 0],
            'link_berkas' => ['type' => 'VARCHAR', 'constraint' => 2048, 'null' => true],
            'keterangan' => ['type' => 'TEXT', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['kerjasama_id', 'urutan']);
        $this->forge->addKey('periode_selesai');
        $this->forge->addForeignKey('kerjasama_id', 'pks_kerjasama', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('pks_dokumen_kerjasama', true, ['ENGINE' => 'InnoDB']);

        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'kerjasama_id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
            'nama_item' => ['type' => 'VARCHAR', 'constraint' => 250],
            'jumlah' => ['type' => 'DECIMAL', 'constraint' => '14,2', 'null' => true],
            'satuan' => ['type' => 'VARCHAR', 'constraint' => 80, 'null' => true],
            'keterangan' => ['type' => 'TEXT', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('kerjasama_id');
        $this->forge->addForeignKey('kerjasama_id', 'pks_kerjasama', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('pks_item_kerjasama', true, ['ENGINE' => 'InnoDB']);
    }

    public function down()
    {
        $this->forge->dropTable('pks_item_kerjasama', true);
        $this->forge->dropTable('pks_dokumen_kerjasama', true);
        $this->forge->dropTable('pks_kerjasama', true);
        $this->forge->dropTable('pks_mitra', true);
    }
}
