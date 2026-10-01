<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<?php
$monthNames = [1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'];
$periodMonth = (int) ($dashboardMonth ?? 1); $periodYear = (int) ($dashboardYear ?? date('Y'));
$periodLabel = ($dashboardBasis ?? 'YTD') . ' · ' . ($monthNames[$periodMonth] ?? '-') . ' ' . $periodYear;
$money = static fn ($v): string => ($v === null || $v === '' || !is_numeric($v)) ? '—' : 'Rp ' . number_format((float)$v, 0, ',', '.');
$percent = static fn ($v): string => ($v === null || $v === '' || !is_numeric($v)) ? '—' : number_format((float)$v, 0, ',', '.') . '%';
$metricCards = [
 ['key'=>'pendapatan','label'=>'Pendapatan Penjaminan','caption'=>'Imbal jasa penjaminan bruto','icon'=>'↗','tone'=>'mint'],
 ['key'=>'klaim','label'=>'Beban Klaim','caption'=>'Jumlah beban klaim','icon'=>'▣','tone'=>'coral'],
 ['key'=>'penjaminan_bersih','label'=>'Penjaminan Bersih','caption'=>'Setelah beban klaim','icon'=>'▥','tone'=>'blue'],
 ['key'=>'laba_sebelum_pajak','label'=>'Laba Sebelum Pajak','caption'=>'Kinerja akhir sebelum pajak','icon'=>'◉','tone'=>'violet'],
];
$trend = ['labels'=>[], 'income'=>[], 'claims'=>[], 'profit'=>[]];
foreach ($monthNames as $month => $label) { if ($month > $periodMonth) break; $trend['labels'][]=substr($label,0,3); foreach (['income'=>'pendapatan','claims'=>'klaim','profit'=>'laba'] as $target=>$source) $trend[$target][]=isset($monthlyTrend[$month][$source]) && is_numeric($monthlyTrend[$month][$source]) ? (float)$monthlyTrend[$month][$source] : null; }
$trendPayload=json_encode($trend,JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT);
$expensePayload=json_encode(['labels'=>array_keys($expenseComposition ?? []),'values'=>array_map(static fn($v):float=>is_numeric($v)?(float)$v:0,array_values($expenseComposition ?? []))],JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT);
$mapPayload=json_encode($regionalLobData ?? [],JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT);
$lobSummary=['KUR'=>$lobBreakdown['KUR']??null,'PEN'=>$lobBreakdown['PEN']??null,'NON KUR'=>$lobBreakdown['NON KUR']??null];
$mainBarPayload=json_encode(['labels'=>array_column($metricCards,'label'),'values'=>array_map(static fn(array $card):float=>(float)($dashboardMetrics[$card['key']]['total']??0),$metricCards)],JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT);
$lobDonutPayload=json_encode(['labels'=>array_keys($lobSummary),'values'=>array_map(static fn($value):float=>max(0,(float)$value),array_values($lobSummary))],JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT);
?>
<style>
.accounting-dashboard{--dash-navy:#092e62;--dash-blue:#187fda;--dash-sky:#eaf5ff;--dash-border:#d9e9f7;--dash-muted:#6683a5;--dash-mint:#16b697;max-width:1640px;margin:0 auto;padding:28px 28px 44px;color:var(--dash-navy);font-family:inherit}
.accounting-dashboard *{box-sizing:border-box}
.accounting-dashboard-heading{display:flex;justify-content:space-between;gap:24px;align-items:flex-start;margin-bottom:20px}
.accounting-dashboard .eyebrow{margin:0 0 5px;color:#2586d8;font-size:11px;font-weight:800;letter-spacing:1.3px}
.accounting-dashboard h1{margin:0;color:#082f66;font-size:34px;line-height:1.15;font-weight:800;letter-spacing:-.7px}
.accounting-dashboard-heading>div>p:last-child{margin:7px 0 0;color:#52769b;font-size:15px}
.dashboard-period{display:inline-flex;align-items:center;gap:10px;padding:13px 17px;border:1px solid var(--dash-border);border-radius:12px;background:#fff;box-shadow:0 6px 16px rgba(20,84,137,.06);color:#1b4d82;font-size:13px;font-weight:800;white-space:nowrap}
.dashboard-period i{font-style:normal;color:#1a83d9}
.dashboard-report-banner{display:flex;align-items:center;justify-content:space-between;gap:20px;padding:18px 22px;margin-bottom:15px;border:1px solid #d5ebfb;border-radius:15px;background:linear-gradient(105deg,#fff 0%,#f5fbff 57%,#ddf6ff 100%);box-shadow:0 9px 21px rgba(30,113,181,.06)}
.dashboard-report-title{display:flex;gap:14px;align-items:center}
.dashboard-report-icon{display:grid;place-items:center;width:50px;height:50px;border-radius:13px;background:#dff2ff;color:#197fd6;font-size:24px}
.dashboard-report-title small{display:block;color:#6a88a6;font-size:10px;font-weight:800;letter-spacing:.8px;margin-bottom:3px}
.dashboard-report-title strong{display:block;color:#07326c;font-size:18px}
.dashboard-source-status{display:flex;align-items:center;gap:8px;padding:10px 14px;border-radius:999px;background:#e8fbf4;color:#078d6b;font-size:12px;font-weight:800;white-space:nowrap}
.dashboard-source-status i,.regional-map-kicker i,.detail-label i{width:9px;height:9px;display:inline-block;border-radius:50%;background:#14b98e;box-shadow:0 0 0 4px rgba(20,185,142,.12)}
.dashboard-metric-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:14px;margin-bottom:14px}
.dashboard-metric-card{min-height:174px;padding:18px;border:1px solid var(--dash-border);border-radius:15px;background:#fff;box-shadow:0 9px 21px rgba(24,92,152,.06);overflow:hidden;position:relative}
.dashboard-metric-card:after{content:'';position:absolute;right:-38px;bottom:-40px;width:130px;height:112px;border-radius:50%;background:rgba(54,170,244,.07)}
.metric-card-head{position:relative;z-index:1;display:flex;align-items:center;justify-content:space-between;gap:10px}
.metric-card-head h2{margin:0;color:#102c61;font-size:14px;line-height:1.25;font-weight:800}
.metric-card-head>span{display:grid;place-items:center;flex:0 0 auto;width:38px;height:38px;border-radius:11px;background:#e4f2ff;color:#147ee0;font-size:22px;font-weight:800}
.metric-value{display:block;position:relative;z-index:1;margin-top:16px;color:#081d62;font-size:24px;line-height:1.1;letter-spacing:-.5px;white-space:nowrap}
.metric-caption{display:block;position:relative;z-index:1;margin-top:7px;color:#718daf;font-size:12px}
.metric-achievement{position:relative;z-index:1;display:flex;align-items:center;gap:6px;width:100%;margin-top:13px;padding:8px 10px;border-radius:9px;background:#eaf8f4;color:#069678;font-size:12px;font-weight:800}
.metric-achievement span{color:inherit}
.dashboard-metric-card.coral .metric-card-head>span{background:#ffe8ed;color:#eb4f69}.dashboard-metric-card.coral .metric-achievement{background:#fff0f2;color:#e7425f}
.dashboard-metric-card.blue .metric-card-head>span{background:#e4f1ff;color:#197fe0}.dashboard-metric-card.blue .metric-achievement{background:#ebf5ff;color:#187fe2}
.dashboard-metric-card.violet .metric-card-head>span{background:#ece9ff;color:#6353e7}.dashboard-metric-card.violet .metric-achievement{background:#f0efff;color:#5b4ae0}
.dashboard-content-grid{display:grid;grid-template-columns:minmax(0,1fr) minmax(0,1fr);gap:14px;margin:14px 0}
.dashboard-panel{min-width:0;padding:18px 18px 15px;border:1px solid var(--dash-border);border-radius:15px;background:#fff;box-shadow:0 9px 21px rgba(24,92,152,.055)}
.dashboard-panel-head{display:flex;justify-content:space-between;gap:12px;align-items:flex-start;margin-bottom:10px}
.dashboard-panel-title{display:flex;gap:11px;align-items:flex-start}.dashboard-panel-title>span{display:grid;place-items:center;width:33px;height:33px;flex:0 0 auto;border-radius:9px;background:#e9f4ff;color:#167fdb;font-size:19px;font-weight:800}
.dashboard-panel h2{margin:0;color:#102c61;font-size:16px;line-height:1.25;font-weight:800}.dashboard-panel p{margin:3px 0 0;color:#6a87aa;font-size:12px;line-height:1.5}
.dashboard-panel-more{color:#197fda;letter-spacing:2px;font-size:16px;line-height:1}
.dashboard-chart-wrap{position:relative;height:276px;width:100%}.dashboard-chart-wrap canvas{max-width:100%!important}
.lob-overview{padding:4px 3px 0}.lob-row{display:grid;grid-template-columns:115px minmax(0,1fr);gap:12px;align-items:center;margin:15px 0}.lob-name{color:#294a6f;font-size:12px;font-weight:800}.lob-track{height:21px;display:flex;align-items:center;position:relative;border-radius:999px;background:#edf4fb;overflow:hidden}.lob-bar{display:block;width:var(--width);min-width:2px;height:100%;border-radius:inherit;background:linear-gradient(90deg,var(--start),var(--end));transition:width .5s ease}.lob-value{position:absolute;right:9px;color:#123c72;font-size:11px;white-space:nowrap}.lob-note{padding-top:4px!important;color:#7893af!important;font-size:11px!important}.lob-note strong{color:#40618a}
.regional-map-card{display:grid;grid-template-columns:minmax(0,1.38fr) minmax(280px,.62fr);gap:0;margin:14px 0;border:1px solid var(--dash-border);border-radius:16px;overflow:hidden;background:#fff;box-shadow:0 9px 21px rgba(24,92,152,.06)}
.regional-map-visual{min-width:0;padding:22px 24px;background:linear-gradient(135deg,#eef9ff 0%,#fff 75%)}.regional-map-kicker,.detail-label{display:flex;align-items:center;gap:8px;margin:0;color:#2781cd!important;font-size:10px!important;font-weight:800;letter-spacing:1px}.regional-map-visual h2,.regional-map-detail h2{margin:6px 0 0;color:#113263;font-size:20px;font-weight:800}
.regional-map-surface{position:relative;min-height:330px;margin-top:10px;border-radius:12px;background:radial-gradient(circle at 25% 30%,#f7fcff 0,#e6f6ff 58%,#d5eeff 100%);overflow:hidden}.regional-map-land{position:absolute;inset:4% 5%;width:90%;height:89%;filter:drop-shadow(0 10px 8px rgba(17,102,169,.18))}.land-main,.land-madura{fill:#91d2f6;stroke:#267dc1;stroke-width:3}.land-madura{fill:#b5e3fa}
.regional-map-marker{position:absolute;left:var(--x);top:var(--y);width:15px;height:15px;padding:0;border:3px solid #fff;border-radius:50%;background:#1485dd;box-shadow:0 0 0 5px rgba(20,133,221,.22);cursor:pointer;transition:transform .2s,background .2s}.regional-map-marker:hover,.regional-map-marker.is-selected{transform:scale(1.32);background:#f4a623}.regional-map-marker.is-unavailable{background:#9fb4c6;box-shadow:0 0 0 5px rgba(109,136,159,.18)}.regional-map-marker:after{content:attr(data-label);position:absolute;top:-22px;left:50%;transform:translateX(-50%);color:#24527e;font-size:10px;font-weight:800;white-space:nowrap;opacity:0;transition:opacity .15s}.regional-map-marker:hover:after,.regional-map-marker.is-selected:after{opacity:1}.regional-map-legend{position:absolute;left:15px;bottom:13px;display:flex;align-items:center;gap:7px;color:#457196;font-size:10px;font-weight:700}.regional-map-legend b{width:8px;height:8px;border-radius:50%;background:#1485dd}
.regional-map-detail{padding:25px 23px;border-left:1px solid var(--dash-border);background:#fbfdff}.regional-map-detail h2{font-size:22px}.detail-period{margin-top:4px!important}.regional-map-total{margin:20px 0 13px;padding:15px;border-radius:11px;background:linear-gradient(110deg,#e8f5ff,#effcf9)}.regional-map-total span{display:block;color:#5f83a7;font-size:11px;font-weight:700}.regional-map-total strong{display:block;margin-top:5px;color:#073a76;font-size:20px;line-height:1.25}.regional-map-lobs{list-style:none;margin:0;padding:0}.regional-map-lobs li{display:flex;justify-content:space-between;gap:12px;padding:12px 1px;border-bottom:1px solid #e8f0f8;color:#577899;font-size:12px}.regional-map-lobs strong{color:#183b69;font-size:12px}.regional-map-empty{display:none;margin:13px 0 0!important;padding:9px;border-radius:8px;background:#fff4e7;color:#9a6724!important;font-size:11px!important}.regional-map-note{margin-top:15px!important;color:#7992ab!important;font-size:11px!important}.regional-map-detail.is-changing{animation:mapDetail .36s ease}@keyframes mapDetail{0%{opacity:.4;transform:translateY(4px)}100%{opacity:1;transform:none}}
@media (max-width:1180px){.dashboard-metric-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.regional-map-card{grid-template-columns:1fr}.regional-map-detail{border-left:0;border-top:1px solid var(--dash-border)}}
@media (max-width:760px){.accounting-dashboard{padding:20px 14px 32px}.accounting-dashboard-heading,.dashboard-report-banner{align-items:flex-start;flex-direction:column}.accounting-dashboard h1{font-size:28px}.dashboard-period{width:100%;justify-content:center}.dashboard-metric-grid,.dashboard-content-grid{grid-template-columns:1fr}.dashboard-metric-card{min-height:158px}.metric-value{font-size:22px;white-space:normal}.dashboard-chart-wrap{height:245px}.lob-row{grid-template-columns:92px minmax(0,1fr);gap:8px}.lob-value{font-size:10px}.regional-map-visual{padding:20px 15px}.regional-map-surface{min-height:280px}.regional-map-detail{padding:20px 16px}}
.dashboard-development{min-height:calc(100vh - 190px);display:grid;place-items:center;padding:42px 18px;background:radial-gradient(circle at 20% 15%,rgba(54,172,240,.16),transparent 31%),radial-gradient(circle at 80% 80%,rgba(44,204,180,.15),transparent 29%)}
.dashboard-development-card{position:relative;width:min(100%,650px);padding:56px 34px 50px;border:1px solid #dcebf8;border-radius:24px;background:rgba(255,255,255,.94);box-shadow:0 24px 55px rgba(20,82,140,.14);text-align:center;overflow:hidden}
.dashboard-development-card:before,.dashboard-development-card:after{content:'';position:absolute;border-radius:50%;pointer-events:none}.dashboard-development-card:before{width:230px;height:230px;top:-155px;left:-100px;background:rgba(50,164,239,.11)}.dashboard-development-card:after{width:180px;height:180px;right:-98px;bottom:-100px;background:rgba(41,199,178,.12)}
.development-orbit{position:relative;z-index:1;width:112px;height:112px;margin:0 auto 27px;border:2px dashed #88c9ef;border-radius:50%;animation:development-spin 12s linear infinite}.development-orbit span{position:absolute;inset:19px;display:grid;place-items:center;border-radius:50%;background:linear-gradient(135deg,#147fd7,#44c6ba);color:#fff;font-size:34px;box-shadow:0 10px 22px rgba(26,139,211,.25);animation:development-float 2.8s ease-in-out infinite}.development-orbit i{position:absolute;width:10px;height:10px;right:4px;top:14px;border-radius:50%;background:#f4b23e;box-shadow:0 0 0 5px rgba(244,178,62,.15)}
.dashboard-development h1{position:relative;z-index:1;margin:0;color:#082f66;font-size:29px;letter-spacing:-.5px}.dashboard-development p{position:relative;z-index:1;max-width:470px;margin:12px auto 0;color:#6984a4;font-size:15px;line-height:1.7}.development-progress{position:relative;z-index:1;width:min(100%,390px);height:9px;margin:28px auto 12px;border-radius:999px;background:#e8f2fa;overflow:hidden}.development-progress i{display:block;width:68%;height:100%;border-radius:inherit;background:linear-gradient(90deg,#1684dc,#43c7b9);animation:development-progress 2.6s ease-in-out infinite}.development-status{position:relative;z-index:1;display:inline-flex;align-items:center;gap:8px;color:#2580ca;font-size:12px;font-weight:800}.development-status b{width:8px;height:8px;border-radius:50%;background:#24bb91;box-shadow:0 0 0 5px rgba(36,187,145,.13);animation:development-pulse 1.7s ease-in-out infinite}@keyframes development-spin{to{transform:rotate(360deg)}}@keyframes development-float{50%{transform:translateY(-5px) rotate(-10deg)}}@keyframes development-progress{0%,100%{width:38%}50%{width:82%}}@keyframes development-pulse{50%{box-shadow:0 0 0 9px rgba(36,187,145,0)}}@media (max-width:560px){.dashboard-development{min-height:calc(100vh - 130px);padding:22px 12px}.dashboard-development-card{padding:45px 22px 40px}.dashboard-development h1{font-size:25px}}
</style>
<main class="dashboard-development" aria-label="Dashboard Akuntansi sedang dikembangkan">
<section class="dashboard-development-card">
<div class="development-orbit" aria-hidden="true"><span>↗</span><i></i></div>
<h1>Dashboard Akuntansi<br>Sedang Dikembangkan</h1>
<p>Kami sedang menyiapkan ringkasan dan visualisasi data yang lebih nyaman untuk dibaca. Dashboard akan dilanjutkan pada tahap berikutnya.</p>
<div class="development-progress" aria-hidden="true"><i></i></div>
<span class="development-status"><b></b>Pembaruan sedang disiapkan</span>
</section>
</main>
<main class="accounting-dashboard accounting-dashboard-data" aria-label="Dashboard Akuntansi" hidden>
<header class="accounting-dashboard-heading"><div><p class="eyebrow">AKUTANSI</p><h1>Dashboard Akuntansi</h1><p>Ringkasan kinerja Laba / Rugi Kanwil Surabaya dan cabang Jawa Timur.</p></div><div class="dashboard-period"><i>▣</i><?= esc($periodLabel) ?></div></header>
<section class="dashboard-report-banner"><div class="dashboard-report-title"><span class="dashboard-report-icon">▥</span><div><small>SUMBER DASHBOARD</small><strong>Laporan Laba / Rugi Korporat Kanwil</strong></div></div><span class="dashboard-source-status"><i></i><?= ($dashboardReportAvailable ?? false) ? 'Data tersedia' : 'Data belum tersedia' ?></span></section>
<section class="dashboard-metric-grid"><?php foreach($metricCards as $card): $metric=(array)($dashboardMetrics[$card['key']]??[]); ?><article class="dashboard-metric-card <?= esc($card['tone'],'attr') ?>"><div class="metric-card-head"><h2><?= esc($card['label']) ?></h2><span><?= esc($card['icon']) ?></span></div><strong class="metric-value"><?= esc($money($metric['total']??null)) ?></strong><span class="metric-caption"><?= esc($card['caption']) ?></span><div class="metric-achievement">↗ <span>Pencapaian <?= esc($percent($metric['percentage']??null)) ?></span></div></article><?php endforeach ?></section>
<section class="dashboard-content-grid"><article class="dashboard-panel"><header class="dashboard-panel-head"><div class="dashboard-panel-title"><span>⌁</span><div><h2>Tren Laba / Rugi Bulanan</h2><p>Realisasi YTD Korporat Kanwil · nilai dalam Rupiah</p></div></div><b class="dashboard-panel-more">•••</b></header><div class="dashboard-chart-wrap"><canvas id="lrTrendChart"></canvas></div></article><article class="dashboard-panel"><header class="dashboard-panel-head"><div class="dashboard-panel-title"><span>▦</span><div><h2>Komposisi Beban</h2><p>Nilai beban dari Laba / Rugi aktif</p></div></div><b class="dashboard-panel-more">•••</b></header><div class="dashboard-chart-wrap"><canvas id="expenseChart"></canvas></div></article></section>
<section class="dashboard-content-grid"><?php $lobMaximum=max(array_map(static fn($v):float=>abs((float)$v),$lobSummary))?:1; ?><article class="dashboard-panel"><header class="dashboard-panel-head"><div class="dashboard-panel-title"><span>≡</span><div><h2>Laba Sebelum Pajak per LOB</h2><p>Rincian Korporat Kanwil dari Laba / Rugi aktif</p></div></div></header><div class="lob-overview"><?php foreach(['KUR'=>['#1d83dd','#56aaf3'],'PEN'=>['#ef5770','#ff91a3'],'NON KUR'=>['#f4a92d','#ffd36d']] as $lob=>[$start,$end]): $value=$lobSummary[$lob];$width=min(100,max(0,abs((float)$value)/$lobMaximum*100)); ?><div class="lob-row"><span class="lob-name"><?= esc($lob) ?></span><div class="lob-track"><i class="lob-bar" style="--width:<?= esc(number_format($width,2,'.',''),'attr') ?>%;--start:<?= esc($start,'attr') ?>;--end:<?= esc($end,'attr') ?>"></i><strong class="lob-value"><?= esc($money($value)) ?></strong></div></div><?php endforeach ?></div><p class="lob-note">Nilai LOB berasal langsung dari baris <strong>Laba Sebelum Pajak</strong>; tidak dihitung ulang di dashboard.</p></article><article class="dashboard-panel"><header class="dashboard-panel-head"><div class="dashboard-panel-title"><span>◎</span><div><h2>Kinerja Laporan</h2><p>Persentase yang tercatat pada Laba / Rugi</p></div></div></header><div class="lob-overview"><?php foreach($metricCards as $card):$metric=(array)($dashboardMetrics[$card['key']]??[]);$p=is_numeric($metric['percentage']??null)?max(0,min(100,(float)$metric['percentage'])):0;?><div class="lob-row"><span class="lob-name"><?= esc($card['label']) ?></span><div class="lob-track"><i class="lob-bar" style="--width:<?= esc(number_format($p,2,'.',''),'attr') ?>%;--start:#137fda;--end:#37bea8"></i><strong class="lob-value"><?= esc($percent($metric['percentage']??null)) ?></strong></div></div><?php endforeach ?></div><p class="lob-note">Pencapaian mengikuti kolom <strong>%</strong> pada Laporan Laba / Rugi, bukan persentase buatan dashboard.</p></article></section>
<section class="regional-map-card"><div class="regional-map-visual"><p class="regional-map-kicker"><i></i>PILIH CABANG</p><h2>Peta Kinerja Jawa Timur</h2><div class="regional-map-surface"><svg class="regional-map-land" viewBox="0 0 640 350" role="img" aria-label="Peta Jawa Timur"><path class="land-main" d="M 7.8,248.3 L 13.8,224.7 L 56.3,205.2 L 35.9,123.0 L 47.8,94.6 L 76.8,105.4 L 101.4,78.5 L 102.6,27.3 L 112.2,16.5 L 160.4,37.7 L 226.2,29.4 L 241.3,88.6 L 265.9,101.3 L 274.5,155.7 L 332.7,178.3 L 387.5,173.0 L 424.0,152.5 L 479.7,182.3 L 465.3,282.1 L 491.9,338.9 L 461.1,313.3 L 414.9,313.0 L 391.9,292.7 L 321.3,260.7 L 240.1,286.0 L 197.9,267.7 L 123.6,255.6 L 106.9,273.1 L 80.0,258.0 L 7.8,248.3 Z"/><path class="land-madura" d="M 407.9,34.0 L 433.8,45.8 L 359.9,92.7 L 249.3,83.1 L 270.9,38.3 L 407.9,34.0 Z"/></svg><button type="button" class="regional-map-marker" style="--x:16%;--y:45%" data-map-unit="Madiun" data-label="Madiun"></button><button type="button" class="regional-map-marker" style="--x:25%;--y:54%" data-map-unit="Kediri" data-label="Kediri"></button><button type="button" class="regional-map-marker" style="--x:38%;--y:61%" data-map-unit="Malang" data-label="Malang"></button><button type="button" class="regional-map-marker" style="--x:43%;--y:28%" data-map-unit="Surabaya" data-label="Surabaya"></button><button type="button" class="regional-map-marker" style="--x:74%;--y:72%" data-map-unit="Banyuwangi" data-label="Banyuwangi"></button><span class="regional-map-legend"><b></b>Klik titik cabang untuk melihat nominal per LOB</span></div></div><aside class="regional-map-detail" aria-live="polite"><p class="detail-label"><i></i>RINCIAN CABANG</p><h2 data-map-name>Surabaya</h2><p class="detail-period"><?= esc($periodLabel) ?> · Laba / Rugi</p><div class="regional-map-total"><span>Laba Sebelum Pajak</span><strong data-map-total>—</strong></div><ul class="regional-map-lobs"><li><span>KUR</span><strong data-map-lob="KUR">—</strong></li><li><span>PEN</span><strong data-map-lob="PEN">—</strong></li><li><span>NON KUR</span><strong data-map-lob="NON KUR">—</strong></li></ul><p class="regional-map-empty" data-map-empty>Belum ada laporan Laba / Rugi untuk cabang ini.</p><p class="regional-map-note">Nominal mengikuti baris <b>Laba Sebelum Pajak</b> pada Laporan Laba / Rugi.</p></aside></section>
<section class="dashboard-content-grid"><article class="dashboard-panel"><header class="dashboard-panel-head"><div class="dashboard-panel-title"><span>▮</span><div><h2>Diagram Batang Komponen Utama</h2><p>Perbandingan nominal pada Laba / Rugi aktif</p></div></div><b class="dashboard-panel-more">•••</b></header><div class="dashboard-chart-wrap"><canvas id="metricBarChart" aria-label="Diagram batang komponen utama Laba Rugi"></canvas></div></article><article class="dashboard-panel"><header class="dashboard-panel-head"><div class="dashboard-panel-title"><span>◔</span><div><h2>Diagram Donut per LOB</h2><p>Komposisi Laba Sebelum Pajak Korporat Kanwil</p></div></div><b class="dashboard-panel-more">•••</b></header><div class="dashboard-chart-wrap"><canvas id="lobDonutChart" aria-label="Diagram donut Laba Sebelum Pajak per LOB"></canvas></div><p class="lob-note">Warna donut: <strong>KUR</strong>, <strong>PEN</strong>, dan <strong>NON KUR</strong>. Nominal detail tetap tersedia pada panel LOB di atas.</p></article></section>
</main>
<script src="<?= esc(base_url('assets/vendor/chartjs/chart.umd.min.js'),'attr') ?>"></script><script>
(()=>{const trend=<?= $trendPayload?:'{}' ?>,expense=<?= $expensePayload?:'{}' ?>,mapData=<?= $mapPayload?:'{}' ?>;const rupiah=v=>{const n=Number(v);return Number.isFinite(n)&&n!==0?new Intl.NumberFormat('id-ID',{style:'currency',currency:'IDR',maximumFractionDigits:0}).format(n):'—'};const short=v=>{const n=Math.abs(Number(v));if(!Number.isFinite(n)||n===0)return'0';return n>=1e12?(Number(v)/1e12).toLocaleString('id-ID',{maximumFractionDigits:1})+' T':(Number(v)/1e9).toLocaleString('id-ID',{maximumFractionDigits:0})+' M'};if(window.Chart){const basic={responsive:true,maintainAspectRatio:false,interaction:{mode:'index',intersect:false},plugins:{legend:{labels:{usePointStyle:true,boxWidth:7,color:'#567795',font:{size:11,weight:'700'}}},tooltip:{backgroundColor:'#092e62',padding:11,cornerRadius:9,callbacks:{label:c=>c.dataset.label+': '+rupiah(c.raw)}}},scales:{x:{grid:{display:false},border:{display:false},ticks:{color:'#7893ad',font:{size:10,weight:'700'}}},y:{grid:{color:'rgba(76,134,180,.13)'},border:{display:false},ticks:{color:'#7893ad',font:{size:10},callback:short}}}};const tc=document.getElementById('lrTrendChart');if(tc)new Chart(tc,{type:'line',data:{labels:trend.labels||[],datasets:[{label:'Pendapatan Penjaminan',data:trend.income||[],borderColor:'#1686df',backgroundColor:'rgba(22,134,223,.11)',pointBackgroundColor:'#fff',pointBorderColor:'#1686df',pointBorderWidth:2,pointRadius:3.5,borderWidth:2.5,fill:true,tension:.4,spanGaps:true},{label:'Beban Klaim',data:trend.claims||[],borderColor:'#f05b70',pointBackgroundColor:'#fff',pointBorderColor:'#f05b70',pointBorderWidth:2,pointRadius:3.5,borderWidth:2.5,fill:false,tension:.4,spanGaps:true},{label:'Laba Sebelum Pajak',data:trend.profit||[],borderColor:'#12b595',pointBackgroundColor:'#fff',pointBorderColor:'#12b595',pointBorderWidth:2,pointRadius:3.5,borderWidth:2.5,fill:false,tension:.4,spanGaps:true}]},options:basic});const ec=document.getElementById('expenseChart');if(ec)new Chart(ec,{type:'bar',data:{labels:expense.labels||[],datasets:[{data:expense.values||[],borderRadius:6,borderSkipped:false,backgroundColor:['#1788dd','#34b8a0','#6eaefa','#9bded4']}]},options:{...basic,indexAxis:'y',plugins:{...basic.plugins,legend:{display:false}},scales:{x:{...basic.scales.x,ticks:{...basic.scales.x.ticks,callback:short}},y:{...basic.scales.y,grid:{display:false},ticks:{color:'#486a8f',font:{size:10,weight:'700'}}}}}})}const markers=[...document.querySelectorAll('[data-map-unit]')],detail=document.querySelector('.regional-map-detail'),name=document.querySelector('[data-map-name]'),total=document.querySelector('[data-map-total]'),empty=document.querySelector('[data-map-empty]'),lobFields=[...document.querySelectorAll('[data-map-lob]')];const select=unit=>{detail?.classList.remove('is-changing');void detail?.offsetWidth;detail?.classList.add('is-changing');const row=mapData[unit]||{},available=!!row.available;name.textContent=unit;total.textContent=available?rupiah(row.total):'—';lobFields.forEach(f=>f.textContent=available?rupiah(row.lobs?.[f.dataset.mapLob]):'—');empty.style.display=available?'none':'block';markers.forEach(m=>m.classList.toggle('is-selected',m.dataset.mapUnit===unit))};markers.forEach(m=>{if(!mapData[m.dataset.mapUnit]?.available)m.classList.add('is-unavailable');m.addEventListener('click',()=>select(m.dataset.mapUnit))});select(mapData.Surabaya?.available?'Surabaya':(markers.find(m=>mapData[m.dataset.mapUnit]?.available)?.dataset.mapUnit||'Surabaya'))})();
</script>
<script>
(() => {
  if (!window.Chart) return;

  const barData = <?= $mainBarPayload ?: '{}' ?>;
  const donutData = <?= $lobDonutPayload ?: '{}' ?>;
  const formatMoney = (value) => 'Rp ' + new Intl.NumberFormat('id-ID', { maximumFractionDigits: 0 }).format(Number(value || 0));
  const shortMoney = (value) => {
    const amount = Number(value || 0);
    if (Math.abs(amount) >= 1000000000) return (amount / 1000000000).toLocaleString('id-ID', { maximumFractionDigits: 1 }) + ' M';
    if (Math.abs(amount) >= 1000000) return (amount / 1000000).toLocaleString('id-ID', { maximumFractionDigits: 0 }) + ' Jt';
    return amount.toLocaleString('id-ID');
  };

  const metricBar = document.getElementById('metricBarChart');
  if (metricBar && Array.isArray(barData.labels) && Array.isArray(barData.values)) {
    new Chart(metricBar, {
      type: 'bar',
      data: {
        labels: barData.labels,
        datasets: [{
          label: 'Realisasi',
          data: barData.values,
          backgroundColor: ['#2f80ed', '#ef5b70', '#f5a623', '#19b797', '#6558e8'],
          borderRadius: 8,
          borderSkipped: false,
          maxBarThickness: 54
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { display: false },
          tooltip: { callbacks: { label: (context) => ' ' + formatMoney(context.raw) } }
        },
        scales: {
          x: { grid: { display: false }, ticks: { color: '#5e7597', font: { size: 11, weight: '600' }, maxRotation: 0 } },
          y: { beginAtZero: true, grid: { color: '#e7eff9' }, ticks: { color: '#5e7597', callback: (value) => shortMoney(value) } }
        }
      }
    });
  }

  const lobDonut = document.getElementById('lobDonutChart');
  if (lobDonut && Array.isArray(donutData.labels) && Array.isArray(donutData.values)) {
    new Chart(lobDonut, {
      type: 'doughnut',
      data: {
        labels: donutData.labels,
        datasets: [{
          data: donutData.values,
          backgroundColor: ['#2f80ed', '#ef5b70', '#f5a623'],
          borderColor: '#ffffff',
          borderWidth: 4,
          hoverOffset: 8
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        cutout: '66%',
        plugins: {
          legend: {
            position: 'bottom',
            labels: { color: '#365274', usePointStyle: true, pointStyle: 'circle', padding: 16, font: { size: 12, weight: '600' } }
          },
          tooltip: { callbacks: { label: (context) => context.label + ': ' + formatMoney(context.raw) } }
        }
      }
    });
  }
})();
</script>
<?= $this->endSection() ?>
