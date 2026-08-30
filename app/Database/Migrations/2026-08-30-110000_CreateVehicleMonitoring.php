<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateVehicleMonitoring extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'nomor_polisi' => ['type' => 'VARCHAR', 'constraint' => 20],
            'nama_kendaraan' => ['type' => 'VARCHAR', 'constraint' => 150],
            'jenis' => ['type' => 'VARCHAR', 'constraint' => 80],
            'merek' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'tipe' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'tahun' => ['type' => 'SMALLINT', 'constraint' => 4, 'unsigned' => true, 'null' => true],
            'warna' => ['type' => 'VARCHAR', 'constraint' => 60, 'null' => true],
            'nomor_rangka' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'nomor_mesin' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'unit_pengguna' => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'pic' => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'kilometer' => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'default' => 0],
            'status' => ['type' => 'ENUM', 'constraint' => ['Tersedia', 'Digunakan', 'Perawatan', 'Tidak Aktif'], 'default' => 'Tersedia'],
            'created_by' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
            'deleted_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('nomor_polisi');
        $this->forge->addKey('status');
        $this->forge->createTable('vehicles', true, ['ENGINE' => 'InnoDB']);

        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'vehicle_id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
            'tanggal_servis' => ['type' => 'DATE'],
            'jenis_perawatan' => ['type' => 'VARCHAR', 'constraint' => 150],
            'bengkel' => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'kilometer' => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'null' => true],
            'biaya' => ['type' => 'DECIMAL', 'constraint' => '18,2', 'default' => 0],
            'servis_berikutnya_tanggal' => ['type' => 'DATE', 'null' => true],
            'servis_berikutnya_km' => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'null' => true],
            'keterangan' => ['type' => 'TEXT', 'null' => true],
            'link_berkas' => ['type' => 'VARCHAR', 'constraint' => 2048, 'null' => true],
            'created_by' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
            'deleted_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['vehicle_id', 'tanggal_servis']);
        $this->forge->addForeignKey('vehicle_id', 'vehicles', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('vehicle_maintenance', true, ['ENGINE' => 'InnoDB']);

        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'vehicle_id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
            'jenis_dokumen' => ['type' => 'ENUM', 'constraint' => ['STNK', 'Pajak', 'KIR', 'Asuransi', 'Lainnya']],
            'nomor_dokumen' => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'tanggal_terbit' => ['type' => 'DATE', 'null' => true],
            'masa_berlaku' => ['type' => 'DATE'],
            'link_berkas' => ['type' => 'VARCHAR', 'constraint' => 2048, 'null' => true],
            'keterangan' => ['type' => 'TEXT', 'null' => true],
            'created_by' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
            'deleted_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['vehicle_id', 'masa_berlaku']);
        $this->forge->addForeignKey('vehicle_id', 'vehicles', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('vehicle_documents', true, ['ENGINE' => 'InnoDB']);

        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'vehicle_id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'null' => true],
            'vehicle_label' => ['type' => 'VARCHAR', 'constraint' => 180],
            'entity_type' => ['type' => 'ENUM', 'constraint' => ['Kendaraan', 'Servis', 'Dokumen']],
            'entity_id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'null' => true],
            'action' => ['type' => 'ENUM', 'constraint' => ['Ditambahkan', 'Diperbarui', 'Dihapus']],
            'description' => ['type' => 'TEXT'],
            'actor_name' => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['vehicle_id', 'created_at']);
        $this->forge->addKey(['entity_type', 'action']);
        $this->forge->addForeignKey('vehicle_id', 'vehicles', 'id', 'CASCADE', 'SET NULL');
        $this->forge->createTable('vehicle_activity_logs', true, ['ENGINE' => 'InnoDB']);
    }

    public function down()
    {
        $this->forge->dropTable('vehicle_activity_logs', true);
        $this->forge->dropTable('vehicle_documents', true);
        $this->forge->dropTable('vehicle_maintenance', true);
        $this->forge->dropTable('vehicles', true);
    }
}
