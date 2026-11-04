<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<?= view('components/coming_soon', [
    'eyebrow' => 'GLOBAL TEMPLATE',
    'heading' => 'Halaman Sedang Dalam Progres',
    'description' => 'Template ini dapat dipakai pada setiap menu yang masih dalam tahap pengembangan. Konten dan tombol kembali dapat disesuaikan untuk masing-masing halaman.',
    'status' => 'Coming soon',
    'backUrl' => site_url('dashboard'),
    'backLabel' => 'Kembali ke Dashboard',
]) ?>

<?= $this->endSection() ?>
