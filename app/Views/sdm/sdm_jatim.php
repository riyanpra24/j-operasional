<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<?php
/** @var array<string, mixed>|null $report */
/** @var list<array<string, mixed>> $attendanceImports */
/** @var int|null $selectedImportId */
/** @var array<string, string> $attendanceFilters */

$statusLabels = [
    'H' => 'Hadir tepat waktu',
    'TLBT' => 'Terlambat',
    'I' => 'Izin / sakit / cuti',
    'A' => 'Alpa',
    'TA' => 'Tidak absen masuk dan pulang',
    'TAM' => 'Tidak absen masuk',
    'TAP' => 'Tidak absen pulang',
    'TPA' => 'Masuk tidak absen',
    'OFF' => 'Libur / akhir pekan',
];
$displayCodes = ['TLBT' => 'TL'];
$dayNames = [1 => 'Sen', 2 => 'Sel', 3 => 'Rab', 4 => 'Kam', 5 => 'Jum', 6 => 'Sab', 7 => 'Min'];
?>

<section class="page-heading sdm-daily-heading">
    <div>
        <p class="eyebrow">SDM &amp; TELLER</p>
        <h1>Data Kehadiran</h1>
        <p>Detail kode kehadiran seluruh karyawan dalam satu periode.</p>
    </div>
</section>

<?php if ($importError !== null): ?>
    <div class="alert alert-danger" role="alert"><strong>Data belum dapat ditampilkan.</strong><p><?= esc($importError) ?></p></div>
<?php endif ?>

<?php if ($attendanceImports !== []): ?>
    <section class="panel filter-panel sdm-daily-filter-panel">
        <form method="get" action="<?= site_url('sdm/data-kehadiran') ?>" class="sdm-daily-filter-form">
            <div class="form-group">
                <label for="attendance_import_id">Periode rekap</label>
                <select id="attendance_import_id" name="import_id">
                    <?php foreach ($attendanceImports as $import): ?>
                        <option value="<?= (int) $import['id'] ?>" <?= $selectedImportId === (int) $import['id'] ? 'selected' : '' ?>><?= esc($import['period_label']) ?></option>
                    <?php endforeach ?>
                </select>
            </div>
            <div class="form-group">
                <label for="attendance_name">Nama karyawan</label>
                <input id="attendance_name" type="search" name="nama" value="<?= esc($attendanceFilters['name'] ?? '', 'attr') ?>" placeholder="Cari nama karyawan" autocomplete="off">
            </div>
            <div class="form-group">
                <label for="attendance_from">Dari tanggal</label>
                <input id="attendance_from" type="date" name="dari" value="<?= esc($attendanceFilters['from'] ?? '', 'attr') ?>" min="<?= esc($report['period_start'] ?? '', 'attr') ?>" max="<?= esc($report['period_end'] ?? '', 'attr') ?>">
            </div>
            <div class="form-group">
                <label for="attendance_to">Sampai tanggal</label>
                <input id="attendance_to" type="date" name="sampai" value="<?= esc($attendanceFilters['to'] ?? '', 'attr') ?>" min="<?= esc($report['period_start'] ?? '', 'attr') ?>" max="<?= esc($report['period_end'] ?? '', 'attr') ?>">
            </div>
            <div class="sdm-daily-filter-actions">
                <button type="submit" class="btn btn-primary">Tampilkan</button>
                <a href="<?= site_url('sdm/data-kehadiran' . ($selectedImportId !== null ? '?import_id=' . (int) $selectedImportId : '')) ?>" class="btn btn-ghost">Reset</a>
                <details class="sdm-daily-menu">
                    <summary class="sdm-daily-menu-trigger" aria-label="Menu tindakan" title="Menu tindakan">
                        <svg aria-hidden="true" viewBox="0 0 24 24" focusable="false"><rect x="3" y="3" width="7" height="7" rx="1.4"></rect><rect x="14" y="3" width="7" height="7" rx="1.4"></rect><rect x="3" y="14" width="7" height="7" rx="1.4"></rect><rect x="14" y="14" width="7" height="7" rx="1.4"></rect></svg>
                    </summary>
                    <div class="sdm-daily-menu-list">
                        <button type="button" data-open-attendance-upload>Unggah Laporan Kehadiran</button>
                        <span class="sdm-daily-menu-divider" aria-hidden="true"></span>
                        <button type="button" class="is-danger" data-open-attendance-delete>Hapus Laporan Kehadiran</button>
                    </div>
                </details>
            </div>
        </form>
    </section>
