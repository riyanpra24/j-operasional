<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<?php $lrMonths = [1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember']; ?>
<?php $allLobs = \App\Libraries\LrRealizationService::LOB_COLUMNS;
$lobSummary = count($selectedLobs) === count($allLobs) ? 'Semua LOB' : (count($selectedLobs) <= 2 ? implode(', ', $selectedLobs) : count($selectedLobs) . ' LOB dipilih'); ?>
<?php $isSimulatedImport = $lrImport !== null && (($lrResult['rule'] ?? '') === \App\Libraries\LrRealizationCalculator::WORKPAPER_RULE); ?>
<?php $bopoPercent = static fn (?string $value): string => $value === null ? '—' : \App\Libraries\LrMoney::percentageDisplayFixed($value, 2);
$bopoAchievement = static fn (?string $value): string => $value === null ? '—' : \App\Libraries\LrMoney::percentageDisplay($value); ?>


<section class="page-heading lr-page-heading">
    <div>
        <p class="eyebrow">AKUTANSI</p>
        <h1>Laporan Laba / Rugi</h1>
    </div>
    <details class="lr-action-menu">
        <summary class="btn btn-primary" aria-label="Buka menu Laporan Laba Rugi">Menu <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="m7 10 5 5 5-5" /></svg></summary>
        <div class="lr-action-menu-popover" role="menu" aria-label="Menu Laporan Laba Rugi">
            <button type="button" role="menuitem" class="lr-action-menu-item" data-lr-upload-open aria-haspopup="dialog" aria-controls="lrUploadDialog">Upload Kertas Kerja</button>
            <button type="button" role="menuitem" class="lr-action-menu-item" data-lr-bopo-open aria-haspopup="dialog" aria-controls="lrBopoDialog">Laporan BOPO</button>
            <button type="button" role="menuitem" class="lr-action-menu-item" data-lr-export-open aria-haspopup="dialog" aria-controls="lrExportDialog">Export Document</button>
            <span class="lr-action-menu-divider" aria-hidden="true"></span>
            <button type="button" role="menuitem" class="lr-action-menu-item is-danger" data-lr-delete-open aria-haspopup="dialog" aria-controls="lrDeleteDialog">Hapus Laporan</button>
        </div>
    </details>
</section>

<nav class="lr-report-tabs" aria-label="Jenis laporan laba rugi">
    <?php foreach (\App\Libraries\LrRealizationService::BASES as $basis): ?>
        <?php $isActive = $basis === $selectedBasis; ?>
        <a class="lr-report-tab <?= $isActive ? 'is-active' : '' ?>" href="<?= site_url('akutansi/laba-rugi?' . http_build_query(['jenis_laporan' => $basis, 'unit_kerja' => $selectedUnit, 'bulan' => $selectedMonth, 'tahun' => $selectedYear, 'lob' => $selectedLobs])) ?>" <?= $isActive ? 'aria-current="page"' : '' ?>>
            <span class="lr-report-tab-badge"><?= $basis ?></span>
            <span><strong>Laporan Laba / Rugi (<?= $basis ?>)</strong><small><?= $basis === 'YTD' ? 'Akumulasi dari awal tahun sampai periode terpilih' : 'Nilai khusus pada periode terpilih' ?></small></span>
        </a>
    <?php endforeach ?>
</nav>

<section class="panel lr-rka-filter-panel">
    <form method="get" action="<?= site_url('akutansi/laba-rugi') ?>" class="lr-rka-filter">
        <input type="hidden" name="jenis_laporan" value="<?= esc($selectedBasis, 'attr') ?>">
        <div class="lr-filter-unit">
            <label class="lr-upload-label" for="lrFilterUnit">Unit Kerja</label>
            <select class="lr-upload-select" id="lrFilterUnit" name="unit_kerja">
                <?php foreach ($reportUnits as $unit): ?>
                    <option value="<?= esc($unit, 'attr') ?>" <?= $unit === $selectedUnit ? 'selected' : '' ?>><?= esc($unit) ?></option>
                <?php endforeach ?>
            </select>
        </div>
        <div class="lr-filter-month">
            <label class="lr-upload-label" for="lrFilterMonth">Bulan</label>
            <select class="lr-upload-select" id="lrFilterMonth" name="bulan" required>
                <?php foreach ($lrMonths as $monthNumber => $monthName): ?>
                    <option value="<?= $monthNumber ?>" <?= $monthNumber === $selectedMonth ? 'selected' : '' ?>><?= $monthName ?></option>
                <?php endforeach ?>
            </select>
        </div>
        <div class="lr-filter-year">
            <label class="lr-upload-label" for="lrFilterYear">Tahun</label>
            <input class="lr-upload-select" id="lrFilterYear" type="number" name="tahun" min="2000" max="2100" step="1" value="<?= $selectedYear ?>" required>
        </div>
        <div class="lr-filter-lob">
            <label class="lr-upload-label">LOB</label>
            <details class="lr-lob-picker">
                <summary><?= esc($lobSummary) ?></summary>
                <div class="lr-lob-options" role="group" aria-label="Pilih LOB yang ditampilkan">
                    <?php foreach ($allLobs as $lob): ?>
                        <label><input type="checkbox" name="lob[]" value="<?= esc($lob, 'attr') ?>" <?= in_array($lob, $selectedLobs, true) ? 'checked' : '' ?>><span><?= esc($lob) ?></span></label>
                    <?php endforeach ?>
                    <small>Pilih satu atau beberapa LOB. Total dan persentase tetap mengikuti keseluruhan laporan.</small>
                </div>
            </details>
        </div>
        <button type="submit" class="btn btn-secondary">Terapkan</button>
        <a class="btn btn-ghost" href="<?= site_url('akutansi/laba-rugi') ?>">Reset</a>
    </form>
</section>

<?php if ($lrImport !== null): ?>
    <?php if ($isSimulatedImport): ?>
    <?php else: ?>
    <?php $lrSourceSheet = $lrResult['sheet'] ?? strtoupper($lrResult['unit'] ?? 'KANWIL'); ?>
    <section class="panel lr-rka-filter-panel">
        <p class="lr-rka-help">Sumber <?= esc($selectedBasis) ?>: <?= esc($lrImport['source_name']) ?> · <?= esc($lrResult['period']) ?> · Sheet <?= esc($lrSourceSheet) ?> · <?= count($lrResult['matches']) ?> baris terpetakan · <?= count($lrResult['unmapped'] ?? []) ?> baris belum terpetakan. Nominal dalam Rupiah (Rp).</p>
        <div class="lr-audit-actions"><button type="button" class="btn btn-secondary" data-lr-mapping-detail-open aria-haspopup="dialog" aria-controls="lrMappingDetailDialog">Detail Pengelompokan</button></div>
        <details class="lr-source-audit">
            <summary>Lihat sumber perhitungan</summary>
            <p>Nilai enam LOB pada Kertas Kerja disalin sebagai angka hasil pengisian. Total dan persentase tetap memakai hasil rumus dari Kertas Kerja. Untuk rincian yang mengikuti pembagian produk, kolom E mengelompokkan NON KUR menjadi KBG/Suretyship, Konsumtif, dan Produktif.</p>
            <ul><?php foreach ($lrResult['matches'] as $match): ?><?php $calculation = $match['calculation_amount'] ?? $match['source_amount']; ?><li><?= esc($match['lob'] ?? 'KUR') ?> · <?= esc($match['report_label'] ?? 'Beban gaji karyawan') ?> — baris <?= (int)$match['row'] ?>, akun <?= esc($match['account']) ?>: sumber <span title="<?= esc('Nilai asli: Rp ' . \App\Libraries\LrMoney::exactFormat($match['source_amount']), 'attr') ?>">Rp <?= esc(\App\Libraries\LrMoney::roundedFormat($match['source_amount'])) ?></span><?php if (!empty($match['sign_inverted'])): ?> → nilai perhitungan Rp <?= esc(\App\Libraries\LrMoney::reportDisplay($calculation)) ?> (tanda dibalik)<?php endif ?><?php if (isset($match['description']) && \App\Libraries\OracleLrSalaryParser::normalizeLabel($match['description']) !== \App\Libraries\OracleLrSalaryParser::normalizeLabel($match['report_label'])): ?> (nama sumber: <?= esc($match['description']) ?>)<?php endif ?></li><?php endforeach ?></ul>
            <?php foreach ($lrResult['sign_rule_inputs'] ?? [] as $input): ?><p><?= esc($input['description']) ?> — baris <?= (int)$input['row'] ?>: sumber Rp <?= esc(\App\Libraries\LrMoney::roundedFormat($input['source_amount'])) ?> → nilai yang disimpan untuk perhitungan berikutnya Rp <?= esc(\App\Libraries\LrMoney::reportDisplay(\App\Libraries\LrSignRules::calculationValue($input['description'], $input['source_amount']))) ?> (tanda dibalik).</p><?php endforeach ?>
            <?php if (!empty($lrResult['all_sheet_sign_rule_inputs'])): ?><details class="lr-source-audit">
                    <summary><?= count($lrResult['all_sheet_sign_rule_inputs']) ?> nilai pembalikan tanda dari seluruh sheet</summary>
                    <ul><?php foreach ($lrResult['all_sheet_sign_rule_inputs'] as $input): ?><li><?= esc($input['sheet']) ?> · <?= esc($input['description']) ?> · baris <?= (int)$input['row'] ?>: sumber Rp <?= esc(\App\Libraries\LrMoney::roundedFormat($input['source_amount'])) ?> → perhitungan Rp <?= esc(\App\Libraries\LrMoney::reportDisplay(\App\Libraries\LrSignRules::calculationValue($input['description'], $input['source_amount'], $input['sheet']))) ?></li><?php endforeach ?></ul>
                </details><?php endif ?>
            <p>Seluruh desimal sumber disimpan dan dijumlahkan tanpa pemotongan. Pembulatan ke rupiah penuh hanya untuk tampilan; arahkan kursor ke nominal untuk melihat nilai asli.</p>
        </details>
        <?php if (!in_array($lrResult['rule'], [\App\Libraries\OracleLrSalaryParser::RULE, \App\Libraries\OracleLrSalaryParser::PREVIOUS_RULE, \App\Libraries\OracleLrSalaryParser::ALL_SHEET_SIGN_RULE], true)): ?><p class="lr-rka-help">Upload ini memakai aturan versi sebelumnya. Data tetap dapat dibaca; gunakan upload terbaru ketika pembaruan unit tersebut sudah diaktifkan.</p><?php endif ?>
        <?php foreach ($lrResult['missing_labels_by_segment'] ?? ['KUR' => $lrResult['missing_labels'] ?? []] as $segment => $missingLabels): ?>
            <?php if ($missingLabels): ?><details class="lr-source-audit">
                    <summary><?= count($missingLabels) ?> rincian beban tidak memiliki baris <?= esc($segment) ?> yang cocok</summary>
                    <p>Tanda — berarti tidak ada nominal yang cocok, bukan nilai nol.</p>
                    <ul><?php foreach ($missingLabels as $label): ?><li><?= esc($label) ?></li><?php endforeach ?></ul>
                </details><?php endif ?>
        <?php endforeach ?>
    </section>
    <?php endif ?>
<?php endif ?>

<?= view('akutansi/partials/report_table', ['reportTitle' => 'Laba / Rugi (' . $selectedBasis . ') ' . ($lrMonths[$selectedMonth] ?? '') . ' ' . $selectedYear, 'selectedUnit' => $selectedUnit, 'selectedYear' => $selectedYear, 'selectedLobs' => $selectedLobs, 'reportValues' => $reportValues]) ?>

<style>
/* Keep both BOPO tables inside their dialog; the table list scrolls vertically. */
#lrBopoDialog {
    display: block !important;
    height: auto !important;
    max-height: calc(100dvh - 28px) !important;
    overflow-x: hidden !important;
    overflow-y: auto !important;
}
#lrBopoDialog > .lr-bopo-body {
    display: grid;
    height: auto;
    min-height: auto;
    overflow: visible;
}
#lrBopoDialog .bopo-table-wrap {
    overflow-x: auto;
    overflow-y: visible;
}
</style>

