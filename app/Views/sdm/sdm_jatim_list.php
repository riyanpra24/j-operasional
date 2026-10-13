<?php

/** @var list<array<string, mixed>> $employees */
/** @var array{keyword: string, workUnit: string, positionFilter: string, perPage: int} $filters */
/** @var list<string> $workUnitOptions */
/** @var list<string> $positionOptions */
/** @var list<string> $summaryWorkUnitOptions */
/** @var array<string, int> $workUnitCounts */
/** @var list<string> $compositionPositions */
/** @var list<string> $compositionColumns */
/** @var array<string, array<string, array<string, int>>> $compositionStatusCounts */
/** @var list<array<string, mixed>> $employeesNeedingReview */
/** @var \CodeIgniter\Pager\PagerInterface $pager */
$sdmRole = (string) session()->get('auth_role');
$canReviewSdm = in_array($sdmRole, ['admin', 'sdm'], true);
$canDeleteAllSdm = $sdmRole === 'admin';
$canImportSdm = $sdmRole === 'admin';
$canExportSdm = in_array($sdmRole, ['admin', 'sdm'], true);
?>

<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<section class="sdm-unit-overview" aria-labelledby="sdmUnitOverviewTitle">
    <header class="sdm-unit-overview-heading">
        <span class="sdm-unit-overview-icon"><img src="https://cdn-icons-png.flaticon.com/512/236/236842.png" alt=""></span>
        <div>
            <h2 id="sdmUnitOverviewTitle">Ringkasan Pegawai per Cabang</h2>
            <p>Jumlah pegawai di setiap kantor cabang dan wilayah <a href="https://www.flaticon.com/free-icon/team_236842" target="_blank" rel="noreferrer">· Ikon: Vectors Market / Flaticon</a></p>
            <i aria-hidden="true"></i>
        </div>
    </header>
    <div class="sdm-unit-summary" aria-label="Jumlah pegawai per unit kerja">
        <?php foreach ($summaryWorkUnitOptions as $unit): ?>
            <a class="sdm-unit-summary-card<?= $unit === 'Kantor Cabang Banyuwangi' ? ' has-banyuwangi-background' : '' ?><?= $unit === 'Kantor Cabang Kediri' ? ' has-kediri-background' : '' ?><?= $unit === 'Kantor Cabang Malang' ? ' has-malang-background' : '' ?><?= $unit === 'Kantor Cabang Madiun' ? ' has-madiun-background' : '' ?><?= $unit === 'Kantor Cabang Surabaya' ? ' has-surabaya-background' : '' ?><?= $unit === 'Kantor Wilayah Surabaya' ? ' has-kanwil-surabaya-background' : '' ?><?= $filters['workUnit'] === $unit ? ' is-selected' : '' ?>" href="<?= esc(site_url('sdm/sdm-jatim?unit_kerja=' . rawurlencode($unit) . '&per_page=' . (int) $filters['perPage']), 'attr') ?>" data-sdm-composition-open data-work-unit="<?= esc($unit, 'attr') ?>" aria-haspopup="dialog" aria-controls="sdmCompositionSummaryDialog" aria-label="Buka rekap komposisi SDM <?= esc($unit, 'attr') ?>">
                <span class="sdm-unit-card-head"><strong><?= esc($unit) ?></strong><i aria-hidden="true">›</i></span>
                <span class="sdm-unit-card-metric"><span class="sdm-unit-card-icon" aria-hidden="true"></span><span><b><?= number_format((int) ($workUnitCounts[$unit] ?? 0), 0, ',', '.') ?></b><small>Pegawai</small></span></span>
            </a>
        <?php endforeach ?>
    </div>
</section>

