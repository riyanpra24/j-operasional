<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<?php
/** @var array<string, mixed>|null $report */
/** @var list<array<string, mixed>> $attendanceImports */
/** @var list<array<string, mixed>> $attendanceExportImports */
/** @var list<string> $attendanceWorkUnits */
/** @var int|null $selectedImportId */
/** @var array<string, string> $attendanceFilters */
/** @var array{per_page:int,current_page:int,last_page:int,total:int,offset:int} $attendancePagination */

$statusLabels = [
    'H' => 'Hadir tepat waktu',
    'TLBT' => 'Terlambat',
    'TLTAP' => 'Terlambat dan tidak absen pulang',
    'ODR' => 'On Duty Request',
    'IZ' => 'Izin',
    'CT' => 'Cuti',
    'A' => 'Alpa',
    'TA' => 'Tidak absen masuk dan pulang',
    'TAM' => 'Tidak absen masuk',
    'TAP' => 'Tidak absen pulang',
    'TPA' => 'Masuk tidak absen',
    'OFF' => 'Libur / akhir pekan',
];
$displayCodes = ['TLBT' => 'TL', 'TLTAP' => 'TL/TAP'];
$dayNames = [1 => 'Sen', 2 => 'Sel', 3 => 'Rab', 4 => 'Kam', 5 => 'Jum', 6 => 'Sab', 7 => 'Min'];
$monthOptions = [1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'];
$uploadYear = (int) date('Y');
$attendanceExportOptions = [];
$attendanceExportYears = [];
$selectedAttendanceImport = null;
$exportImports = $attendanceExportImports ?? $attendanceImports;
foreach ($exportImports as $import) {
    $period = new DateTimeImmutable((string) $import['period_start']);
    $month = (int) $period->format('n');
    $year = (int) $period->format('Y');
    $attendanceExportOptions[] = [
        'work_unit' => (string) ($import['work_unit'] ?? ''),
        'month' => $month,
        'year' => $year,
    ];
    $attendanceExportYears[$year] = $year;
    if ((int) $import['id'] === $selectedImportId) {
        $selectedAttendanceImport = $import;
    }
}
$attendanceExportYears = array_values($attendanceExportYears);
rsort($attendanceExportYears, SORT_NUMERIC);
$exportDefaultUnit = (string) ($selectedAttendanceImport['work_unit'] ?? ($attendanceWorkUnits[0] ?? ''));
$exportDefaultPeriod = $selectedAttendanceImport !== null
    ? new DateTimeImmutable((string) $selectedAttendanceImport['period_start'])
    : null;
$exportDefaultMonth = (int) ($exportDefaultPeriod?->format('n') ?? date('n'));
$exportDefaultYear = (int) ($exportDefaultPeriod?->format('Y') ?? $uploadYear);
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
                <label for="attendance_work_unit">Unit kerja</label>
                <select id="attendance_work_unit" name="unit_kerja">
                    <option value="">Semua unit kerja</option>
                    <?php foreach ($attendanceWorkUnits as $workUnit): ?>
                        <option value="<?= esc($workUnit, 'attr') ?>" <?= ($attendanceFilters['work_unit'] ?? '') === $workUnit ? 'selected' : '' ?>><?= esc($workUnit) ?></option>
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
            <input type="hidden" name="per_page" value="<?= (int) $attendancePagination['per_page'] ?>">
            <div class="sdm-daily-filter-actions">
                <button type="submit" class="btn btn-primary">Tampilkan</button>
                <a href="<?= site_url('sdm/data-kehadiran' . ($selectedImportId !== null ? '?import_id=' . (int) $selectedImportId : '')) ?>" class="btn btn-ghost">Reset</a>
                <details class="sdm-daily-menu">
                    <summary class="sdm-daily-menu-trigger" aria-label="Menu tindakan" title="Menu tindakan">
                        <svg aria-hidden="true" viewBox="0 0 24 24" focusable="false"><rect x="3" y="3" width="7" height="7" rx="1.4"></rect><rect x="14" y="3" width="7" height="7" rx="1.4"></rect><rect x="3" y="14" width="7" height="7" rx="1.4"></rect><rect x="14" y="14" width="7" height="7" rx="1.4"></rect></svg>
                    </summary>
                    <div class="sdm-daily-menu-list">
                        <button type="button" data-open-attendance-upload>Unggah Laporan Kehadiran</button>
                        <?php if ($attendanceImports !== []): ?><button type="button" data-open-attendance-export>Ekspor Rekap Kehadiran</button><?php endif ?>
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
    $attendancePageQuery = array_filter([
        'import_id'  => $selectedImportId,
        'unit_kerja' => $attendanceFilters['work_unit'] ?? '',
        'nama'       => $attendanceFilters['name'] ?? '',
        'dari'       => $attendanceFilters['from'] ?? '',
        'sampai'     => $attendanceFilters['to'] ?? '',
        'per_page'   => $attendancePagination['per_page'],
    ], static fn ($value): bool => $value !== null && $value !== '');
    $attendancePageUrl = static fn (int $page): string => site_url('sdm/data-kehadiran') . '?' . http_build_query(array_merge($attendancePageQuery, ['halaman' => $page]));
    ?>
    <section class="panel sdm-daily-panel" id="attendanceTableArea" aria-live="polite">
        <header class="sdm-daily-panel-header">
            <div>
                <h2>Data Kehadiran Karyawan</h2>
                <p><?= date('d-m-Y', strtotime($report['period_start'])) ?> sampai <?= date('d-m-Y', strtotime($report['period_end'])) ?></p>
            </div>
        </header>

        <div class="sdm-daily-legend" aria-label="Keterangan kode absensi">
            <strong>Keterangan:</strong>
            <?php foreach ($statusLabels as $code => $label): ?>
                <span><i class="sdm-daily-code sdm-daily-code-<?= esc(strtolower(str_replace('-', 'empty', $code)), 'attr') ?>"><?= esc($displayCodes[$code] ?? $code) ?></i><?php if ($code === 'TLTAP'): ?><span class="sdm-daily-legend-label-tltap">Terlambat dan<br>tidak absen pulang</span><?php else: ?><?= esc($label) ?><?php endif ?></span>
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
                            <th class="sdm-daily-fixed sdm-daily-work-unit">Unit Kerja</th>
                            <th class="sdm-daily-total-heading">Total Hadir</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $rowNumber = (int) $attendancePagination['offset'] + 1; ?>
                        <?php foreach ($report['employees'] as $employee): ?>
                            <?php
                            $totalPresent = (int) $employee['totals']['H'] + (int) $employee['totals']['TLBT'] + (int) ($employee['totals']['TLTAP'] ?? 0) + (int) ($employee['totals']['ODR'] ?? 0) + (int) $employee['totals']['TA'] + (int) $employee['totals']['TAM'] + (int) $employee['totals']['TAP'] + (int) ($employee['totals']['TPA'] ?? 0);
                            $detailId = 'attendance-staff-' . $rowNumber;
                            ?>
                            <tr>
                                <td class="sdm-daily-fixed sdm-daily-no"><?= $rowNumber++ ?></td>
                                <td class="sdm-daily-fixed sdm-daily-npp"><?= esc($employee['employee_no']) ?></td>
                                <td class="sdm-daily-fixed sdm-daily-name"><button type="button" class="sdm-staff-toggle" data-attendance-staff-toggle aria-expanded="false" aria-controls="<?= esc($detailId, 'attr') ?>"><i aria-hidden="true">›</i><span><strong><?= esc($employee['employee_name']) ?></strong><small>Lihat detail kehadiran</small></span></button></td>
                                <td class="sdm-daily-fixed sdm-daily-position"><?= esc($employee['position'] ?: '-') ?></td>
                                <td class="sdm-daily-fixed sdm-daily-unit"><?= esc($employee['organization'] ?: '-') ?></td>
                                <td class="sdm-daily-fixed sdm-daily-work-unit"><?= esc($employee['work_unit'] ?: '-') ?></td>
                                <td class="sdm-daily-total"><strong><?= $totalPresent ?></strong></td>
                            </tr>
                            <tr id="<?= esc($detailId, 'attr') ?>" class="sdm-staff-detail" hidden>
                                <td colspan="7">
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

        <footer class="table-list-footer sdm-daily-table-footer">
            <form method="get" action="<?= site_url('sdm/data-kehadiran') ?>" class="table-length-form">
                <?php foreach (['import_id' => $selectedImportId, 'unit_kerja' => $attendanceFilters['work_unit'] ?? '', 'nama' => $attendanceFilters['name'] ?? '', 'dari' => $attendanceFilters['from'] ?? '', 'sampai' => $attendanceFilters['to'] ?? ''] as $name => $value): ?>
                    <?php if ($value !== null && $value !== ''): ?><input type="hidden" name="<?= esc($name, 'attr') ?>" value="<?= esc((string) $value, 'attr') ?>"><?php endif ?>
                <?php endforeach ?>
                <label for="attendance_per_page">Tampilkan</label>
                <select id="attendance_per_page" name="per_page" aria-label="Jumlah karyawan per halaman" data-attendance-page-size>
                    <?php foreach ([10, 20, 50, 100] as $size): ?><option value="<?= $size ?>" <?= $attendancePagination['per_page'] === $size ? 'selected' : '' ?>><?= $size ?></option><?php endforeach ?>
                </select>
                <span>data</span>
            </form>
            <?php if ($attendancePagination['last_page'] > 1): ?>
                <?php
                $firstPage = max(1, $attendancePagination['current_page'] - 2);
                $lastPage = min($attendancePagination['last_page'], $attendancePagination['current_page'] + 2);
                ?>
                <div class="pagination-wrap">
                    <nav aria-label="Navigasi halaman data kehadiran"><ul class="pagination">
                        <?php if ($attendancePagination['current_page'] > 1): ?>
                            <li><a href="<?= esc($attendancePageUrl(1), 'attr') ?>">First</a></li>
                            <li><a href="<?= esc($attendancePageUrl($attendancePagination['current_page'] - 1), 'attr') ?>">Previous</a></li>
                        <?php endif ?>
                        <?php for ($page = $firstPage; $page <= $lastPage; $page++): ?><li <?= $page === $attendancePagination['current_page'] ? 'class="active"' : '' ?>><a href="<?= esc($attendancePageUrl($page), 'attr') ?>"><?= $page ?></a></li><?php endfor ?>
                        <?php if ($attendancePagination['current_page'] < $attendancePagination['last_page']): ?>
                            <li><a href="<?= esc($attendancePageUrl($attendancePagination['current_page'] + 1), 'attr') ?>">Next</a></li>
                            <li><a href="<?= esc($attendancePageUrl($attendancePagination['last_page']), 'attr') ?>">Last</a></li>
                        <?php endif ?>
                    </ul></nav>
                </div>
            <?php endif ?>
        </footer>
    </section>
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

<script>
(() => {
    const cardSelector = '#attendanceTableArea';
    let pendingRequest = null;

    const replaceTableCard = async (url, updateHistory = true) => {
        const currentCard = document.querySelector(cardSelector);
        if (!currentCard) {
            window.location.assign(url);
            return;
        }

        pendingRequest?.abort();
        const request = new AbortController();
        pendingRequest = request;
        currentCard.classList.add('is-loading');

        try {
            const response = await fetch(url, {
                headers: {'X-Requested-With': 'XMLHttpRequest'},
                signal: request.signal,
            });
            if (!response.ok) throw new Error('Attendance table request failed.');

            const documentResponse = new DOMParser().parseFromString(await response.text(), 'text/html');
            const nextCard = documentResponse.querySelector(cardSelector);
            if (!nextCard) throw new Error('Attendance table card is unavailable.');

            currentCard.replaceWith(nextCard);
            if (updateHistory) history.pushState({attendanceTable: true}, '', url);
        } catch (error) {
            if (error.name !== 'AbortError') window.location.assign(url);
        } finally {
            if (pendingRequest === request) pendingRequest = null;
            currentCard.classList.remove('is-loading');
        }
    };

    document.addEventListener('click', (event) => {
        const toggle = event.target.closest('[data-attendance-staff-toggle]');
        if (toggle) {
            const detail = document.getElementById(toggle.getAttribute('aria-controls'));
            if (!detail) return;
            const expanded = toggle.getAttribute('aria-expanded') === 'true';
            toggle.setAttribute('aria-expanded', expanded ? 'false' : 'true');
            detail.hidden = expanded;
            return;
        }

        const link = event.target.closest(`${cardSelector} .pagination a`);
        if (!link || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;
        event.preventDefault();
        replaceTableCard(link.href);
    });

    document.addEventListener('change', (event) => {
        const select = event.target.closest('[data-attendance-page-size]');
        if (!select) return;
        const form = select.form;
        if (!form) return;
        const url = new URL(form.action, window.location.href);
        const query = new URLSearchParams(new FormData(form));
        query.delete('halaman');
        url.search = query.toString();
        replaceTableCard(url.toString());
    });

    window.addEventListener('popstate', () => replaceTableCard(window.location.href, false));
})();
</script>

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
                <div class="attendance-upload-meta-grid">
                    <div class="form-group">
                        <label for="attendance_upload_unit">Unit kerja <span class="required">*</span></label>
                        <select id="attendance_upload_unit" name="unit_kerja" required>
                            <option value="" selected disabled>Pilih unit kerja</option>
                            <?php foreach ($attendanceWorkUnits as $workUnit): ?><option value="<?= esc($workUnit, 'attr') ?>"><?= esc($workUnit) ?></option><?php endforeach ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="attendance_upload_month">Bulan <span class="required">*</span></label>
                        <select id="attendance_upload_month" name="bulan" required>
                            <?php foreach ($monthOptions as $monthNumber => $monthLabel): ?><option value="<?= $monthNumber ?>" <?= $monthNumber === (int) date('n') ? 'selected' : '' ?>><?= esc($monthLabel) ?></option><?php endforeach ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="attendance_upload_year">Tahun <span class="required">*</span></label>
                        <select id="attendance_upload_year" name="tahun" required>
                            <?php foreach (range($uploadYear - 2, $uploadYear + 2) as $year): ?><option value="<?= $year ?>" <?= $year === $uploadYear ? 'selected' : '' ?>><?= $year ?></option><?php endforeach ?>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label for="attendance_file">File Employee Attendance Report ESS <span class="required">*</span></label>
                    <input id="attendance_file" name="attendance_file" type="file" accept=".xls,.html,.htm,application/vnd.ms-excel,text/html" required>
                    <small>Format .xls dari Workplace, maksimal 5 MB. Bulan dan tahun pilihan harus sesuai dengan kolom Date pada file ESS.</small>
                </div>
            </div>
            <footer class="modal-footer">
                <button class="btn btn-ghost" type="button" data-close-attendance-upload>Batal</button>
                <button class="btn btn-primary" type="submit">Unggah dan Rekap</button>
            </footer>
        </form>
    </section>
</div>

<?php if ($attendanceImports !== []): ?>
<div class="input-modal attendance-export-modal" id="attendanceExportModal" hidden aria-hidden="true">
    <button type="button" class="modal-backdrop" data-close-attendance-export aria-label="Tutup ekspor"></button>
    <section class="modal-dialog attendance-upload-dialog" role="dialog" aria-modal="true" aria-labelledby="attendanceExportTitle">
        <header class="modal-header">
            <div class="modal-title-group">
                <span class="modal-title-icon" aria-hidden="true">⇩</span>
                <div>
                    <p>SDM &amp; TELLER</p>
                    <h2 id="attendanceExportTitle">Ekspor Rekap Kehadiran</h2>
                </div>
            </div>
            <button class="modal-close" type="button" data-close-attendance-export aria-label="Tutup ekspor">×</button>
        </header>
        <form method="post" action="<?= site_url('sdm/data-kehadiran/export') ?>" data-attendance-export-form data-attendance-export-options="<?= esc(json_encode($attendanceExportOptions, JSON_UNESCAPED_UNICODE | JSON_HEX_APOS | JSON_HEX_QUOT), 'attr') ?>">
            <?= csrf_field() ?>
            <div class="modal-body attendance-upload-body attendance-export-body">
                <p>Pilih unit kerja dan periode laporan yang ingin diekspor. Pilih semua unit kerja untuk membuat satu file dengan sheet terpisah untuk setiap unit.</p>
                <div class="attendance-upload-meta-grid">
                    <div class="form-group">
                        <label for="attendance_export_unit">Unit kerja <span class="required">*</span></label>
                        <select id="attendance_export_unit" name="unit_kerja" data-attendance-export-unit required>
                            <option value="__all__">Semua unit kerja</option>
                            <?php foreach ($attendanceWorkUnits as $workUnit): ?><option value="<?= esc($workUnit, 'attr') ?>" <?= $workUnit === $exportDefaultUnit ? 'selected' : '' ?>><?= esc($workUnit) ?></option><?php endforeach ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="attendance_export_month">Bulan <span class="required">*</span></label>
                        <select id="attendance_export_month" name="bulan" data-attendance-export-month required>
                            <?php foreach ($monthOptions as $monthNumber => $monthLabel): ?><option value="<?= $monthNumber ?>" <?= $monthNumber === $exportDefaultMonth ? 'selected' : '' ?>><?= esc($monthLabel) ?></option><?php endforeach ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="attendance_export_year">Tahun <span class="required">*</span></label>
                        <select id="attendance_export_year" name="tahun" data-attendance-export-year required>
                            <?php foreach ($attendanceExportYears as $year): ?><option value="<?= $year ?>" <?= $year === $exportDefaultYear ? 'selected' : '' ?>><?= $year ?></option><?php endforeach ?>
                        </select>
                    </div>
                </div>
                <p class="attendance-export-note">Pilihan periode disesuaikan otomatis dengan laporan yang tersedia.</p>
            </div>
            <footer class="modal-footer">
                <button class="btn btn-ghost" type="button" data-close-attendance-export>Batal</button>
                <button class="btn btn-primary" type="submit">Ekspor Dokumen</button>
            </footer>
        </form>
    </section>
</div>
<?php endif ?>

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
