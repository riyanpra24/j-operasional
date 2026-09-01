<?php

namespace App\Controllers;

class Dashboard extends BaseController
{
    public function index(): string
    {
        $db    = db_connect();
        $today = date('Y-m-d');

        if ((string) session()->get('auth_role') === 'admin') {
            return $this->adminDashboard($db, $today);
        }

        $month = date('Y-m');
        $base  = static fn () => $db->table('dokumen_masuk')->where('deleted_at', null);

        $monthlyRows = $base()
            ->select("DATE_FORMAT(tanggal, '%Y-%m') AS periode, COUNT(*) AS total", false)
            ->where('tanggal >=', date('Y-m-01', strtotime('-5 months')))
            ->groupBy('periode')
            ->orderBy('periode', 'ASC')
            ->get()
            ->getResultArray();

        $monthlyMap = array_column($monthlyRows, 'total', 'periode');
        $monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
        $chart       = [];

        for ($i = 5; $i >= 0; $i--) {
            $timestamp = strtotime("-{$i} months");
            $key       = date('Y-m', $timestamp);
            $chart[]   = [
                'label' => $monthNames[(int) date('n', $timestamp) - 1],
                'total' => (int) ($monthlyMap[$key] ?? 0),
            ];
        }

        $jenis = $base()
            ->select('jenis AS label, COUNT(*) AS total')
            ->groupBy('jenis')
            ->orderBy('total', 'DESC')
            ->limit(5)
            ->get()
            ->getResultArray();

        $ekspedisi = $base()
            ->select("COALESCE(NULLIF(ekspedisi, ''), 'Tanpa ekspedisi') AS label, COUNT(*) AS total", false)
            ->groupBy('label')
            ->orderBy('total', 'DESC')
            ->limit(5)
            ->get()
            ->getResultArray();

        $sumRow = $base()->selectSum('jumlah')->get()->getRowArray();

        return view('dashboard/index', [
            'title' => 'Dashboard Dokumen Masuk',
            'stats' => [
                'total'    => $base()->countAllResults(),
                'today'    => $base()->where('tanggal', $today)->countAllResults(),
                'month'    => $base()->like('tanggal', $month, 'after')->countAllResults(),
                'items'    => (int) ($sumRow['jumlah'] ?? 0),
                'senders'  => $base()->select('pengirim')->distinct()->countAllResults(),
            ],
            'chart'     => $chart,
            'jenis'     => $jenis,
            'ekspedisi' => $ekspedisi,
            'recent'    => $base()->orderBy('tanggal', 'DESC')->orderBy('id', 'DESC')->limit(7)->get()->getResultArray(),
        ]);
    }

