<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class SyncVehicleKilometerFromMaintenance extends Migration
{
    public function up(): void
    {
        if (! $this->db->tableExists('vehicles') || ! $this->db->tableExists('vehicle_maintenance')) {
            return;
        }

        $vehicles = $this->db->table('vehicles')->select('id')->get()->getResultArray();
        foreach ($vehicles as $vehicle) {
            $service = $this->db->table('vehicle_maintenance')
                ->selectMax('kilometer', 'kilometer_terakhir')
                ->where('vehicle_id', $vehicle['id'])
                ->where('deleted_at', null)
                ->get()
                ->getRowArray();

            $this->db->table('vehicles')->where('id', $vehicle['id'])->update([
                'kilometer' => (int) ($service['kilometer_terakhir'] ?? 0),
            ]);
        }
    }

    public function down(): void
    {
        // Nilai kilometer sebelumnya tidak dapat direkonstruksi secara aman.
    }
}
