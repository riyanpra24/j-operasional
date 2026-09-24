<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<section class="page-heading">
    <div>
        <p class="eyebrow">AKUTANSI</p>
        <h1>Akutansi</h1>
        <p>Pilih menu laporan, RKA, simulasi hitung, export dokumen, atau penyesuaian sumber Oracle.</p>
    </div>
</section>

<section class="accounting-menu-grid" aria-label="Submenu Akutansi">
    <a class="panel accounting-menu-card is-report" href="<?= site_url('akutansi/laba-rugi') ?>">
        <span class="accounting-menu-icon" aria-hidden="true">▥</span>
        <span><small>LAPORAN</small><strong>Laporan Laba / Rugi</strong><p>Lihat hasil realisasi dan perhitungan per unit kerja serta periode.</p></span>
        <b aria-hidden="true">→</b>
    </a>
    <a class="panel accounting-menu-card is-rka" href="<?= site_url('akutansi/rka-kanwil-surabaya') ?>">
        <span class="accounting-menu-icon" aria-hidden="true">Rp</span>
        <span><small>ANGGARAN</small><strong>RKA Kanwil</strong><p>Kelola RKA seluruh unit kerja melalui Excel atau pengisian manual.</p></span>
        <b aria-hidden="true">→</b>
    </a>
    <a class="panel accounting-menu-card is-export" href="<?= site_url('akutansi/simulasi-hitung') ?>">
        <span class="accounting-menu-icon" aria-hidden="true">∑</span>
        <span><small>SIMULASI</small><strong>Simulasi Hitung</strong><p>Isi kertas kerja otomatis dari LR Oracle dan unduh hasilnya.</p></span>
        <b aria-hidden="true">→</b>
    </a>
    <a class="panel accounting-menu-card is-export" href="<?= site_url('akutansi/export-dokumen') ?>">
        <span class="accounting-menu-icon" aria-hidden="true">⇩</span>
        <span><small>DOKUMEN</small><strong>Export Dokumen</strong><p>Export RKA dan Realisasi dengan format template Deviasi Anggaran.</p></span>
        <b aria-hidden="true">→</b>
    </a>
    <a class="panel accounting-menu-card is-source" href="<?= site_url('akutansi/seting-rumus') ?>">
        <span class="accounting-menu-icon" aria-hidden="true">±</span>
        <span><small>SUMBER ORACLE</small><strong>Penyesuaian Sumber Oracle</strong><p>Ajukan perubahan sumber COA per unit, LOB, dan periode.</p></span>
        <b aria-hidden="true">→</b>
    </a>
</section>

<?= $this->endSection() ?>
