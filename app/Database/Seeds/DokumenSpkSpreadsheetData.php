<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class DokumenSpkSpreadsheetData extends Seeder
{
    public function run(): void
    {
        $now = date('Y-m-d H:i:s');
        $source = 'Import TES SPK.xlsx';
        $records = [
            [1, 'SPK', '001/SPK/W.6/I/2026', '2026-01-28', 'Jasa Penyelenggara Acara/Event Organizer Kegiatan Olahraga Bersama Mitra PT Jamkrindo Kanwil Surabaya', 'https://jamkrindo365-my.sharepoint.com/:b:/g/personal/angger_wicaksono_jamkrindo_co_id/IQBTw7zEhFBJQLTYJVg9wdJ5ARj06wSBJ7evhWPciXe5uNQ?e=FWrCTj'],
            [2, 'SPK', '002/SPK/W.6/I/2026', '2026-01-29', 'Jasa Penyelenggara Acara/Event Organizer Kegiatan Olahraga Bersama Mitra PT Jamkrindo Kanwil Surabaya', 'https://jamkrindo365-my.sharepoint.com/:b:/g/personal/angger_wicaksono_jamkrindo_co_id/IQDNhByAumEERYY2d2EEQJwBAVxpyFMmFE7PShLPBY5TaGo?e=TPYSAY'],
            [3, 'SPK', '003/SPK/W.6/I/2026', '2026-01-29', 'Pekerjaan Pengadaan Realisasi (Genset) Barang Belanja Modal Genset Kantor Cabang Malang', 'https://jamkrindo365-my.sharepoint.com/:b:/g/personal/angger_wicaksono_jamkrindo_co_id/IQBh4Azmuz25Qo6x_HjyNGW5AcF0uV_L02-kPocTjTxjbaY?e=aBwdeh'],
            [4, 'SPK', '004/SPK/W.6-II/2026', '2026-02-09', 'Jasa Penyelenggara Acara/Event Rapat Koordinasi Wilayah (Rakorwil) Surabaya dan Peresmian Gedung Kantor Cabang Malang PT Jamkrindo Tahun 2026', 'https://jamkrindo365-my.sharepoint.com/:b:/g/personal/angger_wicaksono_jamkrindo_co_id/IQBhtIJ7gNRqQ5xpCIhjlq-tAZod1PkcxbRbh8M0KKMXjXw?e=wQHVPs'],
            [6, 'SPK', '005/SPK/W.6-II/2026', '2026-02-22', 'Pengadaan Kegiatan Olahraga Bersama Mitra Melalui Event Organizer PT Jamkrindo Tahun 2026', 'https://jamkrindo365-my.sharepoint.com/:b:/g/personal/angger_wicaksono_jamkrindo_co_id/IQCTQOfh6K_8S46jXuJ3oW49ATpC1CbWcYsX7OcC5zutgaI?e=EQ00IB'],
            [8, 'SPK', '006/SPK/W.6/II/2026', '2026-02-22', 'Pengadaan Sembako Safari Ramadan 1447H Kanwil Surabaya', 'https://jamkrindo365-my.sharepoint.com/:b:/g/personal/angger_wicaksono_jamkrindo_co_id/IQCXEhResOZITbGM7iYgKvInAac8LND7e6a0WFGsstRf5S0?e=Az7Z6U'],
            [9, 'SPK', '007/SPK/W.6/III/2026', '2026-03-08', 'Jasa Penyelenggara Acara/Event Organizer Kegiatan Olahraga Bersama Mitra PT Jamkrindo Kanwil Surabaya', 'https://jamkrindo365-my.sharepoint.com/:b:/g/personal/angger_wicaksono_jamkrindo_co_id/IQBnuX8piEu8RqCNPgUzYN-DAfBJMfmrGlFtA8im6VWX_fY?e=T3Ff6n'],
            [10, 'SPK', '008/SPK/W.6/III/2026', '2026-03-14', 'Jasa Penyelenggara Acara/Event Organizer Kegiatan Olahraga Bersama Mitra PT Jamkrindo Kanwil Surabaya', 'https://jamkrindo365-my.sharepoint.com/:b:/g/personal/angger_wicaksono_jamkrindo_co_id/IQD-EaChqTPcTaObG7Bm1ZfVAW2y6jg0175d6JAiXu1e5G8?e=hOaeTy'],
            [11, 'SPK', '009/SPK/W.6/IV/2026', '2026-04-16', 'Pekerjaan Pengadaan Belanja Modal Genset Kantor Cabang Kediri Tahun 2026', 'https://jamkrindo365-my.sharepoint.com/:b:/g/personal/angger_wicaksono_jamkrindo_co_id/IQCU8eql1JAGT4ITE0mPsaoOAdMwYm_Bwmqn3dpCtE6Xvpo?e=TzYedh'],
            [12, 'SPK', '010/SPK/W.6/IV/2026', '2026-04-16', 'Pekerjaan Pengadaan Pengurusan Perizinan Sertifikat Laik Fungsi (SLF) Kantor Cabang Malang', 'https://jamkrindo365-my.sharepoint.com/:b:/g/personal/angger_wicaksono_jamkrindo_co_id/IQDSmf1SYl67RIob4U8v5IgpAbNCQCRAYNeekfoWNi9-opw?e=CmLbVU'],
            [13, 'SPK', '011/SPK/W.6/IV/2026', '2026-04-26', 'Jasa Penyelenggara Acara/Event Organizer Kegiatan Olahraga Bersama Mitra PT Jamkrindo Kanwil Surabaya', 'https://jamkrindo365-my.sharepoint.com/:b:/g/personal/angger_wicaksono_jamkrindo_co_id/IQDTnZCp-W5aSbtvDE_-fjPQAYc9BBCkuNFg8gal--baT90?e=2KjEGe'],
            [14, 'SPK', '012/SPK/W.6/V/2026', '2026-05-02', 'Pengadaan Jasa Penyelenggara Acara/Event Kegiatan Monitoring Evaluasi Kantor Wilayah Surabaya 2026', 'https://jamkrindo365-my.sharepoint.com/:b:/g/personal/angger_wicaksono_jamkrindo_co_id/IQBWb6X08dU3Rb76lI9GiAR8AeGvqlxujohuKifTuV2QSWc?e=gNZvk3'],
            [15, 'SPK', '013/SPK/W.6/V/2026', '2026-05-13', 'Pengadaan Jasa Event Organizer Kegiatan Undangan Acara Grand Launching Gerakan Pengendalian Inflasi dan Pangan Sejahtera (GPIPS) Serta Rapat Koordinasi (RAKOR) TPIP-TPID Wilayah Jawa 2026', 'https://jamkrindo365-my.sharepoint.com/:b:/g/personal/angger_wicaksono_jamkrindo_co_id/IQAb5G0g5m82RIMN4SdvoaSjAbVhB0OqjsIxJK49LN7tx4A?e=u5gbho'],
            [16, 'SPK', '014/SPK/W.6/VI/2026', '2026-06-04', 'Pengadaan Jasa Event Organizer Kegiatan Monitoring Evaluasi Kanwil Surabaya 8 Juni 2026, Kunjungan/Audiensi Dengan Mitra Perbankan & Olahraga Bersama Mitra', null],
            [17, 'SPK', '015/SPK/W.6/VI/2026', '2026-06-06', 'Pengadaan Jasa Event Organizer Kegiatan Pendampingan Direksi Kunjungan/Audiensi Mitra - JAMPIDUM 6 - 8 Juni 2026', 'https://jamkrindo365-my.sharepoint.com/:b:/g/personal/angger_wicaksono_jamkrindo_co_id/IQAm9SZxIDDNT6lZDtrNioRiARcShPEJOjAX_rflJSQxsGY?e=wnfUjo'],
            [18, 'SPK', '016/SPK/W.6/VI/2026', '2026-06-13', 'Pengadaan Jasa Event Organizer Olahraga Bersama Mitra Tanggal 13 Juni 2026', 'https://jamkrindo365-my.sharepoint.com/:b:/g/personal/angger_wicaksono_jamkrindo_co_id/IQA9LxdEEUfPRYrBsc3VLiyeAYoJvOfrMO0S1JCpEqdVEdc?e=6X0VLy'],
            [19, 'SPK', '017/SPK/W.6/VI/2026', '2026-06-14', 'Pengadaan Jasa Event Organizer Olahraga Bersama Mitra Tanggal 14 Juni 2026', 'https://jamkrindo365-my.sharepoint.com/:b:/g/personal/angger_wicaksono_jamkrindo_co_id/IQDqB4izELJAT7ro0X04lZycAQG1Wtp_oZ7B7rOTAXsRuq4?e=xdtRis'],
            [20, 'SPK', '018/SPK/W.6/VII/2026', '2026-07-15', 'Pengadaan Jasa Event Organizer Dukungan Fasilitas Kegiatan Kemenko Perekonomian di Surabaya 15 - 17 Juli 2026', 'https://jamkrindo365-my.sharepoint.com/:b:/g/personal/angger_wicaksono_jamkrindo_co_id/IQD-f-wKBRTjToS-mAXC-87lAcQCFweL9UqbIifi6wpdlEo?e=TqfPaV'],
            [21, 'SPK', '019/SPK/W.6/VII/2026', '2026-07-18', 'Pengadaan Event Organizer Kegiatan Olahraga Bersama Mitra 18 Juli 2026 Ciputra Golf', 'https://jamkrindo365-my.sharepoint.com/:b:/g/personal/angger_wicaksono_jamkrindo_co_id/IQA2wb0-4Ud2R55d_j_IGM3_Ade_qf8jwnAgLEWcY_gzyoE?e=blCbeK'],
            [22, 'SPK', '020/SPK/W.6/VII/2026', null, 'Pengadaan Perbaikan Toilet dan Ruang Kerja Lantai 3', null],
            [23, 'SPK', '021/SPK/W.6/VII/2026', '2026-07-26', 'Pengadaan Event Organizer Kegiatan Olahraga Bersama Mitra 26 Juli 2026 Ciputra Golf', 'https://jamkrindo365-my.sharepoint.com/:b:/g/personal/angger_wicaksono_jamkrindo_co_id/IQAmf-waTyK1Sbo6_bQopzRyAWXx0Zj7NefZUVUnLQTxIq0?e=cF1xtG'],
            [24, 'SPK', '022/SPK/W.6/VII/2026', '2026-07-31', 'Pengadaan Jasa Event Organizer Dukungan Fasilitas Kegiatan Kemenko Perekonomian di Surabaya 31 Juli - 1 Agustus 2026', null],
            [25, 'SPK', '023/SPK/W.6/VIII/2026', '2026-08-02', 'Pengadaan Event Organizer Kegiatan Olahraga Bersama Mitra 2 Agustus 2026 Ciputra Golf', 'https://jamkrindo365-my.sharepoint.com/:b:/g/personal/angger_wicaksono_jamkrindo_co_id/IQDKBxi7eSTISaCeJsuQh8ITASqgT_AJZ1ZmlkaOScvdqBc?e=0OgosK'],
            [26, 'SPK', '024/SPK/W.6/VIII/2026', null, 'Pengadaan Sewa Kendaraan Operasional PT Jamkrindo Kantor Cabang Surabaya', null],
            [27, 'SPK', '025/SPK/W.6/VIII/2026', '2026-08-09', 'Pengadaan Event Organizer Kegiatan Olahraga Bersama Mitra 9 Agustus 2026 Ciputra Golf', 'https://jamkrindo365-my.sharepoint.com/:b:/g/personal/angger_wicaksono_jamkrindo_co_id/IQCiuuuZtoMbSKo1vOW5ymGMAfdMIUjIYpRxXIU110ojSlo?e=Yjdi6P'],
            [28, 'SPK', '026/SPK/W.6/VIII/2026', '2026-08-22', 'Pengadaan Event Organizer Kegiatan Olahraga Bersama Mitra 22 Agustus 2026 Taman Dayu Golf', 'https://jamkrindo365-my.sharepoint.com/:b:/g/personal/angger_wicaksono_jamkrindo_co_id/IQD8ioiWZ23JQp3RYQSXhbz_ATOe8QjYpnbx8VFAIvx_gUM?e=dwLdZ4'],
        ];

        $this->db->transStart();
        $this->db->table('dokumen_spk')->where('created_by_name', $source)->delete();

        $sequence = 0;
        foreach ($records as [, $type, $number, $date, $subject, $link]) {
            $sequence++;
            $this->db->table('dokumen_spk')->insert([
                'nomor_urut' => $sequence,
                'jenis_dokumen' => $type,
                'nomor_dokumen' => $number,
                'tanggal_dokumen' => $date,
                'tahun' => 2026,
                'perihal' => $subject,
                'link_berkas' => $link,
                'created_by' => null,
                'created_by_name' => $source,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $this->db->transComplete();
    }
}
