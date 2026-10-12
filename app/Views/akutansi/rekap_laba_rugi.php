<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<?php
$months = [1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'];
$previousMonth = $selectedMonth === 1 ? 12 : $selectedMonth - 1;
$previousYear = $selectedMonth === 1 ? $selectedYear - 1 : $selectedYear;
$displayRealisasi = static function (?string $amount): string {
    if ($amount === null) return '—';
    return \App\Libraries\LrMoney::isZero($amount) ? '-' : \App\Libraries\LrMoney::reportDisplay($amount);
};
$displayPercentage = static function (?string $ratio): string {
    return $ratio === null ? '—' : \App\Libraries\LrMoney::percentageDisplay($ratio);
};
$editAmount = static function (?string $amount): string {
    return $amount === null ? '' : \App\Libraries\LrMoney::exactFormat($amount);
};
?>

<section class="page-heading lr-page-heading">
    <div>
        <p class="eyebrow">AKUTANSI</p>
        <h1>Rekap Laba Rugi</h1>
        <p class="lr-rekap-intro">Ringkasan indikator Laba/Rugi per unit kerja dan periode pelaporan.</p>
    </div>
</section>

<section class="panel lr-rka-filter-panel lr-rekap-filter-panel">
    <form method="get" action="<?= site_url('akutansi/rekap-laba-rugi') ?>" class="lr-rka-filter lr-rekap-filter">
        <div class="lr-filter-unit">
            <label class="lr-upload-label" for="lrRecapUnit">Unit Kerja</label>
            <select class="lr-upload-select" id="lrRecapUnit" name="unit_kerja">
                <?php foreach ($reportUnits as $unit): ?>
                    <option value="<?= esc($unit, 'attr') ?>" <?= $unit === $selectedUnit ? 'selected' : '' ?>><?= esc($unit) ?></option>
                <?php endforeach ?>
            </select>
        </div>
        <div class="lr-filter-month">
            <label class="lr-upload-label" for="lrRecapMonth">Bulan</label>
            <select class="lr-upload-select" id="lrRecapMonth" name="bulan">
                <?php foreach ($months as $monthNumber => $monthName): ?>
                    <option value="<?= $monthNumber ?>" <?= $monthNumber === $selectedMonth ? 'selected' : '' ?>><?= esc($monthName) ?></option>
                <?php endforeach ?>
            </select>
        </div>
        <div class="lr-filter-year">
            <label class="lr-upload-label" for="lrRecapYear">Tahun</label>
            <input class="lr-upload-select" id="lrRecapYear" type="number" name="tahun" min="2000" max="2100" step="1" value="<?= $selectedYear ?>" required>
        </div>
        <button type="submit" class="btn btn-secondary">Terapkan</button>
        <a class="btn btn-ghost" href="<?= site_url('akutansi/rekap-laba-rugi') ?>">Reset</a>
        <details class="lr-action-menu lr-filter-menu">
            <summary class="lr-filter-menu-trigger" aria-label="Menu tindakan Rekap Laba Rugi" title="Menu tindakan">
                <svg aria-hidden="true" viewBox="0 0 24 24" focusable="false"><rect x="3" y="3" width="7" height="7" rx="1.4"></rect><rect x="14" y="3" width="7" height="7" rx="1.4"></rect><rect x="3" y="14" width="7" height="7" rx="1.4"></rect><rect x="14" y="14" width="7" height="7" rx="1.4"></rect></svg>
            </summary>
            <div class="lr-action-menu-popover" role="menu" aria-label="Menu Rekap Laba Rugi">
                <button type="button" role="menuitem" class="lr-action-menu-item" data-recap-edit-open aria-haspopup="dialog" aria-controls="lrRecapEditDialog">Edit Rekap</button>
                <span class="lr-action-menu-divider" aria-hidden="true"></span>
                <button type="button" role="menuitem" class="lr-action-menu-item is-danger" data-recap-delete-open>Hapus Rekap</button>
            </div>
        </details>
    </form>
</section>

<dialog id="lrRecapEditDialog" class="lr-settings-dialog lr-rekap-edit-dialog" aria-labelledby="lrRecapEditTitle" data-auto-open="<?= $recapEditAutoOpen ? 'true' : 'false' ?>">
    <header class="lr-settings-header">
        <div>
            <p class="eyebrow">AKUTANSI / REKAP LABA RUGI</p>
            <h2 id="lrRecapEditTitle">Edit Rekap</h2>
        </div>
        <button type="button" class="icon-btn" data-recap-edit-close aria-label="Tutup popup edit">×</button>
    </header>
    <form method="post" action="<?= site_url('akutansi/rekap-laba-rugi/edit') ?>" data-recap-edit-form>
        <?= csrf_field() ?>
        <input type="hidden" name="bulan" value="<?= $selectedMonth ?>">
        <input type="hidden" name="tahun" value="<?= $selectedYear ?>">
        <div class="lr-rekap-edit-toolbar">
            <p class="lr-rka-help">Rekap menyalin nilai awal dari Laporan Laba/Rugi. Perubahan di sini hanya berlaku pada Rekap <?= esc($selectedUnit) ?> · <?= esc($months[$selectedMonth]) ?> <?= $selectedYear ?> dan tidak mengubah laporan sumber. RKA berasal dari RKA Kanwil dan terkunci, kecuali <strong>IJP Cash Basis</strong> yang belum tersedia pada RKA Kanwil.</p>
            <label class="lr-upload-label" for="lrRecapEditUnit">Unit Kerja
                <select class="lr-upload-select" id="lrRecapEditUnit" name="unit_kerja" data-recap-edit-unit>
                    <?php foreach ($reportUnits as $unit): ?>
                        <option value="<?= esc($unit, 'attr') ?>" <?= $unit === $selectedUnit ? 'selected' : '' ?>><?= esc($unit) ?></option>
                    <?php endforeach ?>
                </select>
            </label>
        </div>
        <div class="lr-rekap-edit-table-wrap" tabindex="0" role="region" aria-label="Tabel edit rekap laba rugi">
            <table class="lr-rekap-table lr-rekap-edit-table">
                <thead>
                    <tr>
                        <th scope="col">No.</th>
                        <th scope="col">Indikator</th>
                        <th scope="col">RKA <?= $selectedYear ?></th>
                        <th scope="col">Realisasi<br>S.D. <?= esc(strtoupper($months[$previousMonth])) ?> <?= $previousYear ?></th>
                        <th scope="col">Prognosa<br><?= esc(strtoupper($months[$selectedMonth])) ?></th>
                        <th scope="col">Prognosa<br>S.D. <?= esc(strtoupper($months[$selectedMonth])) ?></th>
                        <th scope="col">% thd RKA</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recapIndicators as $number => $indicator): ?>
                        <?php $label = $indicator['label']; ?>
                        <tr data-recap-edit-row>
                            <td><?= $number + 1 ?></td>
                            <th scope="row"><?= esc($label) ?></th>
                            <?php if ($label === 'IJP CASH BASIS'): ?>
                                <td><input type="text" inputmode="decimal" name="values[<?= esc($label, 'attr') ?>][rka]" value="<?= esc($editAmount($indicator['rka'])) ?>" data-recap-edit-input data-recap-field="rka" aria-label="<?= esc($label) ?> rka"></td>
                            <?php else: ?>
                                <td class="lr-rekap-readonly-cell"><span class="lr-rekap-readonly" data-recap-field="rka" data-recap-amount="<?= esc((string) ($indicator['rka'] ?? ''), 'attr') ?>" title="Nilai RKA dari RKA Kanwil; tidak dapat diedit"><svg aria-hidden="true" viewBox="0 0 24 24" focusable="false"><path d="M7.5 10V7.5a4.5 4.5 0 0 1 9 0V10"></path><rect x="5" y="10" width="14" height="10" rx="2"></rect><path d="M12 14v2"></path></svg><?= $indicator['rka'] === null ? '—' : esc(\App\Libraries\RkaMoney::reportDisplay($indicator['rka'])) ?></span></td>
                            <?php endif ?>
                            <?php foreach (['realisasi', 'prognosa'] as $field): ?>
                                <td><input type="text" inputmode="decimal" name="values[<?= esc($label, 'attr') ?>][<?= $field ?>]" value="<?= esc($editAmount($indicator[$field])) ?>" data-recap-edit-input data-recap-field="<?= $field ?>" aria-label="<?= esc($label) ?> <?= $field ?>"></td>
                            <?php endforeach ?>
                            <td><output data-recap-prognosa-ytd><?= $displayRealisasi($indicator['prognosa_ytd']) ?></output></td>
                            <td><output data-recap-percentage><?= $displayPercentage($indicator['persentase_rka']) ?></output></td>
                        </tr>
                    <?php endforeach ?>
                </tbody>
            </table>
        </div>
        <footer class="lr-settings-footer">
            <button type="button" class="btn btn-secondary" data-recap-edit-close>Batal</button>
            <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
        </footer>
    </form>
