<?php $manualInputStage = $rawInputs !== null; $manualSelectedUnit = $selectedUnit === 'Korporat Kanwil' ? 'Kanwil' : $selectedUnit; ?>
<dialog id="rkaManualDialog" class="lr-settings-dialog lr-rka-manual-dialog" aria-labelledby="rkaManualTitle" data-rka-stage="<?= $manualInputStage ? 'input' : 'selection' ?>" data-auto-open="<?= ($manualAutoOpen ?? false) ? 'true' : 'false' ?>">
<header class="lr-settings-header">
    <div><p class="eyebrow" data-rka-manual-context>AKUTANSI / <?= esc($selectedUnit) ?> · <?= $selectedYear ?></p><h2 id="rkaManualTitle"><?= ($manualMode ?? '') === 'edit' ? 'Edit RKA' : 'Seting Manual RKA' ?></h2></div>
    <button type="button" class="icon-btn" data-rka-manual-close aria-label="Tutup seting manual">×</button>
</header>
<form method="post" action="<?= site_url('akutansi/rka-kanwil-surabaya/seting-manual') ?>" data-rka-manual-form data-rka-step="<?= $manualInputStage ? 'input' : 'selection' ?>" data-rka-data-url="<?= site_url('akutansi/rka-kanwil-surabaya/manual-data') ?>" data-has-unsaved="<?= $rawInputs !== null ? 'true' : 'false' ?>">
<div class="lr-rka-manual-body">
<?php if ($manualError !== null): ?><div class="alert alert-danger" role="alert" data-rka-manual-error><?= esc($manualError) ?></div><?php endif ?>
<div data-rka-selection-stage <?= $manualInputStage ? 'hidden' : '' ?>>
<p class="lr-rka-help">Tahap 1 dari 2 — pilih unit kerja dan tahun RKA.</p>
<div class="lr-rka-manual-selection">
    <div><label class="lr-upload-label" for="rkaManualUnit">Unit Kerja <span aria-hidden="true">*</span></label>
        <select id="rkaManualUnit" class="lr-upload-select" data-rka-manual-unit required>
            <?php foreach ($rkaUnits as $unit): ?><option value="<?= esc($unit, 'attr') ?>" <?= $unit === $manualSelectedUnit ? 'selected' : '' ?> <?= $unit === 'Korporat Kanwil' ? 'disabled' : '' ?>><?= esc($unit) ?><?= $unit === 'Korporat Kanwil' ? ' (otomatis)' : '' ?></option><?php endforeach ?>
        </select>
    </div>
    <div><label class="lr-upload-label" for="rkaManualYear">Tahun RKA <span aria-hidden="true">*</span></label><input id="rkaManualYear" class="lr-upload-select" type="number" min="2000" max="2100" step="1" value="<?= $selectedYear ?>" data-rka-manual-year required></div>
    <button type="button" class="btn btn-primary" data-rka-manual-load>Next →</button>
    <p data-rka-selection-status role="status">Klik Next untuk memeriksa RKA dan melanjutkan ke input nominal.</p>
</div>
<div class="lr-rka-existing-alert" role="alert" data-rka-existing-alert hidden>
    <strong>RKA sudah diseting.</strong>
    <p data-rka-existing-message></p>
    <button type="button" class="btn btn-secondary" data-rka-edit-existing>Edit RKA</button>
    <button type="button" class="btn btn-secondary" data-rka-manual-reset>Seting Ulang</button>
    <button type="button" class="btn btn-secondary lr-rka-delete-button" data-rka-delete-existing aria-haspopup="dialog" aria-controls="rkaDeleteDialog">Hapus RKA</button>
</div>
</div>
<div data-rka-input-stage <?= $manualInputStage ? '' : 'hidden' ?>>
<p class="lr-rka-help">Tahap 2 dari 2 — isi nominal RKA, lalu simpan.</p>
<p class="lr-rka-help">Hanya nominal sumber yang dapat diisi. Kolom hasil rumus berlatar abu-abu, termasuk TOTAL, subtotal, dan Laba Sebelum Pajak, terisi otomatis saat nominal sumber berubah dan tidak dapat diedit.</p>
<p class="lr-rka-help">Isi nominal dalam <strong>Rupiah (Rp)</strong>, contoh <strong>1.234.567,89</strong>. Untuk negatif, gunakan <strong>-1.234.567,89</strong>; hasil ditampilkan sebagai *angka dan tetap dihitung minus. Klik kelompok beban untuk mengisi rinciannya.</p>

    <?= csrf_field() ?>
    <input type="hidden" name="unit_kerja" value="<?= esc($selectedUnit, 'attr') ?>">
    <input type="hidden" name="tahun" value="<?= $selectedYear ?>">
    <input type="hidden" name="revision" value="<?= $revision ?>">
    <input type="hidden" name="rka_mode" value="<?= ($manualMode ?? '') === 'edit' ? 'edit' : 'setting' ?>" data-rka-manual-mode>
    <input type="hidden" name="rka_edit_id" value="<?= esc($manualEditId ?? '', 'attr') ?>" data-rka-manual-edit-id>
    <?= view('akutansi/partials/rka_table', compact('schema', 'selectedUnit', 'selectedYear', 'inputs', 'calculated', 'rawInputs') + ['editable' => true, 'idPrefix' => 'rka-manual']) ?>
</div>
</div>
    <div class="panel lr-rka-save-panel">
        <p data-rka-manual-status role="status" <?= $manualInputStage ? '' : 'hidden' ?>>Jumlah dihitung otomatis. Periksa nominal sebelum menyimpan.</p>
        <label class="lr-rka-confirm" data-rka-save-confirm <?= $manualInputStage ? '' : 'hidden' ?>><input type="checkbox" name="confirm_replace" value="1" required> <span data-rka-manual-confirm-text>Saya mengonfirmasi RKA <?= esc($selectedUnit) ?> tahun <?= $selectedYear ?>. Menyimpan akan mengganti data RKA unit/tahun ini jika sudah ada.</span></label>
        <div><button class="btn btn-secondary" type="button" data-rka-manual-back <?= $manualInputStage ? 'hidden' : '' ?>>← Kembali</button><button class="btn btn-secondary" type="button" data-rka-step-back <?= $manualInputStage ? '' : 'hidden' ?>>← Pilih Unit &amp; Tahun</button><button class="btn btn-secondary" type="button" data-rka-manual-close>Batal</button><button class="btn btn-primary" type="submit" <?= $manualInputStage ? '' : 'hidden disabled' ?>><?= ($manualMode ?? '') === 'edit' ? 'Simpan Perubahan' : 'Simpan RKA' ?></button></div>
    </div>
</form>
</dialog>
<script type="application/json" id="rkaManualSchema"><?= json_encode($schema, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR) ?></script>
