<?php

namespace App\Models;

use CodeIgniter\Model;

class VehicleDocumentModel extends Model
{
    protected $table = 'vehicle_documents';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useSoftDeletes = true;
    protected $useTimestamps = true;
    protected $allowedFields = [
        'vehicle_id', 'jenis_dokumen', 'nomor_dokumen', 'tanggal_terbit', 'masa_berlaku',
        'link_berkas', 'keterangan', 'created_by',
    ];
}
