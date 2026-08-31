<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class VehicleMonitoringDummyData extends Seeder
{
    private const ACTOR = 'Data Dummy Monitoring Kendaraan';

    public function run(): void
    {
        $now = date('Y-m-d H:i:s');
        $vehicles = [
            ['L 1234 ABC', 'Mobil Operasional 01', 'Mobil', 'Toyota', 'Innova Reborn', 2022, 'Silver', 'MHKDUMMY000001', '2GDDUMMY000001', 'Eryuninto', 'Andi Prasetyo', 48250, 'Tersedia'],
            ['L 5678 DEF', 'Mobil Operasional 02', 'Mobil', 'Honda', 'CR-V', 2021, 'Hitam', 'MHKDUMMY000002', '2GDDUMMY000002', 'Riyanto', 'Dimas Saputra', 67340, 'Digunakan'],
            ['L 9012 GHI', 'Mobil Operasional 03', 'Mobil', 'Mitsubishi', 'Xpander', 2023, 'Putih', 'MHKDUMMY000003', '2GDDUMMY000003', 'Fransiskus Medhison', 'Rina Wulandari', 28900, 'Tersedia'],
            ['L 3456 JKL', 'Mobil Operasional 04', 'Mobil', 'Toyota', 'Avanza', 2020, 'Abu-abu', 'MHKDUMMY000004', '2GDDUMMY000004', 'Eryuninto', 'Budi Santoso', 91520, 'Perawatan'],
            ['L 7890 MNO', 'Motor Operasional 01', 'Motor', 'Honda', 'Vario 160', 2024, 'Hitam', 'MHKDUMMY000005', '2GDDUMMY000005', 'Riyanto', 'Fajar Nugroho', 12840, 'Tersedia'],
            ['L 2468 PQR', 'Motor Operasional 02', 'Motor', 'Yamaha', 'NMAX', 2022, 'Biru', 'MHKDUMMY000006', '2GDDUMMY000006', 'Fransiskus Medhison', 'Siti Rahma', 34170, 'Digunakan'],
            ['L 1357 STU', 'Minibus Operasional', 'Minibus', 'Isuzu', 'Elf', 2019, 'Putih', 'MHKDUMMY000007', '2GDDUMMY000007', 'Eryuninto', 'Eko Setiawan', 124600, 'Perawatan'],
            ['L 8642 VWX', 'Kendaraan Tamu', 'Minibus', 'Toyota', 'HiAce', 2021, 'Silver', 'MHKDUMMY000008', '2GDDUMMY000008', 'Riyanto', 'Maya Lestari', 55820, 'Tidak Aktif'],
        ];

        $maintenance = [
            ['L 1234 ABC', '2026-08-10', 'Servis berkala dan ganti oli', 'Auto2000 Surabaya', 48000, 1850000, '2026-11-10', 'Pemeriksaan rutin 10.000 km.', 'https://example.com/dummy/servis-innova-agustus-2026.pdf'],
            ['L 1234 ABC', '2026-03-05', 'Penggantian kampas rem', 'Auto2000 Surabaya', 42100, 2350000, '2026-06-05', 'Kampas rem depan dan pemeriksaan cakram.', null],
            ['L 5678 DEF', '2026-07-18', 'Servis berkala', 'Honda Surabaya Center', 65200, 2100000, '2026-10-18', 'Servis berkala tiga bulanan.', 'https://example.com/dummy/servis-crv-juli-2026.pdf'],
            ['L 9012 GHI', '2026-06-22', 'Ganti oli dan filter', 'Mitsubishi Motors Surabaya', 25750, 1450000, '2026-09-22', null, null],
            ['L 3456 JKL', '2026-08-27', 'Perbaikan sistem pendingin', 'Bengkel Sejahtera', 91520, 4750000, '2026-11-27', 'Kendaraan masih dalam proses perawatan.', 'https://example.com/dummy/perbaikan-avanza-agustus-2026.pdf'],
            ['L 7890 MNO', '2026-08-02', 'Servis ringan', 'AHASS Surabaya', 12000, 425000, '2026-11-02', 'Ganti oli mesin dan pemeriksaan rem.', null],
            ['L 2468 PQR', '2026-05-14', 'Servis CVT', 'Yamaha Service Surabaya', 30200, 875000, '2026-08-14', null, 'https://example.com/dummy/servis-nmax-mei-2026.pdf'],
            ['L 1357 STU', '2026-08-25', 'Perbaikan kaki-kaki', 'Isuzu Surabaya', 124600, 6800000, '2026-11-25', 'Menunggu suku cadang tie rod.', 'https://example.com/dummy/perbaikan-elf-agustus-2026.pdf'],
            ['L 8642 VWX', '2026-02-12', 'Servis berkala', 'Auto2000 Surabaya', 54000, 3200000, '2026-05-12', 'Kendaraan sementara tidak dioperasikan.', null],
        ];

        $documents = [
            ['L 1234 ABC', 'STNK', 'STNK/L1234ABC/2026', '2026-01-15', '2027-01-15', 'https://example.com/dummy/stnk-l1234abc.pdf', null],
            ['L 1234 ABC', 'Pajak', 'PKB/L1234ABC/2026', '2026-01-15', '2027-01-15', 'https://example.com/dummy/pajak-l1234abc.pdf', null],
            ['L 5678 DEF', 'STNK', 'STNK/L5678DEF/2026', '2025-09-12', '2026-09-12', 'https://example.com/dummy/stnk-l5678def.pdf', 'Segera diperpanjang.'],
            ['L 5678 DEF', 'Asuransi', 'ASR/L5678DEF/2026', '2026-02-01', '2027-02-01', 'https://example.com/dummy/asuransi-l5678def.pdf', null],
            ['L 9012 GHI', 'STNK', 'STNK/L9012GHI/2026', '2026-05-20', '2027-05-20', 'https://example.com/dummy/stnk-l9012ghi.pdf', null],
            ['L 9012 GHI', 'Pajak', 'PKB/L9012GHI/2026', '2026-05-20', '2027-05-20', null, null],
            ['L 3456 JKL', 'STNK', 'STNK/L3456JKL/2025', '2025-08-18', '2026-08-18', 'https://example.com/dummy/stnk-l3456jkl.pdf', 'Masa berlaku telah berakhir.'],
            ['L 3456 JKL', 'Asuransi', 'ASR/L3456JKL/2026', '2026-04-10', '2027-04-10', null, null],
            ['L 7890 MNO', 'STNK', 'STNK/L7890MNO/2026', '2026-08-01', '2027-08-01', 'https://example.com/dummy/stnk-l7890mno.pdf', null],
            ['L 2468 PQR', 'Pajak', 'PKB/L2468PQR/2025', '2025-09-05', '2026-09-05', 'https://example.com/dummy/pajak-l2468pqr.pdf', 'Jatuh tempo kurang dari tujuh hari.'],
            ['L 1357 STU', 'STNK', 'STNK/L1357STU/2026', '2026-06-30', '2027-06-30', 'https://example.com/dummy/stnk-l1357stu.pdf', null],
            ['L 1357 STU', 'KIR', 'KIR/L1357STU/2026', '2026-03-14', '2026-09-14', 'https://example.com/dummy/kir-l1357stu.pdf', 'Perpanjangan KIR perlu dijadwalkan.'],
            ['L 8642 VWX', 'STNK', 'STNK/L8642VWX/2026', '2026-02-28', '2027-02-28', 'https://example.com/dummy/stnk-l8642vwx.pdf', null],
            ['L 8642 VWX', 'Asuransi', 'ASR/L8642VWX/2025', '2025-08-25', '2026-08-25', null, 'Asuransi telah berakhir.'],
        ];

        $this->db->transStart();
        $vehicleIds = [];

        foreach ($vehicles as [$plate, $name, $type, $brand, $model, $year, $color, $frame, $engine, $unit, $pic, $kilometer, $status]) {
            $existing = $this->db->table('vehicles')->where('nomor_polisi', $plate)->get()->getRowArray();
            if ($existing !== null) {
                $vehicleIds[$plate] = (int) $existing['id'];
                continue;
            }

            $this->db->table('vehicles')->insert([
                'nomor_polisi' => $plate,
                'nama_kendaraan' => 'Lainnya',
                'nama_kendaraan_lainnya' => $name,
                'jenis' => $type === 'Motor' ? 'Motor' : 'Mobil',
                'status_kendaraan' => 'Kendaraan Aset',
                'merek' => $brand,
                'tipe' => $model,
                'tahun' => $year,
                'warna' => $color,
                'nomor_rangka' => $frame,
                'nomor_mesin' => $engine,
                'unit_pengguna' => $unit,
                'unit_pengguna_lainnya' => null,
                'pic' => 'Bagian Umum 2',
                'pic_internal' => 'Agil Halis Kesawa',
                'kilometer' => $kilometer,
                'status' => $status === 'Tersedia' ? 'Lainnya' : $status,
                'status_lainnya' => $status === 'Tersedia' ? 'Tersedia' : null,
                'created_by' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $vehicleId = (int) $this->db->insertID();
            $vehicleIds[$plate] = $vehicleId;
            $this->log($vehicleId, "{$plate} · {$name}", 'Kendaraan', $vehicleId, 'Ditambahkan', 'Data kendaraan dummy ditambahkan.', $now);
        }

        foreach ($maintenance as [$plate, $date, $type, $workshop, $kilometer, $cost, $nextDate, $notes, $link]) {
            $vehicleId = $vehicleIds[$plate];
            $exists = $this->db->table('vehicle_maintenance')->where(['vehicle_id' => $vehicleId, 'tanggal_servis' => $date, 'jenis_perawatan' => $type])->countAllResults() > 0;
            if ($exists) {
                continue;
            }

            $this->db->table('vehicle_maintenance')->insert([
                'vehicle_id' => $vehicleId,
                'tanggal_servis' => $date,
                'jenis_perawatan' => $type,
                'bengkel' => $workshop,
                'kilometer' => $kilometer,
                'biaya' => $cost,
                'servis_berikutnya_tanggal' => $nextDate,
                'anggaran_servis' => 'Kantor',
                'nama_perusahaan' => 'PT. Jaminan Kredit Indonesia (Persero)',
                'keterangan' => $notes,
                'link_berkas' => $link,
                'created_by' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $id = (int) $this->db->insertID();
            $vehicle = $this->vehicleById($vehicleId);
            $this->log($vehicleId, $vehicle['nomor_polisi'] . ' · ' . $vehicle['nama_kendaraan'], 'Servis', $id, 'Ditambahkan', "Servis {$type} tanggal {$date} ditambahkan sebagai data dummy.", $now);
        }

        foreach ($documents as [$plate, $type, $number, $issuedAt, $expiresAt, $link, $notes]) {
            $vehicleId = $vehicleIds[$plate];
            $exists = $this->db->table('vehicle_documents')->where(['vehicle_id' => $vehicleId, 'jenis_dokumen' => $type, 'nomor_dokumen' => $number])->countAllResults() > 0;
            if ($exists) {
                continue;
            }

            $this->db->table('vehicle_documents')->insert([
                'vehicle_id' => $vehicleId,
                'jenis_dokumen' => $type,
                'nomor_dokumen' => $number,
                'tanggal_terbit' => $issuedAt,
                'masa_berlaku' => $expiresAt,
                'link_berkas' => $link,
                'keterangan' => $notes,
                'created_by' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $id = (int) $this->db->insertID();
            $vehicle = $this->vehicleById($vehicleId);
            $this->log($vehicleId, $vehicle['nomor_polisi'] . ' · ' . $vehicle['nama_kendaraan'], 'Dokumen', $id, 'Ditambahkan', "Dokumen {$type} dengan masa berlaku {$expiresAt} ditambahkan sebagai data dummy.", $now);
        }

        $this->db->transComplete();
    }

    private function vehicleById(int $id): array
    {
        return $this->db->table('vehicles')->where('id', $id)->get()->getRowArray();
    }

    private function log(int $vehicleId, string $label, string $entityType, int $entityId, string $action, string $description, string $createdAt): void
    {
        $this->db->table('vehicle_activity_logs')->insert([
            'vehicle_id' => $vehicleId,
            'vehicle_label' => $label,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'action' => $action,
            'description' => $description,
            'actor_name' => self::ACTOR,
            'created_at' => $createdAt,
        ]);
    }
}
