<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class EnableSoftDeletesAcrossApplication extends Migration
{
    private const TABLES = [
        'agendaris',
        'dokumen_keluar',
        'dokumen_spk',
        'pks_mitra',
        'pks_kerjasama',
        'pks_dokumen_kerjasama',
        'pks_item_kerjasama',
        'users',
    ];

    public function up()
    {
        foreach (self::TABLES as $table) {
            if (! $this->db->fieldExists('deleted_at', $table)) {
                $this->forge->addColumn($table, [
                    'deleted_at' => [
                        'type' => 'DATETIME',
                        'null' => true,
                    ],
                ]);
            }
        }
    }

    public function down()
    {
        foreach (self::TABLES as $table) {
            if ($this->db->fieldExists('deleted_at', $table)) {
                $this->forge->dropColumn($table, 'deleted_at');
            }
        }
    }
}
