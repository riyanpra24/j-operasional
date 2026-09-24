<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<section class="page-heading">
    <div>
        <p class="eyebrow">BAGIAN UMUM 1</p>
        <h1>Pengadaan Barang Jasa</h1>
        <p>Kelola proses dan dokumen pengadaan barang maupun jasa dalam satu tempat.</p>
    </div>
</section>

<?= view('components/under_construction', [
    'module' => 'Pengadaan Barang Jasa',
    'message' => 'Pengelolaan proses dan dokumen pengadaan sedang disiapkan pada halaman ini.',
]) ?>

<?= $this->endSection() ?>
