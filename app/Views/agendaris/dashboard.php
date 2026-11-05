<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<?php
$summary = is_array($summary ?? null) ? $summary : [];
$documents = is_array($dokumenTerbaru ?? null) ? $dokumenTerbaru : [];
$value = static fn (string $key): int => (int) ($summary[$key] ?? 0);
$completion = max(0, min(100, $value('dokumen_persentase')));
$stockPercentages = static function (int $available, int $low, int $empty): array {
    $total = $available + $low + $empty;
    if ($total === 0) return [0, 0];
    $availableEnd = (int) round(($available / $total) * 100);
    $lowEnd = min(100, $availableEnd + (int) round(($low / $total) * 100));
    return [$availableEnd, $lowEnd];
};
[$atkReadyEnd, $atkLowEnd] = $stockPercentages($value('atk_tersedia'), $value('atk_menipis'), $value('atk_habis'));
[$souvenirReadyEnd, $souvenirLowEnd] = $stockPercentages($value('souvenir_tersedia'), $value('souvenir_menipis'), $value('souvenir_habis'));
?>

<style>
    .agendaris-dashboard { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 16px; max-width: 1320px; }
    .agendaris-dashboard-header, .agendaris-summary-grid, .agendaris-menu-grid { grid-column: 1 / -1; }
    .agendaris-dashboard-header { display: flex; align-items: flex-end; justify-content: space-between; gap: 16px; padding: 4px 2px 2px; }
    .agendaris-dashboard-header p { margin: 0 0 5px; color: #1681d5; font-size: 11px; font-weight: 800; letter-spacing: .1em; text-transform: uppercase; }
    .agendaris-dashboard-header h1 { margin: 0; color: #073b78; font-size: clamp(24px, 2vw, 30px); }
    .agendaris-dashboard-header .agendaris-dashboard-date { margin: 0; color: #7187a2; font-size: 12px; }
    .agendaris-summary-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 12px; }
    .agendaris-summary-card, .agendaris-panel { border: 1px solid #d9e8f2; border-radius: 14px; background: #fff; box-shadow: 0 6px 18px rgba(30, 87, 133, .07); }
    .agendaris-summary-card { display: flex; min-height: 114px; padding: 16px; gap: 12px; align-items: center; }
    .agendaris-summary-icon { display: grid; flex: 0 0 40px; width: 40px; height: 40px; place-items: center; border-radius: 12px; color: #fff; font-size: 20px; font-weight: 800; }
    .agendaris-summary-card:nth-child(1) .agendaris-summary-icon { background: linear-gradient(135deg, #1678e8, #35bdea); }
    .agendaris-summary-card:nth-child(2) .agendaris-summary-icon { background: linear-gradient(135deg, #00a77a, #34d8ab); }
    .agendaris-summary-card:nth-child(3) .agendaris-summary-icon { background: linear-gradient(135deg, #7b4de5, #a985fb); }
    .agendaris-summary-card:nth-child(4) .agendaris-summary-icon { background: linear-gradient(135deg, #f47b20, #ffc14d); }
    .agendaris-summary-label { margin: 0; color: #758ba4; font-size: 11px; font-weight: 750; }
    .agendaris-summary-value { margin: 3px 0 2px; color: #073b78; font-size: 24px; font-weight: 850; line-height: 1; }
    .agendaris-summary-note { margin: 0; color: #6f87a1; font-size: 10px; }
    .agendaris-dashboard-main, .agendaris-stock-grid { display: contents; }
    .agendaris-dashboard-main > .agendaris-panel:first-child, .agendaris-stock-grid > .agendaris-panel { order: 0; }
    .agendaris-dashboard-main > .agendaris-panel:last-child { order: 1; grid-column: 1 / -1; }
    .agendaris-panel { align-self: start; min-height: 230px; overflow: hidden; }
    .agendaris-panel-heading { display: flex; align-items: center; justify-content: space-between; gap: 10px; padding: 12px 15px; border-bottom: 1px solid #e2edf4; }
    .agendaris-panel-heading h2 { margin: 0; color: #073b78; font-size: 16px; }
    .agendaris-panel-heading span { color: #7890aa; font-size: 11px; }
    .agendaris-progress-body { display: grid; grid-template-columns: 108px minmax(0, 1fr); align-items: center; gap: 13px; padding: 14px 15px; }
    .agendaris-donut { --completion: <?= $completion ?>%; display: grid; width: 100px; height: 100px; place-items: center; border-radius: 50%; background: conic-gradient(#1f06ac 0 var(--completion), #08cb00 var(--completion) 100%); position: relative; }
    .agendaris-donut::after { width: 70px; height: 70px; border-radius: inherit; background: #fff; content: ''; }
    .agendaris-donut-content { position: absolute; z-index: 1; text-align: center; }
    .agendaris-donut-content strong { display: block; color: #073b78; font-size: 19px; line-height: 1; }
    .agendaris-donut-content span { display: block; margin-top: 3px; color: #758ba4; font-size: 8px; font-weight: 800; text-transform: uppercase; }
    .agendaris-progress-list { display: grid; gap: 8px; }
    .agendaris-progress-item { display: grid; grid-template-columns: 10px minmax(0, 1fr) auto; align-items: center; gap: 9px; }
    .agendaris-progress-dot { width: 9px; height: 9px; border-radius: 50%; background: #1f06ac; box-shadow: 0 0 0 5px rgba(31, 6, 172, .11); }
    .agendaris-progress-item:nth-child(2) .agendaris-progress-dot { background: #08cb00; box-shadow: 0 0 0 5px rgba(8, 203, 0, .12); }
    .agendaris-progress-item:nth-child(3) .agendaris-progress-dot { background: #06b6d4; box-shadow: 0 0 0 5px rgba(6, 182, 212, .12); }
    .agendaris-progress-item p { margin: 0; color: #23496e; font-size: 11px; font-weight: 750; }
    .agendaris-progress-item small { display: block; margin-top: 1px; color: #7f93a9; font-size: 9px; }
    .agendaris-progress-item b { color: #073b78; font-size: 12px; }
    .agendaris-activity-list { display: grid; }
    .agendaris-activity { display: grid; grid-template-columns: 8px minmax(0, 1fr) auto; gap: 11px; align-items: start; padding: 12px 18px; border-bottom: 1px solid #edf3f7; }
    .agendaris-activity:last-child { border-bottom: 0; }
    .agendaris-activity-marker { width: 8px; height: 8px; margin-top: 5px; border-radius: 50%; background: #26b9e8; }
    .agendaris-activity strong { display: block; overflow: hidden; color: #244968; font-size: 12px; text-overflow: ellipsis; white-space: nowrap; }
    .agendaris-activity p { margin: 3px 0 0; overflow: hidden; color: #7c91a8; font-size: 10px; text-overflow: ellipsis; white-space: nowrap; }
    .agendaris-status { padding: 4px 7px; border-radius: 99px; color: #078966; background: #e8fbf4; font-size: 9px; font-weight: 800; white-space: nowrap; }
    .agendaris-status.waiting { color: #bd7205; background: #fff4da; }
    .agendaris-empty { padding: 30px 18px; color: #8497ab; font-size: 12px; text-align: center; }
    .agendaris-stock-panel { display: grid; grid-template-columns: 108px minmax(0, 1fr); align-items: center; gap: 13px; padding: 14px 15px; }
    .agendaris-stock-donut { display: grid; width: 100px; height: 100px; place-items: center; border-radius: 50%; background: conic-gradient(#1f06ac 0 var(--stock-ready), #08cb00 var(--stock-ready) var(--stock-low), #06b6d4 var(--stock-low) 100%); }
    .agendaris-stock-donut.empty { background: #e8eff5; }
    .agendaris-stock-donut::after { width: 70px; height: 70px; border-radius: 50%; background: #fff; content: ''; }
    .agendaris-stock-donut strong { position: absolute; z-index: 1; color: #073b78; font-size: 19px; }
    .agendaris-stock-donut { position: relative; }
    .agendaris-stock-donut span { position: absolute; z-index: 1; margin-top: 31px; color: #7b90a7; font-size: 8px; font-weight: 800; text-transform: uppercase; }
    .agendaris-stock-legend { display: grid; gap: 7px; }
    .agendaris-stock-legend div { display: grid; grid-template-columns: 9px minmax(0, 1fr) auto; align-items: center; gap: 8px; }
    .agendaris-stock-legend i { width: 9px; height: 9px; border-radius: 50%; background: #1f06ac; }
    .agendaris-stock-legend div:nth-child(2) i { background: #08cb00; }
    .agendaris-stock-legend div:nth-child(3) i { background: #06b6d4; }
    .agendaris-stock-legend span { color: #58728e; font-size: 10px; }
    .agendaris-stock-legend b { color: #073b78; font-size: 11px; }
    .agendaris-menu-grid { display: grid; grid-template-columns: repeat(5, minmax(0, 1fr)); gap: 12px; }
    .agendaris-menu-link { display: grid; min-height: 108px; padding: 15px; gap: 8px; border: 1px solid #d9e8f2; border-radius: 13px; color: inherit; background: #fff; box-shadow: 0 5px 14px rgba(30, 87, 133, .05); text-decoration: none; transition: transform .16s ease, border-color .16s ease, box-shadow .16s ease; }
    .agendaris-menu-link:hover { border-color: #8fc9eb; box-shadow: 0 10px 22px rgba(30, 114, 177, .12); transform: translateY(-2px); }
    .agendaris-menu-link span { display: grid; width: 30px; height: 30px; place-items: center; border-radius: 9px; color: #fff; background: linear-gradient(135deg, #147de8, #2fc0eb); font-size: 14px; }
    .agendaris-menu-link strong { color: #103f75; font-size: 12px; }
    .agendaris-menu-link small { color: #7890a7; font-size: 10px; line-height: 1.45; }
    @media (max-width: 1100px) { .agendaris-summary-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } .agendaris-menu-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); } }
    @media (max-width: 960px) { .agendaris-dashboard { grid-template-columns: repeat(2, minmax(0, 1fr)); } .agendaris-dashboard-main > .agendaris-panel:last-child { grid-column: 1 / -1; } }
    @media (max-width: 760px) { .agendaris-dashboard { grid-template-columns: 1fr; } .agendaris-progress-body { grid-template-columns: 1fr; justify-items: center; } .agendaris-progress-list { width: 100%; } .agendaris-stock-panel { grid-template-columns: 108px minmax(0, 1fr); } .agendaris-menu-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } .agendaris-dashboard-header { align-items: flex-start; flex-direction: column; } }
</style>

<main class="agendaris-dashboard" aria-label="Dashboard Agendaris">
    <header class="agendaris-dashboard-header">
        <div><p>Agendaris</p><h1>Dashboard Agendaris</h1></div>
        <p class="agendaris-dashboard-date">Ringkasan data dan aktivitas Agendaris</p>
    </header>

    <section class="agendaris-summary-grid" aria-label="Ringkasan Agendaris">
        <article class="agendaris-summary-card"><span class="agendaris-summary-icon" aria-hidden="true">↙</span><div><p class="agendaris-summary-label">Dokumen Masuk</p><p class="agendaris-summary-value"><?= number_format($value('dokumen_masuk_total'), 0, ',', '.') ?></p><p class="agendaris-summary-note"><?= number_format($value('dokumen_masuk_menunggu'), 0, ',', '.') ?> menunggu penyelesaian</p></div></article>
        <article class="agendaris-summary-card"><span class="agendaris-summary-icon" aria-hidden="true">↗</span><div><p class="agendaris-summary-label">Dokumen Keluar</p><p class="agendaris-summary-value"><?= number_format($value('dokumen_keluar_total'), 0, ',', '.') ?></p><p class="agendaris-summary-note"><?= number_format($value('dokumen_keluar_selesai'), 0, ',', '.') ?> selesai di Agendaris</p></div></article>
        <article class="agendaris-summary-card"><span class="agendaris-summary-icon" aria-hidden="true">▤</span><div><p class="agendaris-summary-label">Stok ATK</p><p class="agendaris-summary-value"><?= number_format($value('atk_total'), 0, ',', '.') ?></p><p class="agendaris-summary-note"><?= number_format($value('atk_menipis'), 0, ',', '.') ?> stok menipis · <?= number_format($value('atk_habis'), 0, ',', '.') ?> habis</p></div></article>
        <article class="agendaris-summary-card"><span class="agendaris-summary-icon" aria-hidden="true">◈</span><div><p class="agendaris-summary-label">Item Tercatat</p><p class="agendaris-summary-value"><?= number_format($value('souvenir_total') + $value('inventaris_total'), 0, ',', '.') ?></p><p class="agendaris-summary-note"><?= number_format($value('souvenir_total'), 0, ',', '.') ?> souvenir · <?= number_format($value('inventaris_total'), 0, ',', '.') ?> inventaris</p></div></article>
    </section>

    <section class="agendaris-dashboard-main">
        <article class="agendaris-panel"><header class="agendaris-panel-heading"><h2>Progres Dokumen Masuk</h2><span>Data Agendaris</span></header><div class="agendaris-progress-body"><div class="agendaris-donut"><div class="agendaris-donut-content"><strong><?= $completion ?>%</strong><span>Selesai</span></div></div><div class="agendaris-progress-list"><div class="agendaris-progress-item"><span class="agendaris-progress-dot"></span><div><p>Total Dokumen</p><small>Seluruh dokumen masuk yang tercatat</small></div><b><?= number_format($value('dokumen_masuk_total'), 0, ',', '.') ?></b></div><div class="agendaris-progress-item"><span class="agendaris-progress-dot"></span><div><p>Dokumen Selesai</p><small>Telah selesai diproses</small></div><b><?= number_format($value('dokumen_masuk_selesai'), 0, ',', '.') ?></b></div><div class="agendaris-progress-item"><span class="agendaris-progress-dot"></span><div><p>Perlu Tindak Lanjut</p><small>Menunggu penyelesaian</small></div><b><?= number_format($value('dokumen_masuk_menunggu'), 0, ',', '.') ?></b></div></div></div></article>
        <article class="agendaris-panel"><header class="agendaris-panel-heading"><h2>Dokumen Terbaru</h2><a href="<?= site_url('agendaris/surat-masuk') ?>">Lihat dokumen</a></header><div class="agendaris-activity-list"><?php if ($documents === []): ?><div class="agendaris-empty">Belum ada dokumen Agendaris yang tercatat.</div><?php else: foreach ($documents as $document): ?><?php $finished = ($document['progres'] ?? '') === 'Selesai'; ?><div class="agendaris-activity"><span class="agendaris-activity-marker"></span><div><strong><?= esc($document['perihal_surat'] ?: ($document['nomor_surat'] ?: 'Dokumen tanpa perihal')) ?></strong><p><?= esc($document['nomor_agendaris'] ?: ($document['nomor_surat'] ?: 'Nomor belum diisi')) ?> · <?= ! empty($document['tanggal_diterima']) ? esc(date('d/m/Y', strtotime((string) $document['tanggal_diterima']))) : 'Tanggal belum diisi' ?></p></div><span class="agendaris-status <?= $finished ? '' : 'waiting' ?>"><?= esc($document['progres'] ?: 'Menunggu') ?></span></div><?php endforeach; endif ?></div></article>
    </section>

    <section class="agendaris-stock-grid" aria-label="Diagram status stok">
        <article class="agendaris-panel"><header class="agendaris-panel-heading"><h2>Diagram Stok ATK</h2><span>Berdasarkan item</span></header><div class="agendaris-stock-panel"><div class="agendaris-stock-donut <?= $value('atk_total') === 0 ? 'empty' : '' ?>" style="--stock-ready: <?= $atkReadyEnd ?>%; --stock-low: <?= $atkLowEnd ?>%;"><strong><?= number_format($value('atk_total'), 0, ',', '.') ?></strong><span>Total Item</span></div><div class="agendaris-stock-legend"><div><i></i><span>Stok tersedia</span><b><?= number_format($value('atk_tersedia'), 0, ',', '.') ?></b></div><div><i></i><span>Stok menipis</span><b><?= number_format($value('atk_menipis'), 0, ',', '.') ?></b></div><div><i></i><span>Stok habis</span><b><?= number_format($value('atk_habis'), 0, ',', '.') ?></b></div></div></div></article>
        <article class="agendaris-panel"><header class="agendaris-panel-heading"><h2>Diagram Stok Souvenir</h2><span>Berdasarkan item</span></header><div class="agendaris-stock-panel"><div class="agendaris-stock-donut <?= $value('souvenir_total') === 0 ? 'empty' : '' ?>" style="--stock-ready: <?= $souvenirReadyEnd ?>%; --stock-low: <?= $souvenirLowEnd ?>%;"><strong><?= number_format($value('souvenir_total'), 0, ',', '.') ?></strong><span>Total Item</span></div><div class="agendaris-stock-legend"><div><i></i><span>Stok tersedia</span><b><?= number_format($value('souvenir_tersedia'), 0, ',', '.') ?></b></div><div><i></i><span>Stok menipis</span><b><?= number_format($value('souvenir_menipis'), 0, ',', '.') ?></b></div><div><i></i><span>Stok habis</span><b><?= number_format($value('souvenir_habis'), 0, ',', '.') ?></b></div></div></div></article>
    </section>

    <section class="agendaris-menu-grid" aria-label="Akses cepat menu Agendaris">
        <a class="agendaris-menu-link" href="<?= site_url('agendaris/surat-masuk') ?>"><span aria-hidden="true">↙</span><strong>Dokumen Masuk</strong><small>Arsip dokumen masuk yang telah selesai.</small></a>
        <a class="agendaris-menu-link" href="<?= site_url('agendaris/surat-keluar') ?>"><span aria-hidden="true">↗</span><strong>Dokumen Keluar</strong><small>Daftar dokumen keluar yang telah tercatat.</small></a>
        <a class="agendaris-menu-link" href="<?= site_url('agendaris/progres-dokumen') ?>"><span aria-hidden="true">◷</span><strong>Progres Dokumen</strong><small>Kelola dokumen yang masih dalam proses.</small></a>
        <a class="agendaris-menu-link" href="<?= site_url('agendaris/souvenir-inventaris') ?>"><span aria-hidden="true">◈</span><strong>Souvenir &amp; Inventaris</strong><small>Pantau stok serta pergerakan item.</small></a>
        <a class="agendaris-menu-link" href="<?= site_url('agendaris/atk-kantor') ?>"><span aria-hidden="true">▤</span><strong>ATK Kantor</strong><small>Kelola stok dan pengambilan ATK.</small></a>
    </section>
</main>

<?= $this->endSection() ?>
