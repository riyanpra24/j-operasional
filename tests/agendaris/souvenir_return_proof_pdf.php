<?php

declare(strict_types=1);

defined('ROOTPATH') || define('ROOTPATH', dirname(__DIR__, 2) . DIRECTORY_SEPARATOR);
defined('APPPATH') || define('APPPATH', ROOTPATH . 'app' . DIRECTORY_SEPARATOR);
require APPPATH . 'Libraries/SouvenirPickupProofPdf.php';
require APPPATH . 'Libraries/SouvenirReturnProofPdf.php';

use App\Libraries\SouvenirReturnProofPdf;

$template = APPPATH . 'Resources' . DIRECTORY_SEPARATOR . 'pdf' . DIRECTORY_SEPARATOR . 'bukti_pengembalian_sisa_souvenir.jpg';
if (! is_file($template)) throw new RuntimeException('Template bukti pengembalian souvenir tidak ditemukan saat pengujian.');

$pdf = (new SouvenirReturnProofPdf($template))->render([
    'nama_pengembali' => 'Ayu Pramesti',
    'divisi_bagian' => 'Divisi Umum',
    'keperluan_kegiatan' => 'Kegiatan relasi dan kunjungan mitra',
    'tanggal_pengembalian' => '2026-10-23',
    'keterangan' => 'Sisa souvenir dari kegiatan kunjungan mitra.',
    'petugas' => 'Petugas Agendaris',
    'items' => [
        ['nama_item' => 'Gantungan kunci', 'jumlah_dikembalikan' => 2, 'satuan' => 'Box'],
        ['nama_item' => 'Kalender meja', 'jumlah_dikembalikan' => 1, 'satuan' => 'Pcs'],
    ],
]);
if (! str_starts_with($pdf, '%PDF-1.4')) throw new RuntimeException('Bukti pengembalian tidak menghasilkan dokumen PDF.');

$output = ROOTPATH . 'output/pdf/bukti-pengembalian-souvenir-contoh.pdf';
if (! is_dir(dirname($output)) && ! mkdir(dirname($output), 0775, true) && ! is_dir(dirname($output))) throw new RuntimeException('Folder keluaran PDF tidak dapat dibuat.');
file_put_contents($output, $pdf);
echo "Bukti pengembalian souvenir PDF: passed\n";
