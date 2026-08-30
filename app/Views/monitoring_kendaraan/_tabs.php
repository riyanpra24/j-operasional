<?php
$tabs = [
    'vehicles' => ['label' => 'Data Kendaraan', 'description' => 'Identitas dan status armada', 'icon' => '▤', 'url' => site_url('bagian-umum-2/monitoring-kendaraan/data-kendaraan')],
    'maintenance' => ['label' => 'Servis & Perawatan', 'description' => 'Jadwal dan biaya servis', 'icon' => '⚙', 'url' => site_url('bagian-umum-2/monitoring-kendaraan/servis-perawatan')],
    'documents' => ['label' => 'Dokumen Kendaraan', 'description' => 'STNK, pajak, KIR, asuransi', 'icon' => '▣', 'url' => site_url('bagian-umum-2/monitoring-kendaraan/dokumen-kendaraan')],
    'reports' => ['label' => 'Riwayat & Laporan', 'description' => 'Rekap aktivitas armada', 'icon' => '↗', 'url' => site_url('bagian-umum-2/monitoring-kendaraan/riwayat-laporan')],
];
?>
<nav class="vehicle-module-tabs" aria-label="Menu Monitoring Kendaraan">
    <?php foreach ($tabs as $key => $tab): ?>
        <a href="<?= esc($tab['url']) ?>" class="vehicle-module-tab <?= $activePage === $key ? 'active' : '' ?>" <?= $activePage === $key ? 'aria-current="page"' : '' ?>>
            <span aria-hidden="true"><?= esc($tab['icon']) ?></span>
            <div><strong><?= esc($tab['label']) ?></strong><small><?= esc($tab['description']) ?></small></div>
        </a>
    <?php endforeach ?>
</nav>
