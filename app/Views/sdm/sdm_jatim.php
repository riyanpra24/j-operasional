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
    'OFF' => 'Libur / akhir pekan',
    '-' => 'Data belum lengkap',
];
$displayCodes = ['TLBT' => 'TL', '-' => 'BL'];
$dayNames = [1 => 'Sen', 2 => 'Sel', 3 => 'Rab', 4 => 'Kam', 5 => 'Jum', 6 => 'Sab', 7 => 'Min'];
?>

<section class="page-heading sdm-daily-heading">
    <div>
        <p class="eyebrow">SDM &amp; TELLER</p>
        <h1>Data Kehadiran</h1>
        <p>Status serta jam masuk dan pulang seluruh karyawan dalam satu periode.</p>
    </div>
    <?php if ($report !== null): ?>
        <div class="sdm-daily-period">
            <small>PERIODE AKTIF</small>
            <strong><?= esc($report['period_label']) ?></strong>
            <span><?= number_format($report['summary']['EMPLOYEES'], 0, ',', '.') ?> karyawan</span>
        </div>
    <?php endif ?>
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
            </div>
        </form>
    </section>
<?php endif ?>

<?php if ($report !== null): ?>
    <?php
    $displayStartDay = (int) $report['display_start_day'];
    $displayEndDay = (int) $report['display_end_day'];
    $displayDayCount = max(1, ($displayEndDay - $displayStartDay) + 1);
    $periodPrefix = substr((string) $report['period_start'], 0, 7);
    ?>
    <section class="panel sdm-daily-panel">
        <header class="sdm-daily-panel-header">
            <div>
                <h2>Data Kehadiran Karyawan</h2>
                <p><?= date('d-m-Y', strtotime($report['period_start'])) ?> sampai <?= date('d-m-Y', strtotime($report['period_end'])) ?></p>
            </div>
            <div class="sdm-daily-time-key"><span>↑ Jam masuk</span><span>↓ Jam pulang</span></div>
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
                <table class="sdm-daily-table">
                    <thead>
                        <tr>
                            <th rowspan="2" class="sdm-daily-fixed sdm-daily-no">No</th>
                            <th rowspan="2" class="sdm-daily-fixed sdm-daily-npp">NPP</th>
                            <th rowspan="2" class="sdm-daily-fixed sdm-daily-name">Nama Karyawan</th>
                            <th rowspan="2" class="sdm-daily-fixed sdm-daily-position">Jabatan</th>
                            <th rowspan="2" class="sdm-daily-fixed sdm-daily-unit">Bagian</th>
                            <th colspan="<?= $displayDayCount ?>">Tanggal Absensi (<?= $displayStartDay ?>–<?= $displayEndDay ?>)</th>
                            <th rowspan="2" class="sdm-daily-total-heading">Total Hadir</th>
                        </tr>
                        <tr>
                            <?php for ($day = $displayStartDay; $day <= $displayEndDay; $day++): ?>
                                <?php
                                $date = new DateTimeImmutable($periodPrefix . '-' . str_pad((string) $day, 2, '0', STR_PAD_LEFT));
                                $calendarDay = $report['calendar_days'][$day] ?? [];
                                $isOff = (bool) ($calendarDay['is_non_working'] ?? false);
                                $calendarLabel = (string) ($calendarDay['label'] ?? ($isOff ? 'Libur' : 'Hari kerja'));
                                ?>
                                <th class="sdm-daily-day-heading <?= $isOff ? 'is-off' : '' ?>" title="<?= esc($calendarLabel, 'attr') ?>">
                                    <strong><?= $day ?> <?= esc($dayNames[(int) $date->format('N')]) ?></strong>
                                    <small><?= $isOff ? 'LIBUR' : 'KERJA' ?></small>
                                </th>
                            <?php endfor ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($report['employees'] as $index => $employee): ?>
                            <?php $totalPresent = (int) $employee['totals']['H'] + (int) $employee['totals']['TLBT'] + (int) $employee['totals']['TA'] + (int) $employee['totals']['TAM'] + (int) $employee['totals']['TAP']; ?>
                            <tr>
                                <td class="sdm-daily-fixed sdm-daily-no"><?= $index + 1 ?></td>
                                <td class="sdm-daily-fixed sdm-daily-npp"><?= esc($employee['employee_no']) ?></td>
                                <td class="sdm-daily-fixed sdm-daily-name"><strong><?= esc($employee['employee_name']) ?></strong></td>
                                <td class="sdm-daily-fixed sdm-daily-position"><?= esc($employee['position'] ?: '-') ?></td>
                                <td class="sdm-daily-fixed sdm-daily-unit"><?= esc($employee['organization'] ?: '-') ?></td>
                                <?php for ($day = $displayStartDay; $day <= $displayEndDay; $day++): ?>
                                    <?php
                                    $dayDetail = $employee['day_details'][$day] ?? null;
                                    $code = (string) ($dayDetail['recap_code'] ?? '-');
                                    $actualIn = ! empty($dayDetail['actual_in']) ? substr((string) $dayDetail['actual_in'], 0, 5) : null;
                                    $actualOut = ! empty($dayDetail['actual_out']) ? substr((string) $dayDetail['actual_out'], 0, 5) : null;
                                    $cellTitle = (string) ($dayDetail['holiday_name'] ?? ($statusLabels[$code] ?? $code));
                                    ?>
                                    <td class="sdm-daily-status sdm-daily-code-<?= esc(strtolower(str_replace('-', 'empty', $code)), 'attr') ?>" title="<?= esc($cellTitle, 'attr') ?>">
                                        <strong><?= esc($displayCodes[$code] ?? $code) ?></strong>
                                        <?php if ($actualIn !== null): ?><span>↑ <?= esc($actualIn) ?></span><?php endif ?>
                                        <?php if ($actualOut !== null): ?><span>↓ <?= esc($actualOut) ?></span><?php endif ?>
                                    </td>
                                <?php endfor ?>
                                <td class="sdm-daily-total"><strong><?= $totalPresent ?></strong></td>
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
<?php else: ?>
    <section class="panel sdm-daily-panel"><div class="empty-state sdm-daily-empty"><span aria-hidden="true">▦</span><strong>Belum ada data absensi</strong><p>Rekap absensi perlu tersedia sebelum detail harian dapat ditampilkan.</p></div></section>
<?php endif ?>

<?= $this->endSection() ?>