<dialog id="lrBopoDialog" class="lr-settings-dialog lr-bopo-dialog" aria-labelledby="lrBopoTitle">
    <header class="lr-settings-header">
        <div><p class="eyebrow">AKUTANSI / LAPORAN LABA / RUGI</p><h2 id="lrBopoTitle">Laporan BOPO</h2></div>
        <button type="button" class="icon-btn" data-lr-bopo-close aria-label="Tutup laporan BOPO">×</button>
    </header>
    <div class="lr-settings-body lr-bopo-body">
        <p class="lr-upload-note">Periode <?= esc($lrMonths[$selectedMonth]) ?> <?= $selectedYear ?> mengikuti filter Laba/Rugi yang sedang aktif.</p>
        <?php foreach ([
            ['title' => 'BOPO YTD s/d ' . $lrMonths[$selectedMonth] . ' ' . $selectedYear, 'values' => $bopoYtdValues],
            ['title' => 'BOPO PTD ' . $lrMonths[$selectedMonth] . ' ' . $selectedYear, 'values' => $bopoPtdValues],
        ] as $bopoReport): ?>
            <section class="bopo-report-panel">
                <header class="bopo-report-header"><div><p>PT JAMKRINDO KANWIL SURABAYA</p><h2><?= esc($bopoReport['title']) ?></h2></div></header>
                <div class="bopo-table-wrap" tabindex="0" role="region" aria-label="<?= esc($bopoReport['title'], 'attr') ?>">
                    <table class="bopo-table"><thead><tr><th>Unit Kerja</th><th>Realisasi</th><th>Target</th><th>Pencapaian</th></tr></thead>
                        <tbody><?php foreach ($bopoUnits as $bopoUnit): ?><?php $bopo = $bopoReport['values'][$bopoUnit] ?? ['realisasi' => null, 'target' => null, 'pencapaian' => null]; ?>
                            <tr><th scope="row"><?= esc($bopoUnit) ?></th><td class="bopo-realization-cell"><span class="bopo-realization-template">Realisasi</span><span class="bopo-realization-value"><?= esc($bopoPercent($bopo['realisasi'] ?? null)) ?></span></td><td><?= esc($bopoPercent($bopo['target'] ?? null)) ?></td><td><?= esc($bopoAchievement($bopo['pencapaian'] ?? null)) ?></td></tr>
                        <?php endforeach ?></tbody>
                    </table>
                </div>
            </section>
        <?php endforeach ?>
    </div>
    <footer class="lr-settings-footer"><button type="button" class="btn btn-secondary" data-lr-bopo-close>Tutup</button></footer>
