<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<section class="page-heading">
    <div>
        <p class="eyebrow">BAGIAN UMUM 1</p>
        <h1>Non Belanja Modal</h1>
        <p>Kelola data dan dokumen non-belanja modal dalam satu tempat.</p>
    </div>
</section>

<section class="panel register-panel">
    <div class="empty-state">
        <span aria-hidden="true">◆</span>
        <strong>Belum ada data non-belanja modal</strong>
        <p>Data non-belanja modal akan ditampilkan pada halaman ini.</p>
    </div>
</section>

<?= $this->endSection() ?>
