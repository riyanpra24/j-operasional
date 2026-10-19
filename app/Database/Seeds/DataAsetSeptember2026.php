<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;
use RuntimeException;

class DataAsetSeptember2026 extends Seeder
{
    public function run()
    {
        $path = APPPATH . 'Database/Seeds/data_aset_september_2026.json';
        $rows = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
        if (count($rows) !== 296 || $this->db->table('data_aset')->countAllResults() !== 0) {
            throw new RuntimeException('Impor dibatalkan: jumlah sumber tidak sesuai atau tabel aset sudah berisi data.');
        }

        $this->db->transStart();
        $this->db->table('data_aset')->insertBatch($rows);
        $this->db->transComplete();
        if (! $this->db->transStatus()) {
            throw new RuntimeException('Impor aset gagal dan transaksi dibatalkan.');
        }
    }
}
