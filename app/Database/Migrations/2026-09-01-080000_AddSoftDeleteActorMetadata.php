<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddSoftDeleteActorMetadata extends Migration
{
    private const TABLES = [
        'agendaris',
        'dokumen_keluar',
        'dokumen_masuk',
        'dokumen_spk',
        'pks_mitra',
        'pks_kerjasama',
        'pks_dokumen_kerjasama',
        'pks_item_kerjasama',
        'users',
        'vehicles',
        'vehicle_maintenance',
        'vehicle_documents',
    ];

    public function up()
    {
        foreach (self::TABLES as $table) {
            $fields = [];
            if (! $this->db->fieldExists('deleted_by_role', $table)) {
                $fields['deleted_by_role'] = ['type' => 'VARCHAR', 'constraint' => 40, 'null' => true];
            }
            if (! $this->db->fieldExists('deleted_by_name', $table)) {
                $fields['deleted_by_name'] = ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true];
            }
            if ($fields !== []) {
                $this->forge->addColumn($table, $fields);
            }
        }
    }

    public function down()
    {
        foreach (self::TABLES as $table) {
            foreach (['deleted_by_role', 'deleted_by_name'] as $column) {
                if ($this->db->fieldExists($column, $table)) {
                    $this->forge->dropColumn($table, $column);
                }
            }
        }
    }
}