    private function adminDashboard($db, string $today): string
    {
        $now = date('Y-m-d H:i:s');
        $soonDate = date('Y-m-d', strtotime($today . ' +30 days'));

        $incomingBase = static fn () => $db->table('dokumen_masuk')->where('deleted_at', null);
        $vehicleBase = static fn () => $db->table('vehicles')->where('deleted_at', null);
        $maintenanceBase = static fn () => $db->table('vehicle_maintenance')->where('deleted_at', null);
        $vehicleDocumentBase = static fn () => $db->table('vehicle_documents')->where('deleted_at', null);

        $incomingTotal = $incomingBase()->countAllResults();
        $incomingPending = $incomingBase()
            ->groupStart()->where('pengambilan', null)->orWhere('pengambilan', '')->groupEnd()
            ->countAllResults();
        $outgoingTotal = $db->table('dokumen_keluar')->where('deleted_at', null)->countAllResults();
        $outgoingWaiting = $db->table('dokumen_keluar')
            ->where('deleted_at', null)
            ->groupStart()->where('progres !=', 'Diambil Ekspedisi')->orWhere('progres', null)->orWhere('progres', '')->groupEnd()
            ->countAllResults();
        $distributionTotal = $db->table('distribusi_dokumen')->countAllResults();

        $agendaIncomingTotal = $db->table('agendaris')->where('deleted_at', null)->countAllResults();
        $agendaIncomingPending = $db->table('agendaris')
            ->where('deleted_at', null)
            ->groupStart()->where('progres !=', 'Selesai')->orWhere('progres', null)->orWhere('progres', '')->groupEnd()
            ->countAllResults();
        $agendaOutgoingPending = $db->table('dokumen_keluar')
            ->where('deleted_at', null)
            ->groupStart()->where('status_agendaris !=', 'Selesai')->orWhere('status_agendaris', null)->orWhere('status_agendaris', '')->groupEnd()
            ->countAllResults();

        $pksRows = $db->table('pks_kerjasama k')
            ->select(
                'k.id, '
                . '(SELECT d.periode_mulai FROM pks_dokumen_kerjasama d WHERE d.kerjasama_id = k.id AND d.deleted_at IS NULL ORDER BY d.urutan DESC LIMIT 1) AS periode_mulai, '
                . '(SELECT d.periode_selesai FROM pks_dokumen_kerjasama d WHERE d.kerjasama_id = k.id AND d.deleted_at IS NULL ORDER BY d.urutan DESC LIMIT 1) AS periode_selesai',
                false,
            )
            ->where('k.deleted_at', null)
            ->get()
            ->getResultArray();
        $pksSummary = ['total' => count($pksRows), 'aktif' => 0, 'segera' => 0, 'berakhir' => 0, 'belum' => 0];
        $pksWarningDate = date('Y-m-d', strtotime($today . ' +20 days'));
        foreach ($pksRows as $pks) {
            $start = $pks['periode_mulai'] ?? null;
            $end = $pks['periode_selesai'] ?? null;
            if (! $start || ! $end || $start > $today) {
                $pksSummary['belum']++;
            } elseif ($end < $today) {
                $pksSummary['berakhir']++;
            } elseif ($end <= $pksWarningDate) {
                $pksSummary['segera']++;
            } else {
                $pksSummary['aktif']++;
            }
        }

        $spkTotal = $db->table('dokumen_spk')->where('deleted_at', null)->countAllResults();
        $spkComplete = $db->table('dokumen_spk')
            ->where('deleted_at', null)
            ->where('tanggal_dokumen IS NOT NULL', null, false)
            ->where('link_berkas IS NOT NULL', null, false)
            ->where('link_berkas !=', '')
            ->countAllResults();
        $spkIncomplete = max(0, $spkTotal - $spkComplete);

        $vehicleTotal = $vehicleBase()->countAllResults();
        $vehicleUsed = $vehicleBase()->where('status', 'Digunakan')->countAllResults();
        $vehicleMaintenanceStatus = $vehicleBase()->where('status', 'Perawatan')->countAllResults();
        $vehicleInactive = $vehicleBase()->where('status', 'Tidak Aktif')->countAllResults();
        $maintenanceTotal = $maintenanceBase()->countAllResults();
        $maintenanceCostRow = $maintenanceBase()->selectSum('biaya')->get()->getRowArray();
        $maintenanceCost = (float) ($maintenanceCostRow['biaya'] ?? 0);
        $maintenanceDue = $maintenanceBase()
            ->where('servis_berikutnya_tanggal IS NOT NULL', null, false)
            ->where('servis_berikutnya_tanggal <=', $soonDate)
            ->countAllResults();
        $vehicleDocumentTotal = $vehicleDocumentBase()->countAllResults();
        $vehicleDocumentExpiring = $vehicleDocumentBase()
            ->where('masa_berlaku >=', $today)
            ->where('masa_berlaku <=', $soonDate)
            ->countAllResults();
        $vehicleDocumentExpired = $vehicleDocumentBase()->where('masa_berlaku <', $today)->countAllResults();
        $vehicleLogTotal = $db->table('vehicle_activity_logs')->countAllResults();

        $userTotal = $db->table('users')->where('deleted_at', null)->countAllResults();
        $activeSessions = $db->table('user_sessions')->where('expires_at >', $now)->countAllResults();

        $todayCount = static function (string $table) use ($db, $today): int {
            $builder = $db->table($table)
                ->where('created_at >=', $today . ' 00:00:00')
                ->where('created_at <=', $today . ' 23:59:59');
            if ($table !== 'vehicle_activity_logs') {
                $builder->where('deleted_at', null);
            }
            return $builder->countAllResults();
        };
        $todayActivity = $todayCount('dokumen_masuk')
            + $todayCount('agendaris')
            + $todayCount('pks_kerjasama')
            + $todayCount('dokumen_spk')
            + $todayCount('vehicle_activity_logs');

        $monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
        $trendMap = [];
        $trendAnchor = date('Y-m-01', strtotime($today));
        $trendStart = date('Y-m-01', strtotime($trendAnchor . ' -5 months'));
        $trendSources = [
            ['table' => 'dokumen_masuk', 'soft_delete' => true],
            ['table' => 'agendaris', 'soft_delete' => true],
            ['table' => 'pks_kerjasama', 'soft_delete' => true],
            ['table' => 'dokumen_spk', 'soft_delete' => true],
            ['table' => 'vehicle_activity_logs', 'soft_delete' => false],
        ];
        foreach ($trendSources as $source) {
            $builder = $db->table($source['table'])
                ->select("DATE_FORMAT(created_at, '%Y-%m') AS periode, COUNT(*) AS total", false)
                ->where('created_at >=', $trendStart . ' 00:00:00');
            if ($source['soft_delete']) {
                $builder->where('deleted_at', null);
            }
            foreach ($builder->groupBy('periode')->get()->getResultArray() as $row) {
                $period = (string) $row['periode'];
                $trendMap[$period] = ($trendMap[$period] ?? 0) + (int) $row['total'];
            }
        }
        $activityTrend = [];
        for ($monthOffset = 5; $monthOffset >= 0; $monthOffset--) {
            $timestamp = strtotime($trendAnchor . " -{$monthOffset} months");
            $period = date('Y-m', $timestamp);
            $activityTrend[] = [
                'label' => $monthNames[(int) date('n', $timestamp) - 1],
                'period' => $period,
                'total' => (int) ($trendMap[$period] ?? 0),
            ];
        }

        $attention = [
            ['label' => 'Dokumen masuk belum ditindaklanjuti', 'count' => $incomingPending, 'section' => 'Security', 'tone' => 'blue', 'url' => site_url('distribusi-dokumen')],
            ['label' => 'Dokumen keluar menunggu ekspedisi', 'count' => $outgoingWaiting, 'section' => 'Security', 'tone' => 'blue', 'url' => site_url('dokumen-keluar')],
            ['label' => 'Progres Agendaris belum selesai', 'count' => $agendaIncomingPending + $agendaOutgoingPending, 'section' => 'Agendaris', 'tone' => 'purple', 'url' => site_url('agendaris/progres-dokumen')],
            ['label' => 'PKS segera berakhir atau berakhir', 'count' => $pksSummary['segera'] + $pksSummary['berakhir'], 'section' => 'Bagian Umum 1', 'tone' => 'orange', 'url' => site_url('bagian-umum-1/pks-barang-jasa')],
            ['label' => 'Dokumen SPK belum lengkap', 'count' => $spkIncomplete, 'section' => 'Bagian Umum 1', 'tone' => 'orange', 'url' => site_url('bagian-umum-1/dokumen-spk')],
            ['label' => 'Jadwal servis jatuh tempo ≤ 30 hari', 'count' => $maintenanceDue, 'section' => 'Bagian Umum 2', 'tone' => 'teal', 'url' => site_url('bagian-umum-2/monitoring-kendaraan/servis-perawatan')],
            ['label' => 'Dokumen kendaraan perlu diperpanjang', 'count' => $vehicleDocumentExpiring + $vehicleDocumentExpired, 'section' => 'Bagian Umum 2', 'tone' => 'teal', 'url' => site_url('bagian-umum-2/monitoring-kendaraan/dokumen-kendaraan')],
        ];
        $attentionTotal = array_sum(array_column($attention, 'count'));

        $modules = [
            [
                'key' => 'security', 'title' => 'Security', 'subtitle' => 'Register dan distribusi dokumen', 'icon' => '◆', 'tone' => 'blue',
                'url' => site_url('dokumen-masuk'),
                'metrics' => [
                    ['label' => 'Dokumen Masuk', 'value' => $incomingTotal, 'meta' => $incomingPending . ' belum ditindaklanjuti', 'url' => site_url('dokumen-masuk')],
                    ['label' => 'Dokumen Keluar', 'value' => $outgoingTotal, 'meta' => $outgoingWaiting . ' menunggu ekspedisi', 'url' => site_url('dokumen-keluar')],
                    ['label' => 'Distribusi Dokumen', 'value' => $distributionTotal, 'meta' => 'Data dalam proses distribusi', 'url' => site_url('distribusi-dokumen')],
                ],
            ],
            [
                'key' => 'agendaris', 'title' => 'Agendaris', 'subtitle' => 'Agenda dan progres surat', 'icon' => '▣', 'tone' => 'purple',
                'url' => site_url('agendaris/surat-masuk'),
                'metrics' => [
                    ['label' => 'Dokumen Masuk', 'value' => $agendaIncomingTotal, 'meta' => $agendaIncomingPending . ' belum selesai', 'url' => site_url('agendaris/surat-masuk')],
                    ['label' => 'Dokumen Keluar', 'value' => $outgoingTotal, 'meta' => $agendaOutgoingPending . ' belum diselesaikan', 'url' => site_url('agendaris/surat-keluar')],
                    ['label' => 'Antrean Progres', 'value' => $agendaIncomingPending + $agendaOutgoingPending, 'meta' => 'Masuk dan keluar', 'url' => site_url('agendaris/progres-dokumen')],
                ],
            ],
            [
                'key' => 'umum1', 'title' => 'Bagian Umum 1', 'subtitle' => 'PKS, SPK, dan pengadaan', 'icon' => '⚙', 'tone' => 'orange',
                'url' => site_url('bagian-umum-1/pks-barang-jasa'),
                'metrics' => [
                    ['label' => 'PKS Barang & Jasa', 'value' => $pksSummary['total'], 'meta' => $pksSummary['aktif'] . ' aktif · ' . $pksSummary['segera'] . ' segera berakhir', 'url' => site_url('bagian-umum-1/pks-barang-jasa')],
                    ['label' => 'Dokumen SPK', 'value' => $spkTotal, 'meta' => $spkIncomplete . ' belum lengkap', 'url' => site_url('bagian-umum-1/dokumen-spk')],
                    ['label' => 'Pengadaan Barang Jasa', 'value' => '—', 'meta' => 'Belum ada data', 'url' => site_url('bagian-umum-1/pengadaan-barang-jasa')],
                ],
            ],
            [
                'key' => 'umum2', 'title' => 'Bagian Umum 2', 'subtitle' => 'Monitoring kendaraan operasional', 'icon' => '▤', 'tone' => 'teal',
                'url' => site_url('bagian-umum-2/monitoring-kendaraan/data-kendaraan'),
                'metrics' => [
                    ['label' => 'Data Kendaraan', 'value' => $vehicleTotal, 'meta' => $vehicleUsed . ' digunakan · ' . $vehicleMaintenanceStatus . ' perawatan · ' . $vehicleInactive . ' tidak aktif', 'url' => site_url('bagian-umum-2/monitoring-kendaraan/data-kendaraan')],
                    ['label' => 'Servis & Perawatan', 'value' => $maintenanceTotal, 'meta' => 'Biaya Rp ' . number_format($maintenanceCost, 0, ',', '.'), 'url' => site_url('bagian-umum-2/monitoring-kendaraan/servis-perawatan')],
                    ['label' => 'Dokumen Kendaraan', 'value' => $vehicleDocumentTotal, 'meta' => ($vehicleDocumentExpiring + $vehicleDocumentExpired) . ' perlu perhatian', 'url' => site_url('bagian-umum-2/monitoring-kendaraan/dokumen-kendaraan')],
                    ['label' => 'Riwayat & Laporan', 'value' => $vehicleLogTotal, 'meta' => 'Aktivitas kendaraan tercatat', 'url' => site_url('bagian-umum-2/monitoring-kendaraan/riwayat-laporan')],
                ],
            ],
        ];

        $recent = [];
        foreach ($db->table('dokumen_masuk')->select('id, pengirim, perihal, created_at')->where('deleted_at', null)->orderBy('created_at', 'DESC')->limit(5)->get()->getResultArray() as $row) {
            $recent[] = ['section' => 'Security', 'title' => 'Dokumen masuk dari ' . $row['pengirim'], 'description' => $row['perihal'] ?: 'Dokumen masuk baru', 'time' => $row['created_at'], 'tone' => 'blue', 'url' => site_url('dokumen-masuk/' . $row['id'])];
        }
        foreach ($db->table('agendaris')->select('id, pengirim, perihal_surat, created_at')->where('deleted_at', null)->orderBy('created_at', 'DESC')->limit(5)->get()->getResultArray() as $row) {
            $recent[] = ['section' => 'Agendaris', 'title' => 'Agenda surat dari ' . $row['pengirim'], 'description' => $row['perihal_surat'] ?: 'Agenda surat masuk', 'time' => $row['created_at'], 'tone' => 'purple', 'url' => site_url('agendaris/surat-masuk')];
        }
        foreach ($db->table('pks_kerjasama')->select('id, kode_internal, nama_kerjasama, created_at')->where('deleted_at', null)->orderBy('created_at', 'DESC')->limit(5)->get()->getResultArray() as $row) {
            $recent[] = ['section' => 'Bagian Umum 1', 'title' => 'PKS ' . $row['kode_internal'], 'description' => $row['nama_kerjasama'], 'time' => $row['created_at'], 'tone' => 'orange', 'url' => site_url('bagian-umum-1/pks-barang-jasa/' . $row['id'])];
        }
        foreach ($db->table('dokumen_spk')->select('id, nomor_dokumen, perihal, created_at')->where('deleted_at', null)->orderBy('created_at', 'DESC')->limit(5)->get()->getResultArray() as $row) {
            $recent[] = ['section' => 'Bagian Umum 1', 'title' => 'SPK ' . $row['nomor_dokumen'], 'description' => $row['perihal'], 'time' => $row['created_at'], 'tone' => 'orange', 'url' => site_url('bagian-umum-1/dokumen-spk')];
        }
        foreach ($db->table('vehicle_activity_logs')->select('vehicle_label, entity_type, action, description, created_at')->orderBy('created_at', 'DESC')->limit(5)->get()->getResultArray() as $row) {
            $recent[] = ['section' => 'Bagian Umum 2', 'title' => $row['entity_type'] . ' ' . strtolower($row['action']), 'description' => $row['vehicle_label'] . ' · ' . $row['description'], 'time' => $row['created_at'], 'tone' => 'teal', 'url' => site_url('bagian-umum-2/monitoring-kendaraan/riwayat-laporan')];
        }
        usort($recent, static fn (array $a, array $b): int => strtotime((string) $b['time']) <=> strtotime((string) $a['time']));
        $recent = array_slice(array_values(array_filter($recent, static fn (array $item): bool => ! empty($item['time']))), 0, 8);

        $totalManagedData = $incomingTotal + $outgoingTotal + $distributionTotal + $agendaIncomingTotal
            + $pksSummary['total'] + $spkTotal + $vehicleTotal + $maintenanceTotal
            + $vehicleDocumentTotal + $vehicleLogTotal + $userTotal;

        $dataComposition = [
            ['label' => 'Security', 'total' => $incomingTotal + $outgoingTotal + $distributionTotal, 'tone' => 'blue'],
            ['label' => 'Agendaris', 'total' => $agendaIncomingTotal, 'tone' => 'purple'],
            ['label' => 'Bagian Umum 1', 'total' => $pksSummary['total'] + $spkTotal, 'tone' => 'orange'],
            ['label' => 'Bagian Umum 2', 'total' => $vehicleTotal + $maintenanceTotal + $vehicleDocumentTotal + $vehicleLogTotal, 'tone' => 'teal'],
            ['label' => 'Administrator', 'total' => $userTotal, 'tone' => 'navy'],
        ];

        return view('dashboard/admin', [
            'title' => 'Dashboard Administrator',
            'overview' => [
                'total_data' => $totalManagedData,
                'attention' => $attentionTotal,
                'today_activity' => $todayActivity,
                'active_sessions' => $activeSessions,
                'users' => $userTotal,
            ],
            'modules' => $modules,
            'attentionItems' => $attention,
            'recentActivity' => $recent,
            'activityTrend' => $activityTrend,
            'dataComposition' => $dataComposition,
        ]);
    }
}
