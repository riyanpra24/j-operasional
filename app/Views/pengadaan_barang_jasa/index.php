<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<?= view('components/coming_soon', [
    'eyebrow' => 'BAGIAN UMUM 1',
    'heading' => 'Pengadaan Barang dan Jasa',
    'description' => 'Pengelolaan proses dan dokumen pengadaan sedang kami siapkan. Halaman ini akan segera tersedia untuk digunakan.',
    'status' => 'Dalam pengembangan',
]) ?>

<?= $this->endSection() ?>
