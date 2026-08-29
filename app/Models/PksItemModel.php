<?php

namespace App\Models;

use CodeIgniter\Model;

class PksItemModel extends Model
{
    protected $table = 'pks_item_kerjasama';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = ['kerjasama_id', 'keterangan'];
}
