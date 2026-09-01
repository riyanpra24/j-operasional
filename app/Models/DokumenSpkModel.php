<?php

namespace App\Models;

use CodeIgniter\Model;

class DokumenSpkModel extends Model
{
    protected $table = 'dokumen_spk';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useSoftDeletes = true;
    protected $useTimestamps = true;
    protected $allowedFields = [
        'nomor_urut',
        'jenis_dokumen',
        'nomor_dokumen',
        'tanggal_dokumen',
        'tahun',
        'perihal',
        'link_berkas',
        'created_by',
        'created_by_name',
    ];
}