<?php endif ?>

<?php if ($report !== null): ?>
    <?php
    $displayStartDay = (int) $report['display_start_day'];
    $displayEndDay = (int) $report['display_end_day'];
    $periodPrefix = substr((string) $report['period_start'], 0, 7);
    ?>
    <section class="panel sdm-daily-panel">
        <header class="sdm-daily-panel-header">
            <div>
                <h2>Data Kehadiran Karyawan</h2>
                <p><?= date('d-m-Y', strtotime($report['period_start'])) ?> sampai <?= date('d-m-Y', strtotime($report['period_end'])) ?></p>
            </div>
        </header>

        <div class="sdm-daily-legend" aria-label="Keterangan kode absensi">
            <strong>Keterangan:</strong>
            <?php foreach ($statusLabels as $code => $label): ?>
                <span><i class="sdm-daily-code sdm-daily-code-<?= esc(strtolower(str_replace('-', 'empty', $code)), 'attr') ?>"><?= esc($displayCodes[$code] ?? $code) ?></i><?= esc($label) ?></span>
            <?php endforeach ?>
        </div>

        <?php if ($report['employees'] === []): ?>
            <div class="empty-state sdm-daily-empty"><span aria-hidden="true">⌕</span><strong>Data tidak ditemukan</strong><p>Ubah filter nama atau rentang tanggal, lalu coba kembali.</p></div>
        <?php else: ?>
            <div class="sdm-daily-table-wrap">
                <table class="sdm-daily-table sdm-staff-attendance-table">
                    <thead>
                        <tr>
                            <th class="sdm-daily-fixed sdm-daily-no">No</th>
                            <th class="sdm-daily-fixed sdm-daily-npp">NPP</th>
                            <th class="sdm-daily-fixed sdm-daily-name">Nama Karyawan</th>
                            <th class="sdm-daily-fixed sdm-daily-position">Jabatan</th>
                            <th class="sdm-daily-fixed sdm-daily-unit">Bagian</th>
                            <th class="sdm-daily-total-heading">Total Hadir</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($report['employees'] as $index => $employee): ?>
                            <?php
                            $totalPresent = (int) $employee['totals']['H'] + (int) $employee['totals']['TLBT'] + (int) $employee['totals']['TA'] + (int) $employee['totals']['TAM'] + (int) $employee['totals']['TAP'] + (int) ($employee['totals']['TPA'] ?? 0);
                            $detailId = 'attendance-staff-' . $index;
                            ?>
                            <tr>
                                <td class="sdm-daily-fixed sdm-daily-no"><?= $index + 1 ?></td>
                                <td class="sdm-daily-fixed sdm-daily-npp"><?= esc($employee['employee_no']) ?></td>
                                <td class="sdm-daily-fixed sdm-daily-name"><button type="button" class="sdm-staff-toggle" data-attendance-staff-toggle aria-expanded="false" aria-controls="<?= esc($detailId, 'attr') ?>"><i aria-hidden="true">›</i><span><strong><?= esc($employee['employee_name']) ?></strong><small>Lihat detail kehadiran</small></span></button></td>
                                <td class="sdm-daily-fixed sdm-daily-position"><?= esc($employee['position'] ?: '-') ?></td>
                                <td class="sdm-daily-fixed sdm-daily-unit"><?= esc($employee['organization'] ?: '-') ?></td>
                                <td class="sdm-daily-total"><strong><?= $totalPresent ?></strong></td>
                            </tr>
                            <tr id="<?= esc($detailId, 'attr') ?>" class="sdm-staff-detail" hidden>
                                <td colspan="6">
                                    <div class="sdm-staff-detail-inner">
                                        <div class="sdm-staff-day-table-wrap"><table class="sdm-staff-day-table sdm-staff-horizontal-table"><thead><tr><th>Hari / Tanggal</th>
                                            <?php for ($day = $displayStartDay; $day <= $displayEndDay; $day++): ?>
                                                <?php
                                                $date = new DateTimeImmutable($periodPrefix . '-' . str_pad((string) $day, 2, '0', STR_PAD_LEFT));
                                                $calendarDay = $report['calendar_days'][$day] ?? [];
                                                $isOff = (bool) ($calendarDay['is_non_working'] ?? false);
                                                $calendarLabel = (string) ($calendarDay['label'] ?? ($isOff ? 'Libur' : 'Hari kerja'));
                                                ?>
                                                <th class="<?= $isOff ? 'is-off' : '' ?>" title="<?= esc($calendarLabel, 'attr') ?>"><strong><?= esc($dayNames[(int) $date->format('N')]) ?></strong><small><?= esc($date->format('d-m')) ?></small></th>
                                            <?php endfor ?>
                                        </tr></thead><tbody><tr><th>Status / Jam</th>
                                            <?php for ($day = $displayStartDay; $day <= $displayEndDay; $day++): ?>
                                                <?php
                                                $date = new DateTimeImmutable($periodPrefix . '-' . str_pad((string) $day, 2, '0', STR_PAD_LEFT));
                                                $calendarDay = $report['calendar_days'][$day] ?? [];
                                                $isOff = (bool) ($calendarDay['is_non_working'] ?? false);
                                                $dayDetail = $employee['day_details'][$day] ?? null;
                                                $code = (string) ($dayDetail['recap_code'] ?? '-');
                                                $actualIn = ! empty($dayDetail['actual_in']) ? substr((string) $dayDetail['actual_in'], 0, 5) : '—';
                                                $actualOut = ! empty($dayDetail['actual_out']) ? substr((string) $dayDetail['actual_out'], 0, 5) : '—';
                                                $note = (string) ($dayDetail['holiday_name'] ?? ($calendarDay['label'] ?? ($statusLabels[$code] ?? 'Data belum tersedia')));
                                                ?>
                                                <td class="sdm-staff-day-cell <?= $isOff ? 'is-off' : '' ?>" title="<?= esc($note, 'attr') ?>"><span class="sdm-staff-status sdm-daily-code-<?= esc(strtolower(str_replace('-', 'empty', $code)), 'attr') ?>"><?= esc($displayCodes[$code] ?? $code) ?></span><small>↑ <?= esc($actualIn) ?></small><small>↓ <?= esc($actualOut) ?></small></td>
                                            <?php endfor ?>
                                        </tr></tbody></table></div>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach ?>
                    </tbody>
                </table>
            </div>
        <?php endif ?>

        <footer class="sdm-daily-footer">
            <span><strong><?= count($report['employees']) ?></strong> karyawan ditampilkan</span>
            <span>Sumber: <?= esc($report['source_name']) ?></span>
        </footer>
    </section>
    <script>
    (() => {
        document.querySelectorAll('[data-attendance-staff-toggle]').forEach((button) => {
            button.addEventListener('click', () => {
                const detail = document.getElementById(button.getAttribute('aria-controls'));
                if (!detail) return;
                const expanded = button.getAttribute('aria-expanded') === 'true';
                button.setAttribute('aria-expanded', expanded ? 'false' : 'true');
                detail.hidden = expanded;
            });
        });
    })();
    </script>