</dialog>

<dialog id="lrExportDialog" class="lr-settings-dialog lr-export-dialog" aria-labelledby="lrExportTitle">
    <header class="lr-settings-header">
        <div><p class="eyebrow">AKUTANSI / LAPORAN LABA / RUGI</p><h2 id="lrExportTitle">Export Dokumen</h2></div>
        <button type="button" class="icon-btn" data-lr-export-close aria-label="Tutup export dokumen">×</button>
    </header>
    <form method="post" action="<?= site_url('akutansi/export-dokumen') ?>" data-lr-export-form>
        <?= csrf_field() ?>
        <div class="lr-settings-body">
            <p>Unduh Realisasi Anggaran dengan seluruh unit dan rumus konsolidasi template Excel.</p>
            <label class="lr-upload-label" for="lrExportBasis">Jenis Laporan</label>
            <select id="lrExportBasis" class="lr-upload-select" name="jenis_laporan" required><?php foreach (\App\Libraries\LrRealizationService::BASES as $basis): ?><option value="<?= esc($basis) ?>" <?= $basis === $selectedBasis ? 'selected' : '' ?>><?= esc($basis) ?></option><?php endforeach ?></select>
            <label class="lr-upload-label" for="lrExportMonth">Bulan</label>
            <select id="lrExportMonth" class="lr-upload-select" name="bulan" required><?php foreach ($lrMonths as $monthNumber => $monthName): ?><option value="<?= $monthNumber ?>" <?= $monthNumber === $selectedMonth ? 'selected' : '' ?>><?= esc($monthName) ?></option><?php endforeach ?></select>
            <label class="lr-upload-label" for="lrExportYear">Tahun</label>
            <input id="lrExportYear" class="lr-upload-select" type="number" name="tahun" min="2000" max="2100" step="1" value="<?= $selectedYear ?>" required>
            <p class="lr-upload-note">Korporat Kanwil, Kanwil, Surabaya, Kediri, Malang, Madiun, dan Banyuwangi selalu disertakan agar konsolidasi tetap valid.</p>
        </div>
        <footer class="lr-settings-footer lr-upload-footer"><button type="button" class="btn btn-secondary" data-lr-export-close>Batal</button><button type="submit" class="btn btn-primary">Export Excel</button></footer>
    </form>
