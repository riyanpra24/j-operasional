<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<?php
$months = [
    1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni',
    7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
];
?>

<section class="page-heading export-document-heading">
    <div>
        <p class="eyebrow">AKUTANSI</p>
        <h1>Export Dokumen</h1>
        <p>Unduh realisasi anggaran dengan format, sheet, dan rumus asli template Excel.</p>
    </div>
</section>

<section class="panel export-document-panel">
    <header class="export-document-intro">
        <span class="export-document-icon" aria-hidden="true">⇩</span>
        <div>
            <p class="eyebrow">REALISASI ANGGARAN</p>
            <h2>RKA dan Realisasi</h2>
            <p>Angka RKA dan realisasi diambil dari sistem. Rumus subtotal, total, persentase, dan konsolidasi pada template tetap dipertahankan.</p>
        </div>
    </header>

    <form method="post" action="<?= site_url('akutansi/export-dokumen') ?>" class="export-document-form" data-export-document-form>
        <?= csrf_field() ?>
        <div class="export-document-filters">
            <label for="exportBasis"><span>Jenis Laporan <b>*</b></span>
                <select id="exportBasis" name="jenis_laporan" class="lr-upload-select" required>
                    <?php foreach ($exportBases as $basis): ?><option value="<?= esc($basis) ?>"><?= esc($basis) ?></option><?php endforeach ?>
                </select>
            </label>
            <label for="exportMonth"><span>Bulan <b>*</b></span>
                <select id="exportMonth" name="bulan" class="lr-upload-select" required>
                    <?php foreach ($months as $number => $label): ?><option value="<?= $number ?>" <?= $number === $selectedMonth ? 'selected' : '' ?>><?= esc($label) ?></option><?php endforeach ?>
                </select>
            </label>
            <label for="exportYear"><span>Tahun <b>*</b></span>
                <input id="exportYear" name="tahun" type="number" min="2000" max="2100" value="<?= esc((string) $selectedYear) ?>" required>
            </label>
        </div>

        <aside class="export-document-note"><strong>Cakupan export</strong><span>Dokumen selalu memuat Korporat Kanwil, Kanwil, Surabaya, Kediri, Malang, Madiun, dan Banyuwangi agar rumus konsolidasi tetap valid.</span></aside>
        <aside class="export-document-note"><strong>Audit otomatis</strong><span>Sebelum berkas diunduh, sistem memeriksa angka RKA, realisasi, total, persentase, dan rumus template.</span></aside>
        <footer class="export-document-actions"><a class="btn btn-secondary" href="<?= site_url('akutansi/laba-rugi') ?>">Kembali</a><button type="submit" class="btn btn-primary">Export Excel</button></footer>
    </form>
</section>

<?= $this->endSection() ?>