</dialog>

<section class="panel lr-rekap-panel" aria-labelledby="lrRecapTitle">
    <header class="lr-rekap-header">
        <div>
            <p>REKAP LABA / RUGI</p>
            <h2 id="lrRecapTitle"><?= esc($selectedUnit) ?> · <?= esc($months[$selectedMonth]) ?> <?= $selectedYear ?></h2>
        </div>
        <span><?= $rkaAvailable ? 'RKA tersedia' : 'RKA belum tersedia' ?></span>
    </header>
    <div class="lr-rekap-table-wrap" tabindex="0" role="region" aria-label="Tabel rekap Laba Rugi">
        <table class="lr-rekap-table">
            <caption>Rekap Laba Rugi <?= esc($selectedUnit) ?> periode <?= esc($months[$selectedMonth]) ?> <?= $selectedYear ?></caption>
            <thead>
                <tr>
                    <th scope="col">No.</th>
                    <th scope="col">Indikator</th>
                    <th scope="col">RKA <?= $selectedYear ?></th>
                    <th scope="col">Realisasi<br>S.D. <?= esc(strtoupper($months[$previousMonth])) ?> <?= $previousYear ?></th>
                    <th scope="col">Prognosa<br><?= esc(strtoupper($months[$selectedMonth])) ?></th>
                    <th scope="col">Prognosa<br>S.D. <?= esc(strtoupper($months[$selectedMonth])) ?></th>
                    <th scope="col">% thd RKA</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recapIndicators as $number => $indicator): ?>
                    <tr>
                        <td><?= $number + 1 ?></td>
                        <th scope="row"><?= esc($indicator['label']) ?></th>
                        <td><?= $indicator['rka'] === null ? '—' : esc(\App\Libraries\RkaMoney::reportDisplay($indicator['rka'])) ?></td>
                        <td><?= $displayRealisasi($indicator['realisasi']) ?></td>
                        <td><?= $displayRealisasi($indicator['prognosa']) ?></td>
                        <td><?= $displayRealisasi($indicator['prognosa_ytd']) ?></td>
                        <td><?= $displayPercentage($indicator['persentase_rka']) ?></td>
                    </tr>
                <?php endforeach ?>
            </tbody>
        </table>
    </div>
</section>
<?= $this->endSection() ?>
