<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

final class AtkKantorDummyData extends Seeder
{
    public function run(): void
    {
        $items = [
            ['Pulpen Gel Hitam', 'Standard', 120, 144, 'Baik', 'Data dummy ATK untuk kebutuhan administrasi.'],
            ['Pulpen Gel Biru', 'Standard', 96, 120, 'Baik', 'Data dummy ATK untuk kebutuhan administrasi.'],
            ['Pulpen Gel Merah', 'Standard', 48, 60, 'Baik', 'Data dummy ATK untuk penandaan dokumen.'],
            ['Pensil HB', 'Faber-Castell', 80, 100, 'Baik', 'Data dummy ATK untuk keperluan umum.'],
            ['Penghapus Putih', 'Faber-Castell', 36, 48, 'Baik', 'Data dummy ATK untuk keperluan umum.'],
            ['Rautan Pensil', 'Kenko', 18, 24, 'Baik', 'Data dummy ATK untuk keperluan umum.'],
            ['Spidol Permanen Hitam', 'Snowman', 24, 30, 'Baik', 'Data dummy ATK untuk pelabelan.'],
            ['Spidol Whiteboard Biru', 'Snowman', 12, 18, 'Baik', 'Data dummy ATK ruang rapat.'],
            ['Stabilo Kuning', 'Stabilo', 20, 24, 'Baik', 'Data dummy ATK untuk penandaan dokumen.'],
            ['Tip-Ex Cair', 'Pentel', 15, 20, 'Baik', 'Data dummy ATK untuk koreksi dokumen.'],
            ['Kertas HVS A4 80 gsm', 'PaperOne', 28, 35, 'Baik', 'Data dummy ATK untuk cetak dokumen.'],
            ['Kertas HVS F4 80 gsm', 'PaperOne', 16, 24, 'Baik', 'Data dummy ATK untuk cetak dokumen.'],
            ['Buku Agenda', 'Sinar Dunia', 10, 12, 'Baik', 'Data dummy ATK untuk pencatatan agenda.'],
            ['Buku Nota', 'Sinar Dunia', 18, 24, 'Baik', 'Data dummy ATK untuk pencatatan singkat.'],
            ['Map Plastik Biru', 'Bantex', 50, 60, 'Baik', 'Data dummy ATK untuk pengarsipan.'],
            ['Map Plastik Merah', 'Bantex', 42, 60, 'Baik', 'Data dummy ATK untuk pengarsipan.'],
            ['Map Kertas Folio', 'Bantex', 35, 50, 'Baik', 'Data dummy ATK untuk pengarsipan dokumen.'],
            ['Ordner A4', 'Bantex', 14, 20, 'Baik', 'Data dummy ATK untuk pengarsipan dokumen.'],
            ['Binder Clip Kecil', 'Joyko', 8, 12, 'Baik', 'Data dummy ATK untuk pengelompokan berkas.'],
            ['Binder Clip Sedang', 'Joyko', 5, 10, 'Baik', 'Data dummy ATK untuk pengelompokan berkas.'],
            ['Paper Clip', 'Joyko', 6, 10, 'Baik', 'Data dummy ATK untuk pengelompokan berkas.'],
            ['Stapler', 'Kangaro', 7, 10, 'Baik', 'Data dummy ATK untuk kebutuhan meja kerja.'],
            ['Isi Staples No. 10', 'Kangaro', 20, 30, 'Baik', 'Data dummy ATK untuk kebutuhan meja kerja.'],
            ['Gunting Kertas', 'Kenko', 9, 12, 'Baik', 'Data dummy ATK untuk kebutuhan meja kerja.'],
            ['Lem Kertas', 'UHU', 11, 15, 'Baik', 'Data dummy ATK untuk kebutuhan meja kerja.'],
            ['Selotip Bening', '3M', 14, 20, 'Baik', 'Data dummy ATK untuk kebutuhan meja kerja.'],
            ['Penggaris 30 cm', 'Butterfly', 16, 20, 'Baik', 'Data dummy ATK untuk kebutuhan meja kerja.'],
            ['Kalkulator', 'Casio', 4, 6, 'Baik', 'Data dummy ATK untuk perhitungan administrasi.'],
            ['Tinta Printer Hitam', 'Epson', 3, 8, 'Baik', 'Data dummy ATK untuk pencetakan dokumen.'],
            ['Toner Printer', 'HP', 0, 3, 'Stok Habis', 'Data dummy ATK yang perlu pengadaan ulang.'],
        ];

        $names = array_column($items, 0);
        $existingNames = array_column($this->db->table('agendaris_atk_kantor')
            ->select('nama_barang')
            ->whereIn('nama_barang', $names)
            ->get()
            ->getResultArray(), 'nama_barang');
        $timestamp = date('Y-m-d H:i:s');
        $rows = [];
        foreach ($items as [$name, $brand, $stock, $quantity, $condition, $notes]) {
            if (in_array($name, $existingNames, true)) continue;
            $rows[] = [
                'nama_barang' => $name,
                'merek' => $brand,
                'stok_barang' => $stock,
                'jumlah' => $quantity,
                'kondisi' => $condition,
                'keterangan' => $notes,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ];
        }

        if ($rows !== []) $this->db->table('agendaris_atk_kantor')->insertBatch($rows);
    }
}
