<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class NormalizeVehicleServiceBudget extends Migration
{
    public function up(): void
    {
        if (! $this->db->tableExists('vehicle_maintenance')
            || ! $this->db->fieldExists('anggaran_servis', 'vehicle_maintenance')
            || ! $this->db->fieldExists('nama_perusahaan', 'vehicle_maintenance')) {
            return;
        }

        $this->db->table('vehicle_maintenance')
            ->groupStart()
                ->where('anggaran_servis', null)
                ->orWhere('anggaran_servis', '')
            ->groupEnd()
            ->update([
                'anggaran_servis' => 'Kantor',
                'nama_perusahaan' => 'PT. Jaminan Kredit Indonesia (Persero)',
            ]);
    }

    public function down(): void
    {
        // Normalisasi data lama tidak dibatalkan agar data servis tetap valid.
    }
}
