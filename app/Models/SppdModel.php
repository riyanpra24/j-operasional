<?php

namespace App\Models;

use CodeIgniter\Model;

final class SppdModel extends Model
{
    protected $table = 'sdm_sppd';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useSoftDeletes = true;
    protected $useTimestamps = true;
    protected $allowedFields = [
        'nomor_sppd', 'tanggal_sppd', 'nama', 'jabatan', 'tujuan', 'keperluan',
        'tanggal_berangkat', 'tanggal_kembali', 'total_hari', 'created_by', 'created_by_name',
        'deleted_by_role', 'deleted_by_name',
    ];
}
