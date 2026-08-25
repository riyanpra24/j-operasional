<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<?php
$v = static fn (string $field, string $default = ''): string => (string) old($field, $dokumen[$field] ?? $default);
$currentPerihal = $v('perihal');
$perihalChoice = (string) old('perihal_pilihan', $currentPerihal === 'Confidential Documents' ? 'Confidential Documents' : ($currentPerihal !== '' ? 'Lainnya' : ''));
$perihalOther = (string) old('perihal_lainnya', $perihalChoice === 'Lainnya' ? $currentPerihal : '');
$jenisOptions = ['Surat', 'Dokumen', 'Paket'];
$currentJenis = (string) ($dokumen['jenis'] ?? '');
$jenisChoice = (string) old('jenis', in_array($currentJenis, $jenisOptions, true) ? $currentJenis : ($currentJenis !== '' ? 'Lainnya' : ''));
$jenisOther = (string) old('jenis_lainnya', $jenisChoice === 'Lainnya' && $currentJenis !== 'Lainnya' ? $currentJenis : '');
?>
<section class="page-heading form-heading"><a href="<?= $dokumen ? site_url('dokumen-masuk/' . $dokumen['id']) : site_url('dokumen-masuk') ?>" class="back-link">← Kembali</a><p class="eyebrow">FORM DOKUMEN MASUK</p><h1><?= esc($title) ?></h1><p>Hari dihitung otomatis berdasarkan tanggal yang dipilih.</p></section>
<form action="<?= esc($action) ?>" method="post" class="data-form"><?= csrf_field() ?>
    <section class="panel form-section"><div class="section-heading"><span class="section-number">01</span><div><h2>Informasi dokumen</h2><p>Lengkapi data sesuai buku register dokumen masuk.</p></div></div><div class="form-grid">
        <div class="form-group span-2"><label for="pengirim">Pengirim <span class="required">*</span></label><input id="pengirim" name="pengirim" maxlength="255" value="<?= esc($v('pengirim')) ?>" required></div>
        <div class="form-group span-2"><label for="perihal_pilihan">Perihal <span class="required">*</span></label><select id="perihal_pilihan" name="perihal_pilihan" data-perihal-select required><option value="">Pilih perihal</option><option value="Confidential Documents" <?= $perihalChoice === 'Confidential Documents' ? 'selected' : '' ?>>Confidential Documents</option><option value="Lainnya" <?= $perihalChoice === 'Lainnya' ? 'selected' : '' ?>>Lainnya</option></select></div>
        <div class="form-group span-2" data-perihal-custom <?= $perihalChoice === 'Lainnya' ? '' : 'hidden' ?>><label for="perihal_lainnya">Perihal lainnya <span class="required">*</span></label><input id="perihal_lainnya" name="perihal_lainnya" data-perihal-custom-input maxlength="255" value="<?= esc($perihalOther) ?>" placeholder="Ketik perihal dokumen" <?= $perihalChoice === 'Lainnya' ? 'required' : 'disabled' ?>></div>
        <div class="form-group span-2"><label for="penerima">Penerima <span class="required">*</span></label><input id="penerima" name="penerima" maxlength="255" value="<?= esc($v('penerima')) ?>" required></div>
        <div class="form-group"><label for="tanggal">Tanggal Diterima <span class="required">*</span></label><input id="tanggal" type="date" name="tanggal" value="<?= esc($v('tanggal', date('Y-m-d'))) ?>" data-date-input required></div>
        <div class="form-group"><label for="hari">Hari</label><input id="hari" value="<?= esc($v('hari')) ?>" data-day-output readonly tabindex="-1"><small>Dihitung otomatis</small></div>
        <div class="form-group"><label for="jenis">Jenis <span class="required">*</span></label><select id="jenis" name="jenis" data-jenis-select required><option value="">Pilih jenis</option><?php foreach ([...$jenisOptions, 'Lainnya'] as $jenis): ?><option value="<?= esc($jenis, 'attr') ?>" <?= $jenisChoice === $jenis ? 'selected' : '' ?>><?= esc($jenis) ?></option><?php endforeach ?></select></div>
        <div class="form-group span-2" data-jenis-custom <?= $jenisChoice === 'Lainnya' ? '' : 'hidden' ?>><label for="jenis_lainnya">Jenis lainnya <span class="required">*</span></label><input id="jenis_lainnya" name="jenis_lainnya" data-jenis-custom-input maxlength="100" value="<?= esc($jenisOther, 'attr') ?>" placeholder="Ketik jenis dokumen" <?= $jenisChoice === 'Lainnya' ? 'required' : 'disabled' ?>></div>
        <div class="form-group"><label for="jumlah">Jumlah <span class="required">*</span></label><input id="jumlah" type="number" min="1" name="jumlah" value="<?= esc($v('jumlah', '1')) ?>" required></div>
        <?= view('components/ekspedisi_selector', ['prefix' => 'form', 'current' => $v('ekspedisi'), 'groupClass' => 'span-2']) ?>
    </div></section>
    <div class="form-actions-sticky"><a href="<?= $dokumen ? site_url('dokumen-masuk/' . $dokumen['id']) : site_url('dokumen-masuk') ?>" class="btn btn-ghost">Batal</a><button type="submit" class="btn btn-primary"><?= esc($submitLabel) ?></button></div>
</form>
<?= $this->endSection() ?>
