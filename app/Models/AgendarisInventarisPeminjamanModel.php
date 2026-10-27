<?php

namespace App\Models;

use CodeIgniter\Model;

class AgendarisInventarisPeminjamanModel extends Model
{
    protected $table = 'agendaris_inventaris_peminjaman';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useSoftDeletes = true;
    protected $useTimestamps = true;
    protected $allowedFields = ['nama_peminjam', 'divisi_bagian', 'keperluan_kegiatan', 'keterangan', 'inventaris_id', 'tanggal_peminjaman'];
}
