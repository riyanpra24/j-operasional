<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<section class="page-heading lr-page-heading lr-rka-heading">
    <div>
        <p class="eyebrow">AKUTANSI</p>
        <h1>RKA Kanwil Surabaya</h1>
        <p>Rencana Kerja dan Anggaran per unit kerja dan tahun, sesuai Template RKA.</p>
    </div>
    <div class="lr-rka-heading-actions">
        <button type="button" class="btn btn-primary" data-rka-settings-open aria-haspopup="dialog" aria-controls="rkaSettingsDialog">
            <span aria-hidden="true">⚙</span> Seting RKA Kanwil Surabaya
        </button>
    </div>
</section>

<?= view('akutansi/partials/rka_filter', compact('rkaUnits', 'selectedUnit', 'selectedYear', 'record', 'revision') + ['filterAction' => site_url('akutansi/rka-kanwil-surabaya')]) ?>
<?= view('akutansi/partials/rka_table', compact('schema', 'selectedUnit', 'selectedYear', 'inputs', 'calculated') + ['editable' => false, 'idPrefix' => 'lr']) ?>

<dialog id="rkaSettingsDialog" class="lr-settings-dialog" aria-labelledby="rkaSettingsTitle" aria-describedby="rkaSettingsDescription">
    <section>
        <header class="lr-settings-header">
            <div>
                <p class="eyebrow">AKUTANSI</p>
                <h2 id="rkaSettingsTitle">Seting RKA Kanwil Surabaya</h2>
            </div>
            <button type="button" class="icon-btn" data-rka-settings-close aria-label="Tutup pengaturan">×</button>
        </header>
        <div class="lr-settings-body">
            <p id="rkaSettingsDescription">Pilih cara pengaturan RKA. Upload Excel dan Seting Manual akan membuka popup di halaman ini.</p>
            <button type="button" class="lr-settings-option" data-rka-upload-open aria-haspopup="dialog" aria-controls="rkaUploadDialog">
                <span class="lr-settings-icon" aria-hidden="true">↑</span>
                <span><strong>Upload Excel</strong><small>Pengaturan RKA melalui file Excel.</small></span>
                <span class="lr-settings-arrow" aria-hidden="true">→</span>
            </button>
            <button type="button" class="lr-settings-option" data-rka-manual-open aria-haspopup="dialog" aria-controls="rkaManualDialog">
                <span class="lr-settings-icon" aria-hidden="true">Rp</span>
                <span><strong>Seting Manual</strong><small>Pengaturan nilai RKA secara manual.</small></span>
                <span class="lr-settings-arrow" aria-hidden="true">→</span>
            </button>
        </div>
        <footer class="lr-settings-footer"><button type="button" class="btn btn-secondary" data-rka-settings-close>Batal</button></footer>
    </section>
</dialog>

