<?php

namespace App\Models;

use CodeIgniter\Model;

class PksDokumenModel extends Model
{
    protected $table = 'pks_dokumen_kerjasama';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = ['kerjasama_id', 'jenis_dokumen', 'urutan', 'nomor_dokumen', 'tanggal_dokumen', 'periode_mulai', 'jangka_waktu_bulan', 'periode_selesai', 'nilai', 'link_berkas', 'keterangan'];
}
