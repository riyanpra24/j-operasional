<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<section class="page-heading">
    <div>
        <p class="eyebrow">BAGIAN UMUM 1</p>
        <h1>Non Belanja Modal</h1>
        <p>Kelola data dan dokumen non-belanja modal dalam satu tempat.</p>
    </div>
</section>

<?= view('components/under_construction', [
    'module' => 'Non Belanja Modal',
    'message' => 'Pengelolaan data dan dokumen non-belanja modal sedang disiapkan pada halaman ini.',
]) ?>

<?= $this->endSection() ?>
