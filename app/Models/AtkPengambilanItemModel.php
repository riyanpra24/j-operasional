<?php

namespace App\Models;

use CodeIgniter\Model;

class AtkPengambilanItemModel extends Model
{
    protected $table = 'agendaris_atk_pengambilan_item';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = ['pengambilan_id', 'atk_id', 'jumlah_diambil'];
}
