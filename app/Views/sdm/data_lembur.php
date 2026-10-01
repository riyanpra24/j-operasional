<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<?php
$formatDuration = static function (int $minutes): string {
    if ($minutes <= 0) {
        return '—';
    }

    return intdiv($minutes, 60) . 'j ' . ($minutes % 60) . 'm';
};
$dayNames = ['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'];
$months = [
    1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
    5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
    9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
];
?>

<section class="page-heading overtime-heading">
    <div>
        <p class="eyebrow">SDM &amp; TELLER</p>
        <h1>Data Lembur</h1>
        <p>Kalender lembur resmi yang dipetakan dari Overtime Report ESS Workplace.</p>
    </div>
</section>

<section class="panel overtime-filter-panel">
    <form method="get" action="<?= site_url('sdm/data-lembur') ?>" class="overtime-filter-form">
        <div class="form-group overtime-month-filter">
            <label for="overtime_month">Bulan</label>
            <select id="overtime_month" name="bulan">
                <?php foreach ($months as $number => $name): ?>
                    <option value="<?= $number ?>" <?= $selectedMonth === $number ? 'selected' : '' ?>><?= esc($name) ?></option>
                <?php endforeach ?>
            </select>
        </div>
        <div class="form-group overtime-year-filter">
            <label for="overtime_year">Tahun</label>
            <select id="overtime_year" name="tahun">
                <?php foreach ($availableYears as $year): ?>
                    <option value="<?= (int) $year ?>" <?= $selectedYear === (int) $year ? 'selected' : '' ?>><?= (int) $year ?></option>
                <?php endforeach ?>
            </select>
        </div>
        <button class="btn btn-primary" type="submit">Tampilkan</button>
        <div class="overtime-filter-copy"><strong><?= esc($monthLabel) ?></strong><span>Kalender nasional dan data lembur ditampilkan sesuai bulan yang dipilih.</span></div>
        <a class="btn btn-ghost" href="<?= site_url('sdm/data-lembur') ?>">Reset</a>
        <details class="overtime-action-menu">
            <summary class="overtime-action-trigger" aria-label="Menu tindakan" title="Menu tindakan"><svg aria-hidden="true" viewBox="0 0 24 24" focusable="false"><rect x="3" y="3" width="7" height="7" rx="1.4"></rect><rect x="14" y="3" width="7" height="7" rx="1.4"></rect><rect x="3" y="14" width="7" height="7" rx="1.4"></rect><rect x="14" y="14" width="7" height="7" rx="1.4"></rect></svg></summary>
            <div class="overtime-action-popover"><button type="button" data-overtime-upload-open>Unggah Overtime Report ESS</button><span class="overtime-action-divider" aria-hidden="true"></span><button type="button" class="is-danger" data-overtime-delete-open <?= $overtimeImports === [] ? 'disabled' : '' ?>>Hapus Laporan Lembur</button></div>
        </details>
    </form>
</section>

