<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddVehicleCustomName extends Migration
{
    public function up(): void
    {
        if (! $this->db->tableExists('vehicles')) {
            return;
        }

        if (! $this->db->fieldExists('nama_kendaraan_lainnya', 'vehicles')) {
            $this->forge->addColumn('vehicles', [
                'nama_kendaraan_lainnya' => [
                    'type' => 'VARCHAR',
                    'constraint' => 150,
                    'null' => true,
                    'after' => 'nama_kendaraan',
                ],
            ]);
        }

        $allowedNames = [
            'Kendaraan Pinwil',
            'Kendaraan Wakil Pinwil',
            'Kendaraan Operasional Kantor',
            'Lainnya',
        ];
        $records = $this->db->table('vehicles')
            ->select('id, nama_kendaraan')
            ->whereNotIn('nama_kendaraan', $allowedNames)
            ->get()
            ->getResultArray();

        foreach ($records as $record) {
            $this->db->table('vehicles')->where('id', $record['id'])->update([
                'nama_kendaraan' => 'Lainnya',
                'nama_kendaraan_lainnya' => $record['nama_kendaraan'],
            ]);
        }
    }

    public function down(): void
    {
        if (! $this->db->tableExists('vehicles') || ! $this->db->fieldExists('nama_kendaraan_lainnya', 'vehicles')) {
            return;
        }

        $records = $this->db->table('vehicles')
            ->select('id, nama_kendaraan_lainnya')
            ->where('nama_kendaraan', 'Lainnya')
            ->where('nama_kendaraan_lainnya IS NOT NULL', null, false)
            ->get()
            ->getResultArray();

        foreach ($records as $record) {
            $this->db->table('vehicles')->where('id', $record['id'])->update([
                'nama_kendaraan' => $record['nama_kendaraan_lainnya'],
            ]);
        }

        $this->forge->dropColumn('vehicles', 'nama_kendaraan_lainnya');
    }
}
