<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<?php
$isEdit = $record !== [];
$errors = session()->getFlashdata('errors');
$errors = is_array($errors) ? $errors : [];
$value = static function (string $field, string $default = '') use ($record): string {
    $old = function_exists('old') ? old($field) : null;
    if ($old !== null) return (string) $old;
    return (string) ($record[$field] ?? $default);
};
$awal = $value('persediaan_awal', '0');
$jenis = $value('jenis', 'Souvenir');
?>

<section class="page-heading heading-actions"><div><p class="eyebrow">AGENDARIS / SOUVENIR DAN INVENTARIS</p><h1><?= $isEdit ? 'Ubah Item' : 'Tambah Item' ?></h1><p>Tentukan satuan dan persediaan awal item.</p></div></section>

<section class="panel form-section">
    <form method="post" action="<?= site_url($isEdit ? 'agendaris/souvenir-inventaris/' . $record['id'] : 'agendaris/souvenir-inventaris') ?>" class="form-grid">
        <?= csrf_field() ?>
        <div class="form-group"><label for="inventoryJenis">Jenis <span class="required">*</span></label><select id="inventoryJenis" name="jenis" required><option value="Souvenir" <?= $jenis === 'Souvenir' ? 'selected' : '' ?>>Souvenir</option><option value="Inventaris" <?= $jenis === 'Inventaris' ? 'selected' : '' ?>>Inventaris</option></select><?php if (isset($errors['jenis'])): ?><small class="field-error"><?= esc($errors['jenis']) ?></small><?php endif ?></div>
        <div class="form-group"><label for="inventoryName">Nama Item <span class="required">*</span></label><input id="inventoryName" name="nama_item" maxlength="150" required value="<?= esc($value('nama_item'), 'attr') ?>" placeholder="Contoh: Mug logo perusahaan"><?php if (isset($errors['nama_item'])): ?><small class="field-error"><?= esc($errors['nama_item']) ?></small><?php endif ?></div>
        <div class="form-group"><label for="inventoryUnit">Satuan <span class="required">*</span></label><select id="inventoryUnit" name="satuan" required><?php foreach ($units as $unitValue => $unitLabel): ?><option value="<?= esc($unitValue, 'attr') ?>" <?= $value('satuan', 'pcs') === $unitValue ? 'selected' : '' ?>><?= esc($unitLabel) ?></option><?php endforeach ?></select><?php if (isset($errors['satuan'])): ?><small class="field-error"><?= esc($errors['satuan']) ?></small><?php endif ?></div>
        <div class="form-group"><label for="inventoryOpening">Persediaan Awal <span class="required">*</span></label><input id="inventoryOpening" type="number" name="persediaan_awal" min="0" max="2147483647" required value="<?= esc($awal, 'attr') ?>"><?php if (isset($errors['persediaan_awal'])): ?><small class="field-error"><?= esc($errors['persediaan_awal']) ?></small><?php endif ?></div>
        <div class="form-group modal-span-2"><label for="inventoryNotes">Keterangan</label><textarea id="inventoryNotes" name="keterangan" rows="4" maxlength="2000" placeholder="Catatan penggunaan atau kondisi persediaan"><?= esc($value('keterangan')) ?></textarea><?php if (isset($errors['keterangan'])): ?><small class="field-error"><?= esc($errors['keterangan']) ?></small><?php endif ?></div>
        <div class="form-actions modal-span-2"><a class="btn btn-ghost" href="<?= site_url('agendaris/souvenir-inventaris') ?>">Batal</a><button type="submit" class="btn btn-primary"><?= $isEdit ? 'Simpan Perubahan' : 'Simpan Item' ?></button></div>
    </form>
</section>

<?= $this->endSection() ?>
