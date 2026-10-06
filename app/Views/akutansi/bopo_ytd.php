<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<?php
$months = [1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'];
$bopoPercent = static fn (?string $value): string => $value === null ? '—' : \App\Libraries\LrMoney::percentageDisplayFixed($value, 2);
$bopoAchievement = static fn (?string $value): string => $value === null ? '—' : \App\Libraries\LrMoney::percentageDisplay($value);
?>

<section class="page-heading lr-page-heading bopo-page-heading">
    <div>
        <p class="eyebrow">AKUTANSI</p>
        <h1>BOPO YTD</h1>
    </div>
</section>

<nav class="lr-report-tabs" aria-label="Jenis laporan akuntansi">
    <a class="lr-report-tab" href="<?= site_url('akutansi/laba-rugi?' . http_build_query(['jenis_laporan' => 'YTD', 'bulan' => $selectedMonth, 'tahun' => $selectedYear])) ?>">
        <span class="lr-report-tab-badge">YTD</span>
        <span><strong>Laporan Laba / Rugi (YTD)</strong><small>Akumulasi dari awal tahun sampai periode terpilih</small></span>
    </a>
    <a class="lr-report-tab" href="<?= site_url('akutansi/laba-rugi?' . http_build_query(['jenis_laporan' => 'PTD', 'bulan' => $selectedMonth, 'tahun' => $selectedYear])) ?>">
        <span class="lr-report-tab-badge">PTD</span>
        <span><strong>Laporan Laba / Rugi (PTD)</strong><small>Nilai khusus pada periode terpilih</small></span>
    </a>
    <a class="lr-report-tab is-active" href="<?= site_url('akutansi/bopo-ytd?' . http_build_query(['bulan' => $selectedMonth, 'tahun' => $selectedYear])) ?>" aria-current="page">
        <span class="lr-report-tab-badge">BOPO</span>
        <span><strong>Laporan BOPO</strong><small>Akumulasi rasio operasional sampai periode terpilih</small></span>
    </a>
</nav>

<section class="panel bopo-filter-panel">
    <form method="get" action="<?= site_url('akutansi/bopo-ytd') ?>" class="bopo-filter-form">
        <label for="bopoMonth">Bulan
            <select id="bopoMonth" class="lr-upload-select" name="bulan">
                <?php foreach ($months as $number => $name): ?>
                    <option value="<?= $number ?>" <?= $number === $selectedMonth ? 'selected' : '' ?>><?= esc($name) ?></option>
                <?php endforeach ?>
            </select>
        </label>
        <label for="bopoYear">Tahun
            <input id="bopoYear" class="lr-upload-select" type="number" name="tahun" min="2000" max="2100" step="1" value="<?= $selectedYear ?>">
        </label>
        <button class="btn btn-secondary" type="submit">Terapkan</button>
        <a class="btn btn-ghost" href="<?= site_url('akutansi/bopo-ytd') ?>">Reset</a>
    </form>
</section>

<section class="panel bopo-report-panel" aria-labelledby="bopoReportTitle">
    <header class="bopo-report-header">
        <div>
            <p>PT JAMKRINDO KANWIL SURABAYA</p>
            <h2 id="bopoReportTitle">BOPO YTD s/d <?= esc($months[$selectedMonth]) ?> <?= $selectedYear ?></h2>
        </div>
        <span><?= esc($months[$selectedMonth]) ?> <?= $selectedYear ?></span>
    </header>
    <div class="bopo-table-wrap" tabindex="0" role="region" aria-label="Tabel BOPO YTD per unit kerja">
        <table class="bopo-table">
            <caption>BOPO YTD per unit kerja</caption>
            <colgroup>
                <col class="bopo-unit-column">
                <col class="bopo-target-column">
                <col class="bopo-month-column">
                <col class="bopo-achievement-column">
            </colgroup>
            <thead>
                <tr>
                    <th scope="col">UNIT KERJA</th>
                    <th scope="col">TARGET</th>
                    <th scope="col"><?= esc(strtoupper($months[$selectedMonth])) ?></th>
                    <th scope="col">PENCAPAIAN</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($bopoUnits as $unit): ?>
                    <?php $bopo = $bopoValues[$unit] ?? ['realisasi' => null, 'target' => null, 'pencapaian' => null]; ?>
                    <tr>
                        <th scope="row"><?= esc($unit) ?></th>
                        <td><?= esc($bopoPercent($bopo['target'] ?? null)) ?></td>
                        <td class="bopo-realization-cell"><span class="bopo-realization-value"><?= esc($bopoPercent($bopo['realisasi'] ?? null)) ?></span></td>
                        <td><?= esc($bopoAchievement($bopo['pencapaian'] ?? null)) ?></td>
                    </tr>
                <?php endforeach ?>
            </tbody>
        </table>
    </div>
</section>

<section class="panel bopo-report-panel" id="bopoPtdReport" aria-labelledby="bopoPtdReportTitle">
    <header class="bopo-report-header">
        <div>
            <p>PT JAMKRINDO KANWIL SURABAYA</p>
            <h2 id="bopoPtdReportTitle">BOPO PTD <?= esc($months[$selectedMonth]) ?> <?= $selectedYear ?></h2>
        </div>
        <span><?= esc($months[$selectedMonth]) ?> <?= $selectedYear ?></span>
    </header>
    <div class="bopo-table-wrap" tabindex="0" role="region" aria-label="Tabel BOPO PTD per unit kerja">
        <table class="bopo-table">
            <caption>BOPO PTD per unit kerja</caption>
            <colgroup>
                <col class="bopo-unit-column">
                <col class="bopo-target-column">
                <col class="bopo-month-column">
                <col class="bopo-achievement-column">
            </colgroup>
            <thead>
                <tr>
                    <th scope="col">UNIT KERJA</th>
                    <th scope="col">TARGET</th>
                    <th scope="col"><?= esc(strtoupper($months[$selectedMonth])) ?></th>
                    <th scope="col">PENCAPAIAN</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($bopoUnits as $unit): ?>
                    <?php $bopo = $bopoPtdValues[$unit] ?? ['realisasi' => null, 'target' => null, 'pencapaian' => null]; ?>
                    <tr>
                        <th scope="row"><?= esc($unit) ?></th>
                        <td><?= esc($bopoPercent($bopo['target'] ?? null)) ?></td>
                        <td class="bopo-realization-cell"><span class="bopo-realization-value"><?= esc($bopoPercent($bopo['realisasi'] ?? null)) ?></span></td>
                        <td><?= esc($bopoAchievement($bopo['pencapaian'] ?? null)) ?></td>
                    </tr>
                <?php endforeach ?>
            </tbody>
        </table>
    </div>
</section>

<?= $this->endSection() ?>
