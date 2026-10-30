<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<style>
    .souvenir-inventaris-table { min-width: 980px; }
    .souvenir-inventaris-table th:nth-child(1) { width: 18%; }
    .souvenir-inventaris-table th:nth-child(2) { width: 12%; }
    .souvenir-inventaris-table th:nth-child(3), .souvenir-inventaris-table th:nth-child(4), .souvenir-inventaris-table th:nth-child(5) { width: 11%; }
    .souvenir-inventaris-table th:nth-child(6) { width: 23%; }
    .souvenir-inventaris-table th:nth-child(7) { width: 14%; }
    .souvenir-inventaris-table td { color: #303b4a; text-align: left; }
    .souvenir-inventaris-table td:first-child strong { color: #111827; }
    .souvenir-inventaris-table td:nth-child(3), .souvenir-inventaris-table td:nth-child(4), .souvenir-inventaris-table td:nth-child(5) { font-variant-numeric: tabular-nums; text-align: left; }
    .souvenir-inventaris-table td:nth-child(6) { white-space: pre-wrap; overflow-wrap: anywhere; }
    .souvenir-stock-with-unit { display: inline-flex; align-items: baseline; gap: 5px; white-space: nowrap; }
    .souvenir-stock-value { width: 32px; color: inherit; font-size: inherit; font-weight: 400; font-variant-numeric: tabular-nums; line-height: 1; text-align: right; }
    .souvenir-stock-unit { color: var(--system-electric-blue, #0878e8); font-size: 9px; font-weight: 700; line-height: 1; }
    .souvenir-inventaris-table .action-buttons { display: flex; flex-wrap: nowrap; align-items: center; justify-content: flex-start; gap: 6px; }
    .souvenir-inventaris-table .action-buttons form { display: flex; flex: 0 0 auto; margin: 0; }
    .souvenir-inventaris-table .action-buttons .icon-btn { flex: 0 0 auto; }
    .souvenir-inventaris-filter-form .lr-action-menu-item.is-pending { color: #64809a; cursor: default; }
    .souvenir-modal-dialog { width: min(720px, calc(100% - 32px)); }
    .souvenir-modal-dialog > form { display: flex; flex: 1; flex-direction: column; min-height: 0; }
    .souvenir-modal-dialog .modal-body { min-height: 0; }
    .souvenir-modal-dialog .modal-footer { flex-shrink: 0; }
    .souvenir-modal-dialog .modal-title-icon { color: #fff; background: linear-gradient(135deg, #0878e8, #39c2e8); }
    .souvenir-modal-dialog .detail-list dd { color: #294763; overflow-wrap: anywhere; }
    .souvenir-pickup-modal-dialog { width: min(980px, calc(100% - 32px)); }
    .souvenir-pickup-form-modal, .souvenir-pickup-detail-modal { z-index: 120; }
    .souvenir-pickup-form-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .souvenir-pickup-form-grid .modal-span-2 { grid-column: 1 / -1; }
    .souvenir-pickup-toolbar { display: flex; justify-content: flex-end; margin-bottom: 14px; }
    .souvenir-pickup-table { min-width: 760px; }
    .souvenir-pickup-table > thead { color: #fff !important; background: var(--system-table-header-gradient) !important; }
    .souvenir-pickup-table > thead > tr > th { color: #fff !important; border-color: rgba(255, 255, 255, .2) !important; background: transparent !important; font-size: var(--system-table-header-font-size) !important; font-weight: 800 !important; }
    .souvenir-pickup-table th:nth-child(1) { width: 22%; }
    .souvenir-pickup-table th:nth-child(2) { width: 24%; }
    .souvenir-pickup-table th:nth-child(3) { width: 24%; }
    .souvenir-pickup-table th:nth-child(4) { width: 16%; }
    .souvenir-pickup-table th:nth-child(5) { width: 14%; }
    .souvenir-pickup-table td { color: #303b4a; text-align: left; }
    .souvenir-pickup-table td:first-child { color: #111827; font-weight: 700; }
    .souvenir-pickup-table .action-buttons { display: flex; flex-wrap: nowrap; align-items: center; gap: 6px; }
    .souvenir-pickup-table .action-buttons .icon-btn { display: inline-grid !important; width: var(--system-table-action-size) !important; min-width: var(--system-table-action-size) !important; height: var(--system-table-action-size) !important; min-height: var(--system-table-action-size) !important; margin: 0 !important; padding: 0 !important; place-items: center !important; color: var(--system-table-action-color) !important; border: 1px solid var(--system-table-action-border) !important; border-radius: 7px !important; background: #fff !important; box-shadow: 0 2px 6px rgba(28, 98, 151, .06) !important; }
    .souvenir-pickup-table .action-buttons .icon-btn svg { width: 15px !important; height: 15px !important; fill: none !important; stroke: currentColor !important; }
    .souvenir-pickup-table .action-buttons .icon-btn:hover { color: #075b99 !important; border-color: #8fc7e9 !important; background: #eef8fd !important; transform: translateY(-1px); }
    .souvenir-pickup-item-list { display: grid; gap: 8px; }
    .souvenir-pickup-item-row { display: flex; align-items: center; gap: 8px; }
    .souvenir-pickup-item-row select { flex: 1; min-width: 0; }
    .souvenir-pickup-quantity { display: flex; flex: 0 0 158px; min-width: 158px; }
    .souvenir-pickup-item-row input { flex: 1; width: auto; border-radius: 8px 0 0 8px; }
    .souvenir-pickup-unit { display: flex; align-items: center; min-width: 42px; padding: 0 9px; color: var(--system-electric-blue, #0878e8); border: 1px solid #c9ddeb; border-left: 0; border-radius: 0 8px 8px 0; background: #eef8ff; font-size: 9px; font-weight: 800; white-space: nowrap; }
    .souvenir-pickup-remove-item { display: inline-grid !important; flex: 0 0 auto; width: var(--system-table-action-size) !important; min-width: var(--system-table-action-size) !important; height: var(--system-table-action-size) !important; min-height: var(--system-table-action-size) !important; margin: 0 !important; padding: 0 !important; place-items: center !important; color: var(--system-table-action-color) !important; border: 1px solid var(--system-table-action-border) !important; border-radius: 7px !important; background: #fff !important; box-shadow: 0 2px 6px rgba(28, 98, 151, .06) !important; line-height: 1 !important; }
    .souvenir-pickup-remove-item svg { width: 15px !important; height: 15px !important; fill: none !important; stroke: currentColor !important; }
    .souvenir-pickup-remove-item:hover { color: #075b99 !important; border-color: #8fc7e9 !important; background: #eef8fd !important; transform: translateY(-1px); }
    .souvenir-pickup-add-item { margin-top: 8px; }
    .souvenir-return-form-modal, .souvenir-return-detail-modal { z-index: 120; }
    .souvenir-return-form-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .souvenir-return-form-grid .modal-span-2 { grid-column: 1 / -1; }
    .souvenir-return-item-list { display: grid; gap: 8px; }
    .souvenir-return-item-row { display: flex; align-items: center; gap: 8px; }
    .souvenir-return-item-row select { flex: 1; min-width: 0; }
    .souvenir-return-quantity { display: flex; flex: 0 0 158px; min-width: 158px; }
    .souvenir-return-item-row input { flex: 1; width: auto; border-radius: 8px 0 0 8px; }
    .souvenir-return-unit { display: flex; align-items: center; min-width: 42px; padding: 0 9px; color: var(--system-electric-blue, #0878e8); border: 1px solid #c9ddeb; border-left: 0; border-radius: 0 8px 8px 0; background: #eef8ff; font-size: 9px; font-weight: 800; white-space: nowrap; }
    .souvenir-return-remove-item { display: inline-grid !important; flex: 0 0 auto; width: var(--system-table-action-size) !important; min-width: var(--system-table-action-size) !important; height: var(--system-table-action-size) !important; min-height: var(--system-table-action-size) !important; margin: 0 !important; padding: 0 !important; place-items: center !important; }
    @media (max-width: 760px) { .souvenir-pickup-form-grid, .souvenir-return-form-grid { grid-template-columns: 1fr; } .souvenir-pickup-form-grid .modal-span-2, .souvenir-return-form-grid .modal-span-2 { grid-column: auto; } }
</style>

<?php
$popupRecords = [];
foreach ($items as $item) $popupRecords[(int) $item['id']] = $item;
$popupState = is_array($popupState ?? null) ? $popupState : null;
$pickupRecords = is_array($pickupRecords ?? null) ? $pickupRecords : [];
$pickupSouvenirs = is_array($pickupSouvenirs ?? null) ? $pickupSouvenirs : [];
$pickupPopupRecords = [];
foreach ($pickupRecords as $record) $pickupPopupRecords[(int) $record['id']] = $record;
$pickupState = is_array($pickupState ?? null) ? $pickupState : null;
$pickupListOpen = (bool) ($pickupListOpen ?? false);
$returnRecords = is_array($returnRecords ?? null) ? $returnRecords : [];
$returnPopupRecords = [];
foreach ($returnRecords as $record) $returnPopupRecords[(int) $record['id']] = $record;
$returnState = is_array($returnState ?? null) ? $returnState : null;
$returnListOpen = (bool) ($returnListOpen ?? false);
$inventoryItems = is_array($inventoryItems ?? null) ? $inventoryItems : [];
$loanRecords = is_array($loanRecords ?? null) ? $loanRecords : [];
$loanPopupRecords = [];
foreach ($loanRecords as $record) $loanPopupRecords[(int) $record['id']] = $record;
$inventoryReturnRecords = is_array($inventoryReturnRecords ?? null) ? $inventoryReturnRecords : [];
$inventoryReturnPopupRecords = [];
foreach ($inventoryReturnRecords as $record) $inventoryReturnPopupRecords[(int) $record['id']] = $record;
$loanState = is_array($loanState ?? null) ? $loanState : null;
$loanListOpen = (bool) ($loanListOpen ?? false);
$inventoryReturnState = is_array($inventoryReturnState ?? null) ? $inventoryReturnState : null;
$inventoryReturnListOpen = (bool) ($inventoryReturnListOpen ?? false);
$jsonFlags = JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_INVALID_UTF8_SUBSTITUTE;
?>

<section class="page-heading heading-actions">
    <div>
        <p class="eyebrow">AGENDARIS</p>
        <h1>Souvenir dan Inventaris</h1>
        <p>Kelola persediaan souvenir dan inventaris Agendaris secara terpusat.</p>
    </div>
</section>

<section class="panel filter-panel">
    <form method="get" action="<?= site_url('agendaris/souvenir-inventaris') ?>" class="filter-form souvenir-inventaris-filter-form">
        <input type="hidden" name="per_page" value="<?= (int) $filters['perPage'] ?>">
        <div class="form-group search-group">
            <label for="souvenirSearch">Cari item</label>
            <div class="input-with-icon"><span aria-hidden="true">⌕</span><input id="souvenirSearch" type="search" name="q" maxlength="150" value="<?= esc($filters['q'], 'attr') ?>" placeholder="Cari nama item atau keterangan"></div>
        </div>
        <div class="form-group">
            <label for="souvenirStock">Status stok</label>
            <select id="souvenirStock" name="stok">
                <option value="">Semua stok</option>
                <option value="tersedia" <?= $filters['stok'] === 'tersedia' ? 'selected' : '' ?>>Masih tersedia</option>
                <option value="habis" <?= $filters['stok'] === 'habis' ? 'selected' : '' ?>>Stok habis</option>
            </select>
        </div>
        <div class="form-group">
            <label for="souvenirJenis">Jenis</label>
            <select id="souvenirJenis" name="jenis">
                <option value="">Semua jenis</option>
                <option value="Souvenir" <?= $filters['jenis'] === 'Souvenir' ? 'selected' : '' ?>>Souvenir</option>
                <option value="Inventaris" <?= $filters['jenis'] === 'Inventaris' ? 'selected' : '' ?>>Inventaris</option>
            </select>
        </div>
        <div class="filter-actions">
            <button class="btn btn-secondary" type="submit">Filter</button>
            <a class="btn btn-ghost" href="<?= site_url('agendaris/souvenir-inventaris') ?>">Reset</a>
            <details class="lr-action-menu lr-filter-menu">
                <summary class="lr-filter-menu-trigger" aria-label="Menu tindakan Souvenir dan Inventaris" title="Menu tindakan"><svg aria-hidden="true" viewBox="0 0 24 24" focusable="false"><rect x="3" y="3" width="7" height="7" rx="1.4"></rect><rect x="14" y="3" width="7" height="7" rx="1.4"></rect><rect x="3" y="14" width="7" height="7" rx="1.4"></rect><rect x="14" y="14" width="7" height="7" rx="1.4"></rect></svg></summary>
                <div class="lr-action-menu-popover" role="menu" aria-label="Menu Souvenir dan Inventaris"><button type="button" role="menuitem" class="lr-action-menu-item" data-souvenir-create>Tambah Item</button><button type="button" role="menuitem" class="lr-action-menu-item" data-souvenir-pickup-open>Pengambilan Souvenir</button><button type="button" role="menuitem" class="lr-action-menu-item" data-souvenir-return-open>Pengembalian Souvenir (Sisa)</button><button type="button" role="menuitem" class="lr-action-menu-item" data-inventory-loan-open>Peminjaman Inventaris</button><button type="button" role="menuitem" class="lr-action-menu-item" data-inventory-return-open>Pengembalian Inventaris</button></div>
            </details>
        </div>
    </form>
</section>

<section class="panel register-panel">
    <div class="table-wrap" role="region" aria-label="Tabel souvenir dan inventaris" tabindex="0">
        <table class="souvenir-inventaris-table">
            <thead><tr><th scope="col">Nama Item</th><th scope="col">Jenis</th><th scope="col">Persediaan Awal</th><th scope="col">Persediaan Akhir</th><th scope="col">Terpakai</th><th scope="col">Keterangan</th><th scope="col">Aksi</th></tr></thead>
            <tbody>
            <?php if ($items === []): ?>
                <tr><td colspan="7"><div class="empty-state"><span aria-hidden="true">▤</span><strong>Data belum tersedia</strong><p>Tambahkan item souvenir atau inventaris untuk memulai pencatatan.</p></div></td></tr>
            <?php else: foreach ($items as $item): ?>
                <tr>
                    <?php $unitLabel = $units[$item['satuan'] ?? 'pcs'] ?? ucfirst((string) ($item['satuan'] ?? 'pcs')); ?>
                    <td><strong><?= esc($item['nama_item']) ?></strong></td>
                    <td><?= esc($item['jenis'] ?? 'Souvenir') ?></td>
                    <td><span class="souvenir-stock-with-unit"><span class="souvenir-stock-value"><?= number_format((int) $item['persediaan_awal'], 0, ',', '.') ?></span><span class="souvenir-stock-unit"><?= esc($unitLabel) ?></span></span></td>
                    <td><span class="souvenir-stock-with-unit"><span class="souvenir-stock-value"><?= number_format((int) $item['persediaan_akhir'], 0, ',', '.') ?></span><span class="souvenir-stock-unit"><?= esc($unitLabel) ?></span></span></td>
                    <td><span class="souvenir-stock-with-unit"><span class="souvenir-stock-value"><?= number_format((int) $item['terpakai'], 0, ',', '.') ?></span><span class="souvenir-stock-unit"><?= esc($unitLabel) ?></span></span></td>
                    <td><?= $item['keterangan'] === null || $item['keterangan'] === '' ? '—' : esc($item['keterangan']) ?></td>
                    <td><div class="action-buttons"><button type="button" class="icon-btn" data-souvenir-view="<?= (int) $item['id'] ?>" title="Lihat detail" aria-label="Lihat detail"><?= view('components/table_action_icon', ['icon' => 'view']) ?></button><button type="button" class="icon-btn" data-souvenir-edit="<?= (int) $item['id'] ?>" title="Ubah" aria-label="Ubah item"><?= view('components/table_action_icon', ['icon' => 'edit']) ?></button><form method="post" action="<?= site_url('agendaris/souvenir-inventaris/' . $item['id'] . '/hapus') ?>" onsubmit="return confirm('Hapus item ini?')"><?= csrf_field() ?><button class="icon-btn icon-btn-delete" type="submit" title="Hapus" aria-label="Hapus item"><?= view('components/table_action_icon', ['icon' => 'delete']) ?></button></form></div></td>
                </tr>
            <?php endforeach; endif ?>
            </tbody>
        </table>
    </div>
    <div class="table-list-footer">
        <form method="get" action="<?= site_url('agendaris/souvenir-inventaris') ?>" class="table-length-form">
            <input type="hidden" name="q" value="<?= esc($filters['q'], 'attr') ?>"><input type="hidden" name="stok" value="<?= esc($filters['stok'], 'attr') ?>"><input type="hidden" name="jenis" value="<?= esc($filters['jenis'], 'attr') ?>">
            <label for="souvenirPerPage">Tampilkan</label><select id="souvenirPerPage" name="per_page" data-table-length><?php foreach ([10, 20, 50, 100] as $size): ?><option value="<?= $size ?>" <?= $filters['perPage'] === $size ? 'selected' : '' ?>><?= $size ?></option><?php endforeach ?></select><span>data</span>
        </form>
        <?php if ($items !== []): ?><div class="pagination-wrap"><?= $pager->links('souvenir_inventaris', 'table_full') ?></div><?php endif ?>
    </div>
</section>

<div class="input-modal" id="souvenirFormModal" hidden aria-hidden="true">
    <div class="modal-backdrop" aria-hidden="true"></div>
    <section class="modal-dialog souvenir-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="souvenirFormTitle">
        <header class="modal-header"><div class="modal-title-group"><span class="modal-title-icon" aria-hidden="true">✎</span><div><p>AGENDARIS / SOUVENIR DAN INVENTARIS</p><h2 id="souvenirFormTitle">Tambah Item</h2></div></div><button type="button" class="modal-close" data-souvenir-close aria-label="Tutup formulir">×</button></header>
        <form method="post" action="<?= site_url('agendaris/souvenir-inventaris') ?>" id="souvenirModalForm">
            <?= csrf_field() ?>
            <div class="modal-body">
                <div class="modal-alert" id="souvenirFormErrors" role="alert" hidden></div>
                <div class="modal-form-grid">
                    <div class="form-group"><label for="souvenirItemJenis">Jenis <span class="required">*</span></label><select id="souvenirItemJenis" name="jenis" required><option value="Souvenir">Souvenir</option><option value="Inventaris">Inventaris</option></select></div>
                    <div class="form-group"><label for="souvenirName">Nama Item <span class="required">*</span></label><input id="souvenirName" name="nama_item" maxlength="150" required placeholder="Contoh: Mug logo perusahaan"></div>
                    <div class="form-group"><label for="souvenirUnit">Satuan <span class="required">*</span></label><select id="souvenirUnit" name="satuan" required><?php foreach ($units as $value => $label): ?><option value="<?= esc($value, 'attr') ?>"><?= esc($label) ?></option><?php endforeach ?></select></div>
                    <div class="form-group"><label for="souvenirOpening">Persediaan Awal <span class="required">*</span></label><input id="souvenirOpening" type="number" name="persediaan_awal" min="0" max="2147483647" required value="0"></div>
                    <div class="form-group modal-span-2"><label for="souvenirNotes">Keterangan</label><textarea id="souvenirNotes" name="keterangan" rows="4" maxlength="2000" placeholder="Catatan penggunaan atau kondisi persediaan"></textarea></div>
                </div>
            </div>
            <footer class="modal-footer"><button type="button" class="btn btn-ghost" data-souvenir-close>Batal</button><button type="submit" class="btn btn-primary" id="souvenirFormSubmit">Simpan Item</button></footer>
        </form>
    </section>
</div>

<div class="input-modal" id="souvenirDetailModal" hidden aria-hidden="true">
    <div class="modal-backdrop" aria-hidden="true"></div>
    <section class="modal-dialog souvenir-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="souvenirDetailTitle">
        <header class="modal-header"><div class="modal-title-group"><span class="modal-title-icon" aria-hidden="true">▤</span><div><p>AGENDARIS / SOUVENIR DAN INVENTARIS</p><h2 id="souvenirDetailTitle">Detail Item</h2></div></div><button type="button" class="modal-close" data-souvenir-close aria-label="Tutup detail">×</button></header>
        <div class="modal-body"><dl class="detail-list grid-two"><div><dt>Nama Item</dt><dd data-souvenir-detail="nama_item">—</dd></div><div><dt>Jenis</dt><dd data-souvenir-detail="jenis">—</dd></div><div><dt>Satuan</dt><dd data-souvenir-detail="satuan">—</dd></div><div><dt>Persediaan Awal</dt><dd data-souvenir-detail="persediaan_awal">—</dd></div><div><dt>Persediaan Akhir</dt><dd data-souvenir-detail="persediaan_akhir">—</dd></div><div><dt>Terpakai</dt><dd data-souvenir-detail="terpakai">—</dd></div><div class="detail-span-full"><dt>Keterangan</dt><dd data-souvenir-detail="keterangan">—</dd></div></dl></div>
        <footer class="modal-footer"><button type="button" class="btn btn-ghost" data-souvenir-close>Tutup</button><button type="button" class="btn btn-primary" id="souvenirDetailEdit">Ubah Item</button></footer>
    </section>
</div>

<div class="input-modal" id="souvenirPickupModal" hidden aria-hidden="true">
    <div class="modal-backdrop" aria-hidden="true"></div>
    <section class="modal-dialog souvenir-pickup-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="souvenirPickupTitle">
        <header class="modal-header"><div class="modal-title-group"><span class="modal-title-icon" aria-hidden="true">↗</span><div><p>AGENDARIS / SOUVENIR DAN INVENTARIS</p><h2 id="souvenirPickupTitle">Pengambilan Souvenir</h2></div></div><button type="button" class="modal-close" data-souvenir-pickup-close aria-label="Tutup pengambilan souvenir">×</button></header>
        <div class="modal-body">
            <div class="souvenir-pickup-toolbar"><button type="button" class="btn btn-primary" data-souvenir-pickup-create>＋ Buat Pengambilan</button></div>
            <div class="table-wrap" role="region" aria-label="Tabel pengambilan souvenir" tabindex="0">
                <table class="souvenir-pickup-table"><thead><tr><th scope="col">Nama</th><th scope="col">Divisi / Bagian</th><th scope="col">Nama Item</th><th scope="col">Tgl Pengambilan</th><th scope="col">Aksi</th></tr></thead><tbody>
                <?php if ($pickupRecords === []): ?><tr><td colspan="5"><div class="empty-state"><span aria-hidden="true">▤</span><strong>Belum ada pengambilan souvenir</strong><p>Buat data pengambilan untuk mencatat siapa yang mengambil souvenir.</p></div></td></tr>
                <?php else: foreach ($pickupRecords as $record): ?><tr><td><?= esc($record['nama_pengambil']) ?></td><td><?= esc($record['divisi_bagian']) ?></td><td><?= esc($record['nama_item']) ?></td><td><?= esc(date('d/m/Y', strtotime($record['tanggal_pengambilan']))) ?></td><td><div class="action-buttons"><button type="button" class="icon-btn" data-souvenir-pickup-view="<?= (int) $record['id'] ?>" title="Lihat pengambilan" aria-label="Lihat pengambilan"><?= view('components/table_action_icon', ['icon' => 'view']) ?></button><button type="button" class="icon-btn" data-souvenir-pickup-edit="<?= (int) $record['id'] ?>" title="Ubah pengambilan" aria-label="Ubah pengambilan"><?= view('components/table_action_icon', ['icon' => 'edit']) ?></button><a class="icon-btn souvenir-pickup-download" href="<?= site_url('agendaris/souvenir-inventaris/pengambilan/' . (int) $record['id'] . '/bukti') ?>" title="Unduh bukti pengambilan" aria-label="Unduh bukti pengambilan <?= esc($record['nama_pengambil'], 'attr') ?>"><?= view('components/table_action_icon', ['icon' => 'download']) ?></a><button class="icon-btn icon-btn-delete" type="button" data-souvenir-pickup-delete="<?= (int) $record['id'] ?>" data-souvenir-pickup-delete-label="<?= esc($record['nama_pengambil'], 'attr') ?>" title="Hapus pengambilan" aria-label="Hapus pengambilan <?= esc($record['nama_pengambil'], 'attr') ?>"><?= view('components/table_action_icon', ['icon' => 'delete']) ?></button></div></td></tr><?php endforeach; endif ?>
                </tbody></table>
            </div>
        </div>
        <footer class="modal-footer"><button type="button" class="btn btn-ghost" data-souvenir-pickup-close>Tutup</button></footer>
    </section>
</div>

<div class="input-modal souvenir-pickup-form-modal" id="souvenirPickupFormModal" hidden aria-hidden="true">
    <div class="modal-backdrop" aria-hidden="true"></div>
    <section class="modal-dialog souvenir-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="souvenirPickupFormTitle">
        <header class="modal-header"><div class="modal-title-group"><span class="modal-title-icon" aria-hidden="true">✎</span><div><p>PENGAMBILAN SOUVENIR</p><h2 id="souvenirPickupFormTitle">Buat Pengambilan</h2></div></div><button type="button" class="modal-close" data-souvenir-pickup-child-close aria-label="Tutup formulir pengambilan">×</button></header>
        <form method="post" action="<?= site_url('agendaris/souvenir-inventaris/pengambilan') ?>" id="souvenirPickupForm">
            <?= csrf_field() ?>
            <div class="modal-body"><div class="modal-alert" id="souvenirPickupFormErrors" role="alert" hidden></div><div class="modal-form-grid souvenir-pickup-form-grid">
                <div class="form-group"><label for="pickupName">Nama <span class="required">*</span></label><input id="pickupName" name="nama_pengambil" maxlength="150" required placeholder="Nama pengambil souvenir"></div>
                <div class="form-group"><label for="pickupDivision">Divisi / Bagian <span class="required">*</span></label><input id="pickupDivision" name="divisi_bagian" maxlength="150" required placeholder="Contoh: Divisi Umum"></div>
                <div class="form-group"><label for="pickupDate">Tgl Pengambilan <span class="required">*</span></label><input id="pickupDate" type="date" name="tanggal_pengambilan" required value="<?= date('Y-m-d') ?>"></div>
                <div class="form-group"><label for="pickupPurpose">Keperluan / Kegiatan <span class="required">*</span></label><input id="pickupPurpose" name="keperluan_kegiatan" maxlength="500" required placeholder="Contoh: Kegiatan sosialisasi"></div>
                <div class="form-group modal-span-2"><label for="pickupNotes">Catatan <span class="optional">(opsional)</span></label><input id="pickupNotes" name="keterangan" maxlength="500" placeholder="Tambahkan catatan bila diperlukan"></div>
                <div class="form-group modal-span-2"><label for="pickupItem">Nama Item dan Jumlah Diambil <span class="required">*</span></label><div class="souvenir-pickup-item-list" data-souvenir-pickup-item-list><div class="souvenir-pickup-item-row"><select id="pickupItem" name="souvenir_ids[]" required data-souvenir-pickup-item aria-label="Nama item souvenir"><option value="">Pilih item souvenir</option><?php foreach ($pickupSouvenirs as $item): ?><?php $pickupUnit = $units[$item['satuan'] ?? 'pcs'] ?? ucfirst((string) ($item['satuan'] ?? 'pcs')); $pickupStock = max(0, (int) ($item['persediaan_akhir'] ?? 0)); ?><option value="<?= (int) $item['id'] ?>" data-unit="<?= esc($pickupUnit, 'attr') ?>" data-stock="<?= $pickupStock ?>"><?= esc($item['nama_item']) ?> — tersedia <?= number_format($pickupStock, 0, ',', '.') ?> <?= esc($pickupUnit) ?></option><?php endforeach ?></select><div class="souvenir-pickup-quantity"><input id="pickupQuantity" type="number" name="jumlah_diambil[]" min="1" max="2147483647" required value="1" data-souvenir-pickup-quantity aria-label="Jumlah satuan yang diambil" title="Jumlah satuan yang diambil"><span class="souvenir-pickup-unit" data-souvenir-pickup-unit hidden>Satuan</span></div><button type="button" class="icon-btn icon-btn-delete souvenir-pickup-remove-item" data-souvenir-pickup-remove-item title="Hapus item" aria-label="Hapus item" hidden><?= view('components/table_action_icon', ['icon' => 'delete']) ?></button></div></div><button type="button" class="btn btn-ghost souvenir-pickup-add-item" data-souvenir-pickup-add-item>＋ Tambah Item</button></div>
            </div></div>
            <footer class="modal-footer"><button type="button" class="btn btn-ghost" data-souvenir-pickup-child-close>Batal</button><button type="submit" class="btn btn-primary" id="souvenirPickupFormSubmit">Simpan Pengambilan</button></footer>
        </form>
    </section>
</div>

<div class="input-modal souvenir-pickup-detail-modal" id="souvenirPickupDetailModal" hidden aria-hidden="true">
    <div class="modal-backdrop" aria-hidden="true"></div>
    <section class="modal-dialog souvenir-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="souvenirPickupDetailTitle">
        <header class="modal-header"><div class="modal-title-group"><span class="modal-title-icon" aria-hidden="true">▤</span><div><p>PENGAMBILAN SOUVENIR</p><h2 id="souvenirPickupDetailTitle">Detail Pengambilan</h2></div></div><button type="button" class="modal-close" data-souvenir-pickup-child-close aria-label="Tutup detail pengambilan">×</button></header>
        <div class="modal-body"><dl class="detail-list grid-two"><div><dt>Nama</dt><dd data-souvenir-pickup-detail="nama_pengambil">—</dd></div><div><dt>Divisi / Bagian</dt><dd data-souvenir-pickup-detail="divisi_bagian">—</dd></div><div><dt>Tgl Pengambilan</dt><dd data-souvenir-pickup-detail="tanggal_pengambilan">—</dd></div><div><dt>Keperluan / Kegiatan</dt><dd data-souvenir-pickup-detail="keperluan_kegiatan">—</dd></div><div class="full-detail"><dt>Catatan</dt><dd data-souvenir-pickup-detail="keterangan">—</dd></div><div class="full-detail"><dt>Nama Item</dt><dd data-souvenir-pickup-detail="nama_item">—</dd></div></dl></div>
        <footer class="modal-footer"><button type="button" class="btn btn-ghost" data-souvenir-pickup-child-close>Tutup</button><button type="button" class="btn btn-primary" id="souvenirPickupDetailEdit">Ubah Pengambilan</button></footer>
    </section>
</div>

<div class="input-modal souvenir-pickup-delete-modal" id="souvenirPickupDeleteModal" hidden aria-hidden="true">
    <button type="button" class="modal-backdrop" data-souvenir-pickup-delete-close aria-label="Batal hapus"></button>
    <section class="modal-dialog delete-modal-dialog" role="alertdialog" aria-modal="true" aria-labelledby="souvenirPickupDeleteTitle">
        <div class="delete-modal-body"><span class="delete-warning-icon" aria-hidden="true">!</span><h2 id="souvenirPickupDeleteTitle">Hapus Pengambilan?</h2><p>Data pengambilan oleh <strong data-souvenir-pickup-delete-label>ini</strong> akan dihapus dan stok souvenir dikembalikan.</p></div>
        <form method="post" action="" data-souvenir-pickup-delete-form class="delete-modal-actions"><?= csrf_field() ?><button type="button" class="btn btn-ghost" data-souvenir-pickup-delete-close>Batal</button><button type="submit" class="btn btn-delete">Ya, hapus</button></form>
    </section>
</div>

<div class="input-modal" id="souvenirReturnModal" hidden aria-hidden="true">
    <div class="modal-backdrop" aria-hidden="true"></div>
    <section class="modal-dialog souvenir-pickup-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="souvenirReturnTitle">
        <header class="modal-header"><div class="modal-title-group"><span class="modal-title-icon" aria-hidden="true">↗</span><div><p>AGENDARIS / SOUVENIR DAN INVENTARIS</p><h2 id="souvenirReturnTitle">Pengembalian Souvenir (Sisa)</h2></div></div><button type="button" class="modal-close" data-souvenir-return-close aria-label="Tutup pengembalian souvenir">×</button></header>
        <div class="modal-body"><div class="souvenir-pickup-toolbar"><button type="button" class="btn btn-primary" data-souvenir-return-create>＋ Buat Pengembalian</button></div><div class="table-responsive"><table class="souvenir-pickup-table"><thead><tr><th scope="col">Nama</th><th scope="col">Divisi / Bagian</th><th scope="col">Nama Item</th><th scope="col">Tgl Pengembalian</th><th scope="col">Aksi</th></tr></thead><tbody>
            <?php if ($returnRecords === []): ?><tr><td colspan="5"><div class="empty-state"><span aria-hidden="true">▤</span><strong>Belum ada pengembalian souvenir</strong><p>Buat data pengembalian untuk mencatat sisa souvenir yang dikembalikan.</p></div></td></tr>
            <?php else: foreach ($returnRecords as $record): ?><tr><td><?= esc($record['nama_pengembali']) ?></td><td><?= esc($record['divisi_bagian']) ?></td><td><?= esc($record['nama_item']) ?></td><td><?= esc(date('d/m/Y', strtotime($record['tanggal_pengembalian']))) ?></td><td><div class="action-buttons"><button type="button" class="icon-btn" data-souvenir-return-view="<?= (int) $record['id'] ?>" title="Lihat pengembalian" aria-label="Lihat pengembalian"><?= view('components/table_action_icon', ['icon' => 'view']) ?></button><button type="button" class="icon-btn" data-souvenir-return-edit="<?= (int) $record['id'] ?>" title="Ubah pengembalian" aria-label="Ubah pengembalian"><?= view('components/table_action_icon', ['icon' => 'edit']) ?></button><a class="icon-btn souvenir-return-download" href="<?= site_url('agendaris/souvenir-inventaris/pengembalian/' . (int) $record['id'] . '/bukti') ?>" title="Unduh bukti pengembalian" aria-label="Unduh bukti pengembalian <?= esc($record['nama_pengembali'], 'attr') ?>"><?= view('components/table_action_icon', ['icon' => 'download']) ?></a><button class="icon-btn icon-btn-delete" type="button" data-souvenir-return-delete="<?= (int) $record['id'] ?>" data-souvenir-return-delete-label="<?= esc($record['nama_pengembali'], 'attr') ?>" title="Hapus pengembalian" aria-label="Hapus pengembalian <?= esc($record['nama_pengembali'], 'attr') ?>"><?= view('components/table_action_icon', ['icon' => 'delete']) ?></button></div></td></tr><?php endforeach; endif ?>
        </tbody></table></div></div>
        <footer class="modal-footer"><button type="button" class="btn btn-ghost" data-souvenir-return-close>Tutup</button></footer>
    </section>
</div>

<div class="input-modal souvenir-return-form-modal" id="souvenirReturnFormModal" hidden aria-hidden="true">
    <div class="modal-backdrop" aria-hidden="true"></div>
    <section class="modal-dialog souvenir-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="souvenirReturnFormTitle">
        <header class="modal-header"><div class="modal-title-group"><span class="modal-title-icon" aria-hidden="true">✎</span><div><p>PENGEMBALIAN SOUVENIR</p><h2 id="souvenirReturnFormTitle">Buat Pengembalian</h2></div></div><button type="button" class="modal-close" data-souvenir-return-child-close aria-label="Tutup formulir pengembalian">×</button></header>
        <form method="post" action="<?= site_url('agendaris/souvenir-inventaris/pengembalian') ?>" id="souvenirReturnForm"><?= csrf_field() ?><div class="modal-body"><div class="modal-alert" id="souvenirReturnFormErrors" role="alert" hidden></div><div class="modal-form-grid souvenir-return-form-grid">
            <div class="form-group"><label for="returnName">Nama <span class="required">*</span></label><input id="returnName" name="nama_pengembali" maxlength="150" required placeholder="Nama pengembali souvenir"></div>
            <div class="form-group"><label for="returnDivision">Divisi / Bagian <span class="required">*</span></label><input id="returnDivision" name="divisi_bagian" maxlength="150" required placeholder="Contoh: Divisi Umum"></div>
            <div class="form-group"><label for="returnDate">Tgl Pengembalian <span class="required">*</span></label><input id="returnDate" type="date" name="tanggal_pengembalian" required value="<?= date('Y-m-d') ?>"></div>
            <div class="form-group"><label for="returnPurpose">Keperluan / Kegiatan <span class="required">*</span></label><input id="returnPurpose" name="keperluan_kegiatan" maxlength="500" required placeholder="Contoh: Kegiatan sosialisasi"></div>
            <div class="form-group modal-span-2"><label for="returnNotes">Catatan <span class="optional">(opsional)</span></label><input id="returnNotes" name="keterangan" maxlength="500" placeholder="Tambahkan catatan bila diperlukan"></div>
            <div class="form-group modal-span-2"><label for="returnItem">Nama Item dan Jumlah Dikembalikan <span class="required">*</span></label><div class="souvenir-return-item-list" data-souvenir-return-item-list><div class="souvenir-return-item-row"><select id="returnItem" name="souvenir_ids[]" required data-souvenir-return-item aria-label="Nama item souvenir"><option value="">Pilih item souvenir</option><?php foreach ($pickupSouvenirs as $item): ?><?php $returnUnit = $units[$item['satuan'] ?? 'pcs'] ?? ucfirst((string) ($item['satuan'] ?? 'pcs')); ?><option value="<?= (int) $item['id'] ?>" data-unit="<?= esc($returnUnit, 'attr') ?>"><?= esc($item['nama_item']) ?></option><?php endforeach ?></select><div class="souvenir-return-quantity"><input id="returnQuantity" type="number" name="jumlah_dikembalikan[]" min="1" max="2147483647" required value="1" data-souvenir-return-quantity aria-label="Jumlah satuan yang dikembalikan"><span class="souvenir-return-unit" data-souvenir-return-unit hidden>Satuan</span></div><button type="button" class="icon-btn icon-btn-delete souvenir-return-remove-item" data-souvenir-return-remove-item title="Hapus item" aria-label="Hapus item" hidden><?= view('components/table_action_icon', ['icon' => 'delete']) ?></button></div></div><button type="button" class="btn btn-ghost souvenir-pickup-add-item" data-souvenir-return-add-item>＋ Tambah Item</button></div>
        </div></div><footer class="modal-footer"><button type="button" class="btn btn-ghost" data-souvenir-return-child-close>Batal</button><button type="submit" class="btn btn-primary" id="souvenirReturnFormSubmit">Simpan Pengembalian</button></footer></form>
    </section>
</div>

<div class="input-modal souvenir-return-detail-modal" id="souvenirReturnDetailModal" hidden aria-hidden="true">
    <div class="modal-backdrop" aria-hidden="true"></div>
    <section class="modal-dialog souvenir-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="souvenirReturnDetailTitle"><header class="modal-header"><div class="modal-title-group"><span class="modal-title-icon" aria-hidden="true">▤</span><div><p>PENGEMBALIAN SOUVENIR</p><h2 id="souvenirReturnDetailTitle">Detail Pengembalian</h2></div></div><button type="button" class="modal-close" data-souvenir-return-child-close aria-label="Tutup detail pengembalian">×</button></header><div class="modal-body"><dl class="detail-list grid-two"><div><dt>Nama</dt><dd data-souvenir-return-detail="nama_pengembali">—</dd></div><div><dt>Divisi / Bagian</dt><dd data-souvenir-return-detail="divisi_bagian">—</dd></div><div><dt>Tgl Pengembalian</dt><dd data-souvenir-return-detail="tanggal_pengembalian">—</dd></div><div><dt>Keperluan / Kegiatan</dt><dd data-souvenir-return-detail="keperluan_kegiatan">—</dd></div><div class="full-detail"><dt>Catatan</dt><dd data-souvenir-return-detail="keterangan">—</dd></div><div class="full-detail"><dt>Nama Item</dt><dd data-souvenir-return-detail="nama_item">—</dd></div></dl></div><footer class="modal-footer"><button type="button" class="btn btn-ghost" data-souvenir-return-child-close>Tutup</button><button type="button" class="btn btn-primary" id="souvenirReturnDetailEdit">Ubah Pengembalian</button></footer></section>
</div>

<div class="input-modal" id="souvenirReturnDeleteModal" hidden aria-hidden="true"><button type="button" class="modal-backdrop" data-souvenir-return-delete-close aria-label="Batal hapus"></button><section class="modal-dialog delete-modal-dialog" role="alertdialog" aria-modal="true" aria-labelledby="souvenirReturnDeleteTitle"><div class="delete-modal-body"><span class="delete-warning-icon" aria-hidden="true">!</span><h2 id="souvenirReturnDeleteTitle">Hapus Pengembalian?</h2><p>Data pengembalian oleh <strong data-souvenir-return-delete-label>ini</strong> akan dihapus dan stok souvenir diperbarui.</p></div><form method="post" action="" data-souvenir-return-delete-form class="delete-modal-actions"><?= csrf_field() ?><button type="button" class="btn btn-ghost" data-souvenir-return-delete-close>Batal</button><button type="submit" class="btn btn-delete">Ya, hapus</button></form></section></div>

<?php foreach ([
    ['key' => 'inventory-loan', 'title' => 'Peminjaman Inventaris', 'verb' => 'Peminjaman', 'name' => 'nama_peminjam', 'date' => 'tanggal_peminjaman', 'quantity' => 'jumlah_dipinjam', 'records' => $loanRecords, 'action' => 'peminjaman-inventaris', 'itemKey' => 'loan_items', 'proof' => 'peminjaman-inventaris'],
    ['key' => 'inventory-return', 'title' => 'Pengembalian Inventaris', 'verb' => 'Pengembalian', 'name' => 'nama_pengembali', 'date' => 'tanggal_pengembalian', 'quantity' => 'jumlah_dikembalikan', 'records' => $inventoryReturnRecords, 'action' => 'pengembalian-inventaris', 'itemKey' => 'return_items', 'proof' => 'pengembalian-inventaris'],
] as $movement): ?>
<div class="input-modal" id="<?= $movement['key'] ?>Modal" hidden aria-hidden="true"><div class="modal-backdrop" aria-hidden="true"></div><section class="modal-dialog souvenir-pickup-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="<?= $movement['key'] ?>Title"><header class="modal-header"><div class="modal-title-group"><span class="modal-title-icon" aria-hidden="true">↗</span><div><p>AGENDARIS / SOUVENIR DAN INVENTARIS</p><h2 id="<?= $movement['key'] ?>Title"><?= esc($movement['title']) ?></h2></div></div><button type="button" class="modal-close" data-<?= $movement['key'] ?>-close aria-label="Tutup <?= esc(strtolower($movement['title']), 'attr') ?>">×</button></header><div class="modal-body"><div class="souvenir-pickup-toolbar"><button type="button" class="btn btn-primary" data-<?= $movement['key'] ?>-create>＋ Buat <?= esc($movement['verb']) ?></button></div><div class="table-responsive"><table class="souvenir-pickup-table"><thead><tr><th>Nama</th><th>Divisi / Bagian</th><th>Nama Item</th><th><?= $movement['verb'] === 'Peminjaman' ? 'Tgl Peminjaman' : 'Tgl Pengembalian' ?></th><th>Aksi</th></tr></thead><tbody><?php if ($movement['records'] === []): ?><tr><td colspan="5"><div class="empty-state"><span aria-hidden="true">▤</span><strong>Belum ada <?= esc(strtolower($movement['title'])) ?></strong><p>Buat data untuk mencatat pergerakan stok inventaris.</p></div></td></tr><?php else: foreach ($movement['records'] as $record): ?><tr><td><?= esc($record[$movement['name']]) ?></td><td><?= esc($record['divisi_bagian']) ?></td><td><?= esc($record['nama_item']) ?></td><td><?= esc(date('d/m/Y', strtotime($record[$movement['date']]))) ?></td><td><div class="action-buttons"><button type="button" class="icon-btn" data-<?= $movement['key'] ?>-view="<?= (int) $record['id'] ?>" title="Lihat" aria-label="Lihat"><?= view('components/table_action_icon', ['icon' => 'view']) ?></button><button type="button" class="icon-btn" data-<?= $movement['key'] ?>-edit="<?= (int) $record['id'] ?>" title="Ubah" aria-label="Ubah"><?= view('components/table_action_icon', ['icon' => 'edit']) ?></button><a class="icon-btn" href="<?= site_url('agendaris/souvenir-inventaris/' . $movement['proof'] . '/' . (int) $record['id'] . '/bukti') ?>" title="Unduh bukti" aria-label="Unduh bukti"><?= view('components/table_action_icon', ['icon' => 'download']) ?></a><button type="button" class="icon-btn icon-btn-delete" data-<?= $movement['key'] ?>-delete="<?= (int) $record['id'] ?>" data-<?= $movement['key'] ?>-delete-label="<?= esc($record[$movement['name']], 'attr') ?>" title="Hapus" aria-label="Hapus"><?= view('components/table_action_icon', ['icon' => 'delete']) ?></button></div></td></tr><?php endforeach; endif ?></tbody></table></div></div><footer class="modal-footer"><button type="button" class="btn btn-ghost" data-<?= $movement['key'] ?>-close>Tutup</button></footer></section></div>
<div class="input-modal" id="<?= $movement['key'] ?>FormModal" hidden aria-hidden="true"><div class="modal-backdrop" aria-hidden="true"></div><section class="modal-dialog souvenir-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="<?= $movement['key'] ?>FormTitle"><header class="modal-header"><div class="modal-title-group"><span class="modal-title-icon" aria-hidden="true">✎</span><div><p><?= strtoupper(esc($movement['title'])) ?></p><h2 id="<?= $movement['key'] ?>FormTitle">Buat <?= esc($movement['verb']) ?></h2></div></div><button type="button" class="modal-close" data-<?= $movement['key'] ?>-child-close aria-label="Tutup formulir">×</button></header><form method="post" action="<?= site_url('agendaris/souvenir-inventaris/' . $movement['action']) ?>" id="<?= $movement['key'] ?>Form"><?= csrf_field() ?><div class="modal-body"><div class="modal-alert" id="<?= $movement['key'] ?>FormErrors" role="alert" hidden></div><div class="modal-form-grid souvenir-pickup-form-grid"><div class="form-group"><label>Nama <span class="required">*</span></label><input name="<?= $movement['name'] ?>" maxlength="150" required placeholder="Nama <?= $movement['verb'] === 'Peminjaman' ? 'peminjam' : 'pengembali' ?> inventaris"></div><div class="form-group"><label>Divisi / Bagian <span class="required">*</span></label><input name="divisi_bagian" maxlength="150" required placeholder="Contoh: Divisi Umum"></div><div class="form-group"><label><?= $movement['verb'] === 'Peminjaman' ? 'Tgl Peminjaman' : 'Tgl Pengembalian' ?> <span class="required">*</span></label><input type="date" name="<?= $movement['date'] ?>" required value="<?= date('Y-m-d') ?>"></div><div class="form-group"><label>Keperluan / Kegiatan <span class="required">*</span></label><input name="keperluan_kegiatan" maxlength="500" required placeholder="Contoh: Kegiatan sosialisasi"></div><div class="form-group modal-span-2"><label>Catatan <span class="optional">(opsional)</span></label><input name="keterangan" maxlength="500" placeholder="Tambahkan catatan bila diperlukan"></div><div class="form-group modal-span-2"><label>Nama Item dan Jumlah <?= $movement['verb'] === 'Peminjaman' ? 'Dipinjam' : 'Dikembalikan' ?> <span class="required">*</span></label><div class="souvenir-pickup-item-list" data-<?= $movement['key'] ?>-item-list><div class="souvenir-pickup-item-row"><select name="inventaris_ids[]" required data-<?= $movement['key'] ?>-item><option value="">Pilih item inventaris</option><?php foreach ($inventoryItems as $item): ?><?php $inventoryUnit = $units[$item['satuan'] ?? 'unit'] ?? ucfirst((string) ($item['satuan'] ?? 'unit')); $inventoryStock = max(0, (int) ($item['persediaan_akhir'] ?? 0)); ?><option value="<?= (int) $item['id'] ?>" data-unit="<?= esc($inventoryUnit, 'attr') ?>" data-stock="<?= $inventoryStock ?>"><?= esc($item['nama_item']) ?> — tersedia <?= number_format($inventoryStock, 0, ',', '.') ?> <?= esc($inventoryUnit) ?></option><?php endforeach ?></select><div class="souvenir-pickup-quantity"><input type="number" name="<?= $movement['quantity'] ?>[]" min="1" max="2147483647" required value="1" data-<?= $movement['key'] ?>-quantity><span class="souvenir-pickup-unit" data-<?= $movement['key'] ?>-unit hidden>Satuan</span></div><button type="button" class="icon-btn icon-btn-delete souvenir-pickup-remove-item" data-<?= $movement['key'] ?>-remove-item title="Hapus item" aria-label="Hapus item" hidden><?= view('components/table_action_icon', ['icon' => 'delete']) ?></button></div></div><button type="button" class="btn btn-ghost souvenir-pickup-add-item" data-<?= $movement['key'] ?>-add-item>＋ Tambah Item</button></div></div></div><footer class="modal-footer"><button type="button" class="btn btn-ghost" data-<?= $movement['key'] ?>-child-close>Batal</button><button type="submit" class="btn btn-primary" data-<?= $movement['key'] ?>-submit>Simpan <?= esc($movement['verb']) ?></button></footer></form></section></div>
<div class="input-modal" id="<?= $movement['key'] ?>DetailModal" hidden aria-hidden="true"><div class="modal-backdrop" aria-hidden="true"></div><section class="modal-dialog souvenir-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="<?= $movement['key'] ?>DetailTitle"><header class="modal-header"><div class="modal-title-group"><span class="modal-title-icon" aria-hidden="true">▤</span><div><p><?= strtoupper(esc($movement['title'])) ?></p><h2 id="<?= $movement['key'] ?>DetailTitle">Detail <?= esc($movement['verb']) ?></h2></div></div><button type="button" class="modal-close" data-<?= $movement['key'] ?>-child-close aria-label="Tutup detail">×</button></header><div class="modal-body"><dl class="detail-list grid-two"><div><dt>Nama</dt><dd data-<?= $movement['key'] ?>-detail="<?= $movement['name'] ?>">—</dd></div><div><dt>Divisi / Bagian</dt><dd data-<?= $movement['key'] ?>-detail="divisi_bagian">—</dd></div><div><dt><?= $movement['verb'] === 'Peminjaman' ? 'Tgl Peminjaman' : 'Tgl Pengembalian' ?></dt><dd data-<?= $movement['key'] ?>-detail="<?= $movement['date'] ?>">—</dd></div><div><dt>Keperluan / Kegiatan</dt><dd data-<?= $movement['key'] ?>-detail="keperluan_kegiatan">—</dd></div><div class="full-detail"><dt>Catatan</dt><dd data-<?= $movement['key'] ?>-detail="keterangan">—</dd></div><div class="full-detail"><dt>Nama Item</dt><dd data-<?= $movement['key'] ?>-detail="nama_item">—</dd></div></dl></div><footer class="modal-footer"><button type="button" class="btn btn-ghost" data-<?= $movement['key'] ?>-child-close>Tutup</button><button type="button" class="btn btn-primary" data-<?= $movement['key'] ?>-detail-edit>Ubah <?= esc($movement['verb']) ?></button></footer></section></div>
<div class="input-modal" id="<?= $movement['key'] ?>DeleteModal" hidden aria-hidden="true"><button type="button" class="modal-backdrop" data-<?= $movement['key'] ?>-delete-close aria-label="Batal hapus"></button><section class="modal-dialog delete-modal-dialog" role="alertdialog" aria-modal="true"><div class="delete-modal-body"><span class="delete-warning-icon" aria-hidden="true">!</span><h2>Hapus <?= esc($movement['verb']) ?>?</h2><p>Data oleh <strong data-<?= $movement['key'] ?>-delete-label>ini</strong> akan dihapus dan stok inventaris diperbarui.</p></div><form method="post" action="" data-<?= $movement['key'] ?>-delete-form class="delete-modal-actions"><?= csrf_field() ?><button type="button" class="btn btn-ghost" data-<?= $movement['key'] ?>-delete-close>Batal</button><button type="submit" class="btn btn-delete">Ya, hapus</button></form></section></div>
<?php endforeach ?>

<script>
(() => {
    const records = <?= json_encode($popupRecords, $jsonFlags) ?>;
    const popupState = <?= json_encode($popupState, $jsonFlags) ?>;
    const baseUrl = <?= json_encode(site_url('agendaris/souvenir-inventaris'), $jsonFlags) ?>;
    const formModal = document.getElementById('souvenirFormModal');
    const detailModal = document.getElementById('souvenirDetailModal');
    const form = document.getElementById('souvenirModalForm');
    const errorsBox = document.getElementById('souvenirFormErrors');
    const pickupRecords = <?= json_encode($pickupPopupRecords, $jsonFlags) ?>;
    const pickupState = <?= json_encode($pickupState, $jsonFlags) ?>;
    const pickupListOpen = <?= json_encode($pickupListOpen, $jsonFlags) ?>;
    const pickupBaseUrl = <?= json_encode(site_url('agendaris/souvenir-inventaris/pengambilan'), $jsonFlags) ?>;
    const pickupModal = document.getElementById('souvenirPickupModal');
    const pickupFormModal = document.getElementById('souvenirPickupFormModal');
    const pickupDetailModal = document.getElementById('souvenirPickupDetailModal');
    const pickupDeleteModal = document.getElementById('souvenirPickupDeleteModal');
    const pickupForm = document.getElementById('souvenirPickupForm');
    const pickupErrorsBox = document.getElementById('souvenirPickupFormErrors');
    const pickupDeleteForm = document.querySelector('[data-souvenir-pickup-delete-form]');
    const pickupDeleteLabel = pickupDeleteModal?.querySelector('[data-souvenir-pickup-delete-label]');
    const pickupItemList = document.querySelector('[data-souvenir-pickup-item-list]');
    const pickupItemTemplate = pickupItemList?.firstElementChild?.cloneNode(true);
    const returnRecords = <?= json_encode($returnPopupRecords, $jsonFlags) ?>;
    const returnState = <?= json_encode($returnState, $jsonFlags) ?>;
    const returnListOpen = <?= json_encode($returnListOpen, $jsonFlags) ?>;
    const returnBaseUrl = <?= json_encode(site_url('agendaris/souvenir-inventaris/pengembalian'), $jsonFlags) ?>;
    const returnModal = document.getElementById('souvenirReturnModal');
    const returnFormModal = document.getElementById('souvenirReturnFormModal');
    const returnDetailModal = document.getElementById('souvenirReturnDetailModal');
    const returnDeleteModal = document.getElementById('souvenirReturnDeleteModal');
    const returnForm = document.getElementById('souvenirReturnForm');
    const returnErrorsBox = document.getElementById('souvenirReturnFormErrors');
    const returnDeleteForm = document.querySelector('[data-souvenir-return-delete-form]');
    const returnDeleteLabel = returnDeleteModal?.querySelector('[data-souvenir-return-delete-label]');
    const returnItemList = document.querySelector('[data-souvenir-return-item-list]');
    const returnItemTemplate = returnItemList?.firstElementChild?.cloneNode(true);
    const inventoryMovementConfigs = [
        { prefix: 'inventory-loan', records: <?= json_encode($loanPopupRecords, $jsonFlags) ?>, state: <?= json_encode($loanState, $jsonFlags) ?>, listOpen: <?= json_encode($loanListOpen, $jsonFlags) ?>, baseUrl: <?= json_encode(site_url('agendaris/souvenir-inventaris/peminjaman-inventaris'), $jsonFlags) ?>, itemKey: 'loan_items', quantityKey: 'jumlah_dipinjam', dateKey: 'tanggal_peminjaman', nameKey: 'nama_peminjam', verb: 'Peminjaman', capToStock: true },
        { prefix: 'inventory-return', records: <?= json_encode($inventoryReturnPopupRecords, $jsonFlags) ?>, state: <?= json_encode($inventoryReturnState, $jsonFlags) ?>, listOpen: <?= json_encode($inventoryReturnListOpen, $jsonFlags) ?>, baseUrl: <?= json_encode(site_url('agendaris/souvenir-inventaris/pengembalian-inventaris'), $jsonFlags) ?>, itemKey: 'return_items', quantityKey: 'jumlah_dikembalikan', dateKey: 'tanggal_pengembalian', nameKey: 'nama_pengembali', verb: 'Pengembalian', capToStock: false, takenRecords: <?= json_encode($loanPopupRecords, $jsonFlags) ?>, takenItemKey: 'loan_items', takenQuantityKey: 'jumlah_dipinjam', returnedItemKey: 'return_items', returnedQuantityKey: 'jumlah_dikembalikan', itemIdKey: 'inventaris_id' },
    ];
    const number = new Intl.NumberFormat('id-ID');
    const itemQuantityTotals = (records, itemKey, itemIdKey, quantityKey, ignoredRecordId = null) => {
        const totals = {};
        Object.values(records || {}).forEach((record) => {
            if (ignoredRecordId !== null && String(record?.id) === String(ignoredRecordId)) return;
            (record?.[itemKey] || []).forEach((item) => {
                const id = String(item?.[itemIdKey] ?? '');
                const quantity = Number(item?.[quantityKey] ?? 0);
                if (id && Number.isFinite(quantity) && quantity > 0) totals[id] = (totals[id] || 0) + quantity;
            });
        });
        return totals;
    };
    const inventoryReturnQuantityErrors = ({ itemList, itemSelector, quantitySelector, takenRecords, takenItemKey, takenQuantityKey, returnedRecords, returnedItemKey, returnedQuantityKey, itemIdKey, editingId = null }) => {
        const taken = itemQuantityTotals(takenRecords, takenItemKey, itemIdKey, takenQuantityKey);
        const returned = itemQuantityTotals(returnedRecords, returnedItemKey, itemIdKey, returnedQuantityKey, editingId);
        const submitted = {};
        const labels = {};
        itemList?.querySelectorAll('.souvenir-pickup-item-row, .souvenir-return-item-row').forEach((row) => {
            const select = row.querySelector(itemSelector);
            const quantity = Number(row.querySelector(quantitySelector)?.value || 0);
            const id = String(select?.value || '');
            if (!id || !Number.isFinite(quantity) || quantity <= 0) return;
            submitted[id] = (submitted[id] || 0) + quantity;
            labels[id] = select.options[select.selectedIndex]?.textContent?.split('—')[0]?.trim() || 'Item';
        });
        return Object.entries(submitted).flatMap(([id, quantity]) => {
            const available = Math.max(0, (taken[id] || 0) - (returned[id] || 0));
            return quantity > available ? [`Jumlah pengembalian ${labels[id]} melebihi jumlah yang pernah dipinjam. Maksimal yang dapat dikembalikan: ${number.format(available)}.`] : [];
        });
    };
    let lastTrigger = null;
    let selectedId = null;
    let pickupSelectedId = null;
    let returnSelectedId = null;

    const close = (modal, restoreFocus = true) => {
        modal.classList.remove('open'); modal.hidden = true; modal.setAttribute('aria-hidden', 'true');
        if (restoreFocus) lastTrigger?.focus();
    };
    const open = (modal) => {
        modal.hidden = false; modal.setAttribute('aria-hidden', 'false');
        requestAnimationFrame(() => modal.classList.add('open'));
        modal.querySelector('.modal-close')?.focus();
    };
    const showErrors = (errors = []) => {
        errorsBox.replaceChildren(); errorsBox.hidden = errors.length === 0;
        if (!errors.length) return;
        const heading = document.createElement('strong'); heading.textContent = 'Periksa kembali data berikut:';
        const list = document.createElement('ul');
        errors.forEach((message) => { const item = document.createElement('li'); item.textContent = message; list.append(item); });
        errorsBox.append(heading, list);
    };
    const openForm = (id = null, values = {}, errors = []) => {
        form.reset();
        form.action = id === null ? baseUrl : baseUrl + '/' + encodeURIComponent(id);
        document.getElementById('souvenirFormTitle').textContent = id === null ? 'Tambah Item' : 'Ubah Item';
        document.getElementById('souvenirFormSubmit').textContent = id === null ? 'Simpan Item' : 'Simpan Perubahan';
        Object.entries(values || {}).forEach(([name, value]) => { const field = form.elements.namedItem(name); if (field) field.value = value ?? ''; });
        showErrors(errors); open(formModal);
    };
    const showPickupErrors = (errors = []) => {
        pickupErrorsBox.replaceChildren(); pickupErrorsBox.hidden = errors.length === 0;
        if (!errors.length) return;
        const heading = document.createElement('strong'); heading.textContent = 'Periksa kembali data berikut:';
        const list = document.createElement('ul');
        errors.forEach((message) => { const item = document.createElement('li'); item.textContent = message; list.append(item); });
        pickupErrorsBox.append(heading, list);
    };
    const refreshPickupItemControls = () => {
        const rows = pickupItemList.querySelectorAll('.souvenir-pickup-item-row');
        rows.forEach((row) => { row.querySelector('[data-souvenir-pickup-remove-item]').hidden = rows.length === 1; });
    };
    const refreshPickupUnit = (row) => {
        const select = row.querySelector('[data-souvenir-pickup-item]');
        const quantity = row.querySelector('[data-souvenir-pickup-quantity]');
        const unit = row.querySelector('[data-souvenir-pickup-unit]');
        const option = select.options[select.selectedIndex];
        const label = option?.dataset.unit || '';
        const currentQuantity = Number(row.dataset.currentQuantity || 0);
        const available = Math.max(0, Number(option?.dataset.stock || 0) + currentQuantity);
        unit.textContent = label || 'Satuan'; unit.hidden = !label;
        quantity.max = select.value ? String(available) : '2147483647';
        if (select.value && Number(quantity.value || 0) > available) quantity.value = available || '';
        quantity.title = select.value ? `Stok tersedia: ${number.format(available)} ${label}` : 'Jumlah satuan yang diambil';
    };
    const addPickupItem = (item = {}) => {
        const row = pickupItemTemplate.cloneNode(true);
        const select = row.querySelector('[data-souvenir-pickup-item]');
        const quantity = row.querySelector('[data-souvenir-pickup-quantity]');
        select.removeAttribute('id'); quantity.removeAttribute('id');
        select.value = item.souvenir_id ?? ''; quantity.value = item.jumlah_diambil ?? 1;
        row.dataset.currentQuantity = item.jumlah_diambil ?? 0;
        pickupItemList.append(row); refreshPickupUnit(row); refreshPickupItemControls();
    };
    const setPickupItems = (values = []) => {
        pickupItemList.replaceChildren();
        const itemIds = Array.isArray(values) ? values : [];
        (itemIds.length ? itemIds : [{}]).forEach((item) => addPickupItem(item));
    };
    const openPickupForm = (id = null, values = {}, errors = []) => {
        pickupForm.reset();
        setPickupItems(values?.pickup_items);
        pickupForm.action = id === null ? pickupBaseUrl : pickupBaseUrl + '/' + encodeURIComponent(id);
        document.getElementById('souvenirPickupFormTitle').textContent = id === null ? 'Buat Pengambilan' : 'Ubah Pengambilan';
        document.getElementById('souvenirPickupFormSubmit').textContent = id === null ? 'Simpan Pengambilan' : 'Simpan Perubahan';
        Object.entries(values || {}).forEach(([name, value]) => {
            if (name === 'pickup_items') return;
            const field = pickupForm.elements.namedItem(name); if (field) field.value = value ?? '';
        });
        showPickupErrors(errors); open(pickupFormModal);
    };
    const pickupDate = (value) => {
        const parts = String(value || '').split('-');
        return parts.length === 3 ? parts.reverse().join('/') : '—';
    };
    const setupInventoryMovement = (config) => {
        const attr = (name) => `data-${config.prefix}-${name}`;
        const select = (name) => `[${attr(name)}]`;
        const modal = document.getElementById(`${config.prefix}Modal`);
        const formModal = document.getElementById(`${config.prefix}FormModal`);
        const detailModal = document.getElementById(`${config.prefix}DetailModal`);
        const deleteModal = document.getElementById(`${config.prefix}DeleteModal`);
        const form = document.getElementById(`${config.prefix}Form`);
        const errorsBox = document.getElementById(`${config.prefix}FormErrors`);
        const itemList = document.querySelector(select('item-list'));
        const itemTemplate = itemList?.firstElementChild?.cloneNode(true);
        const deleteForm = document.querySelector(select('delete-form'));
        const deleteLabel = deleteModal?.querySelector(select('delete-label'));
        let selectedId = null;
        const showErrors = (errors = []) => {
            errorsBox.replaceChildren(); errorsBox.hidden = errors.length === 0;
            if (!errors.length) return;
            const heading = document.createElement('strong'); heading.textContent = 'Periksa kembali data berikut:';
            const list = document.createElement('ul'); errors.forEach((message) => { const item = document.createElement('li'); item.textContent = message; list.append(item); });
            errorsBox.append(heading, list);
        };
        const refreshControls = () => {
            const rows = itemList.querySelectorAll('.souvenir-pickup-item-row');
            rows.forEach((row) => { row.querySelector(select('remove-item')).hidden = rows.length === 1; });
        };
        const refreshUnit = (row) => {
            const item = row.querySelector(select('item')); const quantity = row.querySelector(select('quantity')); const unit = row.querySelector(select('unit'));
            const option = item.options[item.selectedIndex]; const label = option?.dataset.unit || '';
            unit.textContent = label || 'Satuan'; unit.hidden = !label;
            if (!config.capToStock) { quantity.max = '2147483647'; return; }
            const available = Math.max(0, Number(option?.dataset.stock || 0) + Number(row.dataset.currentQuantity || 0));
            quantity.max = item.value ? String(available) : '2147483647';
            if (item.value && Number(quantity.value || 0) > available) quantity.value = available || '';
        };
        const addItem = (item = {}) => {
            const row = itemTemplate.cloneNode(true); const itemSelect = row.querySelector(select('item')); const quantity = row.querySelector(select('quantity'));
            itemSelect.value = item.inventaris_id ?? ''; quantity.value = item[config.quantityKey] ?? 1; row.dataset.currentQuantity = item[config.quantityKey] ?? 0;
            itemList.append(row); refreshUnit(row); refreshControls();
        };
        const setItems = (items = []) => { itemList.replaceChildren(); (Array.isArray(items) && items.length ? items : [{}]).forEach((item) => addItem(item)); };
        const openForm = (id = null, values = {}, errors = []) => {
            form.reset(); setItems(values?.[config.itemKey]); form.action = id === null ? config.baseUrl : `${config.baseUrl}/${encodeURIComponent(id)}`;
            document.getElementById(`${config.prefix}FormTitle`).textContent = id === null ? `Buat ${config.verb}` : `Ubah ${config.verb}`;
            form.querySelector(select('submit')).textContent = id === null ? `Simpan ${config.verb}` : 'Simpan Perubahan';
            Object.entries(values || {}).forEach(([name, value]) => { if (name === config.itemKey) return; const field = form.elements.namedItem(name); if (field) field.value = value ?? ''; });
            form.dataset.editingId = id === null ? '' : String(id);
            showErrors(errors); open(formModal);
        };
        form?.addEventListener('submit', (event) => {
            if (!config.takenRecords) return;
            const errors = inventoryReturnQuantityErrors({
                itemList,
                itemSelector: select('item'),
                quantitySelector: select('quantity'),
                takenRecords: config.takenRecords,
                takenItemKey: config.takenItemKey,
                takenQuantityKey: config.takenQuantityKey,
                returnedRecords: config.records,
                returnedItemKey: config.returnedItemKey,
                returnedQuantityKey: config.returnedQuantityKey,
                itemIdKey: config.itemIdKey,
                editingId: form.dataset.editingId || null,
            });
            if (errors.length) { event.preventDefault(); showErrors(errors); }
        });
        document.querySelector(select('open'))?.addEventListener('click', (event) => { const menu = event.currentTarget.closest('details'); lastTrigger = menu?.querySelector('summary') || event.currentTarget; menu?.removeAttribute('open'); open(modal); });
        document.querySelector(select('create'))?.addEventListener('click', (event) => { lastTrigger = event.currentTarget; openForm(); });
        document.querySelector(select('add-item'))?.addEventListener('click', () => addItem());
        itemList?.addEventListener('click', (event) => { const remove = event.target.closest(select('remove-item')); if (!remove) return; const rows = itemList.querySelectorAll('.souvenir-pickup-item-row'); if (rows.length === 1) { rows[0].querySelector(select('item')).value = ''; refreshUnit(rows[0]); return; } remove.closest('.souvenir-pickup-item-row').remove(); refreshControls(); });
        itemList?.addEventListener('change', (event) => { if (event.target.matches(select('item'))) { const row = event.target.closest('.souvenir-pickup-item-row'); row.dataset.currentQuantity = '0'; refreshUnit(row); } });
        document.querySelectorAll(select('view')).forEach((trigger) => trigger.addEventListener('click', () => { const id = trigger.getAttribute(attr('view')); const record = config.records[id]; if (!record) return; selectedId = id; lastTrigger = trigger; detailModal.querySelectorAll(select('detail')).forEach((element) => { const key = element.getAttribute(attr('detail')); element.textContent = key === config.dateKey ? pickupDate(record[key]) : (record[key] || '—'); }); open(detailModal); }));
        document.querySelectorAll(select('edit')).forEach((trigger) => trigger.addEventListener('click', () => { const id = trigger.getAttribute(attr('edit')); if (!config.records[id]) return; lastTrigger = trigger; openForm(id, config.records[id]); }));
        document.querySelectorAll(select('delete')).forEach((trigger) => trigger.addEventListener('click', () => { const id = trigger.getAttribute(attr('delete')); if (!id || !deleteForm) return; lastTrigger = trigger; if (deleteLabel) deleteLabel.textContent = trigger.getAttribute(attr('delete-label')) || 'ini'; deleteForm.action = `${config.baseUrl}/${encodeURIComponent(id)}/hapus`; open(deleteModal); }));
        detailModal.querySelector(select('detail-edit'))?.addEventListener('click', () => { if (!config.records[selectedId]) return; close(detailModal, false); openForm(selectedId, config.records[selectedId]); });
        document.querySelectorAll(select('close')).forEach((button) => button.addEventListener('click', () => close(button.closest('.input-modal'))));
        document.querySelectorAll(select('child-close')).forEach((button) => button.addEventListener('click', () => close(button.closest('.input-modal'), false)));
        document.querySelectorAll(select('delete-close')).forEach((button) => button.addEventListener('click', () => close(deleteModal)));
        if (config.state?.values) { open(modal); openForm(config.state.id ?? null, config.state.values, config.state.errors || []); }
        else if (config.listOpen) open(modal);
    };
    inventoryMovementConfigs.forEach(setupInventoryMovement);
    const showReturnErrors = (errors = []) => {
        returnErrorsBox.replaceChildren(); returnErrorsBox.hidden = errors.length === 0;
        if (!errors.length) return;
        const heading = document.createElement('strong'); heading.textContent = 'Periksa kembali data berikut:';
        const list = document.createElement('ul');
        errors.forEach((message) => { const item = document.createElement('li'); item.textContent = message; list.append(item); });
        returnErrorsBox.append(heading, list);
    };
    const refreshReturnItemControls = () => {
        const rows = returnItemList.querySelectorAll('.souvenir-return-item-row');
        rows.forEach((row) => { row.querySelector('[data-souvenir-return-remove-item]').hidden = rows.length === 1; });
    };
    const refreshReturnUnit = (row) => {
        const select = row.querySelector('[data-souvenir-return-item]');
        const unit = row.querySelector('[data-souvenir-return-unit]');
        const option = select.options[select.selectedIndex]; const label = option?.dataset.unit || '';
        unit.textContent = label || 'Satuan'; unit.hidden = !label;
    };
    const addReturnItem = (item = {}) => {
        const row = returnItemTemplate.cloneNode(true);
        const select = row.querySelector('[data-souvenir-return-item]');
        const quantity = row.querySelector('[data-souvenir-return-quantity]');
        select.removeAttribute('id'); quantity.removeAttribute('id');
        select.value = item.souvenir_id ?? ''; quantity.value = item.jumlah_dikembalikan ?? 1;
        returnItemList.append(row); refreshReturnUnit(row); refreshReturnItemControls();
    };
    const setReturnItems = (values = []) => {
        returnItemList.replaceChildren();
        const itemIds = Array.isArray(values) ? values : [];
        (itemIds.length ? itemIds : [{}]).forEach((item) => addReturnItem(item));
    };
    const openReturnForm = (id = null, values = {}, errors = []) => {
        returnForm.reset(); setReturnItems(values?.return_items);
        returnForm.action = id === null ? returnBaseUrl : returnBaseUrl + '/' + encodeURIComponent(id);
        document.getElementById('souvenirReturnFormTitle').textContent = id === null ? 'Buat Pengembalian' : 'Ubah Pengembalian';
        document.getElementById('souvenirReturnFormSubmit').textContent = id === null ? 'Simpan Pengembalian' : 'Simpan Perubahan';
        Object.entries(values || {}).forEach(([name, value]) => {
            if (name === 'return_items') return;
            const field = returnForm.elements.namedItem(name); if (field) field.value = value ?? '';
        });
        returnForm.dataset.editingId = id === null ? '' : String(id);
        showReturnErrors(errors); open(returnFormModal);
    };
    document.querySelector('[data-souvenir-create]')?.addEventListener('click', (event) => {
        const menu = event.currentTarget.closest('details'); lastTrigger = menu?.querySelector('summary') || event.currentTarget; menu?.removeAttribute('open'); openForm();
    });
    document.querySelector('[data-souvenir-pickup-open]')?.addEventListener('click', (event) => {
        const menu = event.currentTarget.closest('details'); lastTrigger = menu?.querySelector('summary') || event.currentTarget; menu?.removeAttribute('open'); open(pickupModal);
    });
    document.querySelector('[data-souvenir-pickup-create]')?.addEventListener('click', (event) => {
        lastTrigger = event.currentTarget; openPickupForm();
    });
    document.querySelector('[data-souvenir-pickup-add-item]')?.addEventListener('click', () => addPickupItem());
    pickupItemList?.addEventListener('click', (event) => {
        const remove = event.target.closest('[data-souvenir-pickup-remove-item]'); if (!remove) return;
        const rows = pickupItemList.querySelectorAll('.souvenir-pickup-item-row');
        if (rows.length === 1) { rows[0].querySelector('[data-souvenir-pickup-item]').value = ''; return; }
        remove.closest('.souvenir-pickup-item-row').remove(); refreshPickupItemControls();
    });
    pickupItemList?.addEventListener('change', (event) => {
        if (event.target.matches('[data-souvenir-pickup-item]')) {
            const row = event.target.closest('.souvenir-pickup-item-row');
            row.dataset.currentQuantity = '0';
            refreshPickupUnit(row);
        }
    });
    document.querySelectorAll('[data-souvenir-pickup-view]').forEach((trigger) => trigger.addEventListener('click', () => {
        const id = trigger.dataset.souvenirPickupView; const record = pickupRecords[id]; if (!record) return;
        pickupSelectedId = id; lastTrigger = trigger;
        pickupDetailModal.querySelectorAll('[data-souvenir-pickup-detail]').forEach((element) => {
            const key = element.dataset.souvenirPickupDetail;
            element.textContent = key === 'tanggal_pengambilan' ? pickupDate(record[key]) : (record[key] || '—');
        });
        open(pickupDetailModal);
    }));
    document.querySelectorAll('[data-souvenir-pickup-edit]').forEach((trigger) => trigger.addEventListener('click', () => {
        const id = trigger.dataset.souvenirPickupEdit; if (!pickupRecords[id]) return;
        lastTrigger = trigger; openPickupForm(id, pickupRecords[id]);
    }));
    document.querySelectorAll('[data-souvenir-pickup-delete]').forEach((trigger) => trigger.addEventListener('click', () => {
        const id = trigger.dataset.souvenirPickupDelete;
        if (!id || !pickupDeleteForm) return;
        lastTrigger = trigger;
        if (pickupDeleteLabel) pickupDeleteLabel.textContent = trigger.dataset.souvenirPickupDeleteLabel || 'ini';
        pickupDeleteForm.action = pickupBaseUrl + '/' + encodeURIComponent(id) + '/hapus';
        open(pickupDeleteModal);
    }));
    document.getElementById('souvenirPickupDetailEdit')?.addEventListener('click', () => {
        if (!pickupRecords[pickupSelectedId]) return; close(pickupDetailModal, false); openPickupForm(pickupSelectedId, pickupRecords[pickupSelectedId]);
    });
    document.querySelector('[data-souvenir-return-open]')?.addEventListener('click', (event) => {
        const menu = event.currentTarget.closest('details'); lastTrigger = menu?.querySelector('summary') || event.currentTarget; menu?.removeAttribute('open'); open(returnModal);
    });
    document.querySelector('[data-souvenir-return-create]')?.addEventListener('click', (event) => {
        lastTrigger = event.currentTarget; openReturnForm();
    });
    document.querySelector('[data-souvenir-return-add-item]')?.addEventListener('click', () => addReturnItem());
    returnItemList?.addEventListener('click', (event) => {
        const remove = event.target.closest('[data-souvenir-return-remove-item]'); if (!remove) return;
        const rows = returnItemList.querySelectorAll('.souvenir-return-item-row');
        if (rows.length === 1) { rows[0].querySelector('[data-souvenir-return-item]').value = ''; refreshReturnUnit(rows[0]); return; }
        remove.closest('.souvenir-return-item-row').remove(); refreshReturnItemControls();
    });
    returnItemList?.addEventListener('change', (event) => {
        if (event.target.matches('[data-souvenir-return-item]')) refreshReturnUnit(event.target.closest('.souvenir-return-item-row'));
    });
    document.querySelectorAll('[data-souvenir-return-view]').forEach((trigger) => trigger.addEventListener('click', () => {
        const id = trigger.dataset.souvenirReturnView; const record = returnRecords[id]; if (!record) return;
        returnSelectedId = id; lastTrigger = trigger;
        returnDetailModal.querySelectorAll('[data-souvenir-return-detail]').forEach((element) => {
            const key = element.dataset.souvenirReturnDetail;
            element.textContent = key === 'tanggal_pengembalian' ? pickupDate(record[key]) : (record[key] || '—');
        });
        open(returnDetailModal);
    }));
    document.querySelectorAll('[data-souvenir-return-edit]').forEach((trigger) => trigger.addEventListener('click', () => {
        const id = trigger.dataset.souvenirReturnEdit; if (!returnRecords[id]) return;
        lastTrigger = trigger; openReturnForm(id, returnRecords[id]);
    }));
    document.querySelectorAll('[data-souvenir-return-delete]').forEach((trigger) => trigger.addEventListener('click', () => {
        const id = trigger.dataset.souvenirReturnDelete; if (!id || !returnDeleteForm) return;
        lastTrigger = trigger;
        if (returnDeleteLabel) returnDeleteLabel.textContent = trigger.dataset.souvenirReturnDeleteLabel || 'ini';
        returnDeleteForm.action = returnBaseUrl + '/' + encodeURIComponent(id) + '/hapus'; open(returnDeleteModal);
    }));
    document.getElementById('souvenirReturnDetailEdit')?.addEventListener('click', () => {
        if (!returnRecords[returnSelectedId]) return; close(returnDetailModal, false); openReturnForm(returnSelectedId, returnRecords[returnSelectedId]);
    });
    document.querySelectorAll('[data-souvenir-edit]').forEach((trigger) => trigger.addEventListener('click', () => {
        const id = trigger.dataset.souvenirEdit; if (!records[id]) return;
        lastTrigger = trigger; openForm(id, records[id]);
    }));
    document.querySelectorAll('[data-souvenir-view]').forEach((trigger) => trigger.addEventListener('click', () => {
        const id = trigger.dataset.souvenirView; const record = records[id]; if (!record) return;
        selectedId = id; lastTrigger = trigger;
        detailModal.querySelectorAll('[data-souvenir-detail]').forEach((element) => {
            const key = element.dataset.souvenirDetail; const value = record[key];
            element.textContent = key === 'keterangan' ? (value || '—') : (value === null || value === undefined ? '—' : (key === 'nama_item' || key === 'jenis' ? value : (key === 'satuan' ? (String(value).charAt(0).toUpperCase() + String(value).slice(1)) : number.format(Number(value)))));
        });
        open(detailModal);
    }));
    document.getElementById('souvenirDetailEdit').addEventListener('click', () => {
        if (!records[selectedId]) return; close(detailModal, false); openForm(selectedId, records[selectedId]);
    });
    document.querySelectorAll('[data-souvenir-close]').forEach((button) => button.addEventListener('click', () => close(button.closest('.input-modal'))));
    document.querySelectorAll('[data-souvenir-pickup-close]').forEach((button) => button.addEventListener('click', () => close(button.closest('.input-modal'))));
    document.querySelectorAll('[data-souvenir-pickup-child-close]').forEach((button) => button.addEventListener('click', () => close(button.closest('.input-modal'), false)));
    document.querySelectorAll('[data-souvenir-pickup-delete-close]').forEach((button) => button.addEventListener('click', () => close(pickupDeleteModal)));
    document.querySelectorAll('[data-souvenir-return-close]').forEach((button) => button.addEventListener('click', () => close(button.closest('.input-modal'))));
    document.querySelectorAll('[data-souvenir-return-child-close]').forEach((button) => button.addEventListener('click', () => close(button.closest('.input-modal'), false)));
    document.querySelectorAll('[data-souvenir-return-delete-close]').forEach((button) => button.addEventListener('click', () => close(returnDeleteModal)));
    if (popupState?.values) openForm(popupState.id ?? null, popupState.values, popupState.errors || []);
    if (pickupState?.values) { open(pickupModal); openPickupForm(pickupState.id ?? null, pickupState.values, pickupState.errors || []); }
    else if (pickupListOpen) open(pickupModal);
    if (returnState?.values) { open(returnModal); openReturnForm(returnState.id ?? null, returnState.values, returnState.errors || []); }
    else if (returnListOpen) open(returnModal);
})();
</script>

<?= $this->endSection() ?>
