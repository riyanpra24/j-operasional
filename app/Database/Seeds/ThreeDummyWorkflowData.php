<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class ThreeDummyWorkflowData extends Seeder
{
    public function run(): void
    {
        $now = date('Y-m-d H:i:s');

        $this->db->transStart();

        $agendaRows = [
            [
                'pengirim'         => 'PT Nusantara Logistik',
                'penerima'         => 'Yanto Pujoyuwono',
                'pengambilan'      => 'Petugas Internal',
                'jenis'            => 'Surat',
                'tanggal_diterima' => '2026-08-26',
                'tanggal_surat'    => '2026-08-25',
                'nomor_surat'      => 'AGD/UJI/001/VIII/2026',
                'perihal_surat'    => 'Undangan koordinasi operasional',
                'berkas_link'      => 'https://example.com/dokumen/uji-001',
                'progres'          => 'Menunggu Penyelesaian',
            ],
            [
                'pengirim'         => 'Dinas Perhubungan Surabaya',
                'penerima'         => 'M. Aziz Dwi Pratomo',
                'pengambilan'      => 'Petugas Administrasi',
                'jenis'            => 'Dokumen',
                'tanggal_diterima' => '2026-08-25',
                'tanggal_surat'    => '2026-08-24',
                'nomor_surat'      => 'AGD/UJI/002/VIII/2026',
                'perihal_surat'    => 'Laporan evaluasi layanan',
                'berkas_link'      => 'https://example.com/dokumen/uji-002',
                'progres'          => 'Selesai',
            ],
        ];

        foreach ($agendaRows as $row) {
            if ($this->db->table('agendaris')->where('nomor_surat', $row['nomor_surat'])->countAllResults() > 0) {
                continue;
            }

            $this->db->table('agendaris')->insert($row + [
                'dokumen_masuk_id' => null,
                'created_at'       => $now,
                'updated_at'       => $now,
            ]);
        }

        $nomorKeluar = 'SK/UJI/003/VIII/2026';
        $outgoing = $this->db->table('dokumen_keluar')->where('nomor_surat', $nomorKeluar)->get()->getRowArray();

        if ($outgoing === null) {
            $this->db->table('dokumen_keluar')->insert([
                'nomor_surat'        => $nomorKeluar,
                'jenis_surat'        => 'Surat Balasan',
                'pemohon'            => 'Divisi Umum',
                'pelaksana'           => 'Rina Pratama',
                'up'                  => 'Kepala Bagian Operasional',
                'tanggal_pengiriman' => '2026-08-25',
                'nomor_resi'         => 'RESI-UJI-003',
                'tanggal_diterima'   => '2026-08-26',
                'penerima'           => 'Petugas Penerima',
                'security'           => 'Yayak Andriyani',
                'tanggal_security'   => '2026-08-25',
                'progres'            => 'Diambil Ekspedisi',
                'alamat_penerima'    => 'Jl. Tunjungan No. 10, Surabaya',
                'dokumen_link'       => 'https://example.com/dokumen/uji-003',
            ]);
            $outgoingId = (int) $this->db->insertID();
        } else {
            $outgoingId = (int) $outgoing['id'];
        }

        if ($this->db->table('distribusi_dokumen')->where('dokumen_keluar_id', $outgoingId)->countAllResults() === 0) {
            $this->db->table('distribusi_dokumen')->insert(['dokumen_keluar_id' => $outgoingId]);
        }

        $this->db->transComplete();

        if (! $this->db->transStatus()) {
            throw new \RuntimeException('Tiga data dummy gagal dibuat.');
        }
    }
}
