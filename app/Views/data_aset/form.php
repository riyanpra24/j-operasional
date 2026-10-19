<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<style>
    [data-asset-period-form] input[readonly] { background: #e9edf1 !important; border-color: #cbd3dc !important; color: #35465c !important; cursor: default; }
</style>
<?php
$isEdit = isset($record['id']);
$base = 'bagian-umum-1/data-aset';
$value = static fn (string $key) => old($key, $record[$key] ?? '');
$errors = session()->getFlashdata('errors') ?? [];
?>
<section class="page-heading"><div><p class="eyebrow">BAGIAN UMUM 1 / DATA ASET</p><h1><?= $isEdit ? 'Ubah Aset' : 'Tambah Aset' ?></h1><p>Masukkan informasi aset sesuai dokumen yang tersedia.</p></div></section>
<section class="panel" style="padding:24px">
    <?php if ($errors !== []): ?><div class="alert alert-danger" role="alert"><strong>Periksa kembali data berikut:</strong><ul><?php foreach ($errors as $error): ?><li><?= esc($error) ?></li><?php endforeach ?></ul></div><?php endif ?>
    <form action="<?= site_url($isEdit ? $base . '/' . $record['id'] : $base) ?>" method="post" data-asset-period-form>
        <?= csrf_field() ?>
        <div class="form-grid">
            <div class="form-group"><label for="kategori">Kategori aset *</label><select id="kategori" name="kategori" required><option value="">Pilih kategori</option><?php foreach ($assetGroups as $group): ?><option value="<?= esc($group, 'attr') ?>" <?= $value('kategori') === $group ? 'selected' : '' ?>><?= esc($group) ?></option><?php endforeach ?></select></div>
            <div class="form-group"><label for="kelompok_aset">Kelompok aset *</label><input id="kelompok_aset" name="kelompok_aset" maxlength="255" required value="<?= esc($value('kelompok_aset'), 'attr') ?>"></div>
            <div class="form-group"><label for="kodefikasi_aset">Kodefikasi aset *</label><input id="kodefikasi_aset" name="kodefikasi_aset" maxlength="100" required value="<?= esc($value('kodefikasi_aset'), 'attr') ?>"></div>
            <div class="form-group"><label for="nomor_aset">Nomor aset *</label><input id="nomor_aset" name="nomor_aset" maxlength="100" required value="<?= esc($value('nomor_aset'), 'attr') ?>"></div>
            <div class="form-group"><label for="nama_aset">Nama aset *</label><input id="nama_aset" name="nama_aset" maxlength="150" required value="<?= esc($value('nama_aset'), 'attr') ?>"></div>
            <div class="form-group"><label for="status_aset">Status aset *</label><input id="status_aset" name="status_aset" maxlength="100" required value="<?= esc($value('status_aset'), 'attr') ?>" list="asset-statuses"><datalist id="asset-statuses"><option value="Sedang Digunakan"><option value="Berfungsi"><option value="Sedang Dibangun/Dikembangkan"></datalist></div>
            <div class="form-group"><label for="tanggal_perolehan">Tanggal perolehan</label><input id="tanggal_perolehan" type="date" name="tanggal_perolehan" value="<?= esc($value('tanggal_perolehan'), 'attr') ?>"></div>
            <div class="form-group"><label for="tanggal_habis_manfaat">Tanggal habis masa manfaat</label><input id="tanggal_habis_manfaat" type="date" name="tanggal_habis_manfaat" value="<?= esc($value('tanggal_habis_manfaat'), 'attr') ?>"></div>
            <div class="form-group"><label for="masa_manfaat">Masa manfaat (bulan)</label><input id="masa_manfaat" type="number" name="masa_manfaat" value="<?= esc($value('masa_manfaat'), 'attr') ?>" title="Dihitung otomatis" readonly></div>
            <div class="form-group"><label for="sisa_masa_manfaat">Sisa masa manfaat (bulan)</label><input id="sisa_masa_manfaat" type="number" name="sisa_masa_manfaat" value="<?= esc($value('sisa_masa_manfaat'), 'attr') ?>" title="Dihitung otomatis" readonly></div>
            <div class="form-group"><label for="lokasi">Lokasi</label><input id="lokasi" name="lokasi" maxlength="255" value="<?= esc($value('lokasi'), 'attr') ?>"></div>
            <div class="form-group"><label for="foto_aset">Foto Aset (tautan)</label><input id="foto_aset" name="foto_aset" type="url" maxlength="2048" placeholder="https://..." value="<?= esc($value('foto_aset'), 'attr') ?>"></div>
        </div>
        <div class="form-actions-sticky"><a href="<?= site_url($base) ?>" class="btn btn-ghost">Batal</a><button class="btn btn-primary" type="submit">Simpan</button></div>
    </form>
</section>
<script src="<?= base_url('assets/data-aset-period.js') ?>" data-system-time="<?= (int) round(microtime(true) * 1000) ?>"></script>
<?= $this->endSection() ?>
