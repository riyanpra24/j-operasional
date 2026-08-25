<?php

namespace App\Models;

use CodeIgniter\Model;

class DokumenMasukModel extends Model
{
    protected $table          = 'dokumen_masuk';
    protected $primaryKey     = 'id';
    protected $returnType     = 'array';
    protected $useSoftDeletes = true;
<<<<<<< HEAD
    protected $allowedFields  = ['pengirim', 'perihal', 'penerima', 'hari', 'tanggal', 'jenis', 'jumlah', 'ekspedisi', 'pengambilan', 'tanggal_security', 'penyerahan_at'];
=======
    protected $allowedFields  = ['pengirim', 'perihal', 'penerima', 'hari', 'tanggal', 'jenis', 'jumlah', 'ekspedisi', 'pengambilan', 'pengambilan_at', 'tanggal_security'];
>>>>>>> 3fb4f07c1d3125ff5fb057a935357640b39148e3

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';
}
