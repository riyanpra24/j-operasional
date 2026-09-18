<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<?php $sourceOperator = static function (array $term): string {
    $operator = (string) ($term['operator'] ?? '');
    if (in_array($operator, ['+', '-', '*', '/'], true)) return $operator;
    return (int) ($term['coefficient'] ?? 1) === -1 ? '-' : '+';
}; ?>

<section class="page-heading lr-page-heading formula-page-heading">
    <div>
        <p class="eyebrow">AKUTANSI / <?= $isSourceAdjustmentAdmin ? 'ADMINISTRATOR' : 'PENGAJUAN PERUBAHAN' ?></p>
        <h1>Penyesuaian Sumber Oracle</h1>
        <p>Atur sumber Description COA per unit, LOB, dan periode. Rumus baris hasil dihitung otomatis oleh mesin.</p>
    </div>
    <div class="formula-heading-actions">
        <a class="btn btn-secondary" href="<?= site_url('akutansi/laba-rugi') ?>">Kembali</a>
    </div>
</section>

<?php if ($sourceAdjustmentError !== null): ?>
    <div class="alert alert-danger" role="alert"><strong>Penyesuaian sumber belum disimpan</strong><span><?= esc($sourceAdjustmentError) ?></span></div>
<?php endif ?>

<section class="panel source-adjustment-panel" id="penyesuaian-sumber">
    <header class="source-adjustment-heading">
        <div><p class="eyebrow">PENGECUALIAN BERDASARKAN PERIODE</p><h2>Sumber Oracle per LOB</h2><p>Hitung ulang satu baris rincian dari uraian Oracle mentah untuk unit, LOB, dan periode tertentu. Rumus hasil tetap dikendalikan mesin.</p></div>
        <button class="btn btn-primary" type="button" data-source-adjustment-create>+ <?= $isSourceAdjustmentAdmin ? 'Buat Penyesuaian' : 'Ajukan Penyesuaian' ?></button>
    </header>
    <div class="source-adjustment-warning" role="note"><strong>Pengamanan aktif</strong><span>Periode berlaku wajib, aturan bertumpang tindih ditolak, dan perubahan dicatat. Aturan hanya dapat dihapus setelah dinonaktifkan; riwayat audit tetap disimpan.</span></div>
    <div class="formula-card-grid source-adjustment-grid">
    <?php foreach ($sourceAdjustmentRules as $rule):
        $sourceId = (int) ($rule['id'] ?? 0);
        $sourceActive = (int) ($rule['is_active'] ?? 0) === 1;
        $sourceTerms = is_array($rule['terms'] ?? null) ? $rule['terms'] : [];
        $sourceScope = (string) ($rule['unit_scope'] ?? 'all');
        $sourceColumn = (string) ($rule['column_scope'] ?? 'all');
        $sourceLinesEncoded = base64_encode((string) ($rule['formula_lines'] ?? ''));
    ?>
        <article class="panel formula-card source-adjustment-card">
            <header>
                <div>
                    <div class="formula-card-badges">
                        <span class="formula-scope-badge"><?= esc($formulaScopes[$sourceScope] ?? $sourceScope) ?></span>
                        <span class="formula-column-badge"><?= esc($formulaColumnScopes[$sourceColumn] ?? $sourceColumn) ?></span>
                        <span class="source-period-badge"><?= esc(date('M Y', strtotime((string) $rule['effective_from']))) ?> – <?= esc(date('M Y', strtotime((string) $rule['effective_to']))) ?></span>
                    </div>
                    <h2><?= esc($rule['target_label'] ?? '') ?></h2>
                </div>
                <span class="formula-status <?= $sourceActive ? 'is-active' : 'is-inactive' ?>"><i aria-hidden="true"></i><?= $sourceActive ? 'Aktif' : 'Nonaktif' ?></span>
            </header>
            <div class="formula-preview">
                <?php foreach ($sourceTerms as $term): $sourceTermOperator = $sourceOperator((array) $term); ?>
                    <span class="formula-preview-term <?= $sourceTermOperator === '-' ? 'is-minus' : (in_array($sourceTermOperator, ['*', '/'], true) ? 'is-math' : 'is-plus') ?>"><b><?= esc(['*' => '×', '/' => '÷', '-' => '−'][$sourceTermOperator] ?? '+') ?></b><?= esc($term['source_description'] ?? '') ?></span>
                <?php endforeach ?>
            </div>
            <p class="source-adjustment-reason"><strong>Alasan:</strong> <?= esc($rule['reason'] ?? '') ?></p>
            <footer>
                <span class="formula-setting-audit">Diubah oleh <?= esc(($rule['updated_by_name'] ?? '') ?: 'Sistem') ?><small><?= esc(date('d M Y, H:i', strtotime((string) ($rule['updated_at'] ?? 'now')))) ?></small></span>
                <?php if ($canManageSourceAdjustments): ?><div class="formula-card-actions">
                    <button class="btn btn-secondary" type="button" data-source-adjustment-edit
                        data-id="<?= $sourceId ?>" data-scope="<?= esc($sourceScope, 'attr') ?>" data-column="<?= esc($sourceColumn, 'attr') ?>"
                        data-target="<?= esc($rule['target_label'] ?? '', 'attr') ?>"
                        data-from="<?= esc(substr((string) $rule['effective_from'], 0, 7), 'attr') ?>"
                        data-to="<?= esc(substr((string) $rule['effective_to'], 0, 7), 'attr') ?>"
                        data-reason="<?= esc($rule['reason'] ?? '', 'attr') ?>" data-active="<?= $sourceActive ? '1' : '0' ?>"
                        data-lines="<?= esc($sourceLinesEncoded, 'attr') ?>"><?= $isSourceAdjustmentAdmin ? 'Lihat / Edit' : 'Ajukan Perubahan' ?></button>
                    <?php if ($sourceActive): ?><form method="post" action="<?= site_url('akutansi/seting-rumus/sumber/nonaktifkan') ?>" data-source-adjustment-deactivate data-approval-required="<?= $isSourceAdjustmentAdmin ? '0' : '1' ?>">
                        <?= csrf_field() ?><input type="hidden" name="id" value="<?= $sourceId ?>"><input type="hidden" name="confirm_deactivate" value="1">
                        <button class="btn btn-danger-outline" type="submit"><?= $isSourceAdjustmentAdmin ? 'Nonaktifkan' : 'Ajukan Nonaktifkan' ?></button>
                    </form><?php else: ?><form method="post" action="<?= site_url('akutansi/seting-rumus/sumber/hapus') ?>" data-source-adjustment-delete data-approval-required="<?= $isSourceAdjustmentAdmin ? '0' : '1' ?>">
                        <?= csrf_field() ?><input type="hidden" name="id" value="<?= $sourceId ?>"><input type="hidden" name="confirm_delete" value="1">
                        <button class="btn btn-danger-outline" type="submit"><?= $isSourceAdjustmentAdmin ? 'Hapus' : 'Ajukan Hapus' ?></button>
                    </form><?php endif ?>
                </div><?php endif ?>
            </footer>
        </article>
    <?php endforeach ?>
    <?php if (!$sourceAdjustmentRules): ?><div class="source-adjustment-empty"><strong>Belum ada penyesuaian sumber.</strong><span>Rumus dan mapping standar masih digunakan untuk seluruh periode.</span></div><?php endif ?>
    </div>
