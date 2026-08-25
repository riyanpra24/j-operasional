<?php

namespace App\Models;

use CodeIgniter\Model;

class DokumenMasukModel extends Model
{
    protected $table          = 'dokumen_masuk';
    protected $primaryKey     = 'id';
    protected $returnType     = 'array';
    protected $useSoftDeletes = true;
    protected $allowedFields  = ['pengirim', 'perihal', 'penerima', 'hari', 'tanggal', 'jenis', 'jumlah', 'ekspedisi', 'pengambilan', 'pengambilan_at', 'tanggal_security'];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';
}
