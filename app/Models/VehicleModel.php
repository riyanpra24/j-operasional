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
        'nomor_polisi', 'nama_kendaraan', 'nama_kendaraan_lainnya', 'jenis', 'status_kendaraan', 'merek', 'tipe', 'tahun', 'warna',
        'nomor_rangka', 'nomor_mesin', 'unit_pengguna', 'unit_pengguna_lainnya', 'pic', 'pic_internal', 'kilometer', 'status', 'status_lainnya', 'created_by',
    ];
}
