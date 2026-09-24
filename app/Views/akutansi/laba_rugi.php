<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<?php $lrMonths = [1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember']; ?>
<?php $allLobs = \App\Libraries\LrRealizationService::LOB_COLUMNS;
$lobSummary = count($selectedLobs) === count($allLobs) ? 'Semua LOB' : (count($selectedLobs) <= 2 ? implode(', ', $selectedLobs) : count($selectedLobs) . ' LOB dipilih'); ?>
<?php $isSimulatedImport = $lrImport !== null && (($lrResult['rule'] ?? '') === \App\Libraries\LrRealizationCalculator::WORKPAPER_RULE); ?>


<section class="page-heading lr-page-heading">
    <div>
        <p class="eyebrow">AKUTANSI</p>
        <h1>Laporan Laba / Rugi</h1>
    </div>
    <div class="lr-heading-actions">
        <button type="button" class="btn btn-primary" data-lr-upload-open aria-haspopup="dialog" aria-controls="lrUploadDialog">Upload Kertas Kerja</button>
        <button type="button" class="btn btn-danger-outline" data-lr-delete-open aria-haspopup="dialog" aria-controls="lrDeleteDialog">Hapus Laporan</button>
    </div>
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
        <section class="panel lr-rka-filter-panel">
            <p class="lr-rka-help">Sumber <?= esc($selectedBasis) ?>: hasil <strong>Simulasi Hitung</strong> dari <?= esc($lrImport['source_name']) ?> · <?= esc($lrResult['period'] ?? '') ?> · Sheet <?= esc($lrResult['sheet'] ?? '') ?>. Nilai hasil rumus Kertas Kerja disalin ke sistem tanpa dihitung ulang.</p>
            <details class="lr-source-audit">
                <summary>Lihat sumber angka</summary>
                <p><?= (int) ($lrResult['read_rows'] ?? 0) ?> uraian dan <?= (int) ($lrResult['calculated_cells'] ?? 0) ?> hasil rumus telah dibaca dari sheet ini. Perhitungan, alokasi LOB, total, dan persentase tetap mengikuti Kertas Kerja Simulasi.</p>
            </details>
        </section>
    <?php else: ?>
    <?php $lrSourceSheet = $lrResult['sheet'] ?? strtoupper($lrResult['unit'] ?? 'KANWIL'); ?>
    <section class="panel lr-rka-filter-panel">
        <p class="lr-rka-help">Sumber <?= esc($selectedBasis) ?>: <?= esc($lrImport['source_name']) ?> · <?= esc($lrResult['period']) ?> · Sheet <?= esc($lrSourceSheet) ?> · <?= count($lrResult['matches']) ?> baris terpetakan · <?= count($lrResult['unmapped'] ?? []) ?> baris belum terpetakan. Nominal dalam Rupiah (Rp).</p>
        <div class="lr-audit-actions"><button type="button" class="btn btn-secondary" data-lr-mapping-detail-open aria-haspopup="dialog" aria-controls="lrMappingDetailDialog">Detail Pengelompokan</button><?php if ($isAdmin): ?><a class="btn btn-secondary" href="<?= site_url('akutansi/pengaturan-mapping-oracle') ?>">Atur Mapping Oracle</a><?php endif ?></div>
        <details class="lr-source-audit">
            <summary>Lihat sumber perhitungan</summary>
            <p>Kolom B menentukan kelompok KUR, NON KUR, atau PEN. Kolom D dipasangkan dengan uraian laporan dan Ending Balance pada kolom G dijumlahkan per uraian serta kelompok. Untuk rincian yang mengikuti pembagian produk, kolom E mengelompokkan NON KUR menjadi KBG/Suretyship, Konsumtif, dan Produktif sesuai rumus kertas kerja.</p>
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
            <p>Versi mapping saat upload: <strong><?= esc($lrResult['mapping_version'] ?? 'aturan lama') ?></strong>. Rumus baku dan alias sumber yang sudah diverifikasi diterapkan setiap kali laporan dibuka; perubahan mapping khusus oleh Administrator digunakan pada upload berikutnya.</p>
            <div class="lr-mapping-summary">
                <?php foreach (\App\Libraries\OracleLrMappingService::TARGET_COLUMNS as $column): ?><article><span><?= esc($column) ?></span><strong><?= (int)($lrResult['segment_counts'][$column] ?? 0) ?></strong><small>baris terpetakan</small></article><?php endforeach ?>
            </div>
            <section class="lr-unmapped-section">
                <h3>Data belum terpetakan</h3>
                <?php if (empty($lrResult['unmapped'])): ?><p class="lr-upload-note">Tidak ada baris LOB bernominal yang tertinggal dari mapping aktif.</p>
                <?php else: ?><div class="oracle-mapping-table-wrap">
                        <table class="oracle-mapping-table lr-unmapped-table">
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
        <footer class="lr-settings-footer"><button type="button" class="btn btn-secondary" data-lr-mapping-detail-close>Tutup</button><?php if ($isAdmin): ?><a class="btn btn-primary" href="<?= site_url('akutansi/pengaturan-mapping-oracle') ?>">Atur Mapping</a><?php endif ?></footer>
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
