<?php

namespace App\Models;

use CodeIgniter\Model;

class AgendarisSouvenirPengembalianModel extends Model
{
    protected $table = 'agendaris_souvenir_pengembalian';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useSoftDeletes = true;
    protected $useTimestamps = true;
    protected $allowedFields = ['nama_pengembali', 'divisi_bagian', 'keperluan_kegiatan', 'keterangan', 'souvenir_id', 'tanggal_pengembalian'];
}