<section class="panel filter-panel">
    <form method="get" action="<?= site_url('sdm/sdm-jatim') ?>" class="agendaris-filter-form sdm-list-filter-form">
        <div class="form-group search-group">
            <label for="sdm_jatim_search">Cari SDM</label>
            <div class="input-with-icon">
                <span aria-hidden="true">⌕</span>
                <input id="sdm_jatim_search" type="search" name="q" value="<?= esc($filters['keyword'], 'attr') ?>" placeholder="Nama, NPP, jabatan, atau bagian" autocomplete="off">
            </div>
        </div>
        <div class="form-group"><label for="sdm_jatim_unit">Unit Kerja</label><select id="sdm_jatim_unit" name="unit_kerja">
                <option value="">Semua unit kerja</option><?php foreach ($workUnitOptions as $option): ?><option value="<?= esc($option, 'attr') ?>" <?= $filters['workUnit'] === $option ? 'selected' : '' ?>><?= esc($option) ?></option><?php endforeach ?>
            </select></div>
        <div class="form-group"><label for="sdm_jatim_position">Posisi</label><select id="sdm_jatim_position" name="posisi">
                <option value="">Semua posisi</option><?php foreach ($positionOptions as $option): ?><option value="<?= esc($option, 'attr') ?>" <?= $filters['positionFilter'] === $option ? 'selected' : '' ?>><?= esc($option) ?></option><?php endforeach ?>
            </select></div>
        <input type="hidden" name="per_page" value="<?= (int) $filters['perPage'] ?>">
        <div class="filter-actions">
            <button type="submit" class="btn btn-secondary">Terapkan</button>
            <a href="<?= site_url('sdm/sdm-jatim') ?>" class="btn btn-ghost">Reset</a>
            <details class="lr-action-menu lr-filter-menu">
                <summary class="lr-filter-menu-trigger" aria-label="Menu tindakan SDM Jatim" title="Menu tindakan">
                    <svg aria-hidden="true" viewBox="0 0 24 24" focusable="false">
                        <rect x="3" y="3" width="7" height="7" rx="1.4"></rect>
                        <rect x="14" y="3" width="7" height="7" rx="1.4"></rect>
                        <rect x="3" y="14" width="7" height="7" rx="1.4"></rect>
                        <rect x="14" y="14" width="7" height="7" rx="1.4"></rect>
                    </svg>
                </summary>
                <div class="lr-action-menu-popover" role="menu" aria-label="Menu SDM Jatim">
                    <button type="button" role="menuitem" class="lr-action-menu-item" data-sdm-profile-create aria-haspopup="dialog" aria-controls="sdmProfileDialog">Tambah SDM</button>
                    <?php if ($canImportSdm): ?><button type="button" role="menuitem" class="lr-action-menu-item" data-sdm-profile-import aria-haspopup="dialog" aria-controls="sdmProfileImportDialog">Import Excel</button><?php endif ?>
                    <?php if ($canExportSdm): ?><a href="<?= site_url('sdm/sdm-jatim/export') ?>" role="menuitem" class="lr-action-menu-item">Export Dokumen</a><?php endif ?>
                    <?php if ($canReviewSdm): ?><button type="button" role="menuitem" class="lr-action-menu-item" data-sdm-profile-validation aria-haspopup="dialog" aria-controls="sdmProfileValidationDialog">Data Perlu Dilengkapi (<?= count($employeesNeedingReview) ?>)</button><?php endif ?>
                    <?php if ($canDeleteAllSdm): ?><button type="button" role="menuitem" class="lr-action-menu-item is-danger" data-sdm-profile-delete-all aria-haspopup="dialog" aria-controls="sdmProfileDeleteAllDialog">Hapus seluruh data SDM</button><?php endif ?>
                </div>
            </details>
        </div>
    </form>
</section>

<section class="panel register-panel agendaris-table-panel async-table-card" id="sdmJatimTableArea" aria-live="polite">
    <div class="table-wrap sdm-jatim-table-wrap">
        <table class="sdm-jatim-table">
            <thead>
                <tr>
                    <th>Nama Karyawan</th>
                    <th>NPP</th>
                    <th>Posisi</th>
                    <th>Unit Kerja</th>
                    <th>Status Karyawan</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($employees === []): ?>
                    <tr>
                        <td colspan="6">
                            <div class="empty-state">
                                <span aria-hidden="true">♙</span>
                                <strong>Belum ada data SDM</strong>
                                <p>Tambahkan data SDM baru untuk mulai membangun daftar pegawai.</p>
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($employees as $employee): ?>
                        <tr>
                            <td><strong><?= esc($employee['employee_name']) ?></strong></td>
                            <td><?= esc($employee['employee_no'] ?: '-') ?></td>
                            <td><?= esc($employee['position'] ?: '-') ?></td>
                            <td><?= esc($employee['work_unit'] ?: '—') ?></td>
                            <td><?= esc($employee['employment_status'] ?: '—') ?></td>
                            <?php $profile = ['id' => (int) $employee['id'], 'employee_no' => $employee['employee_no'], 'employee_name' => $employee['employee_name'], 'gender' => $employee['gender'], 'work_unit' => $employee['work_unit'], 'organization' => $employee['organization'], 'position' => $employee['position'], 'employment_status' => $employee['employment_status'], 'employment_date' => $employee['employment_date'], 'phone_number' => $employee['phone_number'], 'corporate_email' => $employee['corporate_email']]; ?>
                            <td class="sdm-jatim-actions"><button type="button" class="icon-btn" data-sdm-profile-view='<?= esc(json_encode($profile, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT), 'attr') ?>' aria-label="Lihat <?= esc($employee['employee_name'], 'attr') ?>">⌕</button><button type="button" class="icon-btn" data-sdm-profile-edit='<?= esc(json_encode($profile, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT), 'attr') ?>' aria-label="Ubah <?= esc($employee['employee_name'], 'attr') ?>">✎</button><button type="button" class="icon-btn is-danger" data-sdm-profile-delete data-id="<?= (int) $employee['id'] ?>" data-name="<?= esc($employee['employee_name'], 'attr') ?>" aria-label="Hapus <?= esc($employee['employee_name'], 'attr') ?>">×</button></td>
                        </tr>
                    <?php endforeach ?>
                <?php endif ?>
            </tbody>
        </table>
    </div>

    <div class="table-list-footer">
        <form method="get" action="<?= site_url('sdm/sdm-jatim') ?>" class="table-length-form">
            <input type="hidden" name="q" value="<?= esc($filters['keyword'], 'attr') ?>">
            <input type="hidden" name="unit_kerja" value="<?= esc($filters['workUnit'], 'attr') ?>">
            <label for="sdm_jatim_per_page">Tampilkan</label>
            <select id="sdm_jatim_per_page" name="per_page" data-async-table-length>
                <?php foreach ([10, 20, 50, 100] as $size): ?>
                    <option value="<?= $size ?>" <?= $filters['perPage'] === $size ? 'selected' : '' ?>><?= $size ?></option>
                <?php endforeach ?>
            </select>
            <span>data</span>
        </form>
        <?php if ($employees !== []): ?><div class="pagination-wrap"><?= $pager->links('sdm_jatim_list', 'table_full') ?></div><?php endif ?>
    </div>
