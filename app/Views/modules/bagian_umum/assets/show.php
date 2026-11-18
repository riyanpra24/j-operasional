<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<?php
$base = 'bagian-umum-1/data-aset';
$fields = ['kategori' => 'Kategori aset', 'kelompok_aset' => 'Kelompok aset', 'kodefikasi_aset' => 'Kodefikasi aset', 'nomor_aset' => 'Nomor aset', 'nama_aset' => 'Nama aset', 'status_aset' => 'Status aset', 'tanggal_perolehan' => 'Tanggal perolehan', 'tanggal_habis_manfaat' => 'Tanggal habis masa manfaat', 'masa_manfaat' => 'Masa manfaat (bulan)', 'sisa_masa_manfaat' => 'Sisa masa manfaat (bulan)', 'lokasi' => 'Lokasi', 'foto_aset' => 'Foto Aset'];
?>
<section class="page-heading"><div><p class="eyebrow">BAGIAN UMUM 1 / DATA ASET</p><h1>Detail Aset</h1></div></section>
<section class="panel" style="padding:24px">
    <dl class="detail-list grid-two"><?php foreach ($fields as $field => $label): $item = $record[$field] ?? null; ?><div><dt><?= esc($label) ?></dt><dd<?php if ($field === 'sisa_masa_manfaat' && ! empty($record['tanggal_habis_manfaat'])): ?> data-asset-remaining="<?= esc($record['tanggal_habis_manfaat'], 'attr') ?>" data-asset-remaining-format="number"<?php endif ?>><?php if ($field === 'foto_aset' && $item && filter_var($item, FILTER_VALIDATE_URL) && in_array(strtolower((string) parse_url($item, PHP_URL_SCHEME)), ['http', 'https'], true)): ?><a href="<?= esc($item, 'attr') ?>" target="_blank" rel="noopener noreferrer">Lihat foto</a><?php else: ?><?= esc($field === 'foto_aset' || $item === null || $item === '' ? '—' : $item) ?><?php endif ?></dd></div><?php endforeach ?></dl>
    <div class="form-actions-sticky"><a href="<?= site_url($base) ?>" class="btn btn-ghost">Kembali</a><a href="<?= site_url($base . '/' . $record['id'] . '/ubah') ?>" class="btn btn-secondary">Ubah</a><form action="<?= site_url($base . '/' . $record['id'] . '/hapus') ?>" method="post" onsubmit="return confirm('Hapus aset ini? <?= (string) session()->get('auth_role') === 'admin' ? 'Data akan dihapus permanen.' : 'Data dapat dipulihkan oleh administrator.' ?>');"><?= csrf_field() ?><button type="submit" class="btn btn-danger">Hapus</button></form></div>
</section>
<script src="<?= base_url('assets/data-aset-period.js') ?>" data-system-time="<?= (int) round(microtime(true) * 1000) ?>"></script>
<?= $this->endSection() ?>