<section class="overtime-workspace">
        <article class="panel overtime-calendar-panel">
            <header class="overtime-panel-heading">
                <div><p>PERIODE REKAP</p><h2><?= esc($monthLabel) ?></h2></div>
                <div class="overtime-calendar-actions"><span class="overtime-legend"><i></i>Ada lembur</span><button class="btn btn-ghost overtime-calendar-edit" type="button" data-overtime-calendar-open>Atur tanggal</button></div>
            </header>
            <div class="overtime-weekdays" aria-hidden="true"><?php foreach ($dayNames as $day): ?><span><?= $day ?></span><?php endforeach ?></div>
            <div class="overtime-calendar-grid" aria-label="Kalender data lembur <?= esc($monthLabel) ?>">
                <?php for ($blank = 0; $blank < $calendarLeadingDays; $blank++): ?><span class="overtime-calendar-blank"></span><?php endfor ?>
                <?php foreach ($calendarDays as $day): ?>
                    <?php
                    $holidayMarker = match ($day['holiday_source']) {
                        'national' => 'Libur nasional',
                        'collective_leave' => 'Cuti bersama',
                        'weekend' => 'Akhir pekan',
                        'manual' => $day['holiday_label'] ?: 'Libur khusus',
                        default => '',
                    };
                    ?>
                    <button type="button" class="overtime-calendar-day <?= $day['is_non_working'] ? 'is-holiday' : '' ?> <?= $day['count'] > 0 ? 'has-overtime' : '' ?> <?= $day['date'] === $selectedDate ? 'is-selected' : '' ?>" data-overtime-date="<?= esc($day['date'], 'attr') ?>" data-overtime-calendar-mode="<?= esc($day['holiday_mode'], 'attr') ?>" data-overtime-calendar-label="<?= esc((string) ($day['holiday_label'] ?? ''), 'attr') ?>" aria-pressed="<?= $day['date'] === $selectedDate ? 'true' : 'false' ?>" title="<?= esc(($day['holiday_label'] ? $day['holiday_label'] . ' · ' : '') . ($day['count'] > 0 ? $day['count'] . ' staf lembur' : 'Tidak ada data lembur'), 'attr') ?>">
                        <strong><?= (int) $day['day'] ?></strong>
                        <?php if ($day['count'] > 0): ?><small><?= (int) $day['count'] ?> staf</small><?php elseif ($holidayMarker !== ''): ?><small class="overtime-holiday-label"><?= esc($holidayMarker) ?></small><?php endif ?>
                    </button>
                <?php endforeach ?>
            </div>
            <p class="overtime-calendar-note"><strong>Catatan:</strong> <b>Libur nasional</b> dan <b>cuti bersama</b> mengikuti kalender resmi; akhir pekan serta pengaturan manual juga ditandai pada kalender.</p>
        </article>

        <article class="panel overtime-detail-panel">
            <header class="overtime-panel-heading">
                <div><p>RINCIAN TANGGAL</p><h2><?= esc((string) $selectedDateLabel) ?></h2></div>
                <span class="overtime-total"><b><?= (int) $overtimeCount ?></b> staf · <?= esc($formatDuration((int) $overtimeMinutes)) ?></span>
            </header>
            <?php if ($overtimeRecords !== []): ?>
                <div class="table-wrap overtime-table-wrap"><table class="overtime-table"><thead><tr><th>Staf</th><th>Unit Kerja</th><th>Shift</th><th>Masuk</th><th>Pulang</th><th>Total Lembur</th></tr></thead><tbody>
                    <?php foreach ($overtimeRecords as $record): ?><tr>
                        <td><strong><?= esc($record['employee_name']) ?></strong><small><?= esc($record['employee_no'] ?: 'NPP belum tersedia') ?></small></td>
                        <td><?= esc($record['organization'] ?: '—') ?></td><td><?= esc($record['shift'] ?: '—') ?></td>
                        <td><?= esc($record['actual_in'] ? substr((string) $record['actual_in'], 0, 5) : '—') ?></td>
                        <td><?= esc($record['actual_out'] ? substr((string) $record['actual_out'], 0, 5) : '—') ?></td>
                        <td><span class="overtime-duration"><?= esc($formatDuration((int) $record['overtime_minutes'])) ?></span><small><?= esc($record['request_status'] ?: '—') ?></small></td>
                    </tr><?php endforeach ?>
                </tbody></table></div>
            <?php else: ?>
                <div class="empty-state overtime-empty"><span aria-hidden="true">◷</span><strong>Tidak ada data lembur pada tanggal ini</strong><p>Pilih tanggal lain yang memiliki penanda biru pada kalender.</p></div>
            <?php endif ?>
            <p class="overtime-disclaimer">Rincian berasal langsung dari total lembur dan status permintaan pada Overtime Report ESS.</p>
        </article>
</section>

