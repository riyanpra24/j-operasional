<?php

namespace App\Modules\BagianUmum\Controllers;

use App\Controllers\BaseController;

class PengadaanBarangJasa extends BaseController
{
    public function index(): string
    {
        return view('modules/bagian_umum/pengadaan_barang_jasa/index', [
            'title' => 'Pengadaan Barang Jasa',
        ]);
    }
}
