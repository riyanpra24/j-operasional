<?php

namespace App\Models;

use CodeIgniter\Model;

class PksKerjasamaModel extends Model
{
    protected $table = 'pks_kerjasama';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useSoftDeletes = true;
    protected $useTimestamps = true;
    protected $allowedFields = ['mitra_id', 'kode_internal', 'nama_kerjasama', 'unit_pengelola', 'pic_internal', 'ruang_lingkup', 'keterangan'];
}
