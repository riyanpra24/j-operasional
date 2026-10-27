<?php

namespace App\Models;

use CodeIgniter\Model;

class AgendarisSouvenirPengembalianItemModel extends Model
{
    protected $table = 'agendaris_souvenir_pengembalian_item';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = ['pengembalian_id', 'souvenir_id', 'jumlah_dikembalikan'];
}
