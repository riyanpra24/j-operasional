<?php

namespace App\Models;

use CodeIgniter\Model;

class DokumenKeluarModel extends Model
{
    protected $table         = 'dokumen_keluar';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useSoftDeletes = true;
    protected $deletedField  = 'deleted_at';
    protected $allowedFields = [
        'nomor_surat',
        'jenis_surat',
        'jumlah_dokumen',
        'nama_ekspedisi',
        'pemohon',
        'pelaksana',
        'up',
        'tanggal_pengiriman',
        'nomor_resi',
        'tanggal_diterima',
        'penerima',
        'security',
        'tanggal_security',
        'diterima_security_at',
        'progres',
        'diambil_ekspedisi_at',
        'status_agendaris',
        'selesai_agendaris_at',
        'alamat_penerima',
        'dokumen_link',
    ];
}
