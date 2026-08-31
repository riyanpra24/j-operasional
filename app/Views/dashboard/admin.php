<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<?php
$displayName = (string) session()->get('auth_display_name');
$months = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
$dateLabel = date('d') . ' ' . $months[(int) date('n') - 1] . ' ' . date('Y');
$initials = strtoupper(substr($displayName !== '' ? $displayName : 'A', 0, 2));
$formatValue = static fn ($value): string => is_numeric($value) ? number_format((float) $value, 0, ',', '.') : (string) $value;
$trendMax = max(1, ...array_map('intval', array_column($activityTrend, 'total')));
$chartWidth = 720;
$chartHeight = 180;
$trendPoints = [];
$trendCount = max(1, count($activityTrend) - 1);
foreach ($activityTrend as $index => $item) {
    $trendPoints[] = [
        round(($index / $trendCount) * $chartWidth, 1),
        round(145 - (((int) $item['total'] / $trendMax) * 105), 1),
    ];
}
$trendPolyline = implode(' ', array_map(static fn (array $point): string => $point[0] . ',' . $point[1], $trendPoints));
$trendAreaPath = $trendPoints === []
    ? ''
    : 'M ' . implode(' L ', array_map(static fn (array $point): string => $point[0] . ' ' . $point[1], $trendPoints)) . " L {$chartWidth} {$chartHeight} L 0 {$chartHeight} Z";
$compositionColors = ['blue' => '#087bb8', 'purple' => '#7152c2', 'orange' => '#e18a12', 'teal' => '#009e91', 'navy' => '#153e6c'];
$compositionTotal = array_sum(array_map('intval', array_column($dataComposition, 'total')));
$donutStops = [];
$donutStart = 0.0;
foreach ($dataComposition as $item) {
    $donutEnd = $compositionTotal > 0 ? $donutStart + (((int) $item['total'] / $compositionTotal) * 360) : $donutStart;
    $color = $compositionColors[$item['tone']] ?? '#8392a5';
    $donutStops[] = $color . ' ' . round($donutStart, 2) . 'deg ' . round($donutEnd, 2) . 'deg';
    $donutStart = $donutEnd;
}
$donutBackground = $compositionTotal > 0 ? 'conic-gradient(' . implode(',', $donutStops) . ')' : '#e8eef2';
?>

<section class="admin-dashboard-hero">
    <div class="admin-dashboard-welcome">
        <span class="admin-dashboard-avatar" aria-hidden="true"><?= esc($initials) ?></span>
        <div>
            <span class="admin-dashboard-kicker">DASHBOARD ADMINISTRATOR</span>
            <h1>Selamat datang, <?= esc($displayName !== '' ? $displayName : 'Administrator') ?></h1>
            <p>Ringkasan terpusat seluruh aktivitas operasional JAKSA per <?= esc($dateLabel) ?>.</p>
        </div>
    </div>
    <div class="admin-dashboard-hero-actions">
        <a class="admin-dashboard-account-link" href="<?= site_url('kelola-akun') ?>">
            <span aria-hidden="true">◎</span>
            <span><strong><?= number_format((int) $overview['users'], 0, ',', '.') ?> akun</strong><small>Kelola pengguna</small></span>
        </a>
        <button type="button" class="admin-dashboard-print" data-print><span aria-hidden="true">⇩</span> Cetak ringkasan</button>
    </div>
</section>

<section class="admin-dashboard-overview" aria-label="Ringkasan utama">
    <article class="admin-overview-card blue">
        <span class="admin-overview-icon" aria-hidden="true">▤</span>
        <div><small>Total Data Terkelola</small><strong><?= number_format((int) $overview['total_data'], 0, ',', '.') ?></strong><p>Akumulasi seluruh modul aktif</p></div>
    </article>
    <article class="admin-overview-card red">
        <span class="admin-overview-icon" aria-hidden="true">!</span>
        <div><small>Perlu Perhatian</small><strong><?= number_format((int) $overview['attention'], 0, ',', '.') ?></strong><p>Antrean dan tenggat yang perlu ditindaklanjuti</p></div>
    </article>
    <article class="admin-overview-card teal">
        <span class="admin-overview-icon" aria-hidden="true">+</span>
        <div><small>Aktivitas Hari Ini</small><strong><?= number_format((int) $overview['today_activity'], 0, ',', '.') ?></strong><p>Pencatatan baru pada modul utama</p></div>
    </article>
    <article class="admin-overview-card purple">
        <span class="admin-overview-icon" aria-hidden="true">●</span>
        <div><small>Sesi Aktif</small><strong><?= number_format((int) $overview['active_sessions'], 0, ',', '.') ?></strong><p>Akun yang sedang terhubung</p></div>
    </article>
