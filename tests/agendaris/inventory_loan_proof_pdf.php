<?php

declare(strict_types=1);

defined('ROOTPATH') || define('ROOTPATH', dirname(__DIR__, 2) . DIRECTORY_SEPARATOR);
defined('APPPATH') || define('APPPATH', ROOTPATH . 'app' . DIRECTORY_SEPARATOR);
require APPPATH . 'Libraries/SouvenirPickupProofPdf.php';
require APPPATH . 'Libraries/InventoryLoanProofPdf.php';

use App\Libraries\InventoryLoanProofPdf;

$pdf = (new InventoryLoanProofPdf())->render([
    'nama_peminjam' => 'Budi Santoso', 'divisi_bagian' => 'Divisi Umum', 'keperluan_kegiatan' => 'Presentasi kantor', 'tanggal_peminjaman' => '2026-10-26',
    'keterangan' => 'Digunakan untuk kegiatan presentasi.', 'petugas' => 'Petugas Agendaris',
    'items' => [['nama_item' => 'Proyektor', 'jumlah_dipinjam' => 1, 'satuan' => 'Unit']],
]);
if (! str_starts_with($pdf, '%PDF-1.4')) throw new RuntimeException('Bukti peminjaman inventaris tidak menghasilkan dokumen PDF.');
$output = ROOTPATH . 'output/pdf/bukti-peminjaman-inventaris-contoh.pdf';
if (! is_dir(dirname($output)) && ! mkdir(dirname($output), 0775, true) && ! is_dir(dirname($output))) throw new RuntimeException('Folder keluaran PDF tidak dapat dibuat.');
file_put_contents($output, $pdf);
echo "Bukti peminjaman inventaris PDF: passed\n";