</section>

<dialog id="sdmProfileDialog" class="lr-settings-dialog sdm-profile-dialog" aria-labelledby="sdmProfileDialogTitle">
    <header class="lr-settings-header">
        <div>
            <p class="eyebrow">SDM &amp; TELLER</p>
            <h2 id="sdmProfileDialogTitle">Tambah SDM</h2>
        </div><button type="button" class="icon-btn" data-sdm-profile-close aria-label="Tutup">×</button>
    </header>
    <form method="post" action="<?= site_url('sdm/sdm-jatim') ?>" data-sdm-profile-form>
        <?= csrf_field() ?>
        <input type="hidden" name="return_query" value="">
        <input type="hidden" name="return_to_validation" value="0">
        <div class="sdm-profile-body">
            <div class="modal-section-heading"><span>01</span>
                <div><strong>Data SDM</strong><small>Lengkapi identitas dan data kepegawaian</small></div>
            </div>
            <div class="modal-form-grid sdm-profile-grid">
                <div class="form-group"><label>NPP <span class="required">*</span></label><input name="employee_no" maxlength="30" required></div>
                <div class="form-group"><label>Nama Lengkap <span class="required">*</span></label><input name="employee_name" maxlength="150" required></div>
                <div class="form-group"><label>Gender</label><select name="gender">
                        <option value="">Pilih gender</option>
                        <option value="L">Laki-laki</option>
                        <option value="P">Perempuan</option>
                    </select></div>
                <div class="form-group"><label>Unit Kerja <span class="required">*</span></label><select name="work_unit" required>
                        <option value="">Pilih unit kerja</option><?php foreach ($workUnitOptions as $option): ?><option value="<?= esc($option, 'attr') ?>"><?= esc($option) ?></option><?php endforeach ?>
                    </select></div>
                <div class="form-group sdm-lockable is-locked"><label>Bagian</label><select name="organization" disabled>
                        <option value="">Pilih bagian</option>
                        <option value="Pimpinan Wilayah">Pimpinan Wilayah</option>
                        <option value="Wakil Pimpinan Wilayah">Wakil Pimpinan Wilayah</option>
                        <option value="Staff Risk Officer">Staff Risk Officer</option>
                        <option value="Klaim dan Subrogasi">Klaim dan Subrogasi</option>
                        <option value="Bisnis">Bisnis</option>
                        <option value="Operasional">Operasional</option>
                        <option value="IT Helpdesk">IT Helpdesk</option>
                    </select><span class="sdm-lock-indicator" aria-hidden="true">🔒</span></div>
                <div class="form-group sdm-lockable is-locked"><label>Kategori Posisi</label><select name="position_group" disabled>
                        <option value="">Pilih kategori posisi</option>
                        <option value="organik">Organik Jamkrindo</option>
                        <option value="outsourcing">Outsourcing</option>
                    </select><span class="sdm-lock-indicator" aria-hidden="true">🔒</span></div>
                <div class="form-group sdm-lockable is-locked"><label>Posisi</label><select name="position" disabled>
                        <option value="">Pilih posisi</option>
                    </select><span class="sdm-lock-indicator" aria-hidden="true">🔒</span></div>
                <div class="form-group"><label>TMT</label><input type="date" name="employment_date"></div>
                <div class="form-group"><label>Status Karyawan <span class="required">*</span></label><select name="employment_status" required>
                        <option value="">Pilih status karyawan</option>
                        <option value="Karyawan Tetap">Karyawan Tetap</option>
                        <option value="Calon Karyawan">Calon Karyawan</option>
                        <option value="PKWT Umum">PKWT Umum</option>
                        <option value="PKWT ELH">PKWT ELH</option>
                        <option value="Magang">Magang</option>
                        <option value="Outsourcing">Outsourcing</option>
                    </select></div>
                <div class="form-group"><label>No. HP</label><input name="phone_number" maxlength="30"></div>
                <div class="form-group sdm-profile-email"><label>Email Korporat</label><input type="email" name="corporate_email" maxlength="190"></div>
            </div>
        </div>
        <footer class="lr-settings-footer"><button type="button" class="btn btn-secondary" data-sdm-profile-close>Batal</button><button type="submit" class="btn btn-primary" data-sdm-profile-submit>Simpan</button></footer>
    </form>
</dialog>

