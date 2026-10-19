<?php

namespace App\Models;

use CodeIgniter\Model;

class DataAsetModel extends Model
{
    protected $table = 'data_aset';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useSoftDeletes = true;
    protected $useTimestamps = true;
    protected $allowedFields = ['kategori', 'kelompok_aset', 'kodefikasi_aset', 'nomor_aset', 'nama_aset', 'status_aset', 'tanggal_perolehan', 'tanggal_habis_manfaat', 'masa_manfaat', 'sisa_masa_manfaat', 'lokasi', 'foto_aset'];
}
