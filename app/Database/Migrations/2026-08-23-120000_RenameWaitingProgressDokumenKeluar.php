<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class RenameWaitingProgressDokumenKeluar extends Migration
{
    public function up()
    {
        $this->db->query(
            "UPDATE dokumen_keluar SET progres = 'Menunggu Ekspedisi' WHERE progres = 'Belum Diambil'"
        );
        $this->db->query(
            "ALTER TABLE dokumen_keluar MODIFY progres VARCHAR(50) NOT NULL DEFAULT 'Menunggu Ekspedisi'"
        );
    }

    public function down()
    {
        $this->db->query(
            "UPDATE dokumen_keluar SET progres = 'Belum Diambil' WHERE progres = 'Menunggu Ekspedisi'"
        );
        $this->db->query(
            "ALTER TABLE dokumen_keluar MODIFY progres VARCHAR(50) NOT NULL DEFAULT 'Belum Diambil'"
        );
    }
}
