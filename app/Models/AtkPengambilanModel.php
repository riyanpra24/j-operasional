<?php

namespace App\Models;

use CodeIgniter\Model;

class AtkPengambilanModel extends Model
{
    protected $table = 'agendaris_atk_pengambilan';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useSoftDeletes = true;
    protected $useTimestamps = true;
    protected $allowedFields = ['nama_pengambil', 'divisi_bagian', 'keperluan_kegiatan', 'keterangan', 'tanggal_pengambilan', 'deleted_by_role', 'deleted_by_name'];
}
