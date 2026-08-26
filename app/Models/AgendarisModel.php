<?php

namespace App\Models;

use CodeIgniter\Model;

class AgendarisModel extends Model
{
    protected $table         = 'agendaris';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $allowedFields = [
        'pengirim',
        'penerima',
        'pengambilan',
        'jenis',
        'tanggal_diterima',
        'tanggal_surat',
        'nomor_surat',
        'nomor_agendaris',
        'tanggal_agendaris',
        'perihal_surat',
        'berkas_link',
        'progres',
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
}
