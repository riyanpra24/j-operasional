<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddVehicleServiceBudget extends Migration
{
    private const OFFICE_COMPANY = 'PT. Jaminan Kredit Indonesia (Persero)';

    public function up(): void
    {
        if (! $this->db->tableExists('vehicle_maintenance')) {
            return;
        }

        if (! $this->db->fieldExists('anggaran_servis', 'vehicle_maintenance')) {
            $this->forge->addColumn('vehicle_maintenance', [
                'anggaran_servis' => [
                    'type' => 'VARCHAR',
                    'constraint' => 30,
                    'null' => true,
                    'after' => 'servis_berikutnya_tanggal',
                ],
            ]);
        }

        if (! $this->db->fieldExists('nama_perusahaan', 'vehicle_maintenance')) {
            $this->forge->addColumn('vehicle_maintenance', [
                'nama_perusahaan' => [
                    'type' => 'VARCHAR',
                    'constraint' => 150,
                    'null' => true,
                    'after' => 'anggaran_servis',
                ],
            ]);
        }

        $this->db->table('vehicle_maintenance')->update([
            'anggaran_servis' => 'Kantor',
            'nama_perusahaan' => self::OFFICE_COMPANY,
        ]);
    }

    public function down(): void
    {
        if (! $this->db->tableExists('vehicle_maintenance')) {
            return;
        }

        foreach (['nama_perusahaan', 'anggaran_servis'] as $field) {
            if ($this->db->fieldExists($field, 'vehicle_maintenance')) {
                $this->forge->dropColumn('vehicle_maintenance', $field);
            }
        }
    }
}
