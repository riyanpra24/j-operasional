<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;
use RuntimeException;

class TwoVehicleMonitoringDummyData extends Seeder
{
    private const ACTOR = 'Data Dummy Monitoring Kendaraan';

    public function run(): void
    {
        $now = date('Y-m-d H:i:s');
        $records = [
            [
                'vehicle' => [
                    'nomor_polisi' => 'L 1122 JKS',
                    'nama_kendaraan' => 'Kendaraan Operasional Kantor',
                    'jenis' => 'Mobil',
                    'status_kendaraan' => 'Kendaraan Aset',
                    'merek' => 'Toyota',
                    'tipe' => 'Innova Zenix',
                    'tahun' => 2024,
                    'warna' => 'Hitam',
                    'nomor_rangka' => 'MHKJAKSA000001',
                    'nomor_mesin' => 'ENGJAKSA000001',
                    'unit_pengguna' => 'Eryuninto',
                    'unit_pengguna_lainnya' => null,
                    'pic' => 'Bagian Umum 2',
                    'pic_internal' => 'Agil Halis Kesawa',
                    'kilometer' => 18450,
                    'status' => 'Digunakan',
                ],
                'maintenance' => [
                    'tanggal_servis' => '2026-08-12',
                    'jenis_perawatan' => 'Servis berkala dan ganti oli',
                    'bengkel' => 'Auto2000 Surabaya',
                    'kilometer' => 18000,
                    'biaya' => 1750000,
                    'servis_berikutnya_tanggal' => '2026-11-12',
                    'anggaran_servis' => 'Asuransi',
                    'nama_perusahaan' => 'PT Asuransi Dummy Indonesia',
                    'keterangan' => 'Data dummy servis berkala kendaraan.',
                    'link_berkas' => null,
                ],
                'document' => [
                    'jenis_dokumen' => 'STNK',
                    'nomor_dokumen' => 'STNK/L1122JKS/2026',
                    'tanggal_terbit' => '2026-04-15',
                    'masa_berlaku' => '2027-04-15',
                    'link_berkas' => null,
                    'keterangan' => 'Dokumen dummy kendaraan operasional.',
                ],
            ],
            [
                'vehicle' => [
                    'nomor_polisi' => 'L 3344 JKS',
                    'nama_kendaraan' => 'Kendaraan Operasional Kantor',
                    'jenis' => 'Mobil',
                    'status_kendaraan' => 'Kendaraan Sewa',
                    'merek' => 'Honda',
                    'tipe' => 'BR-V',
                    'tahun' => 2023,
                    'warna' => 'Putih',
                    'nomor_rangka' => 'MHKJAKSA000002',
                    'nomor_mesin' => 'ENGJAKSA000002',
                    'unit_pengguna' => 'Riyanto',
                    'unit_pengguna_lainnya' => null,
                    'pic' => 'Bagian Umum 2',
                    'pic_internal' => 'Agil Halis Kesawa',
                    'kilometer' => 32600,
                    'status' => 'Digunakan',
                ],
                'maintenance' => [
                    'tanggal_servis' => '2026-08-22',
                    'jenis_perawatan' => 'Pemeriksaan rem dan rotasi ban',
                    'bengkel' => 'Honda Surabaya Center',
                    'kilometer' => 32000,
                    'biaya' => 2100000,
                    'servis_berikutnya_tanggal' => '2026-11-22',
                    'anggaran_servis' => 'Kantor',
                    'nama_perusahaan' => 'PT. Jaminan Kredit Indonesia (Persero)',
                    'keterangan' => 'Data dummy perawatan kendaraan.',
                    'link_berkas' => null,
                ],
                'document' => [
                    'jenis_dokumen' => 'Pajak',
                    'nomor_dokumen' => 'PKB/L3344JKS/2026',
                    'tanggal_terbit' => '2026-10-10',
                    'masa_berlaku' => '2027-10-10',
                    'link_berkas' => null,
                    'keterangan' => 'Dokumen dummy pajak kendaraan.',
                ],
            ],
        ];

        $this->db->transStart();

        foreach ($records as $record) {
            $vehicleId = $this->upsertVehicle($record['vehicle'], $now);
            $this->upsertMaintenance($vehicleId, $record['vehicle'], $record['maintenance'], $now);
            $this->upsertDocument($vehicleId, $record['vehicle'], $record['document'], $now);
        }

        $this->db->transComplete();

        if (! $this->db->transStatus()) {
            throw new RuntimeException('Dua data dummy monitoring kendaraan gagal dibuat.');
        }
    }