<?php else: ?>
    <section class="panel sdm-daily-panel">
        <div class="empty-state sdm-daily-empty sdm-daily-upload-empty">
            <span aria-hidden="true">▦</span>
            <strong>Belum ada laporan kehadiran yang diunggah</strong>
            <p>Unggah <b>Employee Attendance Report ESS</b> dari Workplace untuk menampilkan rekap dan detail kehadiran karyawan.</p>
            <button type="button" class="btn btn-primary" data-open-attendance-upload>Unggah Laporan Kehadiran</button>
        </div>
    </section>
<?php endif ?>

<div class="input-modal attendance-upload-modal" id="attendanceUploadModal" hidden aria-hidden="true">
    <button type="button" class="modal-backdrop" data-close-attendance-upload aria-label="Tutup unggahan"></button>
    <section class="modal-dialog attendance-upload-dialog" role="dialog" aria-modal="true" aria-labelledby="attendanceUploadTitle">
        <header class="modal-header">
            <div class="modal-title-group">
                <span class="modal-title-icon" aria-hidden="true">⇧</span>
                <div>
                    <p>SDM &amp; TELLER</p>
                    <h2 id="attendanceUploadTitle">Unggah Laporan Kehadiran</h2>
                </div>
            </div>
            <button class="modal-close" type="button" data-close-attendance-upload aria-label="Tutup unggahan">×</button>
        </header>
        <form method="post" action="<?= site_url('sdm/data-kehadiran/rekap') ?>" enctype="multipart/form-data">
            <?= csrf_field() ?>
            <div class="modal-body attendance-upload-body">
                <p>Unggah file <strong>Employee Attendance Report ESS</strong> hasil ekspor Workplace. Sistem akan memetakan data harian secara otomatis.</p>
                <div class="form-group">
                    <label for="attendance_file">File Employee Attendance Report ESS <span class="required">*</span></label>
                    <input id="attendance_file" name="attendance_file" type="file" accept=".xls,.html,.htm,application/vnd.ms-excel,text/html" required>
                    <small>Format .xls dari Workplace, maksimal 5 MB.</small>
                </div>
            </div>
            <footer class="modal-footer">
                <button class="btn btn-ghost" type="button" data-close-attendance-upload>Batal</button>
                <button class="btn btn-primary" type="submit">Unggah dan Rekap</button>
            </footer>
        </form>
    </section>