<div class="input-modal" id="overtimeUploadModal" hidden aria-hidden="true"><button type="button" class="modal-backdrop" data-overtime-upload-close aria-label="Tutup unggahan"></button><section class="modal-dialog attendance-upload-dialog" role="dialog" aria-modal="true" aria-labelledby="overtimeUploadTitle"><header class="modal-header"><div class="modal-title-group"><span class="modal-title-icon" aria-hidden="true">⇧</span><div><p>SDM &amp; TELLER</p><h2 id="overtimeUploadTitle">Unggah Overtime Report ESS</h2></div></div><button class="modal-close" type="button" data-overtime-upload-close aria-label="Tutup unggahan">×</button></header><form method="post" action="<?= site_url('sdm/data-lembur/upload') ?>" enctype="multipart/form-data"><?= csrf_field() ?><div class="modal-body attendance-upload-body"><p>Unggah satu file <strong>Overtime Report ESS</strong> hasil ekspor Workplace. Sistem akan memetakan tanggal, staf, durasi, alasan, dan status lembur secara otomatis.</p><div class="form-group"><label for="overtime_file">File Overtime Report ESS <span class="required">*</span></label><input id="overtime_file" name="overtime_file" type="file" accept=".xls,.html,.htm,application/vnd.ms-excel,text/html" required><small>Format .xls dari Workplace, maksimal 5 MB.</small></div></div><footer class="modal-footer"><button class="btn btn-ghost" type="button" data-overtime-upload-close>Batal</button><button class="btn btn-primary" type="submit">Unggah dan Petakan</button></footer></form></section></div>

<div class="input-modal" id="overtimeDeleteModal" hidden aria-hidden="true"><button type="button" class="modal-backdrop" data-overtime-delete-close aria-label="Tutup konfirmasi hapus"></button><section class="modal-dialog delete-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="overtimeDeleteTitle"><header class="modal-header"><div class="modal-title-group"><span class="modal-title-icon" aria-hidden="true">×</span><div><p>SDM &amp; TELLER</p><h2 id="overtimeDeleteTitle">Hapus Laporan Lembur</h2></div></div><button class="modal-close" type="button" data-overtime-delete-close aria-label="Tutup konfirmasi">×</button></header><form method="post" action="<?= site_url('sdm/data-lembur/hapus') ?>"><?= csrf_field() ?><input type="hidden" name="bulan" value="<?= (int) $selectedMonth ?>"><input type="hidden" name="tahun" value="<?= (int) $selectedYear ?>"><div class="modal-body attendance-upload-body"><?php if ($overtimeImports !== []): ?><p>Pilih laporan sumber yang ingin dihapus. Seluruh rincian yang berasal dari laporan tersebut akan ikut terhapus.</p><div class="form-group"><label for="overtime_import_id">Laporan sumber <span class="required">*</span></label><select id="overtime_import_id" name="import_id" required><?php foreach ($overtimeImports as $overtimeImport): ?><option value="<?= (int) $overtimeImport['id'] ?>"><?= esc($overtimeImport['source_name']) ?> · <?= esc($overtimeImport['period_start']) ?> s.d. <?= esc($overtimeImport['period_end']) ?></option><?php endforeach ?></select></div><?php else: ?><p>Tidak ada laporan lembur yang dapat dihapus pada periode ini.</p><?php endif ?></div><footer class="modal-footer"><button class="btn btn-ghost" type="button" data-overtime-delete-close>Batal</button><?php if ($overtimeImports !== []): ?><button class="btn btn-danger" type="submit">Hapus Laporan</button><?php endif ?></footer></form></section></div>

