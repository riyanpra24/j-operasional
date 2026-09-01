<?php

namespace App\Models;

use CodeIgniter\Model;

class AgendarisModel extends Model
{
    protected $table         = 'agendaris';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useSoftDeletes = true;
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
        'disposisi_1',
        'disposisi_1_status',
        'disposisi_1_waktu',
        'disposisi_1_catatan',
        'disposisi_2',
        'disposisi_2_status',
        'disposisi_2_waktu',
        'disposisi_2_catatan',
        'disposisi_3',
        'disposisi_3_status',
        'disposisi_3_waktu',
        'disposisi_3_catatan',
        'progres',
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';
}