</section>

<section class="panel source-request-panel" id="pengajuan-sumber">
    <header class="source-adjustment-heading">
        <div><p class="eyebrow">ALUR PERSETUJUAN</p><h2>Pengajuan Penyesuaian</h2><p>Pengajuan user Akuntansi tidak memengaruhi perhitungan sebelum disetujui Administrator.</p></div>
        <?php $pendingRequestCount = count(array_filter($sourceAdjustmentRequests, static fn (array $request): bool => ($request['status'] ?? '') === 'pending')); ?>
        <span class="source-request-count"><?= $pendingRequestCount ?> menunggu</span>
    </header>
    <div class="source-request-list">
    <?php foreach ($sourceAdjustmentRequests as $request):
        $requestPayload = is_array($request['payload'] ?? null) ? $request['payload'] : [];
        $requestSummary = is_array($requestPayload['summary'] ?? null) ? $requestPayload['summary'] : [];
        $requestInput = is_array($requestPayload['input'] ?? null) ? $requestPayload['input'] : [];
        $requestStatus = (string) ($request['status'] ?? 'pending');
        $requestAction = (string) ($request['action'] ?? 'update');
        $requestActionLabel = ['create' => 'Buat aturan', 'update' => 'Ubah aturan', 'deactivate' => 'Nonaktifkan aturan', 'delete' => 'Hapus aturan'][$requestAction] ?? 'Perubahan aturan';
        $requestStatusLabel = ['pending' => 'Menunggu persetujuan', 'approved' => 'Disetujui', 'rejected' => 'Ditolak'][$requestStatus] ?? $requestStatus;
    ?>
        <article class="source-request-card is-<?= esc($requestStatus, 'attr') ?>">
            <div class="source-request-main">
                <div class="formula-card-badges">
                    <span class="source-request-action"><?= esc($requestActionLabel) ?></span>
                    <span class="formula-scope-badge"><?= esc($formulaScopes[$requestSummary['unit_scope'] ?? ''] ?? ($requestSummary['unit_scope'] ?? '-')) ?></span>
                    <span class="formula-column-badge"><?= esc($formulaColumnScopes[$requestSummary['column_scope'] ?? ''] ?? ($requestSummary['column_scope'] ?? '-')) ?></span>
                </div>
                <h3><?= esc($requestSummary['target_label'] ?? 'Penyesuaian Sumber Oracle') ?></h3>
                <?php if (!empty($requestSummary['effective_from'])): ?><p class="source-request-period"><?= esc($requestSummary['effective_from']) ?> – <?= esc($requestSummary['effective_to'] ?? $requestSummary['effective_from']) ?></p><?php endif ?>
                <?php if (!empty($requestInput['formula_lines'])): ?><div class="formula-preview source-request-terms">
                    <?php foreach (preg_split('/\R/u', (string) $requestInput['formula_lines']) ?: [] as $requestLine):
                        $requestLine = trim($requestLine); if ($requestLine === '') continue; $requestOperator = substr($requestLine, 0, 1); ?>
                        <span class="formula-preview-term <?= $requestOperator === '-' ? 'is-minus' : (in_array($requestOperator, ['*', '/'], true) ? 'is-math' : 'is-plus') ?>"><b><?= esc(['*' => '×', '/' => '÷', '-' => '−'][$requestOperator] ?? '+') ?></b><?= esc(trim(substr($requestLine, 1))) ?></span>
                    <?php endforeach ?>
                </div><?php endif ?>
                <?php if (!empty($requestInput['reason'])): ?><p class="source-adjustment-reason source-request-reason"><strong>Alasan:</strong> <?= esc($requestInput['reason']) ?></p><?php endif ?>
                <p class="source-request-audit">Diajukan oleh <strong><?= esc($request['requested_by_name'] ?? '-') ?></strong> pada <?= esc(date('d M Y, H:i', strtotime((string) ($request['requested_at'] ?? 'now')))) ?></p>
                <?php if ($requestStatus !== 'pending'): ?><p class="source-request-review">Diperiksa oleh <strong><?= esc($request['reviewed_by_name'] ?? '-') ?></strong><?= !empty($request['review_note']) ? ': ' . esc($request['review_note']) : '' ?></p><?php endif ?>
            </div>
            <div class="source-request-side">
                <span class="source-request-status"><?= esc($requestStatusLabel) ?></span>
                <?php if ($isSourceAdjustmentAdmin && $requestStatus === 'pending'): ?>
                <div class="source-request-actions">
                    <form method="post" action="<?= site_url('akutansi/seting-rumus/pengajuan/setujui') ?>" data-source-request-approve>
                        <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) ($request['id'] ?? 0) ?>"><input type="hidden" name="confirm_approve" value="1">
                        <button class="btn btn-primary" type="submit">Setujui</button>
                    </form>
                    <form method="post" action="<?= site_url('akutansi/seting-rumus/pengajuan/tolak') ?>" data-source-request-reject>
                        <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) ($request['id'] ?? 0) ?>"><input type="hidden" name="confirm_reject" value="1">
                        <input type="text" name="review_note" maxlength="500" placeholder="Catatan penolakan (opsional)">
                        <button class="btn btn-danger-outline" type="submit">Tolak</button>
                    </form>
                </div>
                <?php endif ?>
            </div>
        </article>
    <?php endforeach ?>
    <?php if (!$sourceAdjustmentRequests): ?><div class="source-adjustment-empty"><strong>Belum ada pengajuan.</strong><span>Pengajuan dari user Akuntansi akan tampil di sini untuk diperiksa Administrator.</span></div><?php endif ?>
    </div>
