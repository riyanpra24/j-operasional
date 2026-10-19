<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<?php
$months = [1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'];
$period = $months[$selectedMonth] . ' ' . $selectedYear;
// Excel displays the elapsed-month fraction as a whole percentage.
$proportionalPercent = intdiv($selectedMonth * 100 + 6, 12) . '%';
$displayMarkedValue = static function (string $value): string {
    $escaped = esc($value);
    return str_starts_with($value, '*') ? '<span class="monitoring-negative-value">' . $escaped . '</span>' : $escaped;
};
$displayDeviation = static function (?string $realizationRatio) use ($selectedMonth, $displayMarkedValue): string {
    $deviation = \App\Libraries\MonitoringAnggaran::deviation($realizationRatio, $selectedMonth);
    return $deviation === null ? '—' : $displayMarkedValue(\App\Libraries\LrMoney::percentageDisplay($deviation));
};
$groups = [];
foreach ($monitoringRows as $row) {
    if ($row['group']) {
        $groups[] = ['heading' => $row, 'details' => []];
    } elseif ($groups !== []) {
        $groups[count($groups) - 1]['details'][] = $row;
    }
}
$showSearchResults = $search !== '';
$monitoringNotes = $monitoringNotes ?? [];
$editNotes = function_exists('old') ? old('notes') : null;
$editNotes = is_array($editNotes) ? $editNotes : [];
$monitoringEditOpen = $monitoringEditOpen ?? false;
?>

<style>
    .monitoring-budget-table { min-width: 1100px; }
    .monitoring-budget-table .lr-description-column { width: 22%; }
    .monitoring-budget-table .monitoring-value-column { width: 9.5%; }
    .monitoring-budget-table .monitoring-note-column { width: 21%; }
    .monitoring-budget-table .monitoring-budget-group:hover > * { background: #e5f2ff; }
    .monitoring-budget-table .monitoring-budget-group { cursor: pointer; }
    .monitoring-budget-table tbody td:last-child { color: #000; font-weight: 600; }
    .monitoring-budget-table tbody td:nth-child(2) { color: var(--navy); font-weight: 800; }
    .monitoring-budget-table .monitoring-negative-value { color: #c62828; }
    .monitoring-budget-table td:last-child { white-space: pre-wrap; overflow-wrap: anywhere; }
    .monitoring-budget-table:not(.monitoring-edit-table) tbody td:last-child { text-align: left; }
    .monitoring-edit-dialog { width: min(1500px, calc(100% - 28px)); }
    .monitoring-edit-dialog[open] { display: flex; flex-direction: column; height: calc(100dvh - 28px); max-height: calc(100dvh - 28px); overflow: hidden; }
    .monitoring-edit-dialog form { display: flex; flex: 1 1 auto; flex-direction: column; min-height: 0; overflow: hidden; }
    .monitoring-edit-dialog > .lr-settings-header, .monitoring-edit-dialog .lr-settings-footer { flex: 0 0 auto; }
    .monitoring-edit-context { margin: 5px 0 0; color: #526e89; font-size: 11px; line-height: 1.4; }
    .monitoring-edit-scroll { flex: 1 1 auto; overflow: auto; min-height: 0; border-block: 1px solid #dce7ec; }
    .monitoring-edit-table { width: 100%; min-width: 1050px; }
    .monitoring-edit-table thead { position: sticky; top: 0; z-index: 8; }
    body .content-shell .main-content .monitoring-edit-table thead th:first-child { z-index: 9; background: #22396f !important; }
    .monitoring-edit-table th, .monitoring-edit-table td { padding: 5px 6px; }
    .monitoring-edit-table textarea { box-sizing: border-box; min-width: 0; width: 100%; min-height: 36px; padding: 6px 8px; border: 1px solid #a9cae7; border-radius: 7px; color: #163c66; background: #fff; font: inherit; resize: vertical; }
    .monitoring-edit-table textarea:focus { outline: 2px solid #45afe5; outline-offset: 1px; }
</style>

<section class="page-heading">
    <div>
        <p class="eyebrow">AKUNTANSI</p>
        <h1>Monitoring Anggaran</h1>
        <p>Pantau anggaran dan realisasi beban berdasarkan unit dan periode.</p>
    </div>
</section>

<section class="panel filter-panel lr-rka-filter-panel">
    <form method="get" action="<?= site_url('akutansi/monitoring-anggaran') ?>" class="lr-rka-filter lr-report-filter">
        <div class="form-group lr-filter-unit">
            <label class="lr-upload-label" for="budgetUnit">Unit Kerja</label>
            <div class="lr-rka-filter-control"><span class="lr-rka-filter-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M4 20V6l8-3 8 3v14M8 9h.01M8 13h.01M12 9h.01M12 13h.01M16 9h.01M16 13h.01"></path></svg></span>
                <select class="lr-upload-select" id="budgetUnit" name="unit">
                    <?php foreach ($units as $unit): ?>
                        <option value="<?= esc($unit, 'attr') ?>" <?= $unit === $selectedUnit ? 'selected' : '' ?>><?= esc($unit) ?></option>
                    <?php endforeach ?>
                </select>
            </div>
        </div>
        <div class="form-group lr-filter-month">
            <label class="lr-upload-label" for="budgetMonth">Bulan</label>
            <div class="lr-rka-filter-control"><span class="lr-rka-filter-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><rect x="4" y="5" width="16" height="15" rx="2"></rect><path d="M8 3v4M16 3v4M4 10h16"></path></svg></span>
                <select class="lr-upload-select" id="budgetMonth" name="bulan">
                    <?php foreach ($months as $number => $month): ?>
                        <option value="<?= $number ?>" <?= $number === $selectedMonth ? 'selected' : '' ?>><?= esc($month) ?></option>
                    <?php endforeach ?>
                </select>
            </div>
        </div>
        <div class="form-group lr-filter-year">
            <label class="lr-upload-label" for="budgetYear">Tahun</label>
            <div class="lr-rka-filter-control"><span class="lr-rka-filter-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><rect x="4" y="5" width="16" height="15" rx="2"></rect><path d="M8 3v4M16 3v4M4 10h16"></path></svg></span>
                <input class="lr-upload-select" id="budgetYear" type="number" name="tahun" min="2000" max="2100" value="<?= $selectedYear ?>" required>
            </div>
        </div>
        <div class="form-group lr-filter-lob">
            <label class="lr-upload-label" for="budgetSearch">Cari Uraian</label>
            <div class="lr-rka-filter-control"><span class="lr-rka-filter-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"></circle><path d="m16 16 5 5"></path></svg></span>
                <input class="lr-upload-select" id="budgetSearch" type="search" name="uraian" maxlength="150" value="<?= esc($search, 'attr') ?>" placeholder="Cari uraian beban">
            </div>
        </div>
        <div class="filter-actions lr-rka-filter-actions">
            <button class="btn btn-secondary" type="submit">Filter</button>
            <a class="btn btn-ghost" href="<?= site_url('akutansi/monitoring-anggaran') ?>">Reset</a>
            <details class="lr-action-menu lr-filter-menu">
                <summary class="lr-filter-menu-trigger" aria-label="Menu tindakan Monitoring Anggaran" title="Menu tindakan"><svg aria-hidden="true" viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7" rx="1.4"></rect><rect x="14" y="3" width="7" height="7" rx="1.4"></rect><rect x="3" y="14" width="7" height="7" rx="1.4"></rect><rect x="14" y="14" width="7" height="7" rx="1.4"></rect></svg></summary>
                <div class="lr-action-menu-popover" role="menu" aria-label="Menu Monitoring Anggaran"><button type="button" role="menuitem" class="lr-action-menu-item" data-monitoring-edit-open aria-haspopup="dialog" aria-controls="monitoringEditDialog">Edit Anggaran</button><a role="menuitem" class="lr-action-menu-item" href="<?= esc(site_url('akutansi/monitoring-anggaran/export') . '?' . http_build_query(['unit' => $selectedUnit, 'tahun' => $selectedYear, 'bulan' => $selectedMonth, 'uraian' => $search]), 'attr') ?>">Export Excel</a></div>
            </details>
        </div>
    </form>
</section>

<section class="panel lr-report-panel table-title-flat">
    <header class="lr-report-header table-title-flat__header">
        <div><p>MONITORING BEBAN ANGGARAN <?= $selectedYear ?></p><h2>Data Monitoring Anggaran</h2></div>
        <span><?= esc($selectedUnit) ?> · YTD <?= esc($period) ?></span>
    </header>
    <div class="lr-report-scroll table-title-flat__table" role="region" aria-label="Tabel monitoring anggaran" tabindex="0">
        <table class="lr-report-table lr-rka-table monitoring-budget-table">
            <colgroup><col class="lr-description-column"><col span="6" class="monitoring-value-column"><col class="monitoring-note-column"></colgroup>
            <thead><tr>
                <th scope="col">Uraian</th>
                <th scope="col">RKA <?= $selectedYear ?></th>
                <th scope="col">Realisasi YTD <?= esc($period) ?></th>
                <th scope="col">% Realisasi</th>
                <th scope="col">% Proporsional YTD <?= esc($period) ?></th>
                <th scope="col">Deviasi</th>
                <th scope="col">Sisa Anggaran</th>
                <th scope="col">Penjelasan</th>
            </tr></thead>
            <tbody>
                <?php if ($monitoringRows === []): ?>
                    <tr><td class="bukti-bayar-empty" colspan="8"><strong>Uraian tidak ditemukan</strong><span>Coba kata pencarian lain.</span></td></tr>
                <?php else: ?>
                    <?php foreach ($groups as $groupIndex => $group): ?>
                        <?php
                        $groupId = 'monitoring-budget-' . $groupIndex;
                        $detailIds = array_map(static fn ($index) => $groupId . '-detail-' . $index, array_keys($group['details']));
                        $heading = $group['heading'];
                        ?>
                        <tr class="lr-row-subgroup monitoring-budget-group" data-monitoring-group>
                            <th scope="row"><button type="button" class="lr-group-toggle" data-monitoring-toggle aria-expanded="<?= $showSearchResults ? 'true' : 'false' ?>" aria-controls="<?= esc(implode(' ', $detailIds), 'attr') ?>"><span class="lr-group-chevron" aria-hidden="true">›</span><span><?= esc($heading['label']) ?></span></button></th>
                            <td><?= $heading['amount'] === null ? '—' : esc(\App\Libraries\RkaMoney::display($heading['amount'])) ?></td>
                            <td><?= ($heading['realization'] ?? null) === null ? '—' : $displayMarkedValue(\App\Libraries\LrMoney::reportDisplay($heading['realization'])) ?></td><td><?= ($heading['realization_ratio'] ?? null) === null ? '—' : $displayMarkedValue(\App\Libraries\LrMoney::percentageDisplay($heading['realization_ratio'])) ?></td><td><?= esc($proportionalPercent) ?></td><td><?= $displayDeviation($heading['realization_ratio'] ?? null) ?></td><td><?= ($heading['remaining_budget'] ?? null) === null ? '—' : $displayMarkedValue(\App\Libraries\LrMoney::reportDisplay($heading['remaining_budget'])) ?></td><td><?= ($monitoringNotes[$heading['key']] ?? '') === '' ? '—' : esc($monitoringNotes[$heading['key']]) ?></td>
                        </tr>
                        <?php foreach ($group['details'] as $detailIndex => $row): ?>
                            <tr id="<?= esc($detailIds[$detailIndex], 'attr') ?>" class="lr-row-detail monitoring-budget-detail" <?= $showSearchResults ? '' : 'hidden' ?>>
                                <th scope="row"><?= esc($row['label']) ?></th>
                                <td><?= $row['amount'] === null ? '—' : esc(\App\Libraries\RkaMoney::display($row['amount'])) ?></td>
                                <td><?= ($row['realization'] ?? null) === null ? '—' : $displayMarkedValue(\App\Libraries\LrMoney::reportDisplay($row['realization'])) ?></td><td><?= ($row['realization_ratio'] ?? null) === null ? '—' : $displayMarkedValue(\App\Libraries\LrMoney::percentageDisplay($row['realization_ratio'])) ?></td><td><?= esc($proportionalPercent) ?></td><td><?= $displayDeviation($row['realization_ratio'] ?? null) ?></td><td><?= ($row['remaining_budget'] ?? null) === null ? '—' : $displayMarkedValue(\App\Libraries\LrMoney::reportDisplay($row['remaining_budget'])) ?></td><td><?= ($monitoringNotes[$row['key']] ?? '') === '' ? '—' : esc($monitoringNotes[$row['key']]) ?></td>
                            </tr>
                        <?php endforeach ?>
                    <?php endforeach ?>
                <?php endif ?>
            </tbody>
        </table>
    </div>
    <?php if (! $hasRka): ?><p>RKA untuk unit dan tahun ini belum tersedia.</p><?php elseif ($selectedUnit === 'Konsolidasi'): ?><p>Konsolidasi mengikuti data unit yang tersedia pada menu RKA.</p><?php endif ?>
    <?php if (isset($hasRealization) && ! $hasRealization): ?><p>Realisasi laba rugi YTD untuk unit dan periode ini belum tersedia.</p><?php endif ?>
    <?php if ($hasRealization ?? false): ?><p>Tanda — berarti realisasi uraian belum tersedia. Total kelompok hanya ditampilkan jika seluruh rinciannya tersedia.</p><?php endif ?>
</section>

<dialog id="monitoringEditDialog" class="lr-settings-dialog monitoring-edit-dialog" aria-labelledby="monitoringEditTitle" data-auto-open="<?= $monitoringEditOpen ? 'true' : 'false' ?>">
    <header class="lr-settings-header"><div><p class="eyebrow">AKUNTANSI / MONITORING ANGGARAN</p><h2 id="monitoringEditTitle">Edit Anggaran</h2><p class="monitoring-edit-context">Unit kerja: <?= esc($selectedUnit) ?> · Periode: <?= esc($period) ?></p></div><button type="button" class="icon-btn" data-monitoring-edit-close aria-label="Tutup popup edit">×</button></header>
    <form method="post" action="<?= site_url('akutansi/monitoring-anggaran/penjelasan') ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="unit" value="<?= esc($selectedUnit, 'attr') ?>"><input type="hidden" name="tahun" value="<?= $selectedYear ?>"><input type="hidden" name="bulan" value="<?= $selectedMonth ?>"><input type="hidden" name="uraian" value="<?= esc($search, 'attr') ?>">
        <div class="monitoring-edit-scroll" role="region" aria-label="Tabel edit monitoring anggaran" tabindex="0">
            <table class="lr-report-table lr-rka-table monitoring-budget-table monitoring-edit-table">
                <colgroup><col style="width:23%"><col style="width:10%"><col style="width:12%"><col style="width:8%"><col style="width:12%"><col style="width:8%"><col style="width:10%"><col style="width:17%"></colgroup>
                <thead><tr><th>Uraian</th><th>RKA <?= $selectedYear ?></th><th>Realisasi YTD <?= esc($period) ?></th><th>% Realisasi</th><th>% Proporsional YTD <?= esc($period) ?></th><th>Deviasi</th><th>Sisa Anggaran</th><th>Penjelasan</th></tr></thead>
                <tbody>
                    <?php foreach ($groups as $groupIndex => $group): ?>
                    <?php
                    $editGroupId = 'monitoring-edit-' . $groupIndex;
                    $editDetailIds = array_map(static fn ($index) => $editGroupId . '-detail-' . $index, array_keys($group['details']));
                    $editRows = array_merge([$group['heading']], $group['details']);
                    ?>
                    <?php foreach ($editRows as $editIndex => $editRow): ?>
                        <?php $key = $editRow['key']; $note = $monitoringNotes[$key] ?? ''; ?>
                        <tr class="<?= $editRow['group'] ? 'lr-row-subgroup monitoring-budget-group' : 'lr-row-detail monitoring-edit-detail' ?>" <?php if ($editRow['group']): ?>data-monitoring-group<?php else: ?>id="<?= esc($editDetailIds[$editIndex - 1], 'attr') ?>" <?= $showSearchResults ? '' : 'hidden' ?><?php endif ?>>
                            <th scope="row"><?php if ($editRow['group']): ?><button type="button" class="lr-group-toggle" data-monitoring-toggle aria-expanded="<?= $showSearchResults ? 'true' : 'false' ?>" aria-controls="<?= esc(implode(' ', $editDetailIds), 'attr') ?>"><span class="lr-group-chevron" aria-hidden="true">›</span><span><?= esc($editRow['label']) ?></span></button><?php else: ?><?= esc($editRow['label']) ?><?php endif ?></th>
                            <td><?= $editRow['amount'] === null ? '—' : esc(\App\Libraries\RkaMoney::display($editRow['amount'])) ?></td>
                            <td><?= ($editRow['realization'] ?? null) === null ? '—' : $displayMarkedValue(\App\Libraries\LrMoney::reportDisplay($editRow['realization'])) ?></td>
                            <td><?= ($editRow['realization_ratio'] ?? null) === null ? '—' : $displayMarkedValue(\App\Libraries\LrMoney::percentageDisplay($editRow['realization_ratio'])) ?></td>
                            <td><?= esc($proportionalPercent) ?></td><td><?= $displayDeviation($editRow['realization_ratio'] ?? null) ?></td>
                            <td><?= ($editRow['remaining_budget'] ?? null) === null ? '—' : $displayMarkedValue(\App\Libraries\LrMoney::reportDisplay($editRow['remaining_budget'])) ?></td>
                            <td><textarea name="notes[<?= esc($key, 'attr') ?>]" rows="1" maxlength="2000" aria-label="Penjelasan <?= esc($editRow['label'], 'attr') ?>"><?= esc($editNotes[$key] ?? $note) ?></textarea><textarea name="original[<?= esc($key, 'attr') ?>]" hidden><?= esc($note) ?></textarea></td>
                        </tr>
                    <?php endforeach ?>
                    <?php endforeach ?>
                    <?php if ($monitoringRows === []): ?><tr><td colspan="8">Uraian tidak ditemukan.</td></tr><?php endif ?>
                </tbody>
            </table>
        </div>
        <footer class="lr-settings-footer"><button type="button" class="btn btn-secondary" data-monitoring-edit-close>Batal</button><button type="submit" class="btn btn-primary" <?= $monitoringRows === [] ? 'disabled' : '' ?>>Simpan Perubahan</button></footer>
    </form>
</dialog>

<script>
    document.querySelectorAll('.monitoring-budget-table [data-monitoring-group]').forEach((row) => {
        const button = row.querySelector('[data-monitoring-toggle]');
        row.addEventListener('click', (event) => {
            if (event.target.closest('textarea, input, select, a')) return;
            const expanded = button.getAttribute('aria-expanded') !== 'true';
            button.setAttribute('aria-expanded', String(expanded));
            button.getAttribute('aria-controls').split(' ').forEach((id) => {
                const detail = document.getElementById(id);
                if (detail) detail.hidden = !expanded;
            });
        });
    });
    const monitoringDialog = document.getElementById('monitoringEditDialog');
    document.querySelector('[data-monitoring-edit-open]')?.addEventListener('click', (event) => {
        event.currentTarget.closest('details')?.removeAttribute('open');
        monitoringDialog?.showModal();
    });
    monitoringDialog?.querySelectorAll('[data-monitoring-edit-close]').forEach((button) => button.addEventListener('click', () => monitoringDialog.close()));
    if (monitoringDialog?.dataset.autoOpen === 'true') monitoringDialog.showModal();
</script>

<?= $this->endSection() ?>
