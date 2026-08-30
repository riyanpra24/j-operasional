<?php

namespace App\Controllers;

class PengadaanBarangJasa extends BaseController
{
    public function index(): string
    {
        return view('pengadaan_barang_jasa/index', [
            'title' => 'Pengadaan Barang Jasa',
        ]);
    }
}