<dialog id="sdmProfileViewDialog" class="lr-settings-dialog sdm-profile-view-dialog" aria-labelledby="sdmProfileViewTitle">
    <header class="lr-settings-header">
        <div>
            <p class="eyebrow">SDM &amp; TELLER</p>
            <h2 id="sdmProfileViewTitle">Detail SDM</h2>
        </div><button type="button" class="icon-btn" data-sdm-profile-view-close aria-label="Tutup">×</button>
    </header>
    <div class="sdm-profile-detail-grid">
        <div><small>Nama Lengkap</small><strong data-sdm-profile-detail="employee_name"></strong></div>
        <div><small>NPP</small><strong data-sdm-profile-detail="employee_no"></strong></div>
        <div><small>Gender</small><strong data-sdm-profile-detail="gender"></strong></div>
        <div><small>Unit Kerja</small><strong data-sdm-profile-detail="work_unit"></strong></div>
        <div><small>Bagian</small><strong data-sdm-profile-detail="organization"></strong></div>
        <div><small>Posisi</small><strong data-sdm-profile-detail="position"></strong></div>
        <div><small>Status Karyawan</small><strong data-sdm-profile-detail="employment_status"></strong></div>
        <div><small>TMT</small><strong data-sdm-profile-detail="employment_date"></strong></div>
        <div><small>No. HP</small><strong data-sdm-profile-detail="phone_number"></strong></div>
        <div class="sdm-profile-detail-email"><small>Email Korporat</small><strong data-sdm-profile-detail="corporate_email"></strong></div>
    </div>
    <footer class="lr-settings-footer"><button type="button" class="btn btn-primary" data-sdm-profile-view-close>Tutup</button></footer>
</dialog>

<dialog id="sdmProfileDeleteDialog" class="lr-settings-dialog" aria-labelledby="sdmProfileDeleteTitle">
    <header class="lr-settings-header">
        <div>
            <p class="eyebrow">SDM &amp; TELLER</p>
            <h2 id="sdmProfileDeleteTitle">Hapus Data SDM</h2>
        </div><button type="button" class="icon-btn" data-sdm-profile-delete-close aria-label="Tutup">×</button>
    </header>
    <form method="post" action="" data-sdm-profile-delete-form>
        <div class="lr-settings-body">
            <p>Hapus data <strong data-sdm-profile-delete-name></strong>? Tindakan ini tidak dapat dibatalkan.</p><?= csrf_field() ?>
        </div>
        <footer class="lr-settings-footer"><button type="button" class="btn btn-secondary" data-sdm-profile-delete-close>Batal</button><button type="submit" class="btn btn-danger">Hapus</button></footer>
    </form>
</dialog>

<?php if ($canImportSdm): ?>
<dialog id="sdmProfileImportDialog" class="lr-settings-dialog" aria-labelledby="sdmProfileImportTitle">
    <header class="lr-settings-header">
        <div>
            <p class="eyebrow">SDM &amp; TELLER</p>
            <h2 id="sdmProfileImportTitle">Import Data SDM</h2>
        </div><button type="button" class="icon-btn" data-sdm-profile-import-close aria-label="Tutup">×</button>
    </header>
    <form method="post" action="<?= site_url('sdm/sdm-jatim/import') ?>" enctype="multipart/form-data" data-sdm-profile-import-form>
        <div class="lr-settings-body">
            <p>Gunakan template Excel per unit kerja. NPP yang sudah ada akan diperbarui; data dengan NPP baru akan ditambahkan.</p>
            <p><a href="<?= site_url('sdm/sdm-jatim/template') ?>">Unduh template import Excel</a></p>
            <?= csrf_field() ?>
            <input type="hidden" name="return_query" value="">
            <div class="form-group"><label for="sdmProfileImportFile">File Excel <span class="required">*</span></label><input id="sdmProfileImportFile" type="file" name="sdm_excel" accept=".xlsx,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" required></div>
            <label class="lr-upload-manual-approval"><input type="checkbox" name="confirm_import" value="yes" required><span><strong>Saya memahami proses import</strong><small>Data lama tidak dihapus otomatis oleh proses ini.</small></span></label>
        </div>
        <footer class="lr-settings-footer"><button type="button" class="btn btn-secondary" data-sdm-profile-import-close>Batal</button><button type="submit" class="btn btn-secondary">Import Data</button></footer>
    </form>
</dialog>
<?php endif ?>

<?php if ($canReviewSdm): ?>
<dialog id="sdmProfileValidationDialog" class="lr-settings-dialog sdm-profile-validation-dialog" aria-labelledby="sdmProfileValidationTitle">
    <header class="lr-settings-header">
        <div>
            <p class="eyebrow">SDM &amp; TELLER</p>
            <h2 id="sdmProfileValidationTitle">Data Perlu Dilengkapi</h2>
        </div><button type="button" class="icon-btn" data-sdm-profile-validation-close aria-label="Tutup">×</button>
    </header>
    <div class="lr-settings-body">
        <?php if ($employeesNeedingReview === []): ?>
            <p>Semua data SDM sudah sesuai dengan aturan sistem.</p>
        <?php else: ?>
            <p>Data berikut perlu dilengkapi atau diperbaiki agar dapat dipetakan ke sistem dan rekap.</p>
            <div class="table-wrap sdm-jatim-table-wrap">
                <table class="sdm-jatim-table">
                    <thead><tr><th>Nama Karyawan</th><th>NPP</th><th>Posisi</th><th>Unit Kerja</th><th>Status Karyawan</th><th>Perlu Dilengkapi</th><th>Aksi</th></tr></thead>
                    <tbody><?php foreach ($employeesNeedingReview as $employee): ?>
                        <?php $profile = ['id' => (int) $employee['id'], 'employee_no' => $employee['employee_no'], 'employee_name' => $employee['employee_name'], 'gender' => $employee['gender'], 'work_unit' => $employee['work_unit'], 'organization' => $employee['organization'], 'position' => $employee['position'], 'employment_status' => $employee['employment_status'], 'employment_date' => $employee['employment_date'], 'phone_number' => $employee['phone_number'], 'corporate_email' => $employee['corporate_email']]; ?>
                        <tr>
                            <td><strong><?= esc($employee['employee_name'] ?: 'Tanpa nama') ?></strong></td>
                            <td><?= esc($employee['employee_no'] ?: '-') ?></td>
                            <td><?= esc($employee['position'] ?: '-') ?></td>
                            <td><?= esc($employee['work_unit'] ?: '—') ?></td>
                            <td><?= esc($employee['employment_status'] ?: '—') ?></td>
                            <td><?= esc(implode(', ', $employee['validation_issues'])) ?></td>
                            <td class="sdm-jatim-actions"><button type="button" class="icon-btn" data-sdm-profile-validation-edit='<?= esc(json_encode($profile, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT), 'attr') ?>' aria-label="Edit <?= esc($employee['employee_name'] ?: 'data SDM', 'attr') ?>">✎</button></td>
                        </tr>
                    <?php endforeach ?></tbody>
                </table>
            </div>
        <?php endif ?>
    </div>
    <footer class="lr-settings-footer"><button type="button" class="btn btn-secondary" data-sdm-profile-validation-close>Tutup</button></footer>
