<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class RemoveNomorAgendaFromDokumenMasuk extends Migration
{
    public function up()
    {
        if ($this->db->fieldExists('nomor_agenda', 'dokumen_masuk')) {
            $this->db->query('ALTER TABLE dokumen_masuk DROP COLUMN nomor_agenda');
        }
    }

    public function down()
    {
        if ($this->db->fieldExists('nomor_agenda', 'dokumen_masuk')) {
            return;
        }

        $this->db->query('ALTER TABLE dokumen_masuk ADD nomor_agenda INT UNSIGNED NULL AFTER id');

        $rows = $this->db->table('dokumen_masuk')
            ->select('id')
            ->orderBy('created_at', 'ASC')
            ->orderBy('id', 'ASC')
            ->get()
            ->getResultArray();

        foreach ($rows as $index => $row) {
            $this->db->table('dokumen_masuk')
                ->where('id', $row['id'])
                ->update(['nomor_agenda' => $index + 1]);
        }

        $this->db->query(
            'ALTER TABLE dokumen_masuk MODIFY nomor_agenda INT UNSIGNED NOT NULL, '
            . 'ADD UNIQUE KEY dokumen_masuk_nomor_agenda_unique (nomor_agenda)'
        );
    }
}
