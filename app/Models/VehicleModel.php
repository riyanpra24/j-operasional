<?php

namespace App\Models;

use CodeIgniter\Model;

class VehicleModel extends Model
{
    protected $table = 'vehicles';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useSoftDeletes = true;
    protected $useTimestamps = true;
    protected $allowedFields = [
        'nomor_polisi', 'nama_kendaraan', 'jenis', 'merek', 'tipe', 'tahun', 'warna',
        'nomor_rangka', 'nomor_mesin', 'unit_pengguna', 'pic', 'kilometer', 'status', 'created_by',
    ];
}
