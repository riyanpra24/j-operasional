<?php

namespace App\Models;

use CodeIgniter\Model;

class AgendarisSouvenirPengambilanItemModel extends Model
{
    protected $table = 'agendaris_souvenir_pengambilan_item';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = ['pengambilan_id', 'souvenir_id', 'jumlah_diambil'];
}
