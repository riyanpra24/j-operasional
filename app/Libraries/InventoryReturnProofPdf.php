<?php

namespace App\Libraries;

class InventoryReturnProofPdf
{
    public function __construct(
        private readonly string $templatePath = APPPATH . 'Resources/pdf/bukti-pengembalian-inventaris.jpg'
    ) {
    }

    /** @param array{nama_pengembali: string, divisi_bagian: string, keperluan_kegiatan: string, tanggal_pengembalian: string, keterangan?: string|null, petugas?: string|null, items: list<array{nama_item: string, jumlah_dikembalikan: int, satuan: string}>} $data */
    public function render(array $data): string
    {
        return (new SouvenirPickupProofPdf($this->templatePath, '0.000 0.310 0.220'))->render([
            'nama_pengambil' => $data['nama_pengembali'], 'divisi_bagian' => $data['divisi_bagian'], 'keperluan_kegiatan' => $data['keperluan_kegiatan'],
            'tanggal_pengambilan' => $data['tanggal_pengembalian'], 'keterangan' => $data['keterangan'] ?? null, 'petugas' => $data['petugas'] ?? null,
            'items' => array_map(static fn (array $item): array => ['nama_item' => $item['nama_item'], 'jumlah_diambil' => $item['jumlah_dikembalikan'], 'satuan' => $item['satuan']], $data['items']),
        ]);
    }
}
