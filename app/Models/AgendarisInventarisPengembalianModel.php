<?php

namespace App\Models;

use CodeIgniter\Model;

class AgendarisInventarisPengembalianModel extends Model
{
    protected $table = 'agendaris_inventaris_pengembalian';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useSoftDeletes = true;
    protected $useTimestamps = true;
    protected $allowedFields = ['nama_pengembali', 'divisi_bagian', 'keperluan_kegiatan', 'keterangan', 'inventaris_id', 'tanggal_pengembalian'];
}
