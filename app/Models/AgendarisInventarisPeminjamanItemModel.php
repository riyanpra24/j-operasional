<?php

namespace App\Models;

use CodeIgniter\Model;

class AgendarisInventarisPeminjamanItemModel extends Model
{
    protected $table = 'agendaris_inventaris_peminjaman_item';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = ['peminjaman_id', 'inventaris_id', 'jumlah_dipinjam'];
}
