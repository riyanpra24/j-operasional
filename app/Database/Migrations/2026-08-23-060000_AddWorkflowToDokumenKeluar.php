<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddWorkflowToDokumenKeluar extends Migration
{
    public function up()
    {
        $this->db->query(
            'ALTER TABLE dokumen_keluar '
            . 'ADD COLUMN pemohon VARCHAR(255) NULL AFTER jenis_surat, '
            . 'ADD COLUMN pelaksana VARCHAR(255) NULL AFTER pemohon, '
            . 'ADD COLUMN `up` VARCHAR(255) NULL AFTER pelaksana, '
            . 'ADD COLUMN tanggal_diterima DATE NULL AFTER nomor_resi, '
            . 'ADD COLUMN penerima VARCHAR(255) NULL AFTER tanggal_diterima, '
            . 'ADD INDEX dokumen_keluar_pemohon_index (pemohon), '
            . 'ADD INDEX dokumen_keluar_pelaksana_index (pelaksana), '
            . 'ADD INDEX dokumen_keluar_tanggal_diterima_index (tanggal_diterima)'
        );
    }

    public function down()
    {
        $this->db->query(
            'ALTER TABLE dokumen_keluar '
            . 'DROP INDEX dokumen_keluar_tanggal_diterima_index, '
            . 'DROP INDEX dokumen_keluar_pelaksana_index, '
            . 'DROP INDEX dokumen_keluar_pemohon_index, '
            . 'DROP COLUMN penerima, '
            . 'DROP COLUMN tanggal_diterima, '
            . 'DROP COLUMN `up`, '
            . 'DROP COLUMN pelaksana, '
            . 'DROP COLUMN pemohon'
        );
    }
}
