<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class UserRoles extends BaseConfig
{
    public const LABELS = [
        'admin'     => 'Administrator',
        'security'  => 'Security',
        'agendaris' => 'Agendaris',
        'umum_1'    => 'Umum 1',
        'umum_2'    => 'Umum 2',
        'akutansi'  => 'Akuntansi',
        'sdm'       => 'SDM & Teller',
    ];

    public const MODULE_PREFIXES = [
        'security'  => ['dokumen-masuk', 'dokumen-keluar', 'distribusi-dokumen'],
        'agendaris' => ['agendaris'],
        'umum_1'    => ['bagian-umum-1'],
        'umum_2'    => ['bagian-umum-2'],
        'akutansi'  => ['akutansi'],
        'sdm'       => ['sdm'],
    ];

    /** Halaman pertama yang dituju setelah pengguna memilih Mulai. */
    public const DEFAULT_ROUTES = [
        'admin'     => 'dashboard',
        'security'  => 'dokumen-masuk',
        'agendaris' => 'agendaris/surat-masuk',
        'umum_1'    => 'bagian-umum-1/pks-barang-jasa',
        'umum_2'    => 'bagian-umum-2/monitoring-kendaraan/data-kendaraan',
        'akutansi'  => 'akutansi/laba-rugi',
        'sdm'       => 'sdm/dokumen-masuk',
    ];

    public static function label(string $role): string
    {
        return self::LABELS[$role] ?? ucfirst($role);
    }

    public static function isValid(string $role): bool
    {
        return array_key_exists($role, self::LABELS);
    }

    public static function defaultRoute(string $role): string
    {
        return self::DEFAULT_ROUTES[$role] ?? self::DEFAULT_ROUTES['admin'];
    }
}
