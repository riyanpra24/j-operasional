<?php

namespace App\Models;

use CodeIgniter\Model;

class AgendarisSouvenirInventarisModel extends Model
{
    protected $table = 'agendaris_souvenir_inventaris';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useSoftDeletes = true;
    protected $useTimestamps = true;
    protected $allowedFields = ['nama_item', 'jenis', 'satuan', 'persediaan_awal', 'persediaan_akhir', 'terpakai', 'keterangan'];
}
