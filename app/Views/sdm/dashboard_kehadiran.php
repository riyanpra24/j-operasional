<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<section class="page-heading">
    <div>
        <p class="eyebrow">SDM &amp; TELLER</p>
        <h1>Dashboard Kehadiran</h1>
        <p>Ringkasan dan pemantauan data kehadiran karyawan.</p>
    </div>
</section>

<?= view('components/under_construction', [
    'module' => 'Dashboard Kehadiran',
    'message' => 'Ringkasan dan pemantauan kehadiran dari Data Kehadiran sedang disiapkan.',
]) ?>

<?= $this->endSection() ?>