</div>

<?php if ($selectedImportId !== null): ?>
<div class="input-modal attendance-delete-modal" id="attendanceDeleteModal" hidden aria-hidden="true">
    <button type="button" class="modal-backdrop" data-close-attendance-delete aria-label="Tutup konfirmasi hapus"></button>
    <section class="modal-dialog delete-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="attendanceDeleteTitle">
        <header class="modal-header">
            <div class="modal-title-group">
                <span class="modal-title-icon" aria-hidden="true">×</span>
                <div><p>SDM &amp; TELLER</p><h2 id="attendanceDeleteTitle">Hapus Laporan Kehadiran</h2></div>
            </div>
            <button class="modal-close" type="button" data-close-attendance-delete aria-label="Tutup konfirmasi">×</button>
        </header>
        <form method="post" action="<?= site_url('sdm/data-kehadiran/hapus') ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="import_id" value="<?= (int) $selectedImportId ?>">
            <div class="modal-body"><p>Hapus rekap kehadiran periode yang sedang dipilih beserta seluruh detail hariannya?</p><p class="attendance-delete-note">Tindakan ini memengaruhi Data Kehadiran dan Data Lembur pada periode tersebut.</p></div>
            <footer class="modal-footer"><button class="btn btn-ghost" type="button" data-close-attendance-delete>Batal</button><button class="btn btn-danger" type="submit">Hapus Laporan</button></footer>
        </form>
    </section>
</div>
<?php endif ?>

<?= $this->endSection() ?>
