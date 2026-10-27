<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<style>
    .atk-heading { display: flex; align-items: flex-start; justify-content: space-between; gap: 16px; }
    .atk-heading h1 { margin: 0; }
    .atk-heading p { margin: 6px 0 0; color: #607087; }
    .atk-table { min-width: 820px; table-layout: fixed; }
    .atk-table th:nth-child(1) { width: 23%; }
    .atk-table th:nth-child(2) { width: 17%; }
    .atk-table th:nth-child(3), .atk-table th:nth-child(4) { width: 13%; }
    .atk-table th:nth-child(5) { width: 22%; }
    .atk-table th:nth-child(6) { width: 12%; min-width: 112px; }
    .atk-table thead th:nth-child(3), .atk-table thead th:nth-child(4) { text-align: center !important; }
    .atk-table td { color: #31445a; overflow-wrap: anywhere; }
    .atk-table td:nth-child(1) { color: #162235; font-weight: 700; }
    .atk-table tbody td:nth-child(3), .atk-table tbody td:nth-child(4) { padding-inline: 0 !important; font-variant-numeric: tabular-nums; text-align: center !important; vertical-align: middle; }
    .atk-table .quantity-value { display: flex !important; width: 100% !important; min-height: 100%; align-items: center; justify-content: center !important; gap: 4px; font-variant-numeric: tabular-nums; text-align: center; }
    .quantity-number { text-align: center; }
    .unit-value { color: #0877d1; font-weight: 750; text-align: center; white-space: nowrap; }
    .atk-table .action-buttons { display: flex; width: 104px; gap: 6px; flex-wrap: nowrap; justify-content: center; white-space: nowrap; }
    .atk-table .icon-btn { flex: 0 0 29px; min-width: 29px; }
    .atk-modal-dialog { width: min(720px, calc(100% - 32px)); }
    .atk-modal-dialog > form { display: flex; flex: 1; flex-direction: column; min-height: 0; }
    .atk-modal-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .atk-modal-grid .modal-span-2 { grid-column: 1 / -1; }
    .atk-proof-modal-dialog { width: min(900px, calc(100% - 32px)); }
    .atk-proof-table { min-width: 620px; }
    .atk-proof-table thead { background: var(--system-table-header-gradient) !important; }
    .atk-proof-table thead th { color: #fff !important; border-color: rgba(255,255,255,.24) !important; background: transparent !important; }
    .atk-proof-table th:nth-child(1) { width: 22%; }
    .atk-proof-table th:nth-child(2) { width: 28%; }
    .atk-proof-table th:nth-child(3) { width: 22%; }
    .atk-proof-table th:nth-child(4) { width: 18%; }
    .atk-proof-table th:nth-child(5) { width: 10%; min-width: 76px; }
    .atk-proof-table .action-buttons { display: flex; gap: 6px; justify-content: center; }
    .atk-proof-table .icon-btn[disabled] { cursor: not-allowed; opacity: .48; }
    .atk-proof-toolbar { display: flex; align-items: end; justify-content: space-between; gap: 12px; margin-bottom: 14px; }
    .atk-proof-filter { display: grid; flex: 1; grid-template-columns: minmax(200px, 1fr) 170px auto auto auto; align-items: end; gap: 9px; }
    .atk-download-modal-dialog { width: min(390px, calc(100% - 32px)); }
    .atk-download-modal-dialog > form { display: flex; flex: 1; flex-direction: column; min-height: 0; }
    .atk-download-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .atk-pickup-modal-dialog { width: min(520px, calc(100% - 32px)); max-height: calc(100vh - 32px); }
    .atk-pickup-modal-dialog > form { display: flex; flex: 1; flex-direction: column; min-height: 0; }
    .atk-pickup-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .atk-pickup-grid .modal-span-2 { grid-column: 1 / -1; }
    .atk-pickup-item-list { display: grid; gap: 8px; }
    .atk-pickup-modal-dialog .form-group > select,
    .atk-pickup-modal-dialog .form-group > input { height: 36px; min-height: 36px; }
    .atk-pickup-modal-dialog .atk-pickup-item-row { display: grid; grid-template-columns: minmax(0, 1fr) 100px; align-items: center; gap: 8px; }
    .atk-pickup-item-row .icon-btn { min-width: 30px; }
    .atk-pickup-modal-dialog .atk-pickup-quantity { display: flex; height: 36px !important; min-height: 36px !important; max-height: 36px; align-items: stretch; }
    .atk-pickup-modal-dialog .atk-pickup-quantity input { min-width: 0; height: 36px !important; min-height: 36px !important; max-height: 36px; box-sizing: border-box; border-radius: 10px 0 0 10px; }
    .atk-pickup-modal-dialog .atk-pickup-quantity span { display: inline-flex; box-sizing: border-box; flex: 0 0 38px; width: 38px; min-width: 38px; height: 36px !important; min-height: 36px !important; max-height: 36px; align-items: center; justify-content: center; padding: 0 2px; color: #0877d1; border: 1px solid #c9ddeb; border-left: 0; border-radius: 0 10px 10px 0; background: #edf7ff; font-size: 10px; font-weight: 800; line-height: 1; white-space: nowrap; }
    @media (max-width: 760px) { .atk-proof-toolbar { align-items: stretch; flex-direction: column; } .atk-proof-filter { grid-template-columns: 1fr 1fr; } .atk-proof-filter .btn { width: 100%; } }
    @media (max-width: 680px) { .atk-heading { align-items: stretch; flex-direction: column; } .atk-heading .btn { align-self: flex-start; } .atk-modal-grid, .atk-pickup-grid { grid-template-columns: 1fr; } .atk-modal-grid .modal-span-2, .atk-pickup-grid .modal-span-2 { grid-column: auto; } .atk-pickup-modal-dialog .atk-pickup-item-row { grid-template-columns: minmax(0, 1fr) 100px; } }
</style>

<?php
$records = is_array($records ?? null) ? $records : [];
$filters = array_merge(['q' => '', 'stok' => '', 'perPage' => 10], is_array($filters ?? null) ? $filters : []);
$popupState = is_array($popupState ?? null) ? $popupState : null;
$pickupRecords = is_array($pickupRecords ?? null) ? $pickupRecords : [];
$pickupAtks = is_array($pickupAtks ?? null) ? $pickupAtks : [];
$pickupRecipients = is_array($pickupRecipients ?? null) ? $pickupRecipients : [];
$pickupYears = array_values(array_filter(is_array($pickupYears ?? null) ? $pickupYears : [(int) date('Y')], static fn ($year): bool => is_int($year) || ctype_digit((string) $year)));
$pickupMonths = [1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'];
$units = is_array($units ?? null) ? $units : ['Box', 'Pcs', 'Unit', 'Set', 'Rim', 'Pack', 'Buah', 'Lembar'];
$pickupState = is_array($pickupState ?? null) ? $pickupState : null;
$pickupListOpen = ($pickupListOpen ?? false) === true;
$popupRecords = [];
foreach ($records as $record) $popupRecords[(int) $record['id']] = $record;
$jsonFlags = JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_INVALID_UTF8_SUBSTITUTE;
?>

<section class="page-heading heading-actions atk-heading">
    <div>
        <p class="eyebrow">AGENDARIS</p>
        <h1>ATK Kantor</h1>
        <p>Daftar alat tulis kantor beserta stok, barang terpakai, dan keterangannya.</p>
    </div>
</section>

<section class="panel filter-panel">
    <form method="get" action="<?= site_url('agendaris/atk-kantor') ?>" class="filter-form atk-filter-form">
        <input type="hidden" name="per_page" value="<?= (int) $filters['perPage'] ?>">
        <div class="form-group search-group"><label for="atkSearch">Cari ATK</label><div class="input-with-icon"><span aria-hidden="true">⌕</span><input id="atkSearch" type="search" name="q" maxlength="150" value="<?= esc($filters['q'], 'attr') ?>" placeholder="Cari nama barang, merek, atau keterangan"></div></div>
        <div class="form-group"><label for="atkStockFilter">Status stok</label><select id="atkStockFilter" name="stok"><option value="">Semua stok</option><option value="tersedia" <?= $filters['stok'] === 'tersedia' ? 'selected' : '' ?>>Masih tersedia</option><option value="habis" <?= $filters['stok'] === 'habis' ? 'selected' : '' ?>>Stok habis</option></select></div>
        <div class="filter-actions"><button class="btn btn-secondary" type="submit">Filter</button><a class="btn btn-ghost" href="<?= site_url('agendaris/atk-kantor') ?>">Reset</a><details class="lr-action-menu lr-filter-menu"><summary class="lr-filter-menu-trigger" aria-label="Menu tindakan ATK Kantor" title="Menu tindakan"><svg aria-hidden="true" viewBox="0 0 24 24" focusable="false"><rect x="3" y="3" width="7" height="7" rx="1.4"></rect><rect x="14" y="3" width="7" height="7" rx="1.4"></rect><rect x="3" y="14" width="7" height="7" rx="1.4"></rect><rect x="14" y="14" width="7" height="7" rx="1.4"></rect></svg></summary><div class="lr-action-menu-popover" role="menu" aria-label="Menu ATK Kantor"><button type="button" role="menuitem" class="lr-action-menu-item" data-atk-create>Tambah ATK</button><button type="button" role="menuitem" class="lr-action-menu-item" data-atk-pickup-menu-open>Pengambilan ATK</button><button type="button" role="menuitem" class="lr-action-menu-item" data-atk-proof-open>Bukti Pengambilan ATK</button></div></details></div>
    </form>
</section>

<section class="panel register-panel">
    <div class="table-wrap" role="region" aria-label="Tabel ATK Kantor" tabindex="0">
        <table class="atk-table">
            <thead>
                <tr>
                    <th scope="col">Nama Barang</th>
                    <th scope="col">Merek</th>
                    <th scope="col">Stok Barang</th>
                    <th scope="col">Terpakai</th>
                    <th scope="col">Keterangan</th>
                    <th scope="col">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($records === []): ?>
                    <tr><td colspan="6"><div class="empty-state"><span aria-hidden="true">▤</span><strong>Belum ada data ATK Kantor</strong><p>Tambahkan data alat tulis kantor untuk mulai mencatat persediaan.</p></div></td></tr>
                <?php else: foreach ($records as $record): ?>
                    <tr>
                        <td><?= esc($record['nama_barang']) ?></td>
                        <td><?= esc($record['merek'] ?: '—') ?></td>
                        <td><span class="quantity-value"><span class="quantity-number"><?= number_format((int) $record['stok_barang'], 0, ',', '.') ?></span><span class="unit-value"><?= esc($record['satuan'] ?? 'Pcs') ?></span></span></td>
                        <td><span class="quantity-value"><span class="quantity-number"><?= number_format((int) ($record['terpakai'] ?? 0), 0, ',', '.') ?></span><span class="unit-value"><?= esc($record['satuan'] ?? 'Pcs') ?></span></span></td>
                        <td><?= esc($record['keterangan'] ?: '—') ?></td>
                        <td><div class="action-buttons"><button type="button" class="icon-btn" data-atk-view="<?= (int) $record['id'] ?>" title="Lihat detail" aria-label="Lihat <?= esc($record['nama_barang'], 'attr') ?>"><?= view('components/table_action_icon', ['icon' => 'view']) ?></button><button type="button" class="icon-btn" data-atk-edit="<?= (int) $record['id'] ?>" title="Ubah" aria-label="Ubah <?= esc($record['nama_barang'], 'attr') ?>"><?= view('components/table_action_icon', ['icon' => 'edit']) ?></button><button type="button" class="icon-btn icon-btn-delete" data-atk-delete="<?= (int) $record['id'] ?>" data-atk-delete-label="<?= esc($record['nama_barang'], 'attr') ?>" title="Hapus" aria-label="Hapus <?= esc($record['nama_barang'], 'attr') ?>"><?= view('components/table_action_icon', ['icon' => 'delete']) ?></button></div></td>
                    </tr>
                <?php endforeach; endif ?>
            </tbody>
        </table>
    </div>
    <div class="table-list-footer">
        <form method="get" action="<?= site_url('agendaris/atk-kantor') ?>" class="table-length-form">
            <?php foreach (['q', 'stok'] as $key): ?><input type="hidden" name="<?= esc($key, 'attr') ?>" value="<?= esc($filters[$key], 'attr') ?>"><?php endforeach ?>
            <label for="atkPerPage">Tampilkan</label><select id="atkPerPage" name="per_page" aria-label="Jumlah data ATK per halaman" data-table-length><?php foreach ([10, 20, 50, 100] as $size): ?><option value="<?= $size ?>" <?= $filters['perPage'] === $size ? 'selected' : '' ?>><?= $size ?></option><?php endforeach ?></select><span>data</span>
        </form>
        <?= isset($pager) ? $pager->links('atk_kantor', 'table_full') : '' ?>
    </div>
</section>

<div class="input-modal" id="atkProofModal" hidden aria-hidden="true">
    <div class="modal-backdrop" aria-hidden="true"></div>
    <section class="modal-dialog atk-proof-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="atkProofTitle">
        <header class="modal-header"><div class="modal-title-group"><span class="modal-title-icon" aria-hidden="true">▤</span><div><p>AGENDARIS / ATK KANTOR</p><h2 id="atkProofTitle">Bukti Pengambilan ATK</h2></div></div><button type="button" class="modal-close" data-atk-proof-close aria-label="Tutup bukti pengambilan ATK">×</button></header>
        <div class="modal-body"><div class="atk-proof-toolbar"><form class="atk-proof-filter" data-atk-proof-filter><div class="form-group search-group"><label for="atkPickupSearch">Cari bukti</label><div class="input-with-icon"><span aria-hidden="true">⌕</span><input id="atkPickupSearch" type="search" placeholder="Nama pengambil atau barang" data-atk-proof-search></div></div><div class="form-group"><label for="atkPickupDate">Tgl pengambilan</label><input id="atkPickupDate" type="date" data-atk-proof-date></div><button type="submit" class="btn btn-secondary">Filter</button><button type="button" class="btn btn-ghost" data-atk-proof-reset>Reset</button><button type="button" class="btn btn-primary" data-atk-proof-download-open>Unduh Bukti</button></form></div><div class="table-wrap" role="region" aria-label="Daftar bukti pengambilan ATK" tabindex="0"><table class="atk-proof-table"><thead><tr><th scope="col">Nama</th><th scope="col">Nama Barang / ATK</th><th scope="col">Tanggal Pengambilan</th><th scope="col">Jumlah</th><th scope="col">Aksi</th></tr></thead><tbody><?php if ($pickupRecords === []): ?><tr><td colspan="5"><div class="empty-state"><span aria-hidden="true">▤</span><strong>Belum ada bukti pengambilan ATK</strong><p>Data dari Pengambilan ATK akan tampil di sini.</p></div></td></tr><?php else: foreach ($pickupRecords as $pickup): ?><tr data-atk-pickup-row data-search="<?= esc(mb_strtolower((string) ($pickup['nama_pengambil'] . ' ' . $pickup['nama_barang'])), 'attr') ?>" data-date="<?= esc($pickup['tanggal_pengambilan'] ?? '', 'attr') ?>"><td><?= esc($pickup['nama_pengambil'] ?? '—') ?></td><td><?= esc($pickup['nama_barang'] ?? '—') ?></td><td><?= ! empty($pickup['tanggal_pengambilan']) ? esc(date('d/m/Y', strtotime((string) $pickup['tanggal_pengambilan']))) : '—' ?></td><td><?= esc($pickup['jumlah_diambil'] ?? '—') ?></td><td><div class="action-buttons"><form method="post" action="<?= site_url('agendaris/atk-kantor/pengambilan/' . (int) $pickup['id'] . '/hapus') ?>" onsubmit="return confirm('Hapus bukti pengambilan ini? Stok ATK akan dikembalikan.');"><?= csrf_field() ?><button type="submit" class="icon-btn" title="Hapus bukti" aria-label="Hapus bukti <?= esc($pickup['nama_pengambil'] ?? '', 'attr') ?>"><?= view('components/table_action_icon', ['icon' => 'delete']) ?></button></form></div></td></tr><?php endforeach; endif ?></tbody></table></div></div>
        <footer class="modal-footer"><button type="button" class="btn btn-ghost" data-atk-proof-close>Tutup</button></footer>
    </section>
</div>

<div class="input-modal" id="atkProofDownloadModal" hidden aria-hidden="true">
    <div class="modal-backdrop" aria-hidden="true"></div>
    <section class="modal-dialog atk-download-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="atkProofDownloadTitle">
        <header class="modal-header"><div class="modal-title-group"><span class="modal-title-icon" aria-hidden="true">⇩</span><div><p>BUKTI PENGAMBILAN ATK</p><h2 id="atkProofDownloadTitle">Unduh Bukti</h2></div></div><button type="button" class="modal-close" data-atk-proof-download-close aria-label="Tutup unduhan bukti">×</button></header>
        <form method="get" action="<?= site_url('agendaris/atk-kantor/pengambilan/bukti') ?>" target="_blank"><div class="modal-body"><div class="modal-form-grid atk-download-grid"><div class="form-group"><label for="atkProofMonth">Bulan</label><select id="atkProofMonth" name="bulan" required><?php foreach ($pickupMonths as $value => $label): ?><option value="<?= $value ?>" <?= (int) date('n') === $value ? 'selected' : '' ?>><?= esc($label) ?></option><?php endforeach ?></select></div><div class="form-group"><label for="atkProofYear">Tahun</label><select id="atkProofYear" name="tahun" required><?php foreach ($pickupYears as $year): ?><option value="<?= (int) $year ?>" <?= (int) date('Y') === (int) $year ? 'selected' : '' ?>><?= (int) $year ?></option><?php endforeach ?></select></div></div></div><footer class="modal-footer"><button type="button" class="btn btn-ghost" data-atk-proof-download-close>Batal</button><button type="submit" class="btn btn-primary">Unduh</button></footer></form>
    </section>
</div>

<div class="input-modal" id="atkPickupFormModal" hidden aria-hidden="true">
    <div class="modal-backdrop" aria-hidden="true"></div>
    <section class="modal-dialog atk-pickup-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="atkPickupFormTitle">
        <header class="modal-header"><div class="modal-title-group"><span class="modal-title-icon" aria-hidden="true">✎</span><div><p>BUKTI PENGAMBILAN ATK</p><h2 id="atkPickupFormTitle">Tambah Pengambilan</h2></div></div><button type="button" class="modal-close" data-atk-pickup-form-close aria-label="Tutup formulir pengambilan ATK">×</button></header>
        <form method="post" action="<?= site_url('agendaris/atk-kantor/pengambilan') ?>" id="atkPickupForm"><?= csrf_field() ?><div class="modal-body"><div class="modal-alert" id="atkPickupErrors" role="alert" hidden></div><div class="modal-form-grid atk-pickup-grid"><div class="form-group"><label for="atkPickupName">Nama <span class="required">*</span></label><select id="atkPickupName" name="nama_pengambil" required><option value="">Pilih nama pengambil</option><?php foreach ($pickupRecipients as $recipient): ?><option value="<?= esc($recipient, 'attr') ?>"><?= esc($recipient) ?></option><?php endforeach ?></select></div><div class="form-group"><label for="atkPickupDateForm">Tanggal Pengambilan <span class="required">*</span></label><input id="atkPickupDateForm" type="date" name="tanggal_pengambilan" required value="<?= date('Y-m-d') ?>"></div><div class="form-group modal-span-2"><label for="atkPickupItem">Nama Barang / ATK dan Jumlah Diambil <span class="required">*</span></label><div class="atk-pickup-item-list" data-atk-pickup-item-list><div class="atk-pickup-item-row" data-atk-pickup-item-row><select id="atkPickupItem" name="atk_ids[]" required aria-label="Nama barang ATK" data-atk-pickup-item><option value="">Pilih barang ATK</option><?php foreach ($pickupAtks as $item): ?><?php $unit = $item['satuan'] ?? 'Pcs'; ?><option value="<?= (int) $item['id'] ?>" data-unit="<?= esc($unit, 'attr') ?>" data-stock="<?= (int) $item['stok_barang'] ?>"><?= esc($item['nama_barang']) ?> — tersedia <?= number_format((int) $item['stok_barang'], 0, ',', '.') ?> <?= esc($unit) ?></option><?php endforeach ?></select><div class="atk-pickup-quantity"><input id="atkPickupQuantity" type="number" name="jumlah_diambil[]" min="1" max="2147483647" required value="1" aria-label="Jumlah barang yang diambil"><span data-atk-pickup-unit>Sat.</span></div></div></div></div></div></div><footer class="modal-footer"><button type="button" class="btn btn-ghost" data-atk-pickup-form-close>Batal</button><button type="submit" class="btn btn-primary">Simpan Pengambilan</button></footer></form>
    </section>
</div>

<div class="input-modal" id="atkFormModal" hidden aria-hidden="true">
    <div class="modal-backdrop" aria-hidden="true"></div>
    <section class="modal-dialog atk-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="atkFormTitle">
        <header class="modal-header"><div class="modal-title-group"><span class="modal-title-icon" aria-hidden="true">✎</span><div><p>AGENDARIS</p><h2 id="atkFormTitle">Tambah ATK Kantor</h2></div></div><button type="button" class="modal-close" data-atk-close aria-label="Tutup formulir ATK">×</button></header>
        <form method="post" action="<?= site_url('agendaris/atk-kantor') ?>" id="atkForm">
            <?= csrf_field() ?>
            <div class="modal-body"><div class="modal-alert" id="atkFormErrors" role="alert" hidden></div><div class="modal-form-grid atk-modal-grid">
                <div class="form-group"><label for="atkName">Nama Barang <span class="required">*</span></label><input id="atkName" name="nama_barang" maxlength="150" required placeholder="Contoh: Pulpen"></div>
                <div class="form-group"><label for="atkBrand">Merek</label><input id="atkBrand" name="merek" maxlength="150" placeholder="Contoh: Standard"></div>
                <div class="form-group"><label for="atkStock">Stok Barang <span class="required">*</span></label><input id="atkStock" type="number" name="stok_barang" min="0" max="2147483647" required value="0"></div>
                <div class="form-group"><label for="atkUnit">Satuan <span class="required">*</span></label><select id="atkUnit" name="satuan" required><?php foreach ($units as $unit): ?><option value="<?= esc($unit, 'attr') ?>"><?= esc($unit) ?></option><?php endforeach ?></select></div>
                <div class="form-group modal-span-2"><label for="atkNotes">Keterangan</label><textarea id="atkNotes" name="keterangan" rows="4" maxlength="2000" placeholder="Tambahkan keterangan bila diperlukan"></textarea></div>
            </div></div>
            <footer class="modal-footer"><button type="button" class="btn btn-ghost" data-atk-close>Batal</button><button type="submit" class="btn btn-primary" id="atkFormSubmit">Simpan ATK</button></footer>
        </form>
    </section>
</div>

<div class="input-modal" id="atkDetailModal" hidden aria-hidden="true">
    <div class="modal-backdrop" aria-hidden="true"></div>
    <section class="modal-dialog atk-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="atkDetailTitle">
        <header class="modal-header"><div class="modal-title-group"><span class="modal-title-icon" aria-hidden="true">▤</span><div><p>AGENDARIS</p><h2 id="atkDetailTitle">Detail ATK Kantor</h2></div></div><button type="button" class="modal-close" data-atk-detail-close aria-label="Tutup detail ATK">×</button></header>
        <div class="modal-body"><dl class="detail-list grid-two"><div><dt>Nama Barang</dt><dd data-atk-detail="nama_barang">—</dd></div><div><dt>Merek</dt><dd data-atk-detail="merek">—</dd></div><div><dt>Stok Barang</dt><dd data-atk-detail="stok_barang">—</dd></div><div><dt>Terpakai</dt><dd data-atk-detail="terpakai">—</dd></div><div class="full-detail"><dt>Keterangan</dt><dd data-atk-detail="keterangan">—</dd></div></dl></div>
        <footer class="modal-footer"><button type="button" class="btn btn-ghost" data-atk-detail-close>Tutup</button><button type="button" class="btn btn-primary" data-atk-detail-edit>Ubah ATK</button></footer>
    </section>
</div>

<div class="input-modal" id="atkDeleteModal" hidden aria-hidden="true">
    <button type="button" class="modal-backdrop" data-atk-delete-close aria-label="Batal hapus"></button>
    <section class="modal-dialog delete-modal-dialog" role="alertdialog" aria-modal="true" aria-labelledby="atkDeleteTitle">
        <div class="delete-modal-body"><span class="delete-warning-icon" aria-hidden="true">!</span><h2 id="atkDeleteTitle">Hapus ATK Kantor?</h2><p>Data <strong data-atk-delete-label>ini</strong> akan dihapus dari daftar ATK Kantor.</p></div>
        <form method="post" action="" data-atk-delete-form class="delete-modal-actions"><?= csrf_field() ?><button type="button" class="btn btn-ghost" data-atk-delete-close>Batal</button><button type="submit" class="btn btn-delete">Ya, hapus</button></form>
    </section>
</div>

<script>
(() => {
    const records = <?= json_encode($popupRecords, $jsonFlags) ?>;
    const popupState = <?= json_encode($popupState, $jsonFlags) ?>;
    const pickupState = <?= json_encode($pickupState, $jsonFlags) ?>;
    const pickupListOpen = <?= $pickupListOpen ? 'true' : 'false' ?>;
    const baseUrl = <?= json_encode(site_url('agendaris/atk-kantor'), $jsonFlags) ?>;
    const formModal = document.getElementById('atkFormModal');
    const proofModal = document.getElementById('atkProofModal');
    const proofDownloadModal = document.getElementById('atkProofDownloadModal');
    const pickupFormModal = document.getElementById('atkPickupFormModal');
    const detailModal = document.getElementById('atkDetailModal');
    const deleteModal = document.getElementById('atkDeleteModal');
    const form = document.getElementById('atkForm');
    const errorsBox = document.getElementById('atkFormErrors');
    const deleteForm = document.querySelector('[data-atk-delete-form]');
    const deleteLabel = deleteModal?.querySelector('[data-atk-delete-label]');
    const pickupForm = document.getElementById('atkPickupForm');
    const pickupErrors = document.getElementById('atkPickupErrors');
    const pickupItemList = document.querySelector('[data-atk-pickup-item-list]');
    let selectedId = null;
    let lastTrigger = null;
    const number = new Intl.NumberFormat('id-ID');
    const close = (modal, restoreFocus = true) => { modal.classList.remove('open'); modal.hidden = true; modal.setAttribute('aria-hidden', 'true'); if (restoreFocus) lastTrigger?.focus(); };
    const open = (modal) => { modal.hidden = false; modal.setAttribute('aria-hidden', 'false'); requestAnimationFrame(() => modal.classList.add('open')); modal.querySelector('.modal-close')?.focus(); };
    const showErrors = (errors = []) => { errorsBox.replaceChildren(); errorsBox.hidden = errors.length === 0; if (!errors.length) return; const title = document.createElement('strong'); title.textContent = 'Periksa kembali data berikut:'; const list = document.createElement('ul'); errors.forEach((message) => { const item = document.createElement('li'); item.textContent = message; list.append(item); }); errorsBox.append(title, list); };
    const showPickupErrors = (errors = []) => { pickupErrors.replaceChildren(); pickupErrors.hidden = errors.length === 0; if (!errors.length) return; const title = document.createElement('strong'); title.textContent = 'Periksa kembali data berikut:'; const list = document.createElement('ul'); errors.forEach((message) => { const item = document.createElement('li'); item.textContent = message; list.append(item); }); pickupErrors.append(title, list); };
    const openForm = (id = null, values = {}, errors = []) => { form.reset(); form.action = id === null ? baseUrl : `${baseUrl}/${encodeURIComponent(id)}`; document.getElementById('atkFormTitle').textContent = id === null ? 'Tambah ATK Kantor' : 'Ubah ATK Kantor'; document.getElementById('atkFormSubmit').textContent = id === null ? 'Simpan ATK' : 'Simpan Perubahan'; Object.entries(values || {}).forEach(([name, value]) => { const field = form.elements.namedItem(name); if (field) field.value = value ?? ''; }); showErrors(errors); open(formModal); };
    document.querySelector('[data-atk-create]')?.addEventListener('click', (event) => { lastTrigger = event.currentTarget; openForm(); });
    document.querySelector('[data-atk-proof-open]')?.addEventListener('click', (event) => { lastTrigger = event.currentTarget; event.currentTarget.closest('details')?.removeAttribute('open'); open(proofModal); });
    document.querySelector('[data-atk-proof-download-open]')?.addEventListener('click', (event) => { lastTrigger = event.currentTarget; open(proofDownloadModal); });
    const refreshPickupRemoveButtons = () => pickupItemList?.querySelectorAll('[data-atk-pickup-remove-item]').forEach((button) => { button.hidden = pickupItemList.querySelectorAll('[data-atk-pickup-item-row]').length === 1; });
    const openPickupForm = (values = {}, errors = []) => { pickupForm.reset(); pickupForm.elements.namedItem('tanggal_pengambilan').value = values.tanggal_pengambilan || <?= json_encode(date('Y-m-d'), $jsonFlags) ?>; const nameField = pickupForm.elements.namedItem('nama_pengambil'); if (nameField && values.nama_pengambil !== undefined) nameField.value = values.nama_pengambil; showPickupErrors(errors); refreshPickupRemoveButtons(); open(pickupFormModal); };
    document.querySelector('[data-atk-pickup-create]')?.addEventListener('click', (event) => { lastTrigger = event.currentTarget; close(proofModal, false); openPickupForm(); });
    document.querySelector('[data-atk-pickup-menu-open]')?.addEventListener('click', (event) => { lastTrigger = event.currentTarget; event.currentTarget.closest('details')?.removeAttribute('open'); openPickupForm(); });
    const updatePickupUnit = () => { const item = pickupItemList?.querySelector('[data-atk-pickup-item]'); const unit = item?.selectedOptions?.[0]?.dataset.unit || 'Sat.'; const unitOutput = pickupItemList?.querySelector('[data-atk-pickup-unit]'); if (unitOutput) unitOutput.textContent = unit; };
    pickupItemList?.querySelector('[data-atk-pickup-item]')?.addEventListener('change', updatePickupUnit);
    pickupItemList?.addEventListener('click', (event) => { const button = event.target.closest('[data-atk-pickup-remove-item]'); if (!button || pickupItemList.querySelectorAll('[data-atk-pickup-item-row]').length <= 1) return; button.closest('[data-atk-pickup-item-row]')?.remove(); refreshPickupRemoveButtons(); });
    const applyPickupFilter = () => { const query = (document.querySelector('[data-atk-proof-search]')?.value || '').trim().toLocaleLowerCase('id-ID'); const date = document.querySelector('[data-atk-proof-date]')?.value || ''; document.querySelectorAll('[data-atk-pickup-row]').forEach((row) => { row.hidden = (query !== '' && !row.dataset.search.includes(query)) || (date !== '' && row.dataset.date !== date); }); };
    document.querySelector('[data-atk-proof-filter]')?.addEventListener('submit', (event) => { event.preventDefault(); applyPickupFilter(); });
    document.querySelector('[data-atk-proof-reset]')?.addEventListener('click', () => { const search = document.querySelector('[data-atk-proof-search]'); const date = document.querySelector('[data-atk-proof-date]'); if (search) search.value = ''; if (date) date.value = ''; applyPickupFilter(); });
    document.querySelectorAll('[data-atk-view]').forEach((trigger) => trigger.addEventListener('click', () => { const id = trigger.dataset.atkView; const record = records[id]; if (!record) return; selectedId = id; lastTrigger = trigger; detailModal.querySelectorAll('[data-atk-detail]').forEach((element) => { const key = element.dataset.atkDetail; const value = record[key]; element.textContent = ['stok_barang', 'terpakai'].includes(key) ? `${number.format(Number(value || 0))} ${record.satuan || 'Pcs'}` : (value || '—'); }); open(detailModal); }));
    document.querySelectorAll('[data-atk-edit]').forEach((trigger) => trigger.addEventListener('click', () => { const id = trigger.dataset.atkEdit; if (!records[id]) return; lastTrigger = trigger; openForm(id, records[id]); }));
    document.querySelectorAll('[data-atk-delete]').forEach((trigger) => trigger.addEventListener('click', () => { const id = trigger.dataset.atkDelete; if (!id) return; lastTrigger = trigger; deleteForm.action = `${baseUrl}/${encodeURIComponent(id)}/hapus`; deleteLabel.textContent = trigger.dataset.atkDeleteLabel || 'ini'; open(deleteModal); }));
    document.querySelector('[data-atk-detail-edit]')?.addEventListener('click', () => { if (!records[selectedId]) return; close(detailModal, false); openForm(selectedId, records[selectedId]); });
    document.querySelectorAll('[data-atk-close]').forEach((button) => button.addEventListener('click', () => close(formModal)));
    document.querySelectorAll('[data-atk-proof-close]').forEach((button) => button.addEventListener('click', () => close(proofModal)));
    document.querySelectorAll('[data-atk-proof-download-close]').forEach((button) => button.addEventListener('click', () => close(proofDownloadModal)));
    document.querySelectorAll('[data-atk-pickup-form-close]').forEach((button) => button.addEventListener('click', () => close(pickupFormModal)));
    document.querySelectorAll('[data-atk-detail-close]').forEach((button) => button.addEventListener('click', () => close(detailModal)));
    document.querySelectorAll('[data-atk-delete-close]').forEach((button) => button.addEventListener('click', () => close(deleteModal)));
    if (popupState?.values) openForm(popupState.id ?? null, popupState.values, popupState.errors || []);
    if (pickupState?.values) openPickupForm(pickupState.values, pickupState.errors || []);
    else if (pickupListOpen) open(proofModal);
})();
</script>

<?= $this->endSection() ?>
