<?php
/** @var list<array<string, mixed>> $employees */
/** @var list<array<string, mixed>> $imports */
/** @var int|null $selectedImportId */
/** @var array{keyword: string, perPage: int} $filters */
/** @var \CodeIgniter\Pager\PagerInterface $pager */
?>

<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<section class="page-heading">
    <div>
        <p class="eyebrow">SDM &amp; TELLER</p>
        <h1>SDM Jatim</h1>
        <p>Daftar karyawan yang bersumber dari rekap kehadiran SDM Jatim.</p>
    </div>
</section>

<section class="panel filter-panel">
    <form method="get" action="<?= site_url('sdm/sdm-jatim') ?>" class="agendaris-filter-form">
        <div class="form-group search-group">
            <label for="sdm_jatim_search">Cari SDM</label>
            <div class="input-with-icon">
                <span aria-hidden="true">⌕</span>
                <input id="sdm_jatim_search" type="search" name="q" value="<?= esc($filters['keyword'], 'attr') ?>" placeholder="Nama, NPP, jabatan, atau bagian" autocomplete="off">
            </div>
        </div>
        <div class="form-group">
            <label for="sdm_jatim_import">Periode rekap</label>
            <select id="sdm_jatim_import" name="import_id">
                <?php if ($imports === []): ?>
                    <option value="">Belum ada rekap kehadiran</option>
                <?php else: ?>
                    <?php foreach ($imports as $import): ?>
                        <option value="<?= (int) $import['id'] ?>" <?= $selectedImportId === (int) $import['id'] ? 'selected' : '' ?>><?= esc($import['period_label']) ?></option>
                    <?php endforeach ?>
                <?php endif ?>
            </select>
        </div>
        <input type="hidden" name="per_page" value="<?= (int) $filters['perPage'] ?>">
        <div class="filter-actions">
            <button type="submit" class="btn btn-secondary">Terapkan</button>
            <a href="<?= site_url('sdm/sdm-jatim') ?>" class="btn btn-ghost">Reset</a>
        </div>
    </form>
</section>

<section class="panel register-panel agendaris-table-panel">
    <header class="panel-header">
        <div>
            <h2>Daftar SDM Jatim</h2>
            <p>Data diambil dari periode rekap yang dipilih.</p>
        </div>
    </header>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>No.</th>
                    <th>NPP</th>
                    <th>Nama Karyawan</th>
                    <th>Jabatan</th>
                    <th>Bagian</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($employees === []): ?>
                    <tr>
                        <td colspan="5">
                            <div class="empty-state">
                                <span aria-hidden="true">♙</span>
                                <strong>Belum ada data SDM</strong>
                                <p>Pilih rekap kehadiran yang telah tersedia untuk menampilkan daftar SDM Jatim.</p>
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php $number = (($pager->getCurrentPage('sdm_jatim_list') - 1) * $filters['perPage']) + 1; ?>
                    <?php foreach ($employees as $employee): ?>
                        <tr>
                            <td><strong><?= $number++ ?></strong></td>
                            <td><?= esc($employee['employee_no'] ?: '-') ?></td>
                            <td><strong><?= esc($employee['employee_name']) ?></strong></td>
                            <td><?= esc($employee['position'] ?: '-') ?></td>
                            <td><?= esc($employee['organization'] ?: '-') ?></td>
                        </tr>
                    <?php endforeach ?>
                <?php endif ?>
            </tbody>
        </table>
    </div>

    <div class="table-list-footer">
        <form method="get" action="<?= site_url('sdm/sdm-jatim') ?>" class="table-length-form">
            <input type="hidden" name="q" value="<?= esc($filters['keyword'], 'attr') ?>">
            <?php if ($selectedImportId !== null): ?><input type="hidden" name="import_id" value="<?= (int) $selectedImportId ?>"><?php endif ?>
            <label for="sdm_jatim_per_page">Tampilkan</label>
            <select id="sdm_jatim_per_page" name="per_page" onchange="this.form.submit()">
                <?php foreach ([10, 20, 50, 100] as $size): ?>
                    <option value="<?= $size ?>" <?= $filters['perPage'] === $size ? 'selected' : '' ?>><?= $size ?></option>
                <?php endforeach ?>
            </select>
            <span>data</span>
        </form>
        <?php if ($employees !== []): ?><div class="pagination-wrap"><?= $pager->links('sdm_jatim_list', 'default_full') ?></div><?php endif ?>
    </div>
</section>

<?= $this->endSection() ?>
