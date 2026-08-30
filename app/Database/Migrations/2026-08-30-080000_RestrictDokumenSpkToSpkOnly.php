<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use RuntimeException;

class RestrictDokumenSpkToSpkOnly extends Migration
{
    public function up()
    {
        $this->db->transStart();

        $this->db->table('dokumen_spk')
            ->where('jenis_dokumen !=', 'SPK')
            ->delete();

        $records = $this->db->table('dokumen_spk')
            ->select('id, tahun, nomor_urut')
            ->where('jenis_dokumen', 'SPK')
            ->orderBy('tahun', 'ASC')
            ->orderBy('nomor_urut', 'ASC')
            ->get()
            ->getResultArray();

        foreach ($records as $index => $record) {
            $this->db->table('dokumen_spk')
                ->where('id', (int) $record['id'])
                ->update(['nomor_urut' => 1000000 + $index]);
        }

        $currentYear = null;
        $sequence = 0;
        foreach ($records as $record) {
            $year = (int) $record['tahun'];
            if ($currentYear !== $year) {
                $currentYear = $year;
                $sequence = 0;
            }
            $sequence++;
            $this->db->table('dokumen_spk')
                ->where('id', (int) $record['id'])
                ->update(['nomor_urut' => $sequence]);
        }

        $this->db->transComplete();
        if (! $this->db->transStatus()) {
            throw new RuntimeException('Gagal menyesuaikan data Dokumen SPK.');
        }

        $this->db->query("ALTER TABLE `dokumen_spk` MODIFY `jenis_dokumen` ENUM('SPK') NOT NULL DEFAULT 'SPK'");
    }

    public function down()
    {
        $this->db->query("ALTER TABLE `dokumen_spk` MODIFY `jenis_dokumen` VARCHAR(10) NOT NULL DEFAULT 'SPK'");
    }
}
