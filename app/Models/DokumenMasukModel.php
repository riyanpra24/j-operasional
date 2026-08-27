<?php

namespace App\Models;

use CodeIgniter\Model;

class DokumenMasukModel extends Model
{
    protected $table          = 'dokumen_masuk';
    protected $primaryKey     = 'id';
    protected $returnType     = 'array';
    protected $useSoftDeletes = true;
    protected $allowedFields  = ['pengirim', 'perihal', 'penerima', 'hari', 'tanggal', 'jenis', 'jumlah', 'satuan_jumlah', 'ekspedisi', 'pengambilan', 'tanggal_security', 'penyerahan_at', 'serah_terima_shift_at', 'serah_terima_shift_oleh', 'security_penanggung_jawab'];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';
}