</dialog>

<dialog id="lrUploadDialog" class="lr-settings-dialog" aria-labelledby="lrUploadTitle" data-auto-open="<?= $lrUploadError !== null ? 'true' : 'false' ?>">
    <header class="lr-settings-header">
        <div>
            <p class="eyebrow">AKUTANSI / LAPORAN LABA / RUGI</p>
            <h2 id="lrUploadTitle">Upload Kertas Kerja Simulasi (<?= esc($selectedBasis) ?>)</h2>
        </div>
        <button type="button" class="icon-btn" data-lr-upload-close aria-label="Tutup upload Kertas Kerja">×</button>
    </header>
    <form method="post" action="<?= site_url('akutansi/laba-rugi/upload-excel') ?>" enctype="multipart/form-data" data-lr-upload-form>
        <?= csrf_field() ?>
        <div class="lr-settings-body">
            <?php if ($lrUploadError !== null): ?><div class="alert alert-danger" role="alert"><?= esc($lrUploadError) ?></div><?php endif ?>
            <p>Upload hasil Excel dari Simulasi Hitung. Sistem menyalin angka hasil rumus Kertas Kerja ke Laporan Laba / Rugi.</p>
            <input type="hidden" name="unit_kerja" value="<?= esc(in_array($selectedUnit, \App\Libraries\OracleLrSalaryParser::IMPORT_UNITS, true) ? $selectedUnit : 'Kanwil', 'attr') ?>">
            <input type="hidden" name="jenis_laporan" value="<?= esc($selectedBasis, 'attr') ?>">
            <?php foreach ($selectedLobs as $lob): ?><input type="hidden" name="lob[]" value="<?= esc($lob, 'attr') ?>"><?php endforeach ?>
            <label class="lr-upload-label" for="lrUploadYear">Tahun Laporan</label>
            <input class="lr-upload-select" id="lrUploadYear" type="number" name="tahun" min="2000" max="2100" step="1" value="<?= $selectedYear ?>" required>
            <label class="lr-upload-label" for="lrUploadMonth">Bulan Laporan</label>
            <select class="lr-upload-select" id="lrUploadMonth" name="bulan" required>
                <?php foreach ($lrMonths as $monthNumber => $monthName): ?>
                    <option value="<?= $monthNumber ?>" <?= $monthNumber === $lrUploadMonth ? 'selected' : '' ?>><?= $monthName ?></option>
                <?php endforeach ?>
            </select>
            <label class="lr-upload-label" for="lrUploadFile">Berkas Kertas Kerja Simulasi (.xlsx, maksimal 5 MB)</label>
            <input class="lr-upload-input" id="lrUploadFile" type="file" name="lr_excel" accept=".xlsx" required>
            <p class="lr-upload-note">Gunakan hasil terbaru dari Simulasi Hitung untuk jenis laporan, bulan, dan tahun yang sama. Buka lalu simpan kembali berkas di Excel agar seluruh rumus selesai dihitung sebelum di-upload. Nilai hasil rumus disalin tanpa pemotongan.</p>
            <label class="lr-upload-manual-approval">
                <input type="checkbox" name="manual_adjustment_approved" value="1">
                <span><strong>Setujui penyesuaian manual pada berkas ini</strong><small>Gunakan bila Anda menambahkan input Volume atau penyesuaian data lain. Persetujuan berlaku hanya untuk unggahan ini; rumus dan struktur Kertas Kerja tetap diperiksa.</small></span>
            </label>
        </div>
        <footer class="lr-settings-footer lr-upload-footer">
            <button type="button" class="btn btn-secondary" data-lr-upload-close>Batal</button>
            <button type="submit" class="btn btn-primary">Upload &amp; Salin Angka</button>
        </footer>
    </form>
