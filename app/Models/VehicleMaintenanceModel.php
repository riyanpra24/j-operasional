<?php

namespace App\Models;

use CodeIgniter\Model;

class VehicleMaintenanceModel extends Model
{
    protected $table = 'vehicle_maintenance';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useSoftDeletes = true;
    protected $useTimestamps = true;
    protected $allowedFields = [
        'vehicle_id', 'tanggal_servis', 'jenis_perawatan', 'bengkel', 'kilometer', 'biaya',
        'servis_berikutnya_tanggal', 'anggaran_servis', 'nama_perusahaan', 'keterangan', 'link_berkas', 'created_by',
    ];
}
