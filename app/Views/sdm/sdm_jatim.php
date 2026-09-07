<?php
/** @var array<string, mixed>|null $report */
/** @var string|null $importError */
/** @var list<array<string, mixed>> $attendanceImports */
/** @var int|string|null $selectedImportId */
/** @var array<string, mixed> $attendanceFilters */
?>
<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<section class="page-heading sdm-attendance-heading">
    <div>
        <p class="eyebrow">SDM &amp; TELLER</p>
        <h1>SDM Jatim</h1>
        <p>Rekap absensi bulanan dari Employee Attendance Report ESS.</p>
    </div>
</section>

<?php if ($importError !== null): ?>
    <div class="alert alert-danger sdm-attendance-alert" role="alert">
        <div><strong>File belum dapat direkap</strong><p><?= esc($importError) ?></p></div>
    </div>
<?php endif ?>

<section class="panel sdm-attendance-import-panel">
    <div class="panel-header">
        <div>
            <h2>Import laporan ESS</h2>
            <p>Gunakan file Employee Attendance Report ESS berformat .xls, maksimal 5 MB.</p>
        </div>
    </div>
    <form action="<?= site_url('sdm/sdm-jatim/rekap') ?>" method="post" enctype="multipart/form-data" class="sdm-attendance-import-form">
        <?= csrf_field() ?>
        <div class="form-group">
            <label for="attendance_file">File laporan ESS <span class="required">*</span></label>
            <input id="attendance_file" type="file" name="attendance_file" accept=".xls,.html,.htm" required>
            <small>Setelah rekap berhasil, data absensi akan tersimpan otomatis ke database.</small>
        </div>
        <button type="submit" class="btn btn-primary">Rekap &amp; Simpan</button>
    </form>
</section>

<?php if ($attendanceImports !== []): ?>
    <section class="panel filter-panel sdm-attendance-filter-panel">
        <form method="get" action="<?= site_url('sdm/sdm-jatim') ?>" class="sdm-attendance-period-form">
            <input type="hidden" name="mode" value="<?= esc($attendanceFilters['mode'] ?? 'summary', 'attr') ?>">
            <div class="form-group">
                <label for="attendance_import_id">Periode rekap tersimpan</label>
                <select id="attendance_import_id" name="import_id">
                    <?php foreach ($attendanceImports as $import): ?>
                        <option value="<?= (int) $import['id'] ?>" <?= $selectedImportId === (int) $import['id'] ? 'selected' : '' ?>>
                            <?= esc($import['period_label']) ?>
                        </option>
                    <?php endforeach ?>
                </select>
            </div>
            <div class="form-group">
                <label for="attendance_name">Nama karyawan</label>
                <input
                    id="attendance_name"
                    type="search"
                    name="nama"
                    value="<?= esc($attendanceFilters['name'] ?? '', 'attr') ?>"
                    placeholder="Cari nama karyawan"
                    autocomplete="off"
                >
            </div>
            <div class="form-group">
                <label for="attendance_from">Dari tanggal</label>
                <input
                    id="attendance_from"
                    type="date"
                    name="dari"
                    value="<?= esc($attendanceFilters['from'] ?? '', 'attr') ?>"
                    min="<?= esc($report['period_start'] ?? '', 'attr') ?>"
                    max="<?= esc($report['period_end'] ?? '', 'attr') ?>"
                >
            </div>
            <div class="form-group">
                <label for="attendance_to">Sampai tanggal</label>
                <input
                    id="attendance_to"
                    type="date"
                    name="sampai"
                    value="<?= esc($attendanceFilters['to'] ?? '', 'attr') ?>"
                    min="<?= esc($report['period_start'] ?? '', 'attr') ?>"
                    max="<?= esc($report['period_end'] ?? '', 'attr') ?>"
                >
            </div>
            <div class="filter-actions">
                <button type="submit" class="btn btn-secondary">Terapkan</button>
                <a href="<?= site_url('sdm/sdm-jatim?import_id=' . (int) $selectedImportId) ?>" class="btn btn-ghost">Reset</a>
                <button type="button" class="btn btn-danger-outline" data-open-attendance-delete>Hapus Rekap</button>
            </div>
        </form>
    </section>
