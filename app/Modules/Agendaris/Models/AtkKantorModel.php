<?php

namespace App\Modules\Agendaris\Models;

use CodeIgniter\Model;

class AtkKantorModel extends Model
{
    protected $table = 'agendaris_atk_kantor';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useSoftDeletes = true;
    protected $useTimestamps = true;
    protected $allowedFields = ['nama_barang', 'merek', 'stok_barang', 'jumlah', 'satuan', 'kondisi', 'keterangan'];
}