</section>

<dialog id="sourceAdjustmentDialog" class="lr-settings-dialog formula-editor-dialog source-adjustment-dialog" aria-labelledby="sourceAdjustmentTitle">
    <header class="lr-settings-header">
        <div><p class="eyebrow">SUMBER ORACLE BERDASARKAN PERIODE</p><h2 id="sourceAdjustmentTitle" data-source-adjustment-title>Buat Penyesuaian</h2></div>
        <button class="icon-btn" type="button" aria-label="Tutup" data-source-adjustment-close>×</button>
    </header>
    <form method="post" action="<?= site_url('akutansi/seting-rumus/sumber/simpan') ?>" data-source-adjustment-form data-approval-required="<?= $isSourceAdjustmentAdmin ? '0' : '1' ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="" data-source-adjustment-id>
        <textarea name="formula_lines" data-source-adjustment-lines hidden required></textarea>
        <div class="formula-editor-body">
            <div class="source-adjustment-info"><strong>Aturan ini menggantikan sumber baris tujuan hanya pada cakupan dan periode berikut.</strong><span>Nominal dihitung berurutan dari Ending Balance Oracle asli: + tambah, − kurang, × kali, dan ÷ bagi.</span></div>
            <div class="source-adjustment-form-grid">
                <label><span>Cakupan unit *</span><select name="unit_scope" required data-source-adjustment-scope><?php foreach ($formulaScopes as $value => $label): ?><option value="<?= esc($value, 'attr') ?>"><?= esc($label) ?></option><?php endforeach ?></select></label>
                <label><span>Kolom / LOB *</span><select name="column_scope" required data-source-adjustment-column><?php foreach ($formulaColumnScopes as $value => $label): ?><option value="<?= esc($value, 'attr') ?>"><?= esc($label) ?></option><?php endforeach ?></select></label>
                <label class="source-target-field"><span>Baris tujuan *</span><select name="target_label" required data-source-adjustment-target><?php foreach ($sourceAdjustmentTargets as $label): ?><option value="<?= esc($label, 'attr') ?>"><?= esc($label) ?></option><?php endforeach ?></select></label>
                <label><span>Periode mulai *</span><input type="month" name="effective_from" min="2020-01" max="2100-12" required data-source-adjustment-from></label>
                <label><span>Periode selesai *</span><input type="month" name="effective_to" min="2020-01" max="2100-12" required data-source-adjustment-to></label>
            </div>
            <section class="formula-builder">
                <div class="formula-builder-heading"><div><strong>Uraian Oracle pembentuk nilai</strong><small>Cari uraian, klik Add Uraian, lalu tentukan operator pada daftar di bawahnya.</small></div><span data-source-adjustment-count>0 uraian</span></div>
                <label class="formula-component-select"><span>Pilih uraian Oracle</span><span class="source-description-picker"><span class="source-description-combobox"><input type="text" autocomplete="off" role="combobox" aria-autocomplete="list" aria-expanded="false" aria-controls="sourceDescriptionOptions" data-source-adjustment-component placeholder="Ketik untuk mencari Description COA"><button type="button" aria-label="Buka daftar uraian Oracle" data-source-description-toggle>⌄</button></span><span class="source-description-menu" id="sourceDescriptionOptions" role="listbox" data-source-description-menu hidden><?php foreach ($sourceDescriptions as $description): ?><button type="button" role="option" data-source-description-option data-value="<?= esc($description, 'attr') ?>"><?= esc($description) ?></button><?php endforeach ?></span></span></label>
                <div class="formula-builder-actions"><button class="btn formula-add-button" type="button" data-source-adjustment-add-description><b>+</b> Add Uraian</button></div>
                <div class="formula-term-list" data-source-adjustment-term-list></div>
                <p class="formula-term-empty" data-source-adjustment-empty>Belum ada uraian Oracle. Pilih dari dropdown lalu klik Add Uraian.</p>
            </section>
            <label class="source-reason-field"><span>Alasan penyesuaian *</span><textarea name="reason" rows="3" minlength="10" maxlength="500" required data-source-adjustment-reason placeholder="Contoh: Salah pencatatan Cadangan Klaim Surabaya periode Agustus 2026."></textarea><small>Alasan disimpan dalam riwayat audit.</small></label>
            <label class="formula-active-check"><input type="checkbox" name="is_active" value="1" checked data-source-adjustment-active><span><strong>Aktifkan penyesuaian</strong><small>Hanya berlaku pada periode di atas; setelah berakhir sistem kembali ke aturan standar.</small></span></label>
        </div>
        <footer class="lr-settings-footer formula-editor-footer"><button class="btn btn-secondary" type="button" data-source-adjustment-close>Batal</button><button class="btn btn-primary" type="submit" data-source-adjustment-submit disabled><?= $isSourceAdjustmentAdmin ? 'Simpan Penyesuaian' : 'Kirim untuk Persetujuan' ?></button></footer>
    </form>
</dialog>

<?= $this->endSection() ?>