    private function upsertVehicle(array $vehicle, string $now): int
    {
        $existing = $this->db->table('vehicles')
            ->where('nomor_polisi', $vehicle['nomor_polisi'])
            ->get()
            ->getRowArray();

        $payload = $vehicle + [
            'created_by' => null,
            'updated_at' => $now,
            'deleted_at' => null,
        ];

        if ($existing !== null) {
            $this->db->table('vehicles')->where('id', $existing['id'])->update($payload);

            return (int) $existing['id'];
        }

        $payload['created_at'] = $now;
        $this->db->table('vehicles')->insert($payload);
        $vehicleId = (int) $this->db->insertID();
        $this->log($vehicleId, $vehicle, 'Kendaraan', $vehicleId, 'Ditambahkan', 'Data kendaraan dummy ditambahkan.', $now);

        return $vehicleId;
    }

    private function upsertMaintenance(int $vehicleId, array $vehicle, array $maintenance, string $now): void
    {
        $existing = $this->db->table('vehicle_maintenance')->where([
            'vehicle_id' => $vehicleId,
            'tanggal_servis' => $maintenance['tanggal_servis'],
            'jenis_perawatan' => $maintenance['jenis_perawatan'],
        ])->get()->getRowArray();

        $payload = $maintenance + [
            'vehicle_id' => $vehicleId,
            'created_by' => null,
            'updated_at' => $now,
            'deleted_at' => null,
        ];

        if ($existing !== null) {
            $this->db->table('vehicle_maintenance')->where('id', $existing['id'])->update($payload);

            return;
        }

        $payload['created_at'] = $now;
        $this->db->table('vehicle_maintenance')->insert($payload);
        $maintenanceId = (int) $this->db->insertID();
        $this->log(
            $vehicleId,
            $vehicle,
            'Servis',
            $maintenanceId,
            'Ditambahkan',
            "Servis {$maintenance['jenis_perawatan']} tanggal {$maintenance['tanggal_servis']} ditambahkan sebagai data dummy.",
            $now,
        );
    }

    private function upsertDocument(int $vehicleId, array $vehicle, array $document, string $now): void
    {
        $existing = $this->db->table('vehicle_documents')->where([
            'vehicle_id' => $vehicleId,
            'jenis_dokumen' => $document['jenis_dokumen'],
            'nomor_dokumen' => $document['nomor_dokumen'],
        ])->get()->getRowArray();

        $payload = $document + [
            'vehicle_id' => $vehicleId,
            'created_by' => null,
            'updated_at' => $now,
            'deleted_at' => null,
        ];

        if ($existing !== null) {
            $this->db->table('vehicle_documents')->where('id', $existing['id'])->update($payload);

            return;
        }

        $payload['created_at'] = $now;
        $this->db->table('vehicle_documents')->insert($payload);
        $documentId = (int) $this->db->insertID();
        $this->log(
            $vehicleId,
            $vehicle,
            'Dokumen',
            $documentId,
            'Ditambahkan',
            "Dokumen {$document['jenis_dokumen']} ditambahkan sebagai data dummy.",
            $now,
        );
    }

    private function log(
        int $vehicleId,
        array $vehicle,
        string $entityType,
        int $entityId,
        string $action,
        string $description,
        string $createdAt,
    ): void {
        $this->db->table('vehicle_activity_logs')->insert([
            'vehicle_id' => $vehicleId,
            'vehicle_label' => $vehicle['nomor_polisi'] . ' · ' . $vehicle['nama_kendaraan'],
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'action' => $action,
            'description' => $description,
            'actor_name' => self::ACTOR,
            'created_at' => $createdAt,
        ]);
    }
}
