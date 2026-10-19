<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<style>
    @media (min-width: 1200px) {
        body .content-shell .main-content .filter-panel>form.asset-filter-form {
            flex-wrap: nowrap !important;
        }

        body .content-shell .main-content .filter-panel>form.asset-filter-form>.form-group {
            flex: 1 1 0;
            min-width: 0;
        }

        body .content-shell .main-content .filter-panel>form.asset-filter-form>.search-group {
            flex: 1.35 1 0;
        }
    }

    .table-wrap .asset-table {
        width: 100%;
        min-width: 900px;
        table-layout: fixed;
    }

    .asset-table th {
        box-sizing: border-box;
        padding: 9px 8px;
        white-space: normal;
        text-align: center;
    }

    .asset-table thead th:nth-child(1) {
        width: 20%;
    }

    .asset-table thead th:nth-child(2) {
        width: 17%;
    }

    .asset-table thead th:nth-child(3) {
        width: 7%;
    }

    .asset-table thead th:nth-child(4) {
        width: 9%;
    }

    .asset-table thead th:nth-child(5) {
        width: 12%;
    }

    .asset-table thead th:nth-child(6) {
        width: 13%;
    }

    .asset-table thead th:nth-child(7) {
        width: 9%;
    }

    .asset-table thead th:nth-child(8) {
        width: 13%;
    }

    .asset-table td {
        padding: 9px 8px;
        white-space: normal;
        overflow-wrap: anywhere;
    }

    .asset-table .asset-empty {
        position: sticky;
        left: 0;
        width: min(80vw, 900px);
    }

    .asset-table .asset-group>th {
        background: #eaf4fc;
        color: #000;
        font-weight: 800;
        text-align: left;
        text-transform: uppercase;
    }

    .asset-table .asset-detail>td {
        color: #40566e;
    }

    .asset-table .asset-detail>td:nth-child(1),
    .asset-table .asset-detail>td:nth-child(2),
    .asset-table .asset-detail>td:nth-child(4) {
        color: #000;
        font-weight: 700;
    }

    .asset-table .asset-detail>td:first-child {
        padding-left: 18px;
    }

    .asset-table .asset-group-name {
        display: block;
        white-space: normal;
        overflow-wrap: anywhere;
    }

    .asset-table .action-buttons {
        display: flex;
        flex-wrap: nowrap;
        gap: 4px;
    }

    .asset-table .action-buttons .icon-btn {
        flex: 0 0 29px;
    }

    .asset-table .asset-period strong {
        color: #263f60;
    }

    .asset-table .asset-period .asset-period-start {
        color: #637b94;
    }
</style>

<section class="page-heading heading-actions">
    <div>
        <h1>Data Aset</h1>
        <p>Daftar aset tetap dan masa manfaatnya. Masa manfaat dihitung otomatis berdasarkan tanggal, dengan sisa manfaat mengikuti waktu sistem.</p>
    </div>
</section>

<section class="panel filter-panel">
    <form action="<?= site_url('bagian-umum-1/data-aset') ?>" method="get" class="filter-form asset-filter-form">
        <input type="hidden" name="per_page" value="<?= (int) $filters['perPage'] ?>">
        <div class="form-group search-group">
            <label for="assetSearch">Cari aset</label>
            <div class="input-with-icon"><span aria-hidden="true">⌕</span><input id="assetSearch" type="search" name="q" maxlength="150" value="<?= esc($filters['q'], 'attr') ?>" placeholder="Nama, kode, atau nomor aset"></div>
        </div>
        <div class="form-group">
            <label for="assetGroup">Kelompok aset</label>
            <select id="assetGroup" name="kelompok">
                <option value="">Semua kelompok</option><?php foreach ($assetGroups as $group): ?><option value="<?= esc($group, 'attr') ?>" <?= $filters['kelompok'] === $group ? 'selected' : '' ?>><?= esc($group) ?></option><?php endforeach ?>
            </select>
        </div>
        <div class="form-group">
            <label for="assetStatus">Status aset</label>
            <select id="assetStatus" name="status">
                <option value="">Semua status</option><?php foreach ($assetStatuses as $status): ?><option value="<?= esc($status, 'attr') ?>" <?= $filters['status'] === $status ? 'selected' : '' ?>><?= esc($status) ?></option><?php endforeach ?>
            </select>
        </div>
        <div class="form-group"><label for="assetFrom">Perolehan dari</label><input id="assetFrom" type="date" name="dari" value="<?= esc($filters['dari'], 'attr') ?>"></div>
        <div class="form-group"><label for="assetTo">Perolehan sampai</label><input id="assetTo" type="date" name="sampai" value="<?= esc($filters['sampai'], 'attr') ?>"></div>
        <div class="filter-actions">
            <button class="btn btn-secondary" type="submit">Filter</button><a class="btn btn-ghost" href="<?= site_url('bagian-umum-1/data-aset') ?>">Reset</a>
            <details class="lr-action-menu lr-filter-menu">
                <summary class="lr-filter-menu-trigger" aria-label="Menu tindakan Data Aset" title="Menu tindakan"><svg aria-hidden="true" viewBox="0 0 24 24" focusable="false">
                        <rect x="3" y="3" width="7" height="7" rx="1.4"></rect>
                        <rect x="14" y="3" width="7" height="7" rx="1.4"></rect>
                        <rect x="3" y="14" width="7" height="7" rx="1.4"></rect>
                        <rect x="14" y="14" width="7" height="7" rx="1.4"></rect>
                    </svg></summary>
                <div class="lr-action-menu-popover" role="menu" aria-label="Menu Data Aset"><a href="<?= site_url('bagian-umum-1/data-aset/tambah') ?>" role="menuitem" class="lr-action-menu-item" data-asset-create>Tambah Aset</a><?php if ($isAdmin ?? false): ?><button type="button" role="menuitem" class="lr-action-menu-item" data-asset-import aria-haspopup="dialog" aria-controls="assetImportModal">Upload Excel Aset</button><?php endif ?></div>
            </details>
        </div>
    </form>