</dialog>

<?php if ($lrImport !== null && !$isSimulatedImport): ?>
    <dialog id="lrMappingDetailDialog" class="lr-settings-dialog lr-mapping-detail-dialog" aria-labelledby="lrMappingDetailTitle">
        <header class="lr-settings-header">
            <div>
                <p class="eyebrow">AUDIT DATA ORACLE</p>
                <h2 id="lrMappingDetailTitle">Detail Pengelompokan <?= esc($selectedUnit) ?></h2>
            </div><button type="button" class="icon-btn" data-lr-mapping-detail-close aria-label="Tutup detail pengelompokan">×</button>
        </header>
        <div class="lr-settings-body lr-mapping-detail-body">
            <p>Pengelompokan memakai aturan baku dan alias sumber yang sudah diverifikasi pada saat laporan diunggah.</p>
            <div class="lr-mapping-summary">
                <?php foreach (['KUR', 'NON KUR', 'PEN'] as $column): ?><article><span><?= esc($column) ?></span><strong><?= (int)($lrResult['segment_counts'][$column] ?? 0) ?></strong><small>baris terpetakan</small></article><?php endforeach ?>
            </div>
            <section class="lr-unmapped-section">
                <h3>Data belum terpetakan</h3>
                <?php if (empty($lrResult['unmapped'])): ?><p class="lr-upload-note">Tidak ada baris LOB bernominal yang tertinggal dari aturan baku.</p>
                <?php else: ?><div class="lr-audit-table-wrap">
                        <table class="lr-audit-table lr-unmapped-table">
                            <thead>
                                <tr>
                                    <th>Baris</th>
                                    <th>LOB</th>
                                    <th>Description LOB</th>
                                    <th>Description COA</th>
                                    <th>Nominal sumber</th>
                                    <th>Alasan</th>
                                </tr>
                            </thead>
                            <tbody><?php foreach ($lrResult['unmapped'] as $item): ?><tr>
                                        <td><?= (int)$item['row'] ?></td>
                                        <td><?= esc($item['source_lob']) ?></td>
                                        <td><?= esc($item['description_lob']) ?></td>
                                        <td><?= esc($item['description']) ?></td>
                                        <td class="lr-unmapped-amount">Rp <?= esc(\App\Libraries\LrMoney::roundedFormat($item['source_amount'])) ?></td>
                                        <td><?= match ($item['reason'] ?? 'account') {
                                                'lob' => 'LOB belum memiliki aturan',
                                                'segment' => 'Description COA tidak berlaku untuk segmen ini',
                                                default => 'Description COA belum memiliki pasangan'
                                            } ?></td>
                                    </tr><?php endforeach ?></tbody>
                        </table>
                    </div><?php endif ?>
            </section>
        </div>
        <footer class="lr-settings-footer"><button type="button" class="btn btn-secondary" data-lr-mapping-detail-close>Tutup</button></footer>
    </dialog>
