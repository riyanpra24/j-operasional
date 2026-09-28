<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class SdmTellerDummyData extends Seeder
{
    public function run(): void
    {
        $recipient = 'Kiki Ramadhani Suyono';
        $documents = [
            ['PT Surabaya Logistik Nusantara', 'Pengajuan evaluasi vendor logistik', 'Dokumen', 'Menunggu', 'Mohon telaah kelengkapan dokumen vendor.'],
            ['Dinas Perhubungan Kota Surabaya', 'Undangan rapat koordinasi transportasi', 'Surat', 'Diterima', 'Jadwalkan kehadiran perwakilan perusahaan.'],
            ['BPJS Ketenagakerjaan Surabaya', 'Pembaruan data kepesertaan pegawai', 'Berkas', 'Diproses', 'Verifikasi daftar pegawai terlampir.'],
            ['Kantor Pajak Pratama Surabaya', 'Permintaan klarifikasi administrasi pajak', 'Surat', 'Menunggu', 'Siapkan dokumen pendukung perpajakan.'],
            ['PT Bank Mandiri Tbk', 'Pemberitahuan perubahan layanan perbankan', 'Surat', 'Diterima', 'Informasikan kepada unit terkait.'],
            ['PT Telkom Indonesia', 'Penawaran pembaruan layanan komunikasi', 'Dokumen', 'Diproses', 'Tinjau kesesuaian kebutuhan layanan kantor.'],
            ['Universitas Negeri Surabaya', 'Permohonan kerja sama program magang', 'Surat', 'Menunggu', 'Koordinasikan kebutuhan peserta magang.'],
            ['PT PLN (Persero) UP3 Surabaya', 'Pemberitahuan pemeliharaan jaringan listrik', 'Surat', 'Diterima', 'Catat jadwal pemeliharaan dan dampaknya.'],
            ['CV Mitra Karya Sejahtera', 'Penawaran pengadaan perlengkapan kantor', 'Dokumen', 'Diproses', 'Lakukan pengecekan awal spesifikasi barang.'],
            ['Kementerian Ketenagakerjaan', 'Sosialisasi ketentuan ketenagakerjaan terbaru', 'Berkas', 'Menunggu', 'Pelajari materi sosialisasi untuk tindak lanjut.'],
        ];

        $this->db->transStart();

        foreach ($documents as $index => [$sender, $subject, $type, $status, $note]) {
            $sequence = str_pad((string) ($index + 1), 3, '0', STR_PAD_LEFT);
            $nomorSurat = "SDM-DUMMY/{$sequence}/IX/2026";

            if ($this->db->table('agendaris')->where('nomor_surat', $nomorSurat)->countAllResults() > 0) {
                continue;
            }

            $receivedDate = date('Y-m-d', strtotime('-' . $index . ' days'));
            $dispositionTime = date('Y-m-d H:i:s', strtotime($receivedDate . ' 08:' . str_pad((string) ($index + 10), 2, '0', STR_PAD_LEFT) . ':00'));

            $this->db->table('agendaris')->insert([
                'dokumen_masuk_id'      => null,
                'pengirim'               => $sender,
                'penerima'               => 'Bagian SDM & Teller',
                'pengambilan'            => 'Petugas Internal',
                'jenis'                  => $type,
                'tanggal_diterima'       => $receivedDate,
                'tanggal_surat'          => date('Y-m-d', strtotime($receivedDate . ' -1 day')),
                'nomor_surat'            => $nomorSurat,
                'nomor_agendaris'        => "AGD/SDM/{$sequence}/IX/2026",
                'tanggal_agendaris'      => $receivedDate,
                'perihal_surat'          => $subject,
                'berkas_link'            => 'https://example.com/dummy/sdm-teller-' . ($index + 1),
                'progres'                => 'Selesai',
                'disposisi_1'            => $recipient,
                'disposisi_1_status'     => $status,
                'disposisi_1_waktu'      => $dispositionTime,
                'disposisi_1_catatan'    => $note,
                'created_at'             => $dispositionTime,
                'updated_at'             => $dispositionTime,
            ]);
        }

        $this->db->transComplete();

        if (! $this->db->transStatus()) {
            throw new \RuntimeException('Data dummy SDM & Teller gagal dibuat.');
        }
    }
}