<dialog id="rkaUploadDialog" class="lr-settings-dialog" aria-labelledby="rkaUploadTitle" aria-describedby="rkaUploadDescription" data-auto-open="<?= $uploadAutoOpen ? 'true' : 'false' ?>">
    <section>
        <header class="lr-settings-header">
            <div>
                <p class="eyebrow">AKUTANSI / RKA KANWIL SURABAYA</p>
                <h2 id="rkaUploadTitle">Upload Excel RKA Kanwil Surabaya</h2>
            </div>
            <button type="button" class="icon-btn" data-rka-upload-close aria-label="Tutup upload Excel">×</button>
        </header>
        <form method="post" action="<?= site_url('akutansi/rka-kanwil-surabaya/upload-excel') ?>" enctype="multipart/form-data" data-rka-import-form data-rka-upload-step="selection" data-rka-units="<?= esc(json_encode($rkaUnits, JSON_THROW_ON_ERROR), 'attr') ?>" data-rka-data-url="<?= site_url('akutansi/rka-kanwil-surabaya/manual-data') ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="revision" value="<?= $revision ?>" data-rka-upload-revision>
            <input type="hidden" name="rka_reset_existing" value="0" data-rka-upload-reset-flag>
            <input type="hidden" name="rka_reset_id" value="" data-rka-upload-reset-id>
            <input type="hidden" name="rka_all_snapshots" value="" data-rka-upload-all-snapshots>
            <div class="lr-settings-body">
                <?php if ($uploadError !== null): ?><div class="alert alert-danger" role="alert"><?= esc($uploadError) ?></div><?php endif ?>
                <p id="rkaUploadDescription">Gunakan berkas RKA .xlsx, maksimal 5 MB. Format nominal C–H dan F–K didukung. Jangan mengubah posisi baris, kolom, atau rumus.</p>
                <div class="lr-upload-step-content" data-rka-upload-selection>
                    <p class="lr-rka-help">Tahap 1 dari 2 — pilih jenis upload dan tahun RKA.</p>
                    <label class="lr-upload-label" for="rkaUploadScope">Jenis Upload</label>
                    <select id="rkaUploadScope" class="lr-upload-select" name="upload_scope" required>
                        <option value="single" <?= ($uploadMode ?? 'single') === 'single' ? 'selected' : '' ?>>Per Unit Kerja</option>
                        <option value="all" <?= ($uploadMode ?? 'single') === 'all' ? 'selected' : '' ?>>Seluruh Unit Kerja (All Uker)</option>
                    </select>
                    <label class="lr-upload-label" for="rkaUploadUnit" data-rka-upload-unit-label>Unit Kerja</label>
                    <select id="rkaUploadUnit" class="lr-upload-select" name="unit_kerja" required>
                        <option value="" disabled>Pilih unit kerja</option>
                        <?php foreach ($rkaUnits as $unit): ?>
                            <option value="<?= esc($unit, 'attr') ?>" <?= $unit === ($selectedUnit === 'Korporat Kanwil' ? 'Kanwil' : $selectedUnit) ? 'selected' : '' ?> <?= $unit === 'Korporat Kanwil' ? 'disabled' : '' ?>><?= esc($unit) ?><?= $unit === 'Korporat Kanwil' ? ' (otomatis)' : '' ?></option>
                        <?php endforeach ?>
                    </select>
                    <p class="lr-upload-note" data-rka-upload-all-help hidden>Satu file Excel dengan <?= count($rkaUnits) ?> sheet: <?= esc(implode(', ', $rkaUnits)) ?>. Setiap sheet menggunakan struktur Template RKA dan tahun yang sama. Nama sheet yang tidak sesuai akan ditolak.</p>
                    <label class="lr-upload-label" for="rkaUploadYear">Tahun RKA</label>
                    <input id="rkaUploadYear" class="lr-upload-select" type="number" name="tahun" value="<?= $selectedYear ?>" min="2000" max="2100" step="1" required>
                    <button type="button" class="btn btn-primary" data-rka-upload-next>Next →</button>
                    <p class="lr-upload-status" data-rka-upload-selection-status role="status">Klik Next untuk memeriksa RKA sebelum memilih berkas.</p>
                    <div class="lr-rka-existing-alert" data-rka-upload-existing-alert role="alert" hidden><strong>RKA sudah diseting.</strong>
                        <p data-rka-upload-existing-message></p><button type="button" class="btn btn-secondary" data-rka-upload-reset>Seting Ulang</button><button type="button" class="btn btn-secondary lr-rka-delete-button" data-rka-upload-delete-existing aria-haspopup="dialog" aria-controls="rkaDeleteDialog">Hapus RKA</button>
                    </div>
                </div>
                <div class="lr-upload-step-content" data-rka-upload-file-stage hidden>
                    <p class="lr-rka-help" data-rka-upload-active-context>Tahap 2 dari 2 — pilih berkas Excel RKA.</p>
                    <label class="lr-upload-label" for="rkaExcelFile">Berkas Excel</label>
                    <input id="rkaExcelFile" class="lr-upload-input" type="file" name="rka_excel_file" accept=".xlsx,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" data-rka-upload-file required>
                    <p class="lr-upload-status" data-rka-upload-status role="status">Belum ada berkas dipilih.</p>
                    <p class="lr-upload-note">Isi nominal pada enam unit sumber. Korporat Kanwil otomatis merupakan jumlah keenam unit pada tahun yang sama, bukan isian terpisah. Tanda minus dipertahankan; jumlah dan subtotal dihitung sesuai rumus RKA.</p>
                    <label class="lr-rka-confirm"><input type="checkbox" name="confirm_replace" value="1" required> <span data-rka-upload-confirm-text>Saya mengonfirmasi unit dan tahun yang dipilih. Upload akan mengganti RKA unit/tahun tersebut jika sudah ada.</span></label>
                </div>
            </div>
            <footer class="lr-settings-footer lr-upload-footer">
                <button type="button" class="btn btn-secondary" data-rka-upload-back>← Kembali</button>
                <button type="button" class="btn btn-secondary" data-rka-upload-step-back hidden>← Pilih Unit &amp; Tahun</button>
                <button type="button" class="btn btn-secondary" data-rka-upload-close>Batal</button>
                <button type="submit" class="btn btn-primary" data-rka-upload-submit hidden disabled>Upload &amp; Simpan</button>
            </footer>
        </form>
    </section>
</dialog>
<script type="application/json" id="rkaUploadRevisions">
    <?= json_encode($revisions, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR) ?>
</script>
<?= view('akutansi/rka_manual', compact('schema', 'selectedUnit', 'selectedYear', 'inputs', 'calculated', 'revision', 'rkaUnits') + ['manualError' => $manualError ?? null, 'rawInputs' => $rawInputs ?? null, 'manualAutoOpen' => $manualAutoOpen ?? false, 'manualMode' => $manualMode ?? 'setting', 'manualEditId' => $manualEditId ?? '']) ?>

<dialog id="rkaDeleteDialog" class="lr-settings-dialog" aria-labelledby="rkaDeleteTitle">
    <header class="lr-settings-header">
        <div>
            <p class="eyebrow">RKA KANWIL SURABAYA</p>
            <h2 id="rkaDeleteTitle">Konfirmasi Penghapusan RKA</h2>
        </div><button type="button" class="icon-btn" data-rka-delete-close aria-label="Tutup konfirmasi hapus">×</button>
    </header>
    <form method="post" action="<?= site_url('akutansi/rka-kanwil-surabaya/hapus') ?>" data-rka-delete-form>
        <?= csrf_field() ?>
        <input type="hidden" name="unit_kerja" data-rka-delete-unit>
        <input type="hidden" name="tahun" data-rka-delete-year>
        <input type="hidden" name="revision" data-rka-delete-revision>
        <input type="hidden" name="rka_id" data-rka-delete-id>
        <div class="lr-settings-body">
            <p data-rka-delete-description></p>
            <p>Data yang Anda pilih akan dihapus. Konfirmasi untuk menghapusnya.</p><label class="lr-rka-confirm"><input type="checkbox" name="confirm_delete" value="1" required> Saya mengonfirmasi penghapusan RKA unit dan tahun tersebut.</label>
        </div>
        <footer class="lr-settings-footer lr-upload-footer"><button type="button" class="btn btn-secondary" data-rka-delete-close>Batal</button><button type="submit" class="btn btn-primary">Ya, Hapus RKA</button></footer>
    </form>
</dialog>

<?= $this->endSection() ?>