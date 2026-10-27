<?php

namespace App\Models;

use CodeIgniter\Model;

class AgendarisSouvenirPengambilanModel extends Model
{
    protected $table = 'agendaris_souvenir_pengambilan';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useSoftDeletes = true;
    protected $useTimestamps = true;
    protected $allowedFields = ['nama_pengambil', 'divisi_bagian', 'keperluan_kegiatan', 'keterangan', 'souvenir_id', 'tanggal_pengambilan'];
}
