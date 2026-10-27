<?php

namespace App\Models;

use CodeIgniter\Model;

class AgendarisInventarisPengembalianItemModel extends Model
{
    protected $table = 'agendaris_inventaris_pengembalian_item';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = ['pengembalian_id', 'inventaris_id', 'jumlah_dikembalikan'];
}