</section>

<section class="panel register-panel">
    <div class="table-wrap" role="region" aria-label="Tabel data aset, geser untuk melihat seluruh kolom" tabindex="0">
        <table class="asset-table">
            <thead>
                <tr>
                    <th scope="col">Kelompok aset</th>
                    <th scope="col">Kodefikasi aset</th>
                    <th scope="col">Nomor aset</th>
                    <th scope="col">Nama aset</th>
                    <th scope="col">Status aset</th>
                    <th scope="col">Periode Manfaat</th>
                    <th scope="col">Lokasi</th>
                    <th scope="col">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($groups === []): ?>
                    <tr>
                        <td colspan="8">
                            <div class="empty-state asset-empty"><span aria-hidden="true">▤</span><strong>Data aset tidak ditemukan</strong>
                                <p>Ubah filter atau tambahkan data aset.</p>
                            </div>
                        </td>
                    </tr>
                    <?php else: foreach ($groups as $groupName => $records): ?>
                        <tr class="asset-group">
                            <th scope="row" colspan="8"><?= esc($groupName) ?></th>
                        </tr>
                        <?php foreach ($records as $record): ?>
                            <tr class="asset-detail">
                                <td><span class="asset-group-name"><?= esc($record['kelompok_aset']) ?></span></td>
                                <td><?= esc($record['kodefikasi_aset']) ?></td>
                                <td><?= esc($record['nomor_aset']) ?></td>
                                <td><?= esc($record['nama_aset']) ?></td>
                                <td><?= esc($record['status_aset']) ?></td>
                                <td>
                                    <div class="date-cell asset-period">
                                        <strong><?= $record['tanggal_habis_manfaat'] ? esc(date('d-m-Y', strtotime($record['tanggal_habis_manfaat']))) : '—' ?></strong>
                                        <span class="asset-period-start"><?= $record['tanggal_perolehan'] ? esc(date('d-m-Y', strtotime($record['tanggal_perolehan']))) . ' s.d.' : 'Tanggal perolehan belum diisi' ?></span>
                                        <?php if ($record['sisa_masa_manfaat'] !== null): ?><span class="pks-expiry-countdown warning" data-asset-remaining="<?= esc($record['tanggal_habis_manfaat'], 'attr') ?>">Sisa <?= esc($record['sisa_masa_manfaat']) ?> bulan</span><?php endif ?>
                                    </div>
                                </td>
                                <td><?= esc($record['lokasi'] ?: '—') ?></td>
                                <td>
                                    <div class="action-buttons"><a class="icon-btn" href="<?= site_url('bagian-umum-1/data-aset/' . $record['id']) ?>" data-asset-view="<?= (int) $record['id'] ?>" title="Lihat detail" aria-label="Lihat detail"><?= view('components/table_action_icon', ['icon' => 'view']) ?></a><a class="icon-btn" href="<?= site_url('bagian-umum-1/data-aset/' . $record['id'] . '/ubah') ?>" data-asset-edit="<?= (int) $record['id'] ?>" title="Ubah" aria-label="Ubah aset"><?= view('components/table_action_icon', ['icon' => 'edit']) ?></a></div>
                                </td>
                            </tr>
                        <?php endforeach ?>
                <?php endforeach;
                endif ?>
            </tbody>
        </table>
    </div>
    <div class="table-list-footer">
        <form method="get" action="<?= site_url('bagian-umum-1/data-aset') ?>" class="table-length-form">
            <?php foreach (['q', 'kelompok', 'status', 'dari', 'sampai'] as $key): ?><input type="hidden" name="<?= esc($key, 'attr') ?>" value="<?= esc($filters[$key], 'attr') ?>"><?php endforeach ?>
            <label for="assetPerPage">Tampilkan</label>
            <select id="assetPerPage" name="per_page" aria-label="Jumlah aset per halaman" data-table-length><?php foreach ([10, 20, 50, 100] as $size): ?><option value="<?= $size ?>" <?= $filters['perPage'] === $size ? 'selected' : '' ?>><?= $size ?></option><?php endforeach ?></select>
            <span>data</span>
        </form>
        <?php if ($groups !== []): ?><div class="pagination-wrap"><?= $pagination ?></div><?php endif ?>
    </div>
</section>

<?= view('data_aset/modals', ['groups' => $groups, 'assetGroups' => $assetGroups, 'popupState' => $popupState ?? null, 'isAdmin' => $isAdmin ?? false]) ?>

<?= $this->endSection() ?>
