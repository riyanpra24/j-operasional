<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<?php
$months = [1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr', 5 => 'Mei', 6 => 'Jun', 7 => 'Jul', 8 => 'Agu', 9 => 'Sep', 10 => 'Okt', 11 => 'Nov', 12 => 'Des'];
$lastMonth = min(12, max(1, (int) ($dashboardMonth ?? 1)));
$volumePoints = [];
for ($month = 1; $month <= $lastMonth; $month++) {
    $rawValue = $monthlyTrend[$month]['volume'] ?? null;
    $volumePoints[$month] = is_numeric($rawValue) ? (float) $rawValue : null;
}
$availableVolume = array_filter($volumePoints, static fn (?float $value): bool => $value !== null && $value > 0);
$hasVolume = $availableVolume !== [];
$volumeMaximum = $hasVolume ? max($availableVolume) : 1.0;
$latestVolume = $hasVolume ? end($availableVolume) : null;
$formatVolume = static function (?float $value): string {
    return $value === null ? 'Belum ada data' : 'Rp ' . number_format($value, 0, ',', '.');
};
$volumeChartPayload = json_encode([
    'labels' => array_map(static fn (int $month): string => $months[$month], array_keys($volumePoints)),
    'values' => array_values($volumePoints),
], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
?>

<style>
    .accounting-dashboard{display:grid;gap:16px}.accounting-dashboard-heading{padding:4px 2px}.accounting-dashboard-heading h1{margin:4px 0;color:#092b61;font-size:clamp(26px,3vw,36px);letter-spacing:-.045em}.accounting-dashboard-heading p{margin:0;color:#5d7da2;font-size:13px}.volume-dashboard-grid{display:grid;grid-template-columns:minmax(300px,460px);gap:14px}.volume-card{overflow:hidden;border:1px solid #d9e7f4;border-radius:15px;background:linear-gradient(135deg,#fff 0%,#f5f9ff 100%);box-shadow:0 10px 28px rgba(17,81,143,.08)}.volume-card-header{display:flex;align-items:flex-start;justify-content:space-between;gap:12px;padding:18px 19px 0}.volume-card-header h2{margin:0;color:#0a2a5d;font-size:16px}.volume-card-header p{margin:3px 0 0;color:#7190b2;font-size:11px}.volume-card-status{display:grid;place-items:center;width:32px;height:32px;color:#087ea5;background:#e1f4fb;border-radius:10px;font-size:17px;font-weight:900}.volume-amount{display:block;padding:13px 19px 0;color:#092a5f;font-size:clamp(22px,2.2vw,30px);font-weight:900;letter-spacing:-.045em}.volume-card-foot{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:0 19px 15px;color:#6281a6;font-size:10px;font-weight:750}.volume-card-foot strong{color:#0b8d84}.volume-chart{height:174px;padding:0 12px 10px}.volume-chart canvas{display:block;width:100%!important;height:100%!important}.volume-empty{display:grid;height:100%;place-items:center;color:#7e95ab;font-size:12px;text-align:center}@media(max-width:620px){.volume-dashboard-grid{grid-template-columns:1fr}.volume-card{border-radius:13px}.volume-chart{height:158px}}
</style>

<main class="accounting-dashboard" aria-label="Dashboard Akuntansi">
    <header class="accounting-dashboard-heading">
        <p class="eyebrow">AKUTANSI</p>
        <h1>Dashboard Akuntansi</h1>
        <p>Ringkasan operasional berdasarkan data Laba / Rugi Korporat Kanwil.</p>
    </header>

    <section class="volume-dashboard-grid" aria-label="Ringkasan Volume">
        <article class="volume-card">
            <header class="volume-card-header">
                <div><h2>Volume Penjaminan</h2><p>Realisasi YTD Laba / Rugi · <?= esc($months[$lastMonth]) ?> <?= esc((string) ($dashboardYear ?? date('Y'))) ?></p></div>
                <span class="volume-card-status" aria-hidden="true">⌁</span>
            </header>
            <strong class="volume-amount"><?= esc($formatVolume($latestVolume)) ?></strong>
            <div class="volume-card-foot"><span>Sumber: baris Volume pada Laba / Rugi</span><?php if ($hasVolume): ?><strong>YTD</strong><?php endif ?></div>
            <div class="volume-chart">
                <?php if ($hasVolume): ?>
                <canvas id="accountingVolumeChart" aria-label="Grafik tren volume penjaminan per bulan" role="img"></canvas>
                <?php else: ?><div class="volume-empty">Belum ada angka Volume pada Laba / Rugi untuk periode ini.</div><?php endif ?>
            </div>
        </article>
    </section>
</main>
<?php if ($hasVolume): ?>
<script src="<?= esc(base_url('assets/vendor/chartjs/chart.umd.min.js'), 'attr') ?>"></script>
<script>
(() => {
    const canvas = document.getElementById('accountingVolumeChart');
    const payload = <?= $volumeChartPayload ?>;
    if (!canvas || !window.Chart) return;
    const rupiah = (value) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(value);
    new Chart(canvas, {
        type: 'line',
        data: { labels: payload.labels, datasets: [{ label: 'Volume Penjaminan', data: payload.values, borderColor: '#2584d8', backgroundColor: 'rgba(37,132,216,.16)', pointBackgroundColor: '#fff', pointBorderColor: '#2584d8', pointBorderWidth: 2.5, pointRadius: 4, pointHoverRadius: 6, borderWidth: 3, fill: true, tension: .42, spanGaps: true }] },
        options: { responsive: true, maintainAspectRatio: false, interaction: { mode: 'index', intersect: false }, plugins: { legend: { display: false }, tooltip: { displayColors: false, backgroundColor: '#092a5f', padding: 10, cornerRadius: 8, callbacks: { label: (context) => rupiah(context.raw) } } }, scales: { x: { grid: { display: false }, border: { display: false }, ticks: { color: '#7892ae', font: { size: 10, weight: '600' } } }, y: { display: false, beginAtZero: true, grid: { display: false }, border: { display: false } } } }
    });
})();
</script>
<?php endif ?>
<?= $this->endSection() ?>
