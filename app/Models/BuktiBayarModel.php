<?php

namespace App\Models;

use CodeIgniter\Model;

final class BuktiBayarModel extends Model
{
    protected $table = 'accounting_bukti_bayar';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useSoftDeletes = true;
    protected $useTimestamps = true;
    protected $allowedFields = [
        'nomor_memo',
        'nomor_voucher',
        'tanggal',
        'nominal',
        'kategori',
        'berkas_path',
        'berkas_nama',
        'berkas_mime',
        'berkas_url',
        'created_by',
        'created_by_name',
        'deleted_by_role',
        'deleted_by_name',
    ];
}
