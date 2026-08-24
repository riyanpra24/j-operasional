<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class SyncPickedDocumentsToAgendaris extends Migration
{
    public function up()
    {
        $this->db->query(
            'ALTER TABLE agendaris '
            . 'MODIFY tanggal_surat DATE NULL, '
            . 'MODIFY nomor_surat VARCHAR(150) NULL'
        );

        $this->db->query(
            "INSERT INTO agendaris (dokumen_masuk_id, tanggal_surat, nomor_surat, perihal_surat, created_at, updated_at) "
            . "SELECT d.id, NULL, NULL, COALESCE(NULLIF(TRIM(d.perihal), ''), 'Belum diisi'), NOW(), NOW() "
            . 'FROM dokumen_masuk d '
            . 'LEFT JOIN agendaris a ON a.dokumen_masuk_id = d.id '
            . "WHERE d.deleted_at IS NULL AND TRIM(COALESCE(d.pengambilan, '')) <> '' AND a.id IS NULL"
        );
    }

    public function down()
    {
        $this->db->query(
            'UPDATE agendaris a JOIN dokumen_masuk d ON d.id = a.dokumen_masuk_id '
            . 'SET a.tanggal_surat = COALESCE(a.tanggal_surat, d.tanggal), '
            . "a.nomor_surat = COALESCE(a.nomor_surat, CONCAT('AUTO-', a.id))"
        );

        $this->db->query(
            'ALTER TABLE agendaris '
            . 'MODIFY tanggal_surat DATE NOT NULL, '
            . 'MODIFY nomor_surat VARCHAR(150) NOT NULL'
        );
    }
}