<?php endif ?>

<dialog id="lrDeleteDialog" class="lr-settings-dialog" aria-labelledby="lrDeleteTitle">
    <header class="lr-settings-header">
        <div>
            <p class="eyebrow">LAPORAN LABA / RUGI</p>
            <h2 id="lrDeleteTitle">Konfirmasi Hapus Laporan</h2>
        </div><button type="button" class="icon-btn" data-lr-delete-close aria-label="Tutup konfirmasi hapus">×</button>
    </header>
    <form method="post" action="<?= site_url('akutansi/laba-rugi/hapus') ?>" data-lr-delete-form>
        <?= csrf_field() ?>
        <input type="hidden" name="jenis_laporan" value="<?= esc($selectedBasis, 'attr') ?>">
        <?php foreach ($selectedLobs as $lob): ?><input type="hidden" name="lob[]" value="<?= esc($lob, 'attr') ?>"><?php endforeach ?>
        <div class="lr-settings-body">
            <p>Pilih unit kerja dan periode laporan <?= esc($selectedBasis) ?> yang akan dihapus. Seluruh revisi upload pada pilihan tersebut akan dipindahkan ke Data Terhapus.</p>
            <label class="lr-upload-label" for="lrDeleteUnit">Unit Kerja</label>
            <select class="lr-upload-select" id="lrDeleteUnit" name="unit_kerja" required>
                <option value="<?= \App\Libraries\OracleLrImportService::ALL_UNITS ?>">Seluruh Unit Kerja</option>
                <?php $deleteUnit = in_array($selectedUnit, \App\Libraries\OracleLrSalaryParser::IMPORT_UNITS, true) ? $selectedUnit : 'Kanwil';
                foreach (\App\Libraries\OracleLrSalaryParser::IMPORT_UNITS as $unit): ?>
                    <option value="<?= esc($unit, 'attr') ?>" <?= $unit === $deleteUnit ? 'selected' : '' ?>><?= esc($unit) ?></option>
                <?php endforeach ?>
            </select>
            <label class="lr-upload-label" for="lrDeleteMonth">Bulan Laporan</label>
            <select class="lr-upload-select" id="lrDeleteMonth" name="bulan" required><?php $deleteMonth = (int)($lrImport['report_month'] ?? $selectedMonth);
                                                                                        foreach ($lrMonths as $monthNumber => $monthName): ?><option value="<?= $monthNumber ?>" <?= $monthNumber === $deleteMonth ? 'selected' : '' ?>><?= $monthName ?></option><?php endforeach ?></select>
            <label class="lr-upload-label" for="lrDeleteYear">Tahun Laporan</label>
            <input class="lr-upload-select" id="lrDeleteYear" type="number" name="tahun" min="2000" max="2100" step="1" value="<?= $selectedYear ?>" required>
            <p class="lr-upload-note">Jika memilih Seluruh Unit Kerja, semua laporan sumber pada bulan dan tahun tersebut akan dihapus. Data bulan lain dan RKA tidak terpengaruh. Administrator tetap dapat memulihkan data melalui Data Terhapus.</p>
            <label class="lr-rka-confirm"><input type="checkbox" name="confirm_delete" value="1" required><span>Saya mengonfirmasi penghapusan laporan unit, bulan, dan tahun yang dipilih.</span></label>
        </div>
        <footer class="lr-settings-footer lr-upload-footer"><button type="button" class="btn btn-secondary" data-lr-delete-close>Batal</button><button type="submit" class="btn btn-danger-outline">Hapus Laporan</button></footer>
    </form>
</dialog>

<?= $this->endSection() ?>
