<?php

namespace App\Models;

use CodeIgniter\Model;

class DokumenKeluarModel extends Model
{
    protected $table         = 'dokumen_keluar';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $allowedFields = [
        'nomor_surat',
        'jenis_surat',
        'pemohon',
        'pelaksana',
        'up',
        'tanggal_pengiriman',
        'nomor_resi',
        'tanggal_diterima',
        'penerima',
        'security',
        'tanggal_security',
        'progres',
        'alamat_penerima',
    ];
}
