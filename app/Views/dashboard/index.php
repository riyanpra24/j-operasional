<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<?php
$maxChart    = max(1, ...array_column($chart, 'total'));
$chartWidth  = 680;
$chartHeight = 190;
$points      = [];
foreach ($chart as $index => $item) {
    $points[] = [round(($index / 5) * $chartWidth, 1), round(165 - (($item['total'] / $maxChart) * 125), 1)];
}
$polyline = implode(' ', array_map(static fn ($p) => $p[0] . ',' . $p[1], $points));
$areaPath = 'M ' . implode(' L ', array_map(static fn ($p) => $p[0] . ' ' . $p[1], $points)) . " L {$chartWidth} {$chartHeight} L 0 {$chartHeight} Z";

$jenisTotal = max(1, array_sum(array_map('intval', array_column($jenis, 'total'))));
$jenisTop   = array_slice($jenis, 0, 3);
$angleOne   = isset($jenisTop[0]) ? (int) round(((int) $jenisTop[0]['total'] / $jenisTotal) * 360) : 0;
$angleTwo   = $angleOne + (isset($jenisTop[1]) ? (int) round(((int) $jenisTop[1]['total'] / $jenisTotal) * 360) : 0);
$donutStyle = $jenis === []
    ? 'background:#edf0f4'
    : "background:conic-gradient(#ffae34 0 {$angleOne}deg,#10aaa5 {$angleOne}deg {$angleTwo}deg,#2d4b9c {$angleTwo}deg 360deg)";
$todayRate = $stats['total'] > 0 ? min(100, (int) round(($stats['today'] / $stats['total']) * 100)) : 0;
$monthRate = $stats['total'] > 0 ? min(100, (int) round(($stats['month'] / $stats['total']) * 100)) : 0;
$months = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
$dateLabel = date('d') . ' ' . $months[(int) date('n') - 1] . ' ' . date('Y');
$currentRole = (string) session()->get('auth_role');
$displayName = (string) session()->get('auth_display_name');
$canAccessSecurity = in_array($currentRole, ['admin', 'security'], true);
$roleLabel = strtoupper(\Config\UserRoles::label($currentRole));
$profileInitials = strtoupper(substr($displayName !== '' ? $displayName : 'U', 0, 2));
$registerUrl = $canAccessSecurity ? site_url('dokumen-masuk') : site_url('agendaris/surat-masuk');
?>

<section class="reference-top-grid">
    <article class="profile-summary-card">
        <div class="profile-summary-main">
            <span class="profile-avatar"><?= esc($profileInitials) ?></span>
            <div><strong><?= esc($displayName) ?></strong><small><?= esc($roleLabel) ?> · WELCOME TO DASHBOARD</small></div>
        </div>
        <div class="profile-summary-meta"><span><?= number_format($stats['total'], 0, ',', '.') ?> DOKUMEN</span><span><?= strtoupper($dateLabel) ?></span></div>
    </article>

    <article class="company-summary-card">
        <header><div><h1>Data Dokumen Masuk</h1><p>Ringkasan register dokumen operasional</p></div><button type="button" data-print>Download Report <span>↧</span></button></header>
        <div class="company-metrics">
            <div class="company-metric orange"><span>Total Dokumen</span><strong><?= number_format($stats['total'], 0, ',', '.') ?></strong></div>
            <div class="company-metric navy"><span>Bulan Berjalan</span><strong><?= number_format($stats['month'], 0, ',', '.') ?></strong></div>
            <div class="company-metric blue"><span>Total Jumlah</span><strong><?= number_format($stats['items'], 0, ',', '.') ?></strong></div>
        </div>
    </article>
</section>

<section class="reference-chart-grid">
    <article class="reference-panel main-graph-card">
        <header><div><h2>Grafik Dokumen Masuk</h2><p>Pergerakan data enam bulan terakhir</p></div><a href="<?= $canAccessSecurity ? site_url('distribusi-dokumen') : $registerUrl ?>" <?= $canAccessSecurity ? 'data-open-input-modal' : '' ?>>＋ <?= $canAccessSecurity ? 'Dokumen Baru' : 'Buka Agenda' ?></a></header>
        <div class="reference-line-chart">
            <div class="reference-y-axis"><span><?= $maxChart ?></span><span><?= (int) ceil($maxChart * .75) ?></span><span><?= (int) ceil($maxChart * .5) ?></span><span><?= (int) ceil($maxChart * .25) ?></span><span>0</span></div>
            <div class="reference-svg-wrap">
                <svg viewBox="0 0 <?= $chartWidth ?> <?= $chartHeight ?>" preserveAspectRatio="none" aria-label="Grafik dokumen masuk">
                    <defs><linearGradient id="referenceArea" x1="0" x2="0" y1="0" y2="1"><stop offset="0%" stop-color="#ffb13b" stop-opacity=".28"/><stop offset="100%" stop-color="#ffb13b" stop-opacity=".04"/></linearGradient></defs>
                    <?php foreach ([40,70,100,130,165] as $y): ?><line x1="0" y1="<?= $y ?>" x2="680" y2="<?= $y ?>" class="reference-grid-line"/><?php endforeach ?>
                    <path d="<?= esc($areaPath) ?>" fill="url(#referenceArea)"/>
                    <polyline points="<?= esc($polyline) ?>" class="reference-chart-line"/>
                    <?php foreach ($points as $i => $point): ?><circle cx="<?= $point[0] ?>" cy="<?= $point[1] ?>" r="5" class="reference-chart-point"/><text x="<?= $point[0] ?>" y="<?= max(12, $point[1]-12) ?>" text-anchor="middle" class="reference-point-label"><?= $chart[$i]['total'] ?></text><?php endforeach ?>
                </svg>
                <div class="reference-x-axis"><?php foreach ($chart as $item): ?><span><?= esc($item['label']) ?></span><?php endforeach ?></div>
            </div>
        </div>
        <div class="reference-legend"><span><i class="orange"></i>Dokumen Masuk</span><span><i class="green"></i>Data Bulanan</span><span><i class="blue"></i>Register Aktif</span></div>
    </article>

    <article class="reference-panel donut-project-card">
        <header><div><h2>Jenis Dokumen</h2><p>Komposisi register aktif</p></div><span>⚙</span></header>
        <div class="reference-donut" style="<?= esc($donutStyle, 'attr') ?>"><div><strong><?= number_format($stats['total'], 0, ',', '.') ?></strong><small>Total</small></div></div>
        <div class="reference-donut-legend">
            <?php if ($jenisTop === []): ?><span><i class="gray"></i>Belum ada data</span><?php else: ?>
                <?php $legendClasses = ['orange','green','blue']; foreach ($jenisTop as $i => $item): ?><span><i class="<?= $legendClasses[$i] ?>"></i><?= esc($item['label']) ?></span><?php endforeach ?>
            <?php endif ?>
        </div>
    </article>