</dialog>
<?php endif ?>

<?php if ($canDeleteAllSdm): ?>
<dialog id="sdmProfileDeleteAllDialog" class="lr-settings-dialog" aria-labelledby="sdmProfileDeleteAllTitle">
    <header class="lr-settings-header">
        <div>
            <p class="eyebrow">SDM &amp; TELLER</p>
            <h2 id="sdmProfileDeleteAllTitle">Hapus Seluruh Data SDM</h2>
        </div><button type="button" class="icon-btn" data-sdm-profile-delete-all-close aria-label="Tutup">×</button>
    </header>
    <form method="post" action="<?= site_url('sdm/sdm-jatim/hapus-semua') ?>">
        <div class="lr-settings-body">
            <p>Seluruh profil SDM akan dihapus permanen dan tidak dapat dipulihkan.</p>
            <?= csrf_field() ?>
            <label class="lr-upload-manual-approval"><input type="checkbox" name="confirm_delete" value="yes" required><span><strong>Saya memahami tindakan ini</strong><small>Hapus seluruh data hanya setelah Anda yakin.</small></span></label>
        </div>
        <footer class="lr-settings-footer"><button type="submit" class="btn btn-danger">Hapus Seluruh Data</button></footer>
    </form>
</dialog>
<?php endif ?>

<dialog id="sdmCompositionSummaryDialog" class="lr-settings-dialog kanwil-sdm-summary-dialog" aria-labelledby="sdmCompositionSummaryTitle">
    <header class="lr-settings-header">
        <div>
            <p class="eyebrow">SDM &amp; TELLER</p>
            <h2 id="sdmCompositionSummaryTitle">Komposisi SDM</h2>
        </div>
        <button type="button" class="icon-btn" data-sdm-composition-close aria-label="Tutup">×</button>
    </header>
    <div class="kanwil-sdm-summary-body">
        <div class="kanwil-sdm-summary-toolbar">
            <p class="kanwil-sdm-summary-note">Kolom rekap dihitung otomatis dari Bagian, Posisi, dan Status Karyawan.</p>
            <label class="kanwil-sdm-kup-select" data-sdm-kup-selector hidden>
                <select data-sdm-kup-select aria-label="Pilih kantor cabang atau Kantor Unit Pelayanan">
                </select>
            </label>
        </div>
        <div class="table-wrap kanwil-sdm-summary-table-wrap">
            <table class="kanwil-sdm-summary-table">
                <thead>
                    <tr><?php foreach (array_merge(['Posisi'], $compositionColumns, ['Jumlah']) as $header): ?><th><?= esc($header) ?></th><?php endforeach ?></tr>
                </thead>
                <tbody>
                    <?php foreach ($compositionPositions as $position): ?>
                        <tr data-sdm-composition-row="<?= esc($position, 'attr') ?>">
                            <th scope="row"><?= esc($position) ?></th><?php foreach ($compositionColumns as $column): ?><td data-sdm-composition-status="<?= esc($column, 'attr') ?>">-</td><?php endforeach ?><td data-sdm-composition-row-total>-</td>
                        </tr>
                    <?php endforeach ?>
                </tbody>
                <tfoot>
                    <tr>
                        <th>Jumlah SDM</th><?php foreach ($compositionColumns as $column): ?><td data-sdm-composition-status-total="<?= esc($column, 'attr') ?>">-</td><?php endforeach ?><td data-sdm-composition-grand-total>-</td>
                    </tr>
                </tfoot>
            </table>
        </div>
        <div class="kanwil-sdm-summary-legend" aria-label="Keterangan warna komposisi SDM">
            <span><i class="is-navy" aria-hidden="true"></i><strong>Navy</strong>: Organik Jamkrindo</span>
            <span><i class="is-teal" aria-hidden="true"></i><strong>Toska</strong>: Outsourcing</span>
        </div>
    </div>
</dialog>

