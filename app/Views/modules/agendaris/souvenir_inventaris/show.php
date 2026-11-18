<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<section class="page-heading heading-actions"><div><p class="eyebrow">AGENDARIS / SOUVENIR DAN INVENTARIS</p><h1>Detail Item</h1><p>Rincian persediaan souvenir dan inventaris.</p></div></section>

<section class="panel single-detail">
    <dl class="detail-grid">
        <div><dt>Nama Item</dt><dd><?= esc($record['nama_item']) ?></dd></div>
        <div><dt>Jenis</dt><dd><?= esc($record['jenis'] ?? 'Souvenir') ?></dd></div>
        <div><dt>Satuan</dt><dd><?= esc(ucfirst((string) ($record['satuan'] ?? 'pcs'))) ?></dd></div>
        <div><dt>Persediaan Awal</dt><dd><?= number_format((int) $record['persediaan_awal'], 0, ',', '.') ?></dd></div>
        <div><dt>Persediaan Akhir</dt><dd><?= number_format((int) $record['persediaan_akhir'], 0, ',', '.') ?></dd></div>
        <div><dt>Terpakai</dt><dd><?= number_format((int) $record['terpakai'], 0, ',', '.') ?></dd></div>
        <div class="detail-span-full"><dt>Keterangan</dt><dd><?= $record['keterangan'] === null || $record['keterangan'] === '' ? '—' : nl2br(esc($record['keterangan'])) ?></dd></div>
    </dl>
    <div class="form-actions"><a class="btn btn-ghost" href="<?= site_url('agendaris/souvenir-inventaris') ?>">Kembali</a><a class="btn btn-primary" href="<?= site_url('agendaris/souvenir-inventaris/' . $record['id'] . '/ubah') ?>">Ubah Item</a></div>
</section>

<?= $this->endSection() ?>
