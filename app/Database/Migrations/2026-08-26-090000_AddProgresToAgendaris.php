<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddProgresToAgendaris extends Migration
{
    public function up()
    {
        $this->forge->addColumn('agendaris', [
            'progres' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => false,
                'default'    => 'Menunggu Penyelesaian',
                'after'      => 'berkas_link',
            ],
        ]);

        // Pertahankan dokumen lama yang informasinya sudah lengkap sebagai arsip selesai.
        $this->db->query(
            "UPDATE agendaris SET progres = 'Selesai' "
            . "WHERE tanggal_surat IS NOT NULL "
            . "AND TRIM(COALESCE(nomor_surat, '')) <> '' "
            . "AND TRIM(COALESCE(perihal_surat, '')) <> '' "
            . "AND perihal_surat <> 'Belum diisi'"
        );
    }

    public function down()
    {
        $this->forge->dropColumn('agendaris', 'progres');
    }
}