<script>
    (() => {
        const baseUrl = <?= json_encode(site_url('sdm/sdm-jatim')) ?>;
        const formDialog = document.getElementById('sdmProfileDialog');
        const viewDialog = document.getElementById('sdmProfileViewDialog');
        const deleteDialog = document.getElementById('sdmProfileDeleteDialog');
        const deleteAllDialog = document.getElementById('sdmProfileDeleteAllDialog');
        const importDialog = document.getElementById('sdmProfileImportDialog');
        const validationDialog = document.getElementById('sdmProfileValidationDialog');
        const compositionSummaryDialog = document.getElementById('sdmCompositionSummaryDialog');
        const compositionStatusCounts = <?= json_encode($compositionStatusCounts, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
        const form = formDialog?.querySelector('[data-sdm-profile-form]');
        const workUnitField = form?.elements.work_unit;
        const organizationField = form?.elements.organization;
        const positionGroupField = form?.elements.position_group;
        const positionField = form?.elements.position;
        const returnQueryField = form?.elements.return_query;
        const returnToValidationField = form?.elements.return_to_validation;
        const importForm = importDialog?.querySelector('[data-sdm-profile-import-form]');
        const importReturnQueryField = importForm?.elements.return_query;
        form?.addEventListener('submit', () => {
            if (returnQueryField) returnQueryField.value = window.location.search;
        });
        importForm?.addEventListener('submit', () => {
            if (importReturnQueryField) importReturnQueryField.value = window.location.search;
        });
        const syncLockIndicator = field => field?.closest('.sdm-lockable')?.classList.toggle('is-locked', field.disabled);
        const organicStaffPositions = [
            'Staff RM CBC', 'Staff RM CAC', 'Staff RM Subrogasi', 'Staff Bisnis',
            'Staff Klaim', 'Staff Operasional', 'Staff Fungsional',
            'Staff Risk Officer', 'IT Helpdesk', 'Magang',
        ];
        const outsourcingCategoryPositions = ['Admin', 'Driver', 'OB/CS', 'Security'];
        const outsourcingStaffPositions = ['Staff RM CBC', 'Staff RM CAC', 'Staff Bisnis', 'Staff Klaim', 'Staff Operasional'];
        const outsourcingPositions = [...outsourcingCategoryPositions, ...outsourcingStaffPositions];
        const organizationOptionsForUnit = workUnit => {
            const leadership = workUnit.startsWith('Kantor Cabang ')
                ? ['Pemimpin Cabang', 'Wakil Pemimpin Cabang']
                : ['Pimpinan Wilayah', 'Wakil Pimpinan Wilayah'];

            return [
                ...leadership,
                'Staff Risk Officer',
                'Klaim dan Subrogasi',
                'Bisnis',
                'Operasional',
                'IT Helpdesk',
            ];
        };
        const populateOrganizationOptions = (selectedOrganization = '') => {
            if (!organizationField) return;

            const workUnit = String(workUnitField?.value || '');
            organizationField.replaceChildren(new Option('Pilih bagian', ''));
            if (workUnit === '') {
                organizationField.value = '';
                return;
            }

            organizationOptionsForUnit(workUnit).forEach(value => organizationField.append(new Option(value, value)));
            if (selectedOrganization && !Array.from(organizationField.options).some(option => option.value === selectedOrganization)) {
                organizationField.append(new Option(selectedOrganization, selectedOrganization));
            }
            organizationField.value = selectedOrganization;
        };
        const organicPositionOptionsForUnit = workUnit => {
            const isKanwil = workUnit === 'Kantor Wilayah Surabaya';
            const isKup = workUnit.startsWith('Kantor Unit Pelayanan ');
            const leadership = isKanwil
                ? ['Pimpinan Wilayah', 'Wakil Pimpinan Wilayah', 'Kepala Bagian Klaim & Subro', 'Kepala Bagian Bisnis', 'Kepala Bagian Operasional']
                : [
                    ...(isKup ? ['Kepala Kantor Unit Pelayanan'] : ['Pemimpin Cabang']),
                    'Manager Klaim & Subro', 'Manager Bisnis I', 'Manager Bisnis II', 'Manager Operasional',
                ];
            return [...leadership, ...organicStaffPositions];
        };
        const positionGroupFor = (position, employmentStatus = '') => (
            employmentStatus === 'Outsourcing' || outsourcingCategoryPositions.includes(position)
        ) ? 'outsourcing' : 'organik';
        const populatePositionOptions = (selectedPosition = '') => {
            const workUnit = String(workUnitField?.value || '');
            if (!positionField) return;
            positionField.replaceChildren(new Option('Pilih posisi', ''));
            const positionGroup = String(positionGroupField?.value || '');
            positionField.disabled = workUnit === '' || positionGroup === '';
            syncLockIndicator(positionField);
            if (positionField.disabled) return;

            const options = positionGroup === 'outsourcing'
                ? outsourcingPositions
                : organicPositionOptionsForUnit(workUnit);
            options.forEach(value => positionField.append(new Option(value, value)));
            if (selectedPosition && !Array.from(positionField.options).some(option => option.value === selectedPosition)) {
                positionField.append(new Option(selectedPosition, selectedPosition));
            }
            positionField.value = selectedPosition;
        };
        const syncDependentProfileFields = (selectedPosition = '', selectedOrganization = '', employmentStatus = '') => {
            const workUnit = String(workUnitField?.value || '');
            const unlocked = workUnit !== '';
            if (organizationField) {
                organizationField.disabled = !unlocked;
                populateOrganizationOptions(selectedOrganization);
            }
            if (positionGroupField) {
                positionGroupField.disabled = !unlocked;
                positionGroupField.value = selectedPosition ? positionGroupFor(selectedPosition, employmentStatus) : '';
            }
            syncLockIndicator(organizationField);
            syncLockIndicator(positionGroupField);
            populatePositionOptions(selectedPosition);
        };
        const syncPageScroll = () => document.body.classList.toggle('sdm-profile-modal-open', [formDialog, viewDialog, deleteDialog, deleteAllDialog, importDialog, validationDialog, compositionSummaryDialog].some(dialog => dialog?.open));
        const genderLabel = value => value === 'L' ? 'Laki-laki' : (value === 'P' ? 'Perempuan' : value);
        const validationRequested = new URLSearchParams(window.location.search).get('review_data') === '1';
        const open = dialog => {
            if (dialog && !dialog.open) dialog.showModal();
            syncPageScroll();
        };
        const close = dialog => {
            if (dialog?.open) dialog.close();
            syncPageScroll();
        };
        const closeProfileForm = () => {
            const returnToValidation = returnToValidationField?.value === '1';
            close(formDialog);
            if (returnToValidation) open(validationDialog);
        };
        const populate = data => {
            if (!form) return;
            workUnitField.value = data.work_unit || '';
            syncDependentProfileFields(data.position || '', data.organization || '', data.employment_status || '');
            Object.entries(data).forEach(([key, value]) => {
                if (key !== 'work_unit' && form.elements[key]) form.elements[key].value = value || '';
            });
        };
        workUnitField?.addEventListener('change', () => syncDependentProfileFields());
        positionGroupField?.addEventListener('change', () => populatePositionOptions());
        syncDependentProfileFields();
        const kupSelector = compositionSummaryDialog?.querySelector('[data-sdm-kup-selector]');
        const kupSelect = compositionSummaryDialog?.querySelector('[data-sdm-kup-select]');
        const kupUnitsByPrimaryUnit = {
            'Kantor Cabang Banyuwangi': ['Kantor Cabang Banyuwangi', 'Kantor Unit Pelayanan Jember'],
            'Kantor Cabang Surabaya': ['Kantor Cabang Surabaya', 'Kantor Unit Pelayanan Bojonegoro', 'Kantor Unit Pelayanan Pamekasan'],
        };
        let activeCompositionPrimaryUnit = '';
        const formatCompositionCount = count => count > 0 ? String(count) : '-';
        const renderCompositionSummary = workUnit => {
            const counts = compositionStatusCounts[workUnit] || {};
            const totals = {};
            let grandTotal = 0;
            compositionSummaryDialog.querySelector('#sdmCompositionSummaryTitle').textContent = `Komposisi SDM ${workUnit}`.trim();
            compositionSummaryDialog.querySelectorAll('[data-sdm-composition-row]').forEach(row => {
                const rowCounts = counts[row.dataset.sdmCompositionRow] || {};
                let rowTotal = 0;
                row.querySelectorAll('[data-sdm-composition-status]').forEach(cell => {
                    const column = cell.dataset.sdmCompositionStatus;
                    const count = Number(rowCounts[column] || 0);
                    cell.textContent = formatCompositionCount(count);
                    rowTotal += count;
                    totals[column] = (totals[column] || 0) + count;
                });
                row.querySelector('[data-sdm-composition-row-total]').textContent = formatCompositionCount(rowTotal);
                grandTotal += rowTotal;
            });
            compositionSummaryDialog.querySelectorAll('[data-sdm-composition-status-total]').forEach(cell => {
                cell.textContent = formatCompositionCount(totals[cell.dataset.sdmCompositionStatusTotal] || 0);
            });
            compositionSummaryDialog.querySelector('[data-sdm-composition-grand-total]').textContent = formatCompositionCount(grandTotal);
        };
        kupSelect?.addEventListener('change', () => renderCompositionSummary(kupSelect.value || activeCompositionPrimaryUnit));
        document.addEventListener('click', event => {
            const compositionSummaryButton = event.target.closest('[data-sdm-composition-open]');
            if (compositionSummaryButton) {
                event.preventDefault();
                const workUnit = compositionSummaryButton.dataset.workUnit || '';
                activeCompositionPrimaryUnit = workUnit;
                const kupUnits = kupUnitsByPrimaryUnit[workUnit] || [];
                if (kupSelector) kupSelector.hidden = kupUnits.length === 0;
                if (kupSelect) {
                    kupSelect.replaceChildren(...kupUnits.map(unit => new Option(unit, unit)));
                    kupSelect.value = workUnit;
                }
                renderCompositionSummary(workUnit);
                open(compositionSummaryDialog);
                return;
            }
            const importButton = event.target.closest('[data-sdm-profile-import]');
            if (importButton) {
                importForm?.reset();
                open(importDialog);
                return;
            }
            const validationButton = event.target.closest('[data-sdm-profile-validation]');
            if (validationButton) {
                validationButton.closest('details')?.removeAttribute('open');
                open(validationDialog);
                return;
            }
            const validationEditButton = event.target.closest('[data-sdm-profile-validation-edit]');
            if (validationEditButton) {
                const data = JSON.parse(validationEditButton.dataset.sdmProfileValidationEdit);
                close(validationDialog);
                form.reset();
                if (returnToValidationField) returnToValidationField.value = '1';
                populate(data);
                form.action = `${baseUrl}/${data.id}`;
                formDialog.querySelector('h2').textContent = 'Ubah SDM';
                form.querySelector('[data-sdm-profile-submit]').textContent = 'Simpan Perubahan';
                open(formDialog);
                return;
            }
            const createButton = event.target.closest('[data-sdm-profile-create]');
            if (createButton) {
                createButton.closest('details')?.removeAttribute('open');
                form.reset();
                if (returnToValidationField) returnToValidationField.value = '0';
                syncDependentProfileFields();
                form.action = baseUrl;
                formDialog.querySelector('h2').textContent = 'Tambah SDM';
                form.querySelector('[data-sdm-profile-submit]').textContent = 'Simpan';
                open(formDialog);
                return;
            }
            const deleteAllButton = event.target.closest('[data-sdm-profile-delete-all]');
            if (deleteAllButton) {
                deleteAllButton.closest('details')?.removeAttribute('open');
                deleteAllDialog?.querySelector('form')?.reset();
                open(deleteAllDialog);
                return;
            }
            const editButton = event.target.closest('[data-sdm-profile-edit]');
            if (editButton) {
                const data = JSON.parse(editButton.dataset.sdmProfileEdit);
                form.reset();
                if (returnToValidationField) returnToValidationField.value = '0';
                populate(data);
                form.action = `${baseUrl}/${data.id}`;
                formDialog.querySelector('h2').textContent = 'Ubah SDM';
                form.querySelector('[data-sdm-profile-submit]').textContent = 'Simpan Perubahan';
                open(formDialog);
                return;
            }
            const viewButton = event.target.closest('[data-sdm-profile-view]');
            if (viewButton) {
                const data = JSON.parse(viewButton.dataset.sdmProfileView);
                viewDialog.querySelectorAll('[data-sdm-profile-detail]').forEach(field => {
                    const value = data[field.dataset.sdmProfileDetail] || '';
                    field.textContent = field.dataset.sdmProfileDetail === 'gender' ? (genderLabel(value) || '—') : (value || '—');
                });
                open(viewDialog);
                return;
            }
            const deleteButton = event.target.closest('[data-sdm-profile-delete]');
            if (deleteButton) {
                deleteDialog.querySelector('[data-sdm-profile-delete-name]').textContent = deleteButton.dataset.name;
                deleteDialog.querySelector('[data-sdm-profile-delete-form]').action = `${baseUrl}/${deleteButton.dataset.id}/hapus`;
                open(deleteDialog);
            }
        });
        document.querySelectorAll('[data-sdm-profile-close]').forEach(button => button.addEventListener('click', closeProfileForm));
        document.querySelectorAll('[data-sdm-profile-view-close]').forEach(button => button.addEventListener('click', () => close(viewDialog)));
        document.querySelectorAll('[data-sdm-profile-delete-close]').forEach(button => button.addEventListener('click', () => close(deleteDialog)));
        document.querySelectorAll('[data-sdm-profile-delete-all-close]').forEach(button => button.addEventListener('click', () => close(deleteAllDialog)));
        document.querySelectorAll('[data-sdm-profile-import-close]').forEach(button => button.addEventListener('click', () => close(importDialog)));
        document.querySelectorAll('[data-sdm-profile-validation-close]').forEach(button => button.addEventListener('click', () => close(validationDialog)));
        document.querySelectorAll('[data-sdm-composition-close]').forEach(button => button.addEventListener('click', () => close(compositionSummaryDialog)));
        [viewDialog, deleteDialog].forEach(dialog => {
            dialog?.addEventListener('click', event => {
                if (event.target === dialog) close(dialog);
            });
            dialog?.addEventListener('close', syncPageScroll);
        });
        formDialog?.addEventListener('click', event => {
            if (event.target === formDialog) closeProfileForm();
        });
        formDialog?.addEventListener('cancel', event => {
            if (returnToValidationField?.value === '1') {
                event.preventDefault();
                closeProfileForm();
            }
        });
        formDialog?.addEventListener('close', syncPageScroll);
        compositionSummaryDialog?.addEventListener('cancel', event => event.preventDefault());
        compositionSummaryDialog?.addEventListener('close', syncPageScroll);
        deleteAllDialog?.addEventListener('cancel', event => event.preventDefault());
        deleteAllDialog?.addEventListener('close', syncPageScroll);
        importDialog?.addEventListener('cancel', event => event.preventDefault());
        importDialog?.addEventListener('close', syncPageScroll);
        validationDialog?.addEventListener('cancel', event => event.preventDefault());
        validationDialog?.addEventListener('close', syncPageScroll);
        if (validationRequested) open(validationDialog);
    })();
</script>

<?= view('components/async_table_card', ['cardId' => 'sdmJatimTableArea', 'filterFormSelector' => '.sdm-list-filter-form']) ?>

<?= $this->endSection() ?>