</section>

<section class="reference-data-section">
    <header class="reference-section-header"><div><h2>Data Grafik</h2><p>Analitik singkat dokumen masuk</p></div><div class="reference-search">⌕&nbsp; Cari pada tabel dokumen</div><a href="<?= $registerUrl ?>">Lihat Semua <span>→</span></a></header>
    <div class="reference-data-grid">
        <article class="ring-stat-card"><div class="reference-ring orange-ring" style="--ring-value:<?= $todayRate * 3.6 ?>deg"><span><?= $todayRate ?>%</span></div><div><span>Dokumen Hari Ini</span><strong><?= number_format($stats['today'], 0, ',', '.') ?></strong><small>Masuk pada <?= date('d-m-Y') ?></small></div></article>
        <article class="ring-stat-card"><div class="reference-ring green-ring" style="--ring-value:<?= $monthRate * 3.6 ?>deg"><span><?= $monthRate ?>%</span></div><div><span>Dokumen Bulan Ini</span><strong><?= number_format($stats['month'], 0, ',', '.') ?></strong><small>Dari <?= number_format($stats['senders'], 0, ',', '.') ?> pengirim</small></div></article>
        <article class="mini-analytics-card"><div><strong><?= number_format($stats['items'], 0, ',', '.') ?></strong><span>Total Jumlah Dokumen</span></div><svg viewBox="0 0 300 70" preserveAspectRatio="none"><polyline points="<?= esc(implode(' ', array_map(static fn ($p) => round(($p[0] / 680) * 300, 1) . ',' . round(max(5, $p[1] / 2.7), 1), $points))) ?>"/></svg></article>
    </div>
</section>

<section class="reference-panel reference-recent-table">
    <header><div><h2>Dokumen Masuk Terbaru</h2><p>Data yang terakhir dicatat</p></div><a href="<?= $registerUrl ?>">Buka Register →</a></header>
    <div class="table-wrap"><table><thead><tr><th>No.</th><th>Pengirim</th><th>Perihal</th><th>Penerima</th><th>Hari / Tanggal Diterima</th><th>Jenis</th><th>Jumlah</th><th>Ekspedisi</th><?php if ($canAccessSecurity): ?><th></th><?php endif ?></tr></thead><tbody>
        <?php if ($recent === []): ?><tr><td colspan="<?= $canAccessSecurity ? 9 : 8 ?>"><div class="empty-state compact"><span>▤</span><strong>Belum ada dokumen masuk</strong><p>Belum ada data dokumen yang dapat ditampilkan.</p></div></td></tr>
        <?php else: ?><?php foreach ($recent as $index => $row): ?><tr><td><strong><?= $index + 1 ?></strong></td><td><strong><?= esc($row['pengirim']) ?></strong></td><td class="cell-wrap"><?= esc($row['perihal'] ?: '-') ?></td><td><?= esc($row['penerima'] ?: '-') ?></td><td><div class="date-cell"><strong><?= esc($row['hari']) ?></strong><span><?= date('d-m-Y',strtotime($row['tanggal'])) ?></span></div></td><td><?= esc($row['jenis']) ?></td><td><?= number_format($row['jumlah'],0,',','.') ?></td><td><?= esc($row['ekspedisi'] ?: '-') ?></td><?php if ($canAccessSecurity): ?><td><a class="icon-btn" href="<?= site_url('dokumen-masuk/'.$row['id']) ?>" data-open-detail-modal data-detail-url="<?= site_url('dokumen-masuk/'.$row['id']) ?>">→</a></td><?php endif ?></tr><?php endforeach ?><?php endif ?>
    </tbody></table></div>
</section>
<?= $this->endSection() ?>
