<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class DummyOperationalData extends Seeder
{
    public function run(): void
    {
        $now = date('Y-m-d H:i:s');
        $securityNames = [
            'Yanto Pujoyuwono',
            'M. Aziz Dwi Pratomo',
            'Ach. Fathur Rozi',
            'Yayak Andriyani',
        ];

        $incoming = [
            ['PT Industri Kereta Api', 'Confidential Documents', 'M. Aziz Dwi Pratomo', '2026-08-25', 'Dokumen', 1, 'JNE', null],
            ['Kementerian Perhubungan', 'Undangan rapat koordinasi', 'Yanto Pujoyuwono', '2026-08-24', 'Surat', 1, 'Pos Indonesia', 'Budi Santoso'],
            ['PT Kereta Api Indonesia', 'Dokumen evaluasi operasional', 'Ach. Fathur Rozi', '2026-08-23', 'Dokumen', 2, 'TIKI', 'Siti Rahma'],
            ['Dinas Tenaga Kerja', 'Pemberitahuan kegiatan perusahaan', 'Yayak Andriyani', '2026-08-22', 'Surat', 1, null, null],
            ['Bank Mandiri', 'Dokumen administrasi perbankan', 'M. Aziz Dwi Pratomo', '2026-08-21', 'Berkas', 3, 'JNE', 'Rizky Maulana'],
            ['PT Telkom Indonesia', 'Penawaran layanan korporat', 'Yanto Pujoyuwono', '2026-08-20', 'Surat', 1, 'SiCepat', null],
            ['Kantor Pajak Pratama', 'Permintaan kelengkapan dokumen', 'Ach. Fathur Rozi', '2026-08-19', 'Dokumen', 4, 'Pos Indonesia', 'Dewi Anggraini'],
            ['BPJS Ketenagakerjaan', 'Rekap kepesertaan karyawan', 'Yayak Andriyani', '2026-08-18', 'Berkas', 2, 'J&T Express', null],
            ['PT PLN Persero', 'Tagihan penggunaan listrik', 'M. Aziz Dwi Pratomo', '2026-08-17', 'Surat', 1, null, 'Andi Prasetyo'],
            ['CV Sumber Teknik', 'Sampel komponen pemeliharaan', 'Yanto Pujoyuwono', '2026-08-16', 'Paket', 2, 'JNE', null],
            ['Universitas Negeri Surabaya', 'Permohonan kunjungan industri', 'Ach. Fathur Rozi', '2026-08-15', 'Surat', 1, 'TIKI', 'Nadia Putri'],
            ['PT Pos Logistik Indonesia', 'Laporan pengiriman bulanan', 'Yayak Andriyani', '2026-08-14', 'Dokumen', 1, 'Pos Indonesia', null],
        ];

        $this->db->transStart();

        foreach ($incoming as $index => [$pengirim, $perihal, $penerima, $tanggal, $jenis, $jumlah, $ekspedisi, $pengambilan]) {
            $incomingBuilder = $this->db->table('dokumen_masuk');
            $existing = $incomingBuilder
                ->where('pengirim', $pengirim)
                ->where('perihal', $perihal)
                ->where('tanggal', $tanggal)
                ->get()
                ->getRowArray();

            if ($existing === null) {
                $incomingBuilder->insert([
                    'pengirim'          => $pengirim,
                    'perihal'           => $perihal,
                    'penerima'          => $penerima,
                    'hari'              => $this->dayName($tanggal),
                    'tanggal'           => $tanggal,
                    'jenis'             => $jenis,
                    'jumlah'            => $jumlah,
                    'ekspedisi'         => $ekspedisi,
                    'pengambilan'       => $pengambilan,
                    'penyerahan_at'     => $pengambilan !== null ? $now : null,
                    'tanggal_security'  => $pengambilan !== null ? $tanggal : null,
                    'created_at'        => $now,
                    'updated_at'        => $now,
                ]);
                $incomingId = (int) $this->db->insertID();
            } else {
                $incomingId = (int) $existing['id'];
            }

            if ($pengambilan === null) {
                continue;
            }

            $agendaBuilder = $this->db->table('agendaris');
            if ($agendaBuilder->where('dokumen_masuk_id', $incomingId)->countAllResults() === 0) {
                $agendaBuilder->insert([
                    'dokumen_masuk_id' => $incomingId,
                    'pengirim'         => $pengirim,
                    'penerima'         => $penerima,
                    'pengambilan'      => $pengambilan,
                    'jenis'            => $jenis,
                    'tanggal_diterima' => $tanggal,
                    'tanggal_surat'    => date('Y-m-d', strtotime($tanggal . ' -2 days')),
                    'nomor_surat'      => sprintf('AGD/DUMMY/%03d/VIII/2026', $index + 1),
                    'perihal_surat'    => $perihal,
                    'berkas_link'      => 'https://example.com/dokumen/agenda-' . ($index + 1),
                    'created_at'       => $now,
                    'updated_at'       => $now,
                ]);
            }
        }

        $outgoing = [
            ['SK/DUMMY/001/VIII/2026', 'Surat Tugas', 'Divisi Operasional', 'Ahmad Fauzi', 'Kepala Unit', '2026-08-18', 'Kantor Daop 8 Surabaya', null, null, null, 'Menunggu Ekspedisi'],
            ['SK/DUMMY/002/VIII/2026', 'Surat Balasan', 'Divisi SDM', 'Nina Kartika', 'Bagian Rekrutmen', '2026-08-19', 'Universitas Negeri Surabaya', null, null, null, 'Menunggu Ekspedisi'],
            ['SK/DUMMY/003/VIII/2026', 'Dokumen Kontrak', 'Divisi Umum', 'Bagus Pratama', 'Direktur CV Sumber Teknik', '2026-08-20', 'Jl. Raya Industri No. 15, Surabaya', 'Yanto Pujoyuwono', '2026-08-20', null, 'Menunggu Ekspedisi'],
            ['SK/DUMMY/004/VIII/2026', 'Laporan Bulanan', 'Divisi Akutansi', 'Dewi Lestari', 'Manajer Keuangan', '2026-08-21', 'Kantor Pusat Jakarta', 'M. Aziz Dwi Pratomo', '2026-08-21', 'RESI-DUMMY-004', 'Diambil Ekspedisi'],
            ['SK/DUMMY/005/VIII/2026', 'Surat Undangan', 'Sekretariat', 'Rian Hidayat', 'Pimpinan Instansi', '2026-08-22', 'Kementerian Perhubungan, Jakarta', null, null, null, 'Menunggu Ekspedisi'],
            ['SK/DUMMY/006/VIII/2026', 'Dokumen Evaluasi', 'Divisi Operasional', 'Sari Wulandari', 'Tim Evaluasi', '2026-08-23', 'PT Kereta Api Indonesia, Bandung', 'Ach. Fathur Rozi', '2026-08-23', 'RESI-DUMMY-006', 'Diambil Ekspedisi'],
            ['SK/DUMMY/007/VIII/2026', 'Surat Keterangan', 'Divisi SDM', 'Eko Saputra', 'BPJS Ketenagakerjaan', '2026-08-24', 'Kantor BPJS Ketenagakerjaan Surabaya', null, null, null, 'Menunggu Ekspedisi'],
            ['SK/DUMMY/008/VIII/2026', 'Berkas Administrasi', 'Divisi Akutansi', 'Maya Sari', 'Account Officer', '2026-08-25', 'Bank Mandiri Cabang Surabaya', 'Yayak Andriyani', '2026-08-25', null, 'Menunggu Ekspedisi'],
        ];

        foreach ($outgoing as $index => [$nomor, $jenis, $pemohon, $pelaksana, $up, $tanggal, $alamat, $security, $tanggalSecurity, $resi, $progres]) {
            $outgoingBuilder = $this->db->table('dokumen_keluar');
            $existing = $outgoingBuilder->where('nomor_surat', $nomor)->get()->getRowArray();

            if ($existing === null) {
                $outgoingBuilder->insert([
                    'nomor_surat'        => $nomor,
                    'jenis_surat'        => $jenis,
                    'jumlah_dokumen'     => (($index % 3) + 1) . ' berkas',
                    'nama_ekspedisi'     => ['JNE', 'TIKI', 'Pos Indonesia', 'J&T Express'][$index % 4],
                    'pemohon'            => $pemohon,
                    'pelaksana'          => $pelaksana,
                    'up'                 => $up,
                    'tanggal_pengiriman' => $tanggal,
                    'nomor_resi'         => $resi,
                    'tanggal_diterima'   => $progres === 'Diambil Ekspedisi' ? date('Y-m-d', strtotime($tanggal . ' +2 days')) : null,
                    'penerima'           => $progres === 'Diambil Ekspedisi' ? 'Petugas Penerima' : null,
                    'security'           => $security,
                    'tanggal_security'   => $tanggalSecurity,
                    'progres'            => $progres,
                    'alamat_penerima'    => $alamat,
                    'dokumen_link'       => 'https://example.com/dokumen/surat-keluar-' . ($index + 1),
                ]);
                $outgoingId = (int) $this->db->insertID();
            } else {
                $outgoingId = (int) $existing['id'];
                $outgoingBuilder->where('id', $outgoingId)->update([
                    'jumlah_dokumen' => (($index % 3) + 1) . ' berkas',
                    'nama_ekspedisi' => ['JNE', 'TIKI', 'Pos Indonesia', 'J&T Express'][$index % 4],
                ]);
            }

            $distributionBuilder = $this->db->table('distribusi_dokumen');
            if ($distributionBuilder->where('dokumen_keluar_id', $outgoingId)->countAllResults() === 0) {
                $distributionBuilder->insert(['dokumen_keluar_id' => $outgoingId]);
            }
        }

        $this->db->transComplete();

        if (! $this->db->transStatus()) {
            throw new \RuntimeException('Data dummy gagal dibuat.');
        }
    }

    private function dayName(string $date): string
    {
        return [1 => 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'][(int) date('N', strtotime($date))];
    }
}
