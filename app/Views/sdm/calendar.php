<?php
/** @var int $year */
/** @var int $month */
/** @var string $monthLabel */
/** @var list<array<string, mixed>> $days */
/** @var int $leadingDays */
/** @var int $importId */
/** @var bool $autoOpen */
/** @var string $previousUrl */
/** @var string $nextUrl */

$monthNames = [
    1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
    5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
    9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
];
?>

<div
    class="input-modal attendance-calendar-modal"
    id="attendanceCalendarModal"
    data-calendar-auto-open="<?= $autoOpen ? 'true' : 'false' ?>"
    data-calendar-data-url="<?= site_url('sdm/kalender') ?>"
    data-calendar-year="<?= $year ?>"
    data-calendar-month="<?= $month ?>"
    hidden
    aria-hidden="true"
>
    <button type="button" class="modal-backdrop" data-calendar-close aria-label="Tutup kalender"></button>
    <section class="modal-dialog attendance-calendar-popup-dialog" role="dialog" aria-modal="true" aria-labelledby="attendanceCalendarModalTitle">
        <header class="modal-header">
            <div class="modal-title-group">
                <span class="modal-title-icon" aria-hidden="true">▦</span>
                <div><p>SDM &amp; TELLER</p><h2 id="attendanceCalendarModalTitle">Kalender Kehadiran</h2></div>
            </div>
            <button type="button" class="modal-close" data-calendar-close aria-label="Tutup">×</button>
        </header>

        <div class="attendance-calendar-popup-body">
            <section class="attendance-calendar-main" aria-label="Kalender <?= esc($monthLabel, 'attr') ?>">
                <header class="attendance-calendar-toolbar">
                    <div><h3 data-calendar-month-title><?= esc($monthLabel) ?></h3><p>Klik tanggal untuk mengatur hari kerja atau hari libur.</p></div>
                    <div class="attendance-calendar-navigation">
                        <button type="button" class="btn btn-ghost btn-sm" data-calendar-previous aria-label="Bulan sebelumnya">←</button>
                        <form class="attendance-calendar-picker" data-calendar-picker-form>
                            <label class="sr-only" for="calendar_month">Bulan</label>
                            <select id="calendar_month" name="kalender_bulan" required>
                                <?php foreach ($monthNames as $number => $name): ?>
                                    <option value="<?= $number ?>" <?= $month === $number ? 'selected' : '' ?>><?= esc($name) ?></option>
                                <?php endforeach ?>
                            </select>
                            <label class="sr-only" for="calendar_year">Tahun</label>
                            <input id="calendar_year" type="number" name="kalender_tahun" value="<?= $year ?>" min="2020" max="2100" inputmode="numeric" required>
                            <button type="submit" class="btn btn-secondary btn-sm">Tampilkan</button>
                        </form>
                        <button type="button" class="btn btn-ghost btn-sm" data-calendar-next aria-label="Bulan berikutnya">→</button>
                    </div>
                </header>

                <div class="attendance-calendar-legend" aria-label="Keterangan kalender">
                    <span><i class="calendar-legend-dot is-workday"></i>Hari kerja</span>
                    <span><i class="calendar-legend-dot is-weekend"></i>Akhir pekan</span>
                    <span><i class="calendar-legend-dot is-national"></i>Libur nasional/cuti bersama</span>
                    <span><i class="calendar-legend-dot is-manual"></i>Diatur manual</span>
                </div>
                <div class="attendance-calendar-load-error" data-calendar-load-error hidden></div>

                <div data-calendar-month-content>
                    <?= view('sdm/_calendar_month', compact('monthLabel', 'days', 'leadingDays')) ?>
                </div>
            </section>

            <aside class="attendance-calendar-editor">
                <div class="attendance-calendar-editor-heading">
                    <small>PENGATURAN TANGGAL</small>
                    <h3>Libur atau Hari Kerja</h3>
                    <p>Pilih salah satu tanggal pada kalender.</p>
                </div>
                <form action="<?= site_url('sdm/kalender') ?>" method="post">
                    <?= csrf_field() ?>
                    <input type="hidden" name="import_id" value="<?= $importId ?>">
                    <input type="hidden" name="calendar_date" data-calendar-date>
                    <div class="attendance-calendar-selected-date">
                        <small>TANGGAL DIPILIH</small><strong data-calendar-date-label>Belum dipilih</strong>
                    </div>
                    <div class="form-group">
                        <label for="calendar_mode">Status tanggal</label>
                        <select id="calendar_mode" name="mode" data-calendar-mode required disabled>
                            <option value="auto">Otomatis sesuai kalender</option>
                            <option value="holiday">Libur</option>
                            <option value="workday">Hari kerja</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="calendar_label">Keterangan</label>
                        <input id="calendar_label" type="text" name="label" maxlength="150" placeholder="Pilih tanggal terlebih dahulu" data-calendar-label disabled>
                    </div>
                    <div class="attendance-calendar-impact-note">Perubahan langsung memengaruhi kode <strong>OFF</strong> dan <strong>BL</strong> pada Detail Absensi Harian.</div>
                    <button type="submit" class="btn btn-primary" data-calendar-submit disabled>Simpan Pengaturan</button>
                </form>
            </aside>
        </div>
    </section>
</div>
