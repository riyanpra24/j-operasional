<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<section class="page-heading export-document-heading">
    <div>
        <p class="eyebrow">AKUTANSI</p>
        <h1>Export Dokumen</h1>
        <p>Persiapan ekspor dokumen Excel dari data sistem.</p>
    </div>
</section>

<?= view('components/under_construction', [
    'module' => 'Export Dokumen',
    'message' => 'Fitur ekspor RKA dan realisasi ke dokumen Excel sedang disiapkan.',
]) ?>

<?= $this->endSection() ?>
