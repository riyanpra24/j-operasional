<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<section class="page-heading">
    <div>
        <p class="eyebrow">BAGIAN UMUM 1</p>
        <h1>Pengadaan Barang Jasa</h1>
        <p>Kelola proses dan dokumen pengadaan barang maupun jasa dalam satu tempat.</p>
    </div>
</section>

<section class="panel register-panel">
    <div class="empty-state">
        <span aria-hidden="true">◆</span>
        <strong>Belum ada data pengadaan</strong>
        <p>Data pengadaan barang dan jasa akan ditampilkan pada halaman ini.</p>
    </div>
</section>

<?= $this->endSection() ?>
