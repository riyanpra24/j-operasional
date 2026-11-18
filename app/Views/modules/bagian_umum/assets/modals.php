<?php
$baseUrl = site_url('bagian-umum-1/data-aset');
$popupRecords = [];
foreach ($groups as $records) {
    foreach ($records as $record) {
        $popupRecords[(int) $record['id']] = $record;
    }
}
$fields = [
    'kategori' => 'Kategori aset', 'kelompok_aset' => 'Kelompok aset', 'kodefikasi_aset' => 'Kodefikasi aset',
    'nomor_aset' => 'Nomor aset', 'nama_aset' => 'Nama aset', 'status_aset' => 'Status aset',
    'tanggal_perolehan' => 'Tanggal perolehan', 'tanggal_habis_manfaat' => 'Tanggal habis masa manfaat',
    'masa_manfaat' => 'Masa manfaat (bulan)', 'sisa_masa_manfaat' => 'Sisa masa manfaat (bulan)', 'lokasi' => 'Lokasi', 'foto_aset' => 'Foto Aset',
];
$jsonFlags = JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_INVALID_UTF8_SUBSTITUTE;
?>
<style>
    .asset-modal-dialog { width: min(760px, 100%); }
    .asset-modal-dialog > form { display: flex; flex: 1; flex-direction: column; min-height: 0; }
    .asset-modal-dialog .modal-header { background: #f2f9ff; }
    .asset-modal-dialog .modal-title-icon { color: #fff; background: linear-gradient(135deg, #0756c9, #2cbce8); }
    .asset-modal-dialog .modal-body { min-height: 0; }
    .asset-modal-dialog .modal-footer { flex-shrink: 0; }
    .asset-modal-dialog .detail-list dt { color: #647e98; }
    .asset-modal-dialog .detail-list dd { color: #27435f; overflow-wrap: anywhere; }
    [data-asset-period-form] input[readonly] { background: #e9edf1 !important; border-color: #cbd3dc !important; color: #35465c !important; cursor: default; }
</style>

<?php if ($isAdmin ?? false): ?>
<div class="input-modal" id="assetImportModal" hidden aria-hidden="true">
    <div class="modal-backdrop" aria-hidden="true"></div>
    <section class="modal-dialog asset-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="assetImportTitle">
        <header class="modal-header"><div class="modal-title-group"><span class="modal-title-icon" aria-hidden="true">↑</span><div><p>BAGIAN UMUM 1 / DATA ASET</p><h2 id="assetImportTitle">Upload Excel Aset</h2></div></div><button type="button" class="modal-close" data-asset-close aria-label="Tutup">×</button></header>
        <form method="post" action="<?= esc($baseUrl . '/impor', 'attr') ?>" enctype="multipart/form-data">
            <?= csrf_field() ?>
            <div class="modal-body">
                <div class="form-group"><label for="assetExcel">File Excel (.xlsx, maksimal 5 MB)</label><input id="assetExcel" name="asset_excel" type="file" accept=".xlsx,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" required></div>
                <p>Gunakan format Template Aset. Baris jumlah tidak diimpor. Nomor aset yang sudah ada akan dilewati dan tidak diubah.</p>
            </div>
            <footer class="modal-footer"><button type="button" class="btn btn-ghost" data-asset-close>Batal</button><button type="submit" class="btn btn-primary">Upload</button></footer>
        </form>
    </section>
</div>
<?php endif ?>

<div class="input-modal" id="assetFormModal" hidden aria-hidden="true">
    <div class="modal-backdrop" aria-hidden="true"></div>
    <section class="modal-dialog asset-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="assetFormTitle">
        <header class="modal-header">
            <div class="modal-title-group"><span class="modal-title-icon" aria-hidden="true">✎</span><div><p>BAGIAN UMUM 1 / DATA ASET</p><h2 id="assetFormTitle">Tambah Aset</h2></div></div>
            <button type="button" class="modal-close" data-asset-close aria-label="Tutup formulir">×</button>
        </header>
        <form method="post" action="<?= esc($baseUrl, 'attr') ?>" id="assetModalForm" data-asset-period-form>
            <?= csrf_field() ?><input type="hidden" name="asset_modal" value="1">
            <div class="modal-body">
                <div class="modal-alert" id="assetFormErrors" role="alert" hidden></div>
                <div class="modal-form-grid">
                    <div class="form-group"><label for="assetKategori">Kategori aset *</label><select id="assetKategori" name="kategori" required><option value="">Pilih kategori</option><?php foreach ($assetGroups as $group): ?><option value="<?= esc($group, 'attr') ?>"><?= esc($group) ?></option><?php endforeach ?></select></div>
                    <div class="form-group"><label for="assetKelompok">Kelompok aset *</label><input id="assetKelompok" name="kelompok_aset" maxlength="255" required></div>
                    <div class="form-group"><label for="assetKode">Kodefikasi aset *</label><input id="assetKode" name="kodefikasi_aset" maxlength="100" required></div>
                    <div class="form-group"><label for="assetNomor">Nomor aset *</label><input id="assetNomor" name="nomor_aset" maxlength="100" required></div>
                    <div class="form-group"><label for="assetNama">Nama aset *</label><input id="assetNama" name="nama_aset" maxlength="150" required></div>
                    <div class="form-group"><label for="assetStatusField">Status aset *</label><input id="assetStatusField" name="status_aset" maxlength="100" list="assetModalStatuses" required><datalist id="assetModalStatuses"><option value="Sedang Digunakan"><option value="Berfungsi"><option value="Sedang Dibangun/Dikembangkan"></datalist></div>
                    <div class="form-group"><label for="assetTanggalPerolehan">Tanggal perolehan</label><input id="assetTanggalPerolehan" name="tanggal_perolehan" type="date"></div>
                    <div class="form-group"><label for="assetTanggalHabis">Tanggal habis masa manfaat</label><input id="assetTanggalHabis" name="tanggal_habis_manfaat" type="date"></div>
                    <div class="form-group"><label for="assetMasa">Masa manfaat (bulan)</label><input id="assetMasa" name="masa_manfaat" type="number" title="Dihitung otomatis" readonly></div>
                    <div class="form-group"><label for="assetSisa">Sisa masa manfaat (bulan)</label><input id="assetSisa" name="sisa_masa_manfaat" type="number" title="Dihitung otomatis" readonly></div>
                    <div class="form-group"><label for="assetLokasi">Lokasi</label><input id="assetLokasi" name="lokasi" maxlength="255"></div>
                    <div class="form-group"><label for="assetFoto">Foto Aset (tautan)</label><input id="assetFoto" name="foto_aset" type="url" maxlength="2048" placeholder="https://..."></div>
                </div>
            </div>
            <footer class="modal-footer"><button type="button" class="btn btn-ghost" data-asset-close>Batal</button><button type="submit" class="btn btn-primary">Simpan</button></footer>
        </form>
    </section>
</div>

<div class="input-modal" id="assetDetailModal" hidden aria-hidden="true">
    <div class="modal-backdrop" aria-hidden="true"></div>
    <section class="modal-dialog asset-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="assetDetailTitle">
        <header class="modal-header"><div class="modal-title-group"><span class="modal-title-icon" aria-hidden="true">▤</span><div><p>BAGIAN UMUM 1 / DATA ASET</p><h2 id="assetDetailTitle">Detail Aset</h2></div></div><button type="button" class="modal-close" data-asset-close aria-label="Tutup detail">×</button></header>
        <div class="modal-body"><dl class="detail-list grid-two"><?php foreach ($fields as $key => $label): ?><div><dt><?= esc($label) ?></dt><dd data-asset-detail="<?= esc($key, 'attr') ?>">—</dd></div><?php endforeach ?></dl></div>
        <footer class="modal-footer">
            <form id="assetDeleteForm" method="post" onsubmit="return confirm('Hapus aset ini? <?= (string) session()->get('auth_role') === 'admin' ? 'Data akan dihapus permanen.' : 'Data dapat dipulihkan oleh administrator.' ?>');"><?= csrf_field() ?><button type="submit" class="btn btn-danger">Hapus</button></form>
            <button type="button" class="btn btn-ghost" data-asset-close>Tutup</button><button type="button" class="btn btn-primary" id="assetDetailEdit">Ubah</button>
        </footer>
    </section>
</div>

<script src="<?= base_url('assets/data-aset-period.js') ?>" data-system-time="<?= (int) round(microtime(true) * 1000) ?>"></script>
<script>
(() => {
    const records = <?= json_encode($popupRecords, $jsonFlags) ?>;
    const popupState = <?= json_encode($popupState, $jsonFlags) ?>;
    const baseUrl = <?= json_encode($baseUrl, $jsonFlags) ?>;
    const formModal = document.getElementById('assetFormModal');
    const detailModal = document.getElementById('assetDetailModal');
    const form = document.getElementById('assetModalForm');
    const errorsBox = document.getElementById('assetFormErrors');
    let lastTrigger = null;
    let selectedId = null;
    const close = (modal, restoreFocus = true) => {
        modal.classList.remove('open'); modal.hidden = true; modal.setAttribute('aria-hidden', 'true');
        if (restoreFocus) lastTrigger?.focus();
    };
    const open = (modal) => {
        modal.hidden = false; modal.setAttribute('aria-hidden', 'false');
        requestAnimationFrame(() => modal.classList.add('open'));
        modal.querySelector('.modal-close')?.focus();
    };
    const openForm = (id, values = {}, errors = []) => {
        form.reset();
        form.action = id === null ? baseUrl : baseUrl + '/' + encodeURIComponent(id);
        document.getElementById('assetFormTitle').textContent = id === null ? 'Tambah Aset' : 'Ubah Aset';
        Object.entries(values).forEach(([name, value]) => {
            const field = form.elements.namedItem(name);
            if (field && name !== 'asset_modal' && name !== '<?= esc(csrf_token(), 'js') ?>') field.value = value ?? '';
        });
        window.AssetPeriod?.update(form);
        errorsBox.replaceChildren();
        errorsBox.hidden = errors.length === 0;
        if (errors.length) {
            const title = document.createElement('strong'); title.textContent = 'Periksa kembali data berikut:'; errorsBox.append(title);
            const list = document.createElement('ul'); errors.forEach((message) => { const item = document.createElement('li'); item.textContent = message; list.append(item); }); errorsBox.append(list);
        }
        open(formModal);
    };
    document.querySelector('[data-asset-create]')?.addEventListener('click', (event) => {
        event.preventDefault(); const menu = event.currentTarget.closest('details'); lastTrigger = menu?.querySelector('summary') || event.currentTarget; menu?.removeAttribute('open'); openForm(null);
    });
    document.querySelector('[data-asset-import]')?.addEventListener('click', (event) => {
        const menu = event.currentTarget.closest('details'); lastTrigger = menu?.querySelector('summary') || event.currentTarget; menu?.removeAttribute('open'); open(document.getElementById('assetImportModal'));
    });
    document.querySelectorAll('[data-asset-edit]').forEach((trigger) => trigger.addEventListener('click', (event) => {
        const id = event.currentTarget.dataset.assetEdit; if (!records[id]) return;
        event.preventDefault(); lastTrigger = event.currentTarget; openForm(id, records[id]);
    }));
    document.querySelectorAll('[data-asset-view]').forEach((trigger) => trigger.addEventListener('click', (event) => {
        const id = event.currentTarget.dataset.assetView; const record = records[id]; if (!record) return;
        event.preventDefault(); selectedId = id; lastTrigger = event.currentTarget;
        detailModal.querySelectorAll('[data-asset-detail]').forEach((element) => {
            const key = element.dataset.assetDetail; const value = record[key];
            element.removeAttribute('data-asset-remaining');
            if (key === 'foto_aset' && value) {
                try {
                    const url = new URL(value);
                    if (url.protocol !== 'http:' && url.protocol !== 'https:') throw new Error('Invalid URL');
                    const link = document.createElement('a'); link.href = url.href; link.target = '_blank'; link.rel = 'noopener noreferrer'; link.textContent = 'Lihat foto'; link.className = 'text-link'; element.replaceChildren(link);
                } catch (_) { element.textContent = '—'; }
            } else if (key === 'sisa_masa_manfaat' && record.tanggal_habis_manfaat && window.AssetPeriod) {
                element.dataset.assetRemaining = record.tanggal_habis_manfaat; element.dataset.assetRemainingFormat = 'number';
                element.textContent = String(window.AssetPeriod.remaining(record.tanggal_habis_manfaat) ?? '—');
            } else {
                element.textContent = value === null || value === '' || value === undefined ? '—' : (key.startsWith('tanggal_') && /^\d{4}-\d{2}-\d{2}$/.test(value) ? value.split('-').reverse().join('-') : String(value));
            }
        });
        document.getElementById('assetDeleteForm').action = baseUrl + '/' + encodeURIComponent(id) + '/hapus'; open(detailModal);
    }));
    document.getElementById('assetDetailEdit').addEventListener('click', () => { if (!records[selectedId]) return; close(detailModal, false); openForm(selectedId, records[selectedId]); });
    document.querySelectorAll('[data-asset-close]').forEach((button) => button.addEventListener('click', () => close(button.closest('.input-modal'))));
    if (popupState && popupState.values) openForm(popupState.id ?? null, popupState.values, popupState.errors || []);
})();
</script>
