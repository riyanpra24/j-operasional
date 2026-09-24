<?php

namespace App\Controllers;

use Config\UserRoles;

class Welcome extends BaseController
{
    public function index(): string
    {
        $role = (string) session()->get('auth_role');
        $nextPages = [
            'admin' => 'dashboard',
            'security' => 'dokumen-masuk',
            'agendaris' => 'agendaris/surat-masuk',
            'umum_1' => 'bagian-umum-1/pks-barang-jasa',
            'umum_2' => 'bagian-umum-2/monitoring-kendaraan/data-kendaraan',
            'akutansi' => 'akutansi/laba-rugi',
            'sdm' => 'sdm/dokumen-masuk',
        ];

        return view('welcome/index', [
            'title' => 'Selamat Datang',
            'displayName' => (string) session()->get('auth_display_name'),
            'roleLabel' => UserRoles::label($role),
            'nextUrl' => site_url($nextPages[$role] ?? 'dashboard'),
        ]);
    }
}