<div class="input-modal" id="overtimeCalendarModal" hidden aria-hidden="true"><button type="button" class="modal-backdrop" data-overtime-calendar-close aria-label="Tutup pengaturan tanggal"></button><section class="modal-dialog attendance-upload-dialog" role="dialog" aria-modal="true" aria-labelledby="overtimeCalendarTitle"><header class="modal-header"><div class="modal-title-group"><span class="modal-title-icon" aria-hidden="true">▦</span><div><p>SDM &amp; TELLER</p><h2 id="overtimeCalendarTitle">Atur Status Tanggal</h2></div></div><button class="modal-close" type="button" data-overtime-calendar-close aria-label="Tutup pengaturan">×</button></header><form method="post" action="<?= site_url('sdm/kalender') ?>" data-overtime-calendar-form><?= csrf_field() ?><input type="hidden" name="return_to" value="data-lembur"><input type="hidden" name="calendar_date" value="<?= esc($selectedDate, 'attr') ?>" data-overtime-calendar-date><div class="modal-body attendance-upload-body"><p data-overtime-calendar-copy>Atur status kalender untuk <?= esc((string) $selectedDateLabel) ?>.</p><div class="form-group"><label>Status tanggal <span class="required">*</span></label><div class="overtime-calendar-mode"><label><input type="radio" name="mode" value="auto" checked> Otomatis dari kalender nasional</label><label><input type="radio" name="mode" value="holiday"> Libur</label><label><input type="radio" name="mode" value="workday"> Masuk / hari kerja</label></div></div><div class="form-group"><label for="overtimeCalendarLabel">Keterangan</label><input id="overtimeCalendarLabel" name="label" maxlength="150" data-overtime-calendar-label placeholder="Contoh: Libur operasional khusus"></div></div><footer class="modal-footer"><button class="btn btn-ghost" type="button" data-overtime-calendar-close>Batal</button><button class="btn btn-primary" type="submit">Simpan Status</button></footer></form></section></div>

<script>
(() => {
    const setupModal = (modalId, openSelector, closeSelector) => {
        const modal = document.querySelector(modalId);
        if (!modal) return { modal: null, open: () => {}, close: () => {} };
        const open = () => { modal.hidden = false; modal.setAttribute('aria-hidden', 'false'); requestAnimationFrame(() => modal.classList.add('open')); document.body.style.overflow = 'hidden'; };
        const close = () => { modal.classList.remove('open'); modal.setAttribute('aria-hidden', 'true'); document.body.style.overflow = ''; setTimeout(() => { modal.hidden = true; }, 180); };
        document.querySelectorAll(openSelector).forEach((button) => button.addEventListener('click', open));
        modal.querySelectorAll(closeSelector).forEach((button) => button.addEventListener('click', close));
        return { modal, open, close };
    };

    const upload = setupModal('#overtimeUploadModal', '[data-overtime-upload-open]', '[data-overtime-upload-close]');
    const calendar = setupModal('#overtimeCalendarModal', '[data-overtime-calendar-open]', '[data-overtime-calendar-close]');
    const deletion = setupModal('#overtimeDeleteModal', '[data-overtime-delete-open]', '[data-overtime-delete-close]');
    document.querySelectorAll('[data-overtime-date]').forEach((button) => {
        button.addEventListener('click', () => {
            const url = new URL(window.location.href);
            url.searchParams.set('tanggal', button.dataset.overtimeDate);
            window.location.assign(url.toString());
        });
    });

    document.querySelectorAll('[data-overtime-upload-open]').forEach((button) => button.addEventListener('click', () => button.closest('details')?.removeAttribute('open')));
    document.querySelectorAll('[data-overtime-delete-open]').forEach((button) => button.addEventListener('click', () => button.closest('details')?.removeAttribute('open')));
    document.querySelectorAll('[data-overtime-calendar-open]').forEach((button) => button.addEventListener('click', () => {
        const day = document.querySelector('.overtime-calendar-day.is-selected');
        if (!day || !calendar.modal) return;
        const form = calendar.modal.querySelector('[data-overtime-calendar-form]');
        const date = day.dataset.overtimeDate || '';
        form.querySelector('[data-overtime-calendar-date]').value = date;
        form.querySelector('[data-overtime-calendar-label]').value = day.dataset.overtimeCalendarLabel || '';
        const mode = day.dataset.overtimeCalendarMode || 'auto';
        const input = form.querySelector(`input[name="mode"][value="${mode}"]`);
        if (input) input.checked = true;
        calendar.modal.querySelector('[data-overtime-calendar-copy]').textContent = `Atur status kalender untuk ${new Intl.DateTimeFormat('id-ID', { day: 'numeric', month: 'long', year: 'numeric' }).format(new Date(`${date}T00:00:00`))}.`;
    }));
    document.addEventListener('keydown', (event) => { if (event.key === 'Escape') { upload.close(); calendar.close(); deletion.close(); } });
})();
</script>

<?= $this->endSection() ?>