</section>

<section class="admin-visual-grid" aria-label="Visualisasi ringkasan aplikasi">
    <article class="admin-dashboard-panel admin-trend-panel">
        <header>
            <div><span>DIAGRAM AKTIVITAS</span><h2>Tren Enam Bulan</h2><p>Jumlah pencatatan baru dari modul utama setiap bulan.</p></div>
            <span class="admin-chart-summary"><strong><?= number_format(array_sum(array_map('intval', array_column($activityTrend, 'total'))), 0, ',', '.') ?></strong><small>Total aktivitas</small></span>
        </header>
        <div class="admin-line-chart">
            <div class="admin-chart-y-axis" aria-hidden="true">
                <span><?= number_format($trendMax, 0, ',', '.') ?></span>
                <span><?= number_format((int) ceil($trendMax * .75), 0, ',', '.') ?></span>
                <span><?= number_format((int) ceil($trendMax * .5), 0, ',', '.') ?></span>
                <span><?= number_format((int) ceil($trendMax * .25), 0, ',', '.') ?></span>
                <span>0</span>
            </div>
            <div class="admin-chart-canvas">
                <svg viewBox="0 0 <?= $chartWidth ?> <?= $chartHeight ?>" preserveAspectRatio="none" role="img" aria-label="Diagram aktivitas enam bulan">
                    <defs><linearGradient id="adminTrendArea" x1="0" x2="0" y1="0" y2="1"><stop offset="0%" stop-color="#008baa" stop-opacity=".3"/><stop offset="100%" stop-color="#00a49d" stop-opacity=".03"/></linearGradient></defs>
                    <?php foreach ([40, 66, 92, 118, 145] as $gridY): ?><line x1="0" y1="<?= $gridY ?>" x2="<?= $chartWidth ?>" y2="<?= $gridY ?>" class="admin-chart-grid-line"/><?php endforeach ?>
                    <?php if ($trendAreaPath !== ''): ?><path d="<?= esc($trendAreaPath, 'attr') ?>" fill="url(#adminTrendArea)"/><?php endif ?>
                    <polyline points="<?= esc($trendPolyline, 'attr') ?>" class="admin-chart-line"/>
                    <?php foreach ($trendPoints as $index => $point): ?>
                        <circle cx="<?= $point[0] ?>" cy="<?= $point[1] ?>" r="5" class="admin-chart-point"/>
                        <text x="<?= $point[0] ?>" y="<?= max(15, $point[1] - 13) ?>" text-anchor="middle" class="admin-chart-value"><?= (int) $activityTrend[$index]['total'] ?></text>
                    <?php endforeach ?>
                </svg>
                <div class="admin-chart-x-axis"><?php foreach ($activityTrend as $item): ?><span><strong><?= esc($item['label']) ?></strong><small><?= esc(substr($item['period'], 0, 4)) ?></small></span><?php endforeach ?></div>
            </div>
        </div>
        <footer><span><i></i>Aktivitas tercatat</span><small>Sumber: Security, Agendaris, PKS, SPK, dan Monitoring Kendaraan</small></footer>
    </article>

    <article class="admin-dashboard-panel admin-donut-panel">
        <header><div><span>DONUT RINGKAS</span><h2>Komposisi Data</h2><p>Perbandingan jumlah data pada setiap unit.</p></div></header>
        <div class="admin-donut-content">
            <div class="admin-donut-chart" style="background:<?= esc($donutBackground, 'attr') ?>" role="img" aria-label="Komposisi <?= number_format($compositionTotal, 0, ',', '.') ?> data">
                <div><strong><?= number_format($compositionTotal, 0, ',', '.') ?></strong><small>Total data</small></div>
            </div>
            <div class="admin-donut-legend">
                <?php foreach ($dataComposition as $item): ?>
                    <?php $percentage = $compositionTotal > 0 ? round(((int) $item['total'] / $compositionTotal) * 100) : 0; ?>
                    <div class="<?= esc($item['tone'], 'attr') ?>">
                        <i aria-hidden="true"></i>
                        <span><strong><?= esc($item['label']) ?></strong><small><?= number_format((int) $item['total'], 0, ',', '.') ?> data</small></span>
                        <b><?= $percentage ?>%</b>
                    </div>
                <?php endforeach ?>
            </div>
        </div>
    </article>
