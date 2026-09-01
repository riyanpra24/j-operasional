<?php

namespace App\Models;

use CodeIgniter\Model;

class PksMitraModel extends Model
{
    protected $table = 'pks_mitra';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useSoftDeletes = true;
    protected $useTimestamps = true;
    protected $allowedFields = ['nama_mitra', 'alamat', 'nama_kontak', 'jabatan_kontak', 'telepon', 'email'];
}
