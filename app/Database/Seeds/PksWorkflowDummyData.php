<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;
use DateTimeImmutable;

class PksWorkflowDummyData extends Seeder
{
    public function run(): void
    {
        $now = date('Y-m-d H:i:s');
        $today = new DateTimeImmutable('today');

        $records = [
            [
                'mitra' => [
                    'nama_mitra' => 'CV Cakrawala Sarana Kantor',
                    'alamat' => 'Jl. Raya Darmo No. 88, Surabaya',
                    'nama_kontak' => 'Rizky Firmansyah',
                    'jabatan_kontak' => 'Account Manager',
                    'telepon' => '0812-1000-1001',
                    'email' => 'rizky@cakrawalasaranakantor.example',
                ],
                'kerjasama' => [
                    'kode_internal' => 'PKS-DUMMY-001',
                    'nama_kerjasama' => 'Pengadaan Alat Tulis dan Perlengkapan Kantor',
                    'unit_pengelola' => 'Bagian Umum 1',
                    'pic_internal' => 'Angger Wicaksono',
                    'ruang_lingkup' => 'Penyediaan alat tulis, kertas, tinta printer, dan perlengkapan kerja kantor.',
                    'keterangan' => 'Contoh PKS yang data utamanya sudah dibuat tetapi dokumen PKS belum dilengkapi.',
                ],
                'documents' => [],
                'items' => [
                    ['nama_item' => 'Kertas A4 80 gsm', 'jumlah' => 500, 'satuan' => 'Rim', 'keterangan' => 'Pengiriman dilakukan bertahap sesuai permintaan.'],
                    ['nama_item' => 'Tinta printer', 'jumlah' => 120, 'satuan' => 'Unit', 'keterangan' => 'Menyesuaikan tipe printer kantor.'],
                ],
            ],
            [
                'mitra' => [
                    'nama_mitra' => 'PT Arunika Teknologi Indonesia',
                    'alamat' => 'Jl. Jemursari No. 125, Surabaya',
                    'nama_kontak' => 'Nadia Putri',
                    'jabatan_kontak' => 'Business Development',
                    'telepon' => '0812-1000-1002',
                    'email' => 'nadia@arunikateknologi.example',
                ],
                'kerjasama' => [
                    'kode_internal' => 'PKS-DUMMY-002',
                    'nama_kerjasama' => 'Pemeliharaan Perangkat Jaringan Kantor',
                    'unit_pengelola' => 'Bagian Umum 1',
                    'pic_internal' => 'Agil Halis Kesawa',
                    'ruang_lingkup' => 'Preventive maintenance perangkat jaringan, access point, dan dukungan teknis.',
                    'keterangan' => 'Contoh PKS yang sudah lengkap namun periode kerjanya belum dimulai.',
                ],
                'documents' => [
                    [
                        'jenis_dokumen' => 'PKS', 'urutan' => 0, 'nomor_dokumen' => '012/PKS/JKS-AT/VIII/2026',
                        'tanggal_dokumen' => $this->date($today, '+30 days'), 'periode_mulai' => $this->date($today, '+45 days'),
                        'periode_selesai' => $this->date($today, '+410 days'), 'nilai' => 185000000,
                        'link_berkas' => 'https://example.com/pks/pks-dummy-002', 'keterangan' => 'Dokumen telah ditandatangani; pelaksanaan dimulai sesuai periode kontrak.',
                    ],
                ],
                'items' => [
                    ['nama_item' => 'Preventive maintenance jaringan', 'jumlah' => 12, 'satuan' => 'Bulan', 'keterangan' => 'Pemeriksaan rutin setiap bulan.'],
                    ['nama_item' => 'Layanan dukungan teknis', 'jumlah' => 24, 'satuan' => 'Tiket', 'keterangan' => 'Kuota dukungan selama masa kontrak.'],
                ],
            ],
            [
                'mitra' => [
                    'nama_mitra' => 'PT Sinar Bersih Nusantara',
                    'alamat' => 'Jl. Ahmad Yani No. 210, Surabaya',
                    'nama_kontak' => 'Dewi Anggraini',
                    'jabatan_kontak' => 'Site Coordinator',
                    'telepon' => '0812-1000-1003',
                    'email' => 'dewi@sinarbersih.example',
                ],
                'kerjasama' => [
                    'kode_internal' => 'PKS-DUMMY-003',
                    'nama_kerjasama' => 'Jasa Kebersihan Gedung Kantor',
                    'unit_pengelola' => 'Bagian Umum 1',
                    'pic_internal' => 'Angger Wicaksono',
                    'ruang_lingkup' => 'Penyediaan tenaga kebersihan, perlengkapan kerja, dan bahan habis pakai.',
                    'keterangan' => 'Contoh PKS aktif dengan beberapa item pekerjaan.',
                ],
                'documents' => [
                    [
                        'jenis_dokumen' => 'PKS', 'urutan' => 0, 'nomor_dokumen' => '021/PKS/JKS-SBN/II/2026',
                        'tanggal_dokumen' => $this->date($today, '-190 days'), 'periode_mulai' => $this->date($today, '-180 days'),
                        'periode_selesai' => $this->date($today, '+185 days'), 'nilai' => 468000000,
                        'link_berkas' => 'https://example.com/pks/pks-dummy-003', 'keterangan' => 'PKS induk jasa kebersihan gedung kantor.',
                    ],
                ],
                'items' => [
                    ['nama_item' => 'Tenaga kebersihan', 'jumlah' => 8, 'satuan' => 'Orang', 'keterangan' => 'Penempatan pada hari kerja dan jadwal piket.'],
                    ['nama_item' => 'General cleaning', 'jumlah' => 12, 'satuan' => 'Kegiatan', 'keterangan' => 'Dilaksanakan satu kali setiap bulan.'],
                    ['nama_item' => 'Bahan kebersihan', 'jumlah' => 12, 'satuan' => 'Paket', 'keterangan' => 'Disediakan setiap awal bulan.'],
                ],
            ],
            [
                'mitra' => [
                    'nama_mitra' => 'PT Garda Prima Sejahtera',
                    'alamat' => 'Jl. Rungkut Industri No. 17, Surabaya',
                    'nama_kontak' => 'Bayu Prakoso',
                    'jabatan_kontak' => 'Operation Supervisor',
                    'telepon' => '0812-1000-1004',
                    'email' => 'bayu@gardaprima.example',
                ],
                'kerjasama' => [
                    'kode_internal' => 'PKS-DUMMY-004',
                    'nama_kerjasama' => 'Jasa Pengamanan Kantor',
                    'unit_pengelola' => 'Bagian Umum 2',
                    'pic_internal' => 'Agil Halis Kesawa',
                    'ruang_lingkup' => 'Penyediaan petugas pengamanan untuk penjagaan area kantor selama 24 jam.',
                    'keterangan' => 'Contoh PKS yang akan berakhir dalam 20 hari dan perlu segera ditindaklanjuti.',
                ],
                'documents' => [
                    [
                        'jenis_dokumen' => 'PKS', 'urutan' => 0, 'nomor_dokumen' => '033/PKS/JKS-GPS/IX/2025',
                        'tanggal_dokumen' => $this->date($today, '-345 days'), 'periode_mulai' => $this->date($today, '-340 days'),
                        'periode_selesai' => $this->date($today, '+18 days'), 'nilai' => 720000000,
                        'link_berkas' => 'https://example.com/pks/pks-dummy-004', 'keterangan' => 'Perlu evaluasi dan keputusan perpanjangan sebelum masa berlaku selesai.',
                    ],
                ],
                'items' => [
                    ['nama_item' => 'Petugas pengamanan', 'jumlah' => 6, 'satuan' => 'Orang', 'keterangan' => 'Dibagi dalam tiga shift.'],
                    ['nama_item' => 'Koordinator lapangan', 'jumlah' => 1, 'satuan' => 'Orang', 'keterangan' => 'Koordinasi dan pelaporan harian.'],
                ],
            ],
            [
                'mitra' => [
                    'nama_mitra' => 'CV Tirta Sejuk Abadi',
                    'alamat' => 'Jl. Kertajaya No. 42, Surabaya',
                    'nama_kontak' => 'Siti Rahma',
                    'jabatan_kontak' => 'Administrasi Kontrak',
                    'telepon' => '0812-1000-1005',
                    'email' => 'siti@tirtasejuk.example',
                ],
                'kerjasama' => [
                    'kode_internal' => 'PKS-DUMMY-005',
                    'nama_kerjasama' => 'Pemeliharaan AC dan Sistem Ventilasi',
                    'unit_pengelola' => 'Bagian Umum 2',
                    'pic_internal' => 'Angger Wicaksono',
                    'ruang_lingkup' => 'Pemeliharaan berkala, perbaikan, dan penggantian komponen ringan unit AC.',
                    'keterangan' => 'Contoh PKS yang masa berlakunya telah berakhir.',
                ],
                'documents' => [
                    [
                        'jenis_dokumen' => 'PKS', 'urutan' => 0, 'nomor_dokumen' => '047/PKS/JKS-TSA/VII/2025',
                        'tanggal_dokumen' => $this->date($today, '-420 days'), 'periode_mulai' => $this->date($today, '-410 days'),
                        'periode_selesai' => $this->date($today, '-45 days'), 'nilai' => 96000000,
                        'link_berkas' => 'https://example.com/pks/pks-dummy-005', 'keterangan' => 'Kontrak telah berakhir dan menunggu proses penutupan administrasi.',
                    ],
                ],
                'items' => [
                    ['nama_item' => 'Servis berkala unit AC', 'jumlah' => 48, 'satuan' => 'Unit', 'keterangan' => 'Pemeriksaan dan pembersihan rutin.'],
                    ['nama_item' => 'Penggantian komponen ringan', 'jumlah' => 1, 'satuan' => 'Paket', 'keterangan' => 'Sesuai kebutuhan dan persetujuan PIC.'],
                ],
            ],
            [
                'mitra' => [
                    'nama_mitra' => 'PT Lintas Karya Digital',
                    'alamat' => 'Jl. Basuki Rahmat No. 76, Surabaya',
                    'nama_kontak' => 'Agung Wicaksono',
                    'jabatan_kontak' => 'Project Manager',
                    'telepon' => '0812-1000-1006',
                    'email' => 'agung@lintaskarya.example',
                ],
                'kerjasama' => [
                    'kode_internal' => 'PKS-DUMMY-006',
                    'nama_kerjasama' => 'Layanan Internet dan Koneksi Cadangan',
                    'unit_pengelola' => 'Bagian Umum 1',
                    'pic_internal' => 'Agil Halis Kesawa',
                    'ruang_lingkup' => 'Penyediaan koneksi internet utama, koneksi cadangan, dan dukungan gangguan.',
                    'keterangan' => 'Contoh PKS aktif yang pernah diperpanjang melalui addendum.',
                ],
                'documents' => [
                    [
                        'jenis_dokumen' => 'PKS', 'urutan' => 0, 'nomor_dokumen' => '058/PKS/JKS-LKD/I/2025',
                        'tanggal_dokumen' => $this->date($today, '-590 days'), 'periode_mulai' => $this->date($today, '-570 days'),
                        'periode_selesai' => $this->date($today, '-205 days'), 'nilai' => 240000000,
                        'link_berkas' => 'https://example.com/pks/pks-dummy-006-induk', 'keterangan' => 'PKS induk layanan internet kantor.',
                    ],
                    [
                        'jenis_dokumen' => 'Addendum', 'urutan' => 1, 'nomor_dokumen' => '058/ADD-01/JKS-LKD/I/2026',
                        'tanggal_dokumen' => $this->date($today, '-220 days'), 'periode_mulai' => $this->date($today, '-204 days'),
                        'periode_selesai' => $this->date($today, '+160 days'), 'nilai' => 264000000,
                        'link_berkas' => 'https://example.com/pks/pks-dummy-006-addendum', 'keterangan' => 'Perpanjangan masa berlaku dan penyesuaian nilai layanan.',
                    ],
                ],
                'items' => [
                    ['nama_item' => 'Internet dedicated utama', 'jumlah' => 200, 'satuan' => 'Mbps', 'keterangan' => 'Layanan utama dengan SLA 99,5%.'],
                    ['nama_item' => 'Koneksi internet cadangan', 'jumlah' => 100, 'satuan' => 'Mbps', 'keterangan' => 'Aktif otomatis saat koneksi utama bermasalah.'],
                ],
            ],
            [
                'mitra' => [
                    'nama_mitra' => 'PT Mobilitas Karya Indonesia',
                    'alamat' => 'Jl. Mayjen Sungkono No. 101, Surabaya',
                    'nama_kontak' => 'Fajar Nugroho',
                    'jabatan_kontak' => 'Fleet Coordinator',
                    'telepon' => '0812-1000-1007',
                    'email' => 'fajar@mobilitaskarya.example',
                ],
                'kerjasama' => [
                    'kode_internal' => 'PKS-DUMMY-007',
                    'nama_kerjasama' => 'Sewa Kendaraan Operasional',
                    'unit_pengelola' => 'Bagian Umum 2',
                    'pic_internal' => 'Angger Wicaksono',
                    'ruang_lingkup' => 'Penyediaan kendaraan operasional beserta perawatan dan kendaraan pengganti.',
                    'keterangan' => 'Contoh PKS dengan addendum yang akan segera berakhir.',
                ],
                'documents' => [
                    [
                        'jenis_dokumen' => 'PKS', 'urutan' => 0, 'nomor_dokumen' => '064/PKS/JKS-MKI/IX/2025',
                        'tanggal_dokumen' => $this->date($today, '-360 days'), 'periode_mulai' => $this->date($today, '-350 days'),
                        'periode_selesai' => $this->date($today, '-20 days'), 'nilai' => 540000000,
                        'link_berkas' => 'https://example.com/pks/pks-dummy-007-induk', 'keterangan' => 'PKS induk sewa kendaraan operasional.',
                    ],
                    [
                        'jenis_dokumen' => 'Addendum', 'urutan' => 1, 'nomor_dokumen' => '064/ADD-01/JKS-MKI/VIII/2026',
                        'tanggal_dokumen' => $this->date($today, '-30 days'), 'periode_mulai' => $this->date($today, '-19 days'),
                        'periode_selesai' => $this->date($today, '+14 days'), 'nilai' => 585000000,
                        'link_berkas' => 'https://example.com/pks/pks-dummy-007-addendum', 'keterangan' => 'Perpanjangan sementara sambil menunggu proses pengadaan berikutnya.',
                    ],
                ],
                'items' => [
                    ['nama_item' => 'Kendaraan MPV operasional', 'jumlah' => 3, 'satuan' => 'Unit', 'keterangan' => 'Termasuk servis berkala dan kendaraan pengganti.'],
                    ['nama_item' => 'Kendaraan niaga ringan', 'jumlah' => 1, 'satuan' => 'Unit', 'keterangan' => 'Digunakan untuk kebutuhan distribusi barang.'],
                ],
            ],
        ];

        $this->db->transStart();

        foreach ($records as $record) {
            $cooperation = $this->db->table('pks_kerjasama')
                ->where('kode_internal', $record['kerjasama']['kode_internal'])
                ->get()
                ->getRowArray();

            if ($cooperation === null) {
                $this->db->table('pks_mitra')->insert($record['mitra'] + ['created_at' => $now, 'updated_at' => $now]);
                $partnerId = (int) $this->db->insertID();

                $this->db->table('pks_kerjasama')->insert($record['kerjasama'] + [
                    'mitra_id' => $partnerId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
                $cooperationId = (int) $this->db->insertID();
            } else {
                $cooperationId = (int) $cooperation['id'];
                $partnerId = (int) $cooperation['mitra_id'];
                $this->db->table('pks_mitra')->where('id', $partnerId)->update($record['mitra'] + ['updated_at' => $now]);
                $this->db->table('pks_kerjasama')->where('id', $cooperationId)->update($record['kerjasama'] + ['updated_at' => $now]);
            }

            foreach ($record['documents'] as $document) {
                $document['jangka_waktu_bulan'] = $this->monthsBetween(
                    $document['periode_mulai'],
                    $document['periode_selesai']
                );
                $existingDocument = $this->db->table('pks_dokumen_kerjasama')
                    ->where('kerjasama_id', $cooperationId)
                    ->where('urutan', $document['urutan'])
                    ->get()
                    ->getRowArray();

                if ($existingDocument === null) {
                    $this->db->table('pks_dokumen_kerjasama')->insert($document + [
                        'kerjasama_id' => $cooperationId,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                } else {
                    $this->db->table('pks_dokumen_kerjasama')->where('id', $existingDocument['id'])->update($document + ['updated_at' => $now]);
                }
            }

            foreach ($record['items'] as $item) {
                $existingItem = $this->db->table('pks_item_kerjasama')
                    ->where('kerjasama_id', $cooperationId)
                    ->where('nama_item', $item['nama_item'])
                    ->get()
                    ->getRowArray();

                if ($existingItem === null) {
                    $this->db->table('pks_item_kerjasama')->insert($item + [
                        'kerjasama_id' => $cooperationId,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                } else {
                    $this->db->table('pks_item_kerjasama')->where('id', $existingItem['id'])->update($item + ['updated_at' => $now]);
                }
            }
        }

        $this->db->transComplete();

        if (! $this->db->transStatus()) {
            throw new \RuntimeException('Data dummy alur PKS gagal dibuat.');
        }
    }

    private function date(DateTimeImmutable $today, string $modifier): string
    {
        return $today->modify($modifier)->format('Y-m-d');
    }

    private function monthsBetween(string $start, string $end): int
    {
        $interval = (new DateTimeImmutable($start))->diff(new DateTimeImmutable($end));
        return max(1, ($interval->y * 12) + $interval->m);
    }
}