</section>

<section class="admin-dashboard-section">
    <header class="admin-dashboard-section-heading">
        <div><span>SELURUH UNIT</span><h2>Ringkasan Modul</h2><p>Pantau jumlah data dan status utama dari setiap bagian.</p></div>
    </header>

    <div class="admin-module-grid">
        <?php foreach ($modules as $module): ?>
            <article class="admin-module-card <?= esc($module['tone'], 'attr') ?>">
                <header>
                    <div class="admin-module-heading">
                        <span class="admin-module-icon" aria-hidden="true"><?= esc($module['icon']) ?></span>
                        <div><span><?= esc(strtoupper($module['key'])) ?></span><h3><?= esc($module['title']) ?></h3><p><?= esc($module['subtitle']) ?></p></div>
                    </div>
                    <a href="<?= esc($module['url'], 'attr') ?>">Buka modul <span aria-hidden="true">→</span></a>
                </header>
                <div class="admin-module-metrics">
                    <?php foreach ($module['metrics'] as $metric): ?>
                        <a class="admin-module-metric" href="<?= esc($metric['url'], 'attr') ?>">
                            <span><?= esc($metric['label']) ?></span>
                            <strong><?= esc($formatValue($metric['value'])) ?></strong>
                            <small><?= esc($metric['meta']) ?></small>
                        </a>
                    <?php endforeach ?>
                </div>
            </article>
        <?php endforeach ?>
    </div>
</section>

<section class="admin-dashboard-lower-grid">
    <article class="admin-dashboard-panel admin-attention-panel">
        <header><div><span>PRIORITAS</span><h2>Perlu Perhatian</h2><p>Daftar pekerjaan yang belum selesai atau mendekati tenggat.</p></div><strong><?= number_format((int) $overview['attention'], 0, ',', '.') ?></strong></header>
        <div class="admin-attention-list">
            <?php foreach ($attentionItems as $item): ?>
                <a href="<?= esc($item['url'], 'attr') ?>" class="admin-attention-item <?= esc($item['tone'], 'attr') ?>">
                    <span class="admin-attention-count"><?= number_format((int) $item['count'], 0, ',', '.') ?></span>
                    <span><strong><?= esc($item['label']) ?></strong><small><?= esc($item['section']) ?></small></span>
                    <i aria-hidden="true">→</i>
                </a>
            <?php endforeach ?>
        </div>
    </article>

    <article class="admin-dashboard-panel admin-activity-panel">
        <header><div><span>TERBARU</span><h2>Aktivitas Aplikasi</h2><p>Data terbaru lintas unit dalam satu rangkuman.</p></div></header>
        <div class="admin-activity-list">
            <?php if ($recentActivity === []): ?>
                <div class="admin-dashboard-empty"><span>▤</span><strong>Belum ada aktivitas</strong><p>Aktivitas terbaru akan tampil di sini.</p></div>
            <?php else: ?>
                <?php foreach ($recentActivity as $item): ?>
                    <a href="<?= esc($item['url'], 'attr') ?>" class="admin-activity-item <?= esc($item['tone'], 'attr') ?>">
                        <span class="admin-activity-dot" aria-hidden="true"></span>
                        <span class="admin-activity-copy">
                            <small><?= esc(strtoupper($item['section'])) ?></small>
                            <strong><?= esc($item['title']) ?></strong>
                            <p><?= esc($item['description'] ?: '-') ?></p>
                        </span>
                        <time datetime="<?= esc($item['time'], 'attr') ?>"><?= esc(date('d-m-Y', strtotime($item['time']))) ?><small><?= esc(date('H:i', strtotime($item['time']))) ?> WIB</small></time>
                    </a>
                <?php endforeach ?>
            <?php endif ?>
        </div>
    </article>
</section>

<?= $this->endSection() ?>
