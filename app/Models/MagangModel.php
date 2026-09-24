<?php

namespace App\Models;

use CodeIgniter\Model;

final class MagangModel extends Model
{
    protected $table = 'sdm_magang';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useSoftDeletes = true;
    protected $useTimestamps = true;
    protected $allowedFields = [
        'nomor', 'nama_magang', 'nomor_kontrak_kerja', 'unit_kerja', 'jenis_magang',
        'tanggal_mulai', 'tanggal_selesai', 'link_pkk', 'keterangan', 'created_by', 'created_by_name',
        'deleted_by_role', 'deleted_by_name',
    ];
}
