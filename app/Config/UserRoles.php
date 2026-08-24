<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class UserRoles extends BaseConfig
{
    public const LABELS = [
        'admin'     => 'Administrator',
        'security'  => 'Security',
        'agendaris' => 'Agendaris',
        'umum'      => 'Umum',
        'akutansi'  => 'Akutansi',
        'sdm'       => 'SDM',
    ];

    public const MODULE_PREFIXES = [
        'security'  => ['dokumen-masuk', 'dokumen-keluar', 'distribusi-dokumen'],
        'agendaris' => ['agendaris'],
        'umum'      => [],
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
