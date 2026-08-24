<?php

namespace App\Controllers;

class Dashboard extends BaseController
{
    public function index(): string
    {
        $db    = db_connect();
        $today = date('Y-m-d');
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
}
