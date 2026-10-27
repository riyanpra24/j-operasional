<?php

declare(strict_types=1);

defined('ROOTPATH') || define('ROOTPATH', dirname(__DIR__, 2) . DIRECTORY_SEPARATOR);
defined('APPPATH') || define('APPPATH', ROOTPATH . 'app' . DIRECTORY_SEPARATOR);
require APPPATH . 'Libraries/SouvenirPickupProofPdf.php';

use App\Libraries\SouvenirPickupProofPdf;

$template = APPPATH . 'Resources' . DIRECTORY_SEPARATOR . 'pdf' . DIRECTORY_SEPARATOR . 'bukti_pengambilan_souvenir_baru.jpg';
if (! is_file($template)) {
    throw new RuntimeException('Template bukti pengambilan souvenir tidak ditemukan saat pengujian.');
}

$pdf = (new SouvenirPickupProofPdf($template))->render([
    'nama_pengambil' => 'Ayu Pramesti',
    'divisi_bagian' => 'Divisi Umum',
    'keperluan_kegiatan' => 'Kegiatan relasi dan kunjungan mitra',
    'tanggal_pengambilan' => '2026-10-23',
    'keterangan' => 'Diserahkan untuk mendukung kegiatan kunjungan mitra.',
    'petugas' => 'Petugas Agendaris',
    'items' => [
        ['nama_item' => 'Gantungan kunci', 'jumlah_diambil' => 5, 'satuan' => 'Box'],
        ['nama_item' => 'Kalender meja', 'jumlah_diambil' => 3, 'satuan' => 'Pcs'],
    ],
]);

if (! str_starts_with($pdf, '%PDF-1.4')) {
    throw new RuntimeException('Bukti pengambilan tidak menghasilkan dokumen PDF.');
}

$output = ROOTPATH . 'output/pdf/bukti-pengambilan-souvenir-contoh.pdf';
if (! is_dir(dirname($output)) && ! mkdir(dirname($output), 0775, true) && ! is_dir(dirname($output))) {
    throw new RuntimeException('Folder keluaran PDF tidak dapat dibuat.');
}

file_put_contents($output, $pdf);

echo "Bukti pengambilan souvenir PDF: passed\n";
