<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

final class AgendarisSouvenirInventarisDummyData extends Seeder
{
    public function run(): void
    {
        $items = [
            ['Pulpen logo perusahaan', 'Souvenir', 200, 126, 'Untuk kebutuhan rapat dan kunjungan tamu.'],
            ['Notebook eksklusif', 'Souvenir', 150, 94, 'Souvenir kegiatan internal dan eksternal.'],
            ['Tumbler stainless', 'Souvenir', 100, 58, 'Persediaan cendera mata mitra.'],
            ['Mug keramik', 'Souvenir', 120, 77, 'Digunakan untuk acara kantor.'],
            ['Payung lipat', 'Souvenir', 75, 43, 'Souvenir untuk agenda tertentu.'],
            ['Tas jinjing kanvas', 'Souvenir', 130, 89, 'Persediaan tas souvenir.'],
            ['Lanyard ID card', 'Souvenir', 300, 216, 'Untuk kegiatan dan tamu perusahaan.'],
            ['Gantungan kunci', 'Souvenir', 250, 173, 'Souvenir ringan untuk pengunjung.'],
            ['Kalender meja', 'Souvenir', 180, 64, 'Distribusi awal tahun.'],
            ['Plakat penghargaan', 'Souvenir', 40, 22, 'Cadangan penghargaan dan apresiasi.'],
            ['Flashdisk USB', 'Inventaris', 80, 51, 'Kebutuhan dokumentasi dan souvenir.'],
            ['Map dokumen premium', 'Inventaris', 220, 141, 'Untuk dokumen rapat dan kunjungan.'],
            ['Amplop logo perusahaan', 'Inventaris', 500, 328, 'Kebutuhan surat-menyurat dan agenda.'],
            ['Stiker logo perusahaan', 'Inventaris', 400, 285, 'Materi pendukung kegiatan promosi.'],
            ['Mousepad kantor', 'Inventaris', 90, 49, 'Souvenir untuk mitra kerja.'],
            ['Topi bordir', 'Inventaris', 60, 32, 'Persediaan acara luar ruang.'],
            ['Rompi kegiatan', 'Inventaris', 45, 30, 'Dipakai untuk kegiatan operasional tertentu.'],
            ['Banner lipat', 'Inventaris', 25, 18, 'Media kegiatan dan pameran.'],
            ['Tote bag batik', 'Inventaris', 110, 69, 'Souvenir acara kantor wilayah.'],
            ['Buku profil perusahaan', 'Inventaris', 160, 104, 'Materi informasi untuk calon mitra.'],
        ];

        $names = array_column($items, 0);
        $existing = $this->db->table('agendaris_souvenir_inventaris')
            ->select('id, nama_item')
            ->whereIn('nama_item', $names)
            ->get()
            ->getResultArray();
        $existingByName = [];
        foreach ($existing as $record) $existingByName[(string) $record['nama_item']] = (int) $record['id'];
        $units = [
            'Pulpen logo perusahaan' => 'box', 'Notebook eksklusif' => 'pcs', 'Tumbler stainless' => 'pcs', 'Mug keramik' => 'pcs',
            'Payung lipat' => 'pcs', 'Tas jinjing kanvas' => 'pcs', 'Lanyard ID card' => 'box', 'Gantungan kunci' => 'box',
            'Kalender meja' => 'pcs', 'Plakat penghargaan' => 'pcs', 'Flashdisk USB' => 'pcs', 'Map dokumen premium' => 'box',
            'Amplop logo perusahaan' => 'box', 'Stiker logo perusahaan' => 'box', 'Mousepad kantor' => 'pcs', 'Topi bordir' => 'pcs',
            'Rompi kegiatan' => 'pcs', 'Banner lipat' => 'unit', 'Tote bag batik' => 'pcs', 'Buku profil perusahaan' => 'box',
        ];
        $timestamp = date('Y-m-d H:i:s');
        $rows = [];
        $updates = [];

        foreach ($items as [$nama, $jenis, $awal, $akhir, $keterangan]) {
            $satuan = $units[$nama] ?? 'pcs';
            if (isset($existingByName[$nama])) {
                $updates[] = ['id' => $existingByName[$nama], 'jenis' => $jenis, 'satuan' => $satuan, 'updated_at' => $timestamp];
                continue;
            }
            $rows[] = [
                'nama_item' => $nama,
                'jenis' => $jenis,
                'satuan' => $satuan,
                'persediaan_awal' => $awal,
                'persediaan_akhir' => $akhir,
                'terpakai' => $awal - $akhir,
                'keterangan' => $keterangan,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ];
        }

        if ($rows !== []) $this->db->table('agendaris_souvenir_inventaris')->insertBatch($rows);
        if ($updates !== []) $this->db->table('agendaris_souvenir_inventaris')->updateBatch($updates, 'id');
    }
}
