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
        'akutansi'  => 'Akutansi',
        'sdm'       => 'SDM',
    ];

    public const MODULE_PREFIXES = [
        'security'  => ['dokumen-masuk', 'dokumen-keluar', 'distribusi-dokumen'],
        'agendaris' => ['agendaris'],
        'umum_1'    => ['bagian-umum-1'],
        'umum_2'    => ['bagian-umum-2'],
        'akutansi'  => [],
        'sdm'       => [],
    ];

    public static function label(string $role): string
    {
        return self::LABELS[$role] ?? ucfirst($role);
    }

    public static function isValid(string $role): bool
    {
        return array_key_exists($role, self::LABELS);
    }
}
