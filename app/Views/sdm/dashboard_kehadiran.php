<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<section class="page-heading">
    <div>
        <p class="eyebrow">SDM &amp; TELLER</p>
        <h1>Dashboard Kehadiran</h1>
        <p>Ringkasan dan pemantauan data kehadiran karyawan.</p>
    </div>
</section>

<section class="panel register-panel">
    <div class="empty-state">
        <span aria-hidden="true">▦</span>
        <strong>Dashboard Kehadiran siap dikembangkan</strong>
        <p>Data dashboard nantinya dapat dirangkum dari rekap absensi yang tersimpan di SDM Jatim.</p>
        <a href="<?= site_url('sdm/sdm-jatim') ?>" class="btn btn-secondary btn-sm">Buka SDM Jatim</a>
    </div>
</section>

<?= $this->endSection() ?>