<?php endif ?>

<?php if ($report !== null): ?>
    <?php
    $statusLabels = [
        'H' => 'Hadir',
        'I' => 'Izin / sakit / cuti',
        'A' => 'Alpa',
        'TA' => 'Presensi tidak lengkap',
        'TAM' => 'Hanya absen pulang',
        'TAP' => 'Hanya absen masuk',
        'OFF' => 'Libur',
        '-' => 'Data belum lengkap',
    ];
    $statusDisplayCodes = ['-' => 'BL'];
    $displayStartDay = (int) $report['display_start_day'];
    $displayEndDay = (int) $report['display_end_day'];
    $displayDayCount = ($displayEndDay - $displayStartDay) + 1;
    $weekendDays = [];
    $dayNames = [1 => 'Sen', 2 => 'Sel', 3 => 'Rab', 4 => 'Kam', 5 => 'Jum', 6 => 'Sab', 7 => 'Min'];
    $periodPrefix = substr($report['period_start'], 0, 7);
    for ($day = $displayStartDay; $day <= $displayEndDay; $day++) {
        $weekendDays[$day] = (int) (new DateTimeImmutable($periodPrefix . '-' . str_pad((string) $day, 2, '0', STR_PAD_LEFT)))->format('N') >= 6;
    }
    $viewMode = $attendanceFilters['mode'] ?? 'summary';
    $viewQuery = array_filter([
        'import_id' => $selectedImportId,
        'nama' => $attendanceFilters['name'] ?? '',
        'dari' => $attendanceFilters['from'] ?? '',
        'sampai' => $attendanceFilters['to'] ?? '',
    ], static fn ($value): bool => $value !== '' && $value !== null);
    $summaryUrl = site_url('sdm/sdm-jatim') . '?' . http_build_query(array_merge($viewQuery, ['mode' => 'summary']));
    $detailUrl = site_url('sdm/sdm-jatim') . '?' . http_build_query(array_merge($viewQuery, ['mode' => 'detail']));
    ?>

    <?php if ($report['warnings'] !== [] || $report['anomalies'] !== []): ?>
        <div class="alert alert-warning sdm-attendance-alert sdm-attendance-anomaly-alert" role="status">
            <div>
                <strong>Perlu diperiksa</strong>
                <?php foreach ($report['warnings'] as $warning): ?><p><?= esc($warning) ?></p><?php endforeach ?>
                <?php if ($report['anomalies'] !== []): ?>
                    <p><?= count($report['anomalies']) ?> data berstatus Alpa atau Presensi Tidak Lengkap.</p>
                <?php endif ?>
            </div>
            <?php if ($report['anomalies'] !== []): ?>
                <button type="button" class="btn btn-secondary btn-sm" data-open-attendance-anomaly>Edit Data Anomali</button>
            <?php endif ?>
        </div>
    <?php endif ?>

    <?php if ($report['anomalies'] !== []): ?>
        <div class="input-modal attendance-anomaly-modal" id="attendanceAnomalyModal" hidden aria-hidden="true">
            <button type="button" class="modal-backdrop" data-close-attendance-anomaly aria-label="Tutup editor anomali"></button>
            <section class="modal-dialog attendance-anomaly-dialog" role="dialog" aria-modal="true" aria-labelledby="attendanceAnomalyTitle">
                <header class="modal-header">
                    <div class="modal-title-group"><span class="modal-title-icon">!</span><div><p>SDM JATIM</p><h2 id="attendanceAnomalyTitle">Edit Data Anomali</h2></div></div>
                    <button type="button" class="modal-close" data-close-attendance-anomaly aria-label="Tutup">×</button>
                </header>
                <form action="<?= site_url('sdm/sdm-jatim/anomali') ?>" method="post">
                    <?= csrf_field() ?>
                    <input type="hidden" name="import_id" value="<?= (int) $selectedImportId ?>">
                    <div class="modal-body attendance-anomaly-body">
                        <div class="attendance-anomaly-intro"><div><strong>Data yang perlu diperiksa</strong><small>Hanya data Alpa dan Presensi Tidak Lengkap yang dapat dikoreksi.</small></div><span class="sdm-attendance-anomaly-count"><?= count($report['anomalies']) ?> data</span></div>
                        <div class="table-wrap sdm-attendance-anomaly-scroll">
                            <table class="sdm-attendance-anomaly-table">
                                <thead><tr><th>No</th><th>Tanggal</th><th>NIK</th><th>Nama Karyawan</th><th>Kode Rekap</th><th>Jam Masuk</th><th>Jam Pulang</th><th>Status Koreksi</th><th>Keterangan</th></tr></thead>
                                <tbody>
                                    <?php foreach ($report['anomalies'] as $index => $anomaly): ?>
                                        <tr>
                                            <td><?= $index + 1 ?></td><td><?= date('d-m-Y', strtotime($anomaly['attendance_date'])) ?></td><td><?= esc($anomaly['employee_no']) ?></td><td><strong><?= esc($anomaly['employee_name']) ?></strong></td>
                                            <td><span class="attendance-summary-badge <?= $anomaly['recap_code'] === 'A' ? 'attendance-summary-a' : 'attendance-summary-ta' ?>" title="Status ESS: <?= esc($anomaly['raw_status'] ?: '-', 'attr') ?>"><?= esc($anomaly['recap_code']) ?></span></td>
                                            <td><input type="time" name="records[<?= (int) $anomaly['id'] ?>][actual_in]" value="<?= esc(substr((string) $anomaly['actual_in'], 0, 5), 'attr') ?>"></td>
                                            <td><input type="time" name="records[<?= (int) $anomaly['id'] ?>][actual_out]" value="<?= esc(substr((string) $anomaly['actual_out'], 0, 5), 'attr') ?>"></td>
                                            <td><select name="records[<?= (int) $anomaly['id'] ?>][recap_code]" required><?php foreach (['H' => 'Hadir', 'I' => 'Izin', 'A' => 'Alpa', 'TA' => 'Tidak Lengkap', 'TAM' => 'Hanya Absen Pulang', 'TAP' => 'Hanya Absen Masuk', 'OFF' => 'Libur'] as $code => $label): ?><option value="<?= $code ?>" <?= $anomaly['recap_code'] === $code ? 'selected' : '' ?>><?= esc($label) ?></option><?php endforeach ?></select></td>
                                            <td><input type="text" name="records[<?= (int) $anomaly['id'] ?>][remark]" value="<?= esc($anomaly['remark'] ?? '', 'attr') ?>" maxlength="500" placeholder="Alasan koreksi"></td>
                                        </tr>
                                    <?php endforeach ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <footer class="modal-footer"><button type="button" class="btn btn-ghost" data-close-attendance-anomaly>Batal</button><button type="submit" class="btn btn-primary">Simpan Perubahan</button></footer>
                </form>
            </section>
        </div>
    <?php endif ?>

    <section class="sdm-attendance-summary" aria-label="Ringkasan absensi">
        <article><small>PERIODE</small><strong><?= esc($report['period_label']) ?></strong><span><?= esc($report['source_name']) ?></span></article>
        <article><small>KARYAWAN</small><strong><?= number_format($report['summary']['EMPLOYEES'], 0, ',', '.') ?></strong><span><?= number_format($report['summary']['ROWS'], 0, ',', '.') ?> baris ESS</span></article>
        <article><small>HADIR</small><strong><?= number_format($report['summary']['H'], 0, ',', '.') ?></strong><span>Presensi lengkap</span></article>
        <article><small>PERLU DIPERIKSA</small><strong><?= number_format($report['summary']['A'] + $report['summary']['TA'] + $report['summary']['TAM'] + $report['summary']['TAP'] + $report['summary']['BL'], 0, ',', '.') ?></strong><span>Alpa, presensi atau data belum lengkap</span></article>
    </section>

    <section class="panel register-panel agendaris-table-panel sdm-attendance-recap-panel <?= $viewMode === 'summary' ? 'sdm-attendance-summary-table-panel' : '' ?>">
        <div class="panel-header">
            <div>
                <h2><?= $viewMode === 'summary' ? 'Ringkasan Absensi Karyawan' : 'Detail Absensi Harian' ?></h2>
                <p>
                    <?= $viewMode === 'summary'
                        ? 'Ringkasan hasil absensi agar lebih cepat dibaca.'
                        : 'Status harian karyawan untuk tanggal ' . $displayStartDay . '–' . $displayEndDay . '.' ?>
                </p>
            </div>
            <div class="sdm-attendance-view-tools">
                <?php if ($viewMode === 'detail'): ?>
                    <div class="sdm-attendance-legend" aria-label="Keterangan kode absensi">
                        <?php foreach ($statusLabels as $code => $label): ?>
                            <span><i class="attendance-code attendance-code-<?= esc(strtolower(str_replace('-', 'empty', $code)), 'attr') ?>"><?= esc($statusDisplayCodes[$code] ?? $code) ?></i><?= esc($label) ?></span>
                        <?php endforeach ?>
                    </div>
                <?php endif ?>
                <nav class="sdm-attendance-view-switch" aria-label="Pilihan tampilan absensi">
                    <a href="<?= esc($summaryUrl, 'attr') ?>" class="<?= $viewMode === 'summary' ? 'is-active' : '' ?>">Ringkas</a>
                    <a href="<?= esc($detailUrl, 'attr') ?>" class="<?= $viewMode === 'detail' ? 'is-active' : '' ?>">Detail Harian</a>
                </nav>
            </div>
        </div>

        <?php if ($viewMode === 'summary'): ?>
            <div class="table-wrap sdm-attendance-table-scroll">
                <table class="sdm-attendance-overview-table">
                    <thead>
                        <tr>
                            <th>No</th><th>NIK</th><th>Nama Karyawan</th><th>Unit Organisasi</th>
                            <th>Hadir</th><th>Izin</th><th>Alpa</th><th>TA</th><th>TAM</th><th>TAP</th><th>Libur</th><th>Kehadiran</th><th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($report['employees'] === []): ?>
                            <tr><td colspan="13" class="sdm-attendance-filter-empty"><div class="empty-state"><span aria-hidden="true">⌕</span><strong>Data tidak ditemukan</strong><p>Ubah nama atau rentang tanggal, lalu coba kembali.</p></div></td></tr>
                        <?php else: ?>
                            <?php foreach ($report['employees'] as $index => $employee): ?>
                                <?php $employeeDetailUrl = site_url('sdm/sdm-jatim') . '?' . http_build_query(array_merge($viewQuery, ['mode' => 'detail', 'pegawai' => $employee['employee_key']])); ?>
                                <tr>
                                    <td><?= $index + 1 ?></td>
                                    <td><?= esc($employee['employee_no']) ?></td>
                                    <td><strong><?= esc($employee['employee_name']) ?></strong></td>
                                    <td><?= esc($employee['organization']) ?></td>
                                    <td><span class="attendance-summary-badge attendance-summary-h"><?= $employee['totals']['H'] ?></span></td>
                                    <td><span class="attendance-summary-badge attendance-summary-i"><?= $employee['totals']['I'] ?></span></td>
                                    <td><span class="attendance-summary-badge attendance-summary-a"><?= $employee['totals']['A'] ?></span></td>
                                    <td><span class="attendance-summary-badge attendance-summary-ta"><?= $employee['totals']['TA'] ?></span></td>
                                    <td><span class="attendance-summary-badge attendance-summary-tam"><?= $employee['totals']['TAM'] ?></span></td>
                                    <td><span class="attendance-summary-badge attendance-summary-tap"><?= $employee['totals']['TAP'] ?></span></td>
                                    <td><span class="attendance-summary-badge attendance-summary-off"><?= $employee['totals']['OFF'] ?></span></td>
                                    <td><strong class="attendance-summary-rate"><?= $employee['attendance_rate'] !== null ? number_format($employee['attendance_rate'], 1, ',', '.') . '%' : '-' ?></strong></td>
                                    <td><a href="<?= esc($employeeDetailUrl, 'attr') ?>" class="btn btn-secondary btn-sm">Lihat Detail</a></td>
                                </tr>
                            <?php endforeach ?>
                        <?php endif ?>
                    </tbody>
                </table>
            </div>
        <?php elseif ($report['employees'] === []): ?>
            <div class="sdm-attendance-filter-empty"><div class="empty-state"><span aria-hidden="true">⌕</span><strong>Data tidak ditemukan</strong><p>Ubah nama atau rentang tanggal, lalu coba kembali.</p></div></div>
        <?php else: ?>
            <div class="table-wrap sdm-attendance-table-scroll">
                <table class="sdm-attendance-table">
                    <thead>
                        <tr>
                            <th rowspan="2" class="attendance-fixed attendance-no">No</th>
                            <th rowspan="2" class="attendance-fixed attendance-nik">NIK</th>
                            <th rowspan="2" class="attendance-fixed attendance-name">Nama Karyawan</th>
                            <th rowspan="2" class="attendance-fixed attendance-unit">Unit Organisasi</th>
                            <th colspan="<?= $displayDayCount ?>">Tanggal Absensi (<?= $displayStartDay ?> - <?= $displayEndDay ?>)</th>
                            <th colspan="9">Rekap</th>
                        </tr>
                        <tr>
                            <?php for ($day = $displayStartDay; $day <= $displayEndDay; $day++): ?>
                                <?php $dayNumber = (int) (new DateTimeImmutable($periodPrefix . '-' . str_pad((string) $day, 2, '0', STR_PAD_LEFT)))->format('N'); ?>
                                <th class="attendance-day-heading attendance-time-heading <?= $weekendDays[$day] ? 'attendance-weekend' : '' ?>"><span><?= $day ?></span> <small><?= $dayNames[$dayNumber] ?></small></th>
                            <?php endfor ?>
                            <th>H</th><th>I</th><th>A</th><th>TA</th><th>TAM</th><th>TAP</th><th>OFF</th><th>BL</th><th>% Hadir</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($report['employees'] as $index => $employee): ?>
                            <tr>
                                <td class="attendance-fixed attendance-no"><?= $index + 1 ?></td>
                                <td class="attendance-fixed attendance-nik"><?= esc($employee['employee_no']) ?></td>
                                <td class="attendance-fixed attendance-name"><?= esc($employee['employee_name']) ?></td>
                                <td class="attendance-fixed attendance-unit"><?= esc($employee['organization']) ?></td>
                                <?php for ($day = $displayStartDay; $day <= $displayEndDay; $day++): ?>
                                    <?php
                                    $dayDetail = $employee['day_details'][$day] ?? null;
                                    $code = $dayDetail['recap_code'] ?? '-';
                                    $actualIn = ! empty($dayDetail['actual_in']) ? substr((string) $dayDetail['actual_in'], 0, 5) : null;
                                    $actualOut = ! empty($dayDetail['actual_out']) ? substr((string) $dayDetail['actual_out'], 0, 5) : null;
                                    ?>
                                    <td class="attendance-code attendance-time-cell attendance-code-<?= esc(strtolower(str_replace('-', 'empty', $code)), 'attr') ?> <?= $weekendDays[$day] ? 'attendance-weekend' : '' ?>" title="<?= esc($statusLabels[$code] ?? $code, 'attr') ?>">
                                        <strong><?= esc($statusDisplayCodes[$code] ?? $code) ?></strong>
                                        <?php if ($actualIn !== null): ?><span>↑ <?= esc($actualIn) ?></span><?php endif ?>
                                        <?php if ($actualOut !== null): ?><span>↓ <?= esc($actualOut) ?></span><?php endif ?>
                                    </td>
                                <?php endfor ?>
                                <td class="attendance-total"><?= $employee['totals']['H'] ?></td><td class="attendance-total"><?= $employee['totals']['I'] ?></td><td class="attendance-total"><?= $employee['totals']['A'] ?></td><td class="attendance-total"><?= $employee['totals']['TA'] ?></td><td class="attendance-total"><?= $employee['totals']['TAM'] ?></td><td class="attendance-total"><?= $employee['totals']['TAP'] ?></td><td class="attendance-total"><?= $employee['totals']['OFF'] ?></td><td class="attendance-total attendance-total-bl"><?= $employee['totals']['BL'] ?></td>
                                <td class="attendance-rate"><?= $employee['attendance_rate'] !== null ? number_format($employee['attendance_rate'], 1, ',', '.') . '%' : '-' ?></td>
                            </tr>
                        <?php endforeach ?>
                    </tbody>
                </table>
            </div>
        <?php endif ?>
        <div class="table-list-footer sdm-attendance-table-footer">
            <span><strong><?= number_format($report['summary']['EMPLOYEES'], 0, ',', '.') ?></strong> karyawan ditampilkan</span>
            <span>Disimpan <?= date('d-m-Y H:i', strtotime($report['imported_at'])) ?> WIB oleh <strong><?= esc($report['imported_by_name']) ?></strong></span>
        </div>
        <footer class="sdm-attendance-note"><strong>Dasar pemetaan:</strong> PRS lengkap = H, hanya absen masuk = TAP, hanya absen pulang = TAM, presensi tidak lengkap lainnya = TA, CB2/RI/CT = I, ABS = A, dan OFF = hari libur.</footer>
    </section>

    <div class="delete-modal attendance-recap-delete-modal" id="attendanceRecapDeleteModal" hidden aria-hidden="true">
        <button type="button" class="modal-backdrop" data-close-attendance-delete aria-label="Batal hapus rekap"></button>
        <section class="modal-dialog delete-modal-dialog" role="alertdialog" aria-modal="true" aria-labelledby="attendanceDeleteTitle">
            <div class="delete-modal-body">
                <span class="delete-warning-icon">!</span>
                <h2 id="attendanceDeleteTitle">Hapus Rekap Absensi?</h2>
                <p>Rekap periode <strong><?= esc($report['period_label']) ?></strong> beserta seluruh data absensinya <?= (string) session()->get('auth_role') === 'admin' ? 'akan dihapus permanen dan tidak dapat dipulihkan.' : 'akan dihapus dari daftar.' ?></p>
            </div>
            <form action="<?= site_url('sdm/sdm-jatim/hapus') ?>" method="post" class="delete-modal-actions">
                <?= csrf_field() ?>
                <input type="hidden" name="import_id" value="<?= (int) $selectedImportId ?>">
                <button type="button" class="btn btn-ghost" data-close-attendance-delete>Batal</button>
                <button type="submit" class="btn btn-delete"><?= (string) session()->get('auth_role') === 'admin' ? 'Ya, hapus permanen' : 'Ya, hapus' ?></button>
            </form>
        </section>
    </div>
<?php else: ?>
    <section class="panel register-panel sdm-attendance-empty-panel">
        <div class="empty-state">
            <span aria-hidden="true">▦</span>
            <strong>Belum ada rekap absensi</strong>
            <p>Pilih laporan ESS di atas untuk membuat Database Absensi Bulanan.</p>
        </div>
    </section>
<?php endif ?>

<?= $this->endSection() ?>
