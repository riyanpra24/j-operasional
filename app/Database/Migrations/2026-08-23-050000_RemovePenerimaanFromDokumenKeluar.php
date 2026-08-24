<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class RemovePenerimaanFromDokumenKeluar extends Migration
{
    public function up()
    {
        $this->db->query(
            'ALTER TABLE dokumen_keluar '
            . 'DROP INDEX tanggal_diterima, '
            . 'DROP COLUMN tanggal_diterima, '
            . 'DROP COLUMN nama_penerima'
        );
    }

    public function down()
    {
        $this->db->query(
            "ALTER TABLE dokumen_keluar "
            . "ADD COLUMN tanggal_diterima DATE NULL AFTER nomor_resi, "
            . "ADD COLUMN nama_penerima VARCHAR(255) NOT NULL DEFAULT '' AFTER tanggal_diterima, "
            . 'ADD INDEX tanggal_diterima (tanggal_diterima)'
        );
        $this->db->query('ALTER TABLE dokumen_keluar ALTER COLUMN nama_penerima DROP DEFAULT');
    }
}
