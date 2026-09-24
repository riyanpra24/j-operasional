<?php
/** @var list<array<string, mixed>> $records */
/** @var list<string> $units */
/** @var list<string> $types */
/** @var array<string, mixed> $filters */
/** @var int $nextContractSequence */
/** @var int $total */
/** @var \CodeIgniter\Pager\PagerInterface $pager */
$baseUrl = site_url('sdm/data-magang');
$query = service('request')->getServer('QUERY_STRING');
$returnUrl = $baseUrl . ($query ? '?' . $query : '');
$modalRecords = [];
foreach ($records as $record) {
    $modalRecords[(string) $record['id']] = [
        'id' => (int) $record['id'], 'nomor' => $record['nomor'], 'nama_magang' => $record['nama_magang'],
        'nomor_kontrak_kerja' => $record['nomor_kontrak_kerja'], 'unit_kerja' => $record['unit_kerja'],
        'jenis_magang' => $record['jenis_magang'], 'tanggal_mulai' => $record['tanggal_mulai'],
        'tanggal_selesai' => $record['tanggal_selesai'], 'link_pkk' => $record['link_pkk'], 'keterangan' => $record['keterangan'],
    ];
}
$formatDate = static fn (?string $value): string => $value ? date('d-m-Y', strtotime($value)) : 'Belum diisi';
?>
<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<section class="page-heading heading-actions magang-page-heading">
    <div>
        <p class="eyebrow">SDM &amp; TELLER</p>
        <h1>Data Magang</h1>
        <p>Database peserta magang dan Perjanjian Kontrak Kerja Magang (PKKM).</p>
    </div>
    <div class="magang-heading-actions">
        <button class="btn btn-secondary" type="button" data-magang-import>⇧ Import Excel</button>
        <button class="btn btn-primary" type="button" data-magang-create>＋ Tambah Peserta</button>
    </div>
</section>

<?php if (session()->getFlashdata('errors')): ?>
    <section class="modal-alert magang-page-error" role="alert"><strong>Data belum dapat disimpan.</strong><ul><?php foreach (session()->getFlashdata('errors') as $error): ?><li><?= esc($error) ?></li><?php endforeach ?></ul></section>
<?php endif ?>

<section class="panel filter-panel magang-filter-panel">
    <form action="<?= $baseUrl ?>" method="get" class="magang-filter-form">
        <div class="form-group search-group"><label for="magangSearch">Cari peserta</label><div class="input-with-icon"><span>⌕</span><input id="magangSearch" name="q" type="search" value="<?= esc($filters['keyword']) ?>" placeholder="Nama, nomor kontrak, unit kerja, atau jenis magang"></div></div>
        <div class="form-group"><label for="magangUnit">Unit Kerja</label><select id="magangUnit" name="unit_kerja"><option value="">Semua unit kerja</option><?php foreach ($units as $option): ?><option value="<?= esc($option, 'attr') ?>" <?= $filters['unit'] === $option ? 'selected' : '' ?>><?= esc($option) ?></option><?php endforeach ?></select></div>
        <div class="form-group"><label for="magangType">Jenis Magang</label><select id="magangType" name="jenis"><option value="">Semua jenis</option><?php foreach ($types as $option): ?><option value="<?= esc($option, 'attr') ?>" <?= $filters['type'] === $option ? 'selected' : '' ?>><?= esc($option) ?></option><?php endforeach ?></select></div>
        <div class="form-group"><label for="magangStatus">Status</label><select id="magangStatus" name="status"><option value="">Semua status</option><?php foreach (['Aktif', 'Selesai', 'Belum Mulai', 'Belum Lengkap'] as $option): ?><option value="<?= esc($option, 'attr') ?>" <?= $filters['status'] === $option ? 'selected' : '' ?>><?= esc($option) ?></option><?php endforeach ?></select></div>
        <?= view('components/list_order_filter', ['id' => 'magangOrder', 'value' => $filters['order']]) ?>
        <input type="hidden" name="per_page" value="<?= (int) $filters['perPage'] ?>">
        <div class="filter-actions"><button class="btn btn-secondary" type="submit">Terapkan</button><a class="btn btn-ghost" href="<?= $baseUrl ?>">Reset</a></div>
    </form>
</section>

<section class="panel register-panel magang-register-panel">
    <header class="panel-header magang-register-header"><div><h2>Database Peserta Magang</h2><p>Status dihitung otomatis dari tanggal awal dan selesai magang.</p></div><span><?= number_format($total, 0, ',', '.') ?> peserta</span></header>
    <div class="table-wrap"><table class="magang-table"><thead><tr><th>No.</th><th>Peserta</th><th>Nomor Kontrak Kerja</th><th>Unit Kerja</th><th>Jenis Magang</th><th>Periode Magang</th><th>Status</th><th>PKK</th><th>Aksi</th></tr></thead><tbody>
        <?php if ($records === []): ?>
            <tr><td colspan="9"><div class="empty-state"><span>♙</span><strong>Belum ada data magang</strong><p>Tambahkan peserta secara manual atau impor Excel Database Magang.</p><button class="btn btn-primary btn-sm" type="button" data-magang-create>Tambah Peserta</button></div></td></tr>
        <?php else: foreach ($records as $index => $record): ?>
            <tr>
                <td><strong><?= (($pager->getCurrentPage('data_magang') - 1) * $filters['perPage']) + $index + 1 ?></strong><?php if ($record['nomor']): ?><small>Data <?= (int) $record['nomor'] ?></small><?php endif ?></td>
                <td><div class="magang-name"><strong><?= esc($record['nama_magang']) ?></strong><?php if ($record['keterangan']): ?><small><?= esc($record['keterangan']) ?></small><?php endif ?></div></td>
                <td><strong class="magang-contract"><?= esc($record['nomor_kontrak_kerja']) ?></strong></td>
                <td><?= esc($record['unit_kerja']) ?></td>
                <td><?= esc($record['jenis_magang']) ?></td>
                <td><div class="magang-period"><strong><?= $formatDate($record['tanggal_mulai']) ?></strong><span>s.d. <?= $formatDate($record['tanggal_selesai']) ?></span></div></td>
                <td><span class="magang-status <?= esc($record['status_class']) ?>"><?= esc($record['status']) ?></span></td>
                <td><?php if ($record['link_pkk']): ?><a class="btn btn-secondary btn-sm" href="<?= esc($record['link_pkk'], 'attr') ?>" target="_blank" rel="noopener noreferrer">↗ Buka PKK</a><?php else: ?><span class="magang-missing">Belum ada</span><?php endif ?></td>
                <td><div class="table-actions"><button class="icon-btn" type="button" data-magang-edit="<?= (int) $record['id'] ?>" title="Ubah data peserta">✎</button><button class="icon-btn icon-btn-delete" type="button" data-magang-delete="<?= (int) $record['id'] ?>" title="Hapus data peserta">×</button></div></td>
            </tr>
        <?php endforeach; endif ?>
    </tbody></table></div>
    <div class="table-list-footer"><form method="get" action="<?= $baseUrl ?>" class="table-length-form"><input type="hidden" name="q" value="<?= esc($filters['keyword']) ?>"><input type="hidden" name="unit_kerja" value="<?= esc($filters['unit']) ?>"><input type="hidden" name="jenis" value="<?= esc($filters['type']) ?>"><input type="hidden" name="status" value="<?= esc($filters['status']) ?>"><input type="hidden" name="urutan" value="<?= esc($filters['order']) ?>"><label for="magangPerPage">Tampilkan</label><select id="magangPerPage" name="per_page" onchange="this.form.submit()"><?php foreach ([10, 20, 50, 100] as $size): ?><option value="<?= $size ?>" <?= $filters['perPage'] === $size ? 'selected' : '' ?>><?= $size ?></option><?php endforeach ?></select><span>data</span></form><?php if ($records !== []): ?><div class="pagination-wrap"><?= $pager->links('data_magang', 'default_full') ?></div><?php endif ?></div>
</section>

<div class="input-modal" id="magangFormModal" hidden aria-hidden="true"><button type="button" class="modal-backdrop" data-magang-form-close aria-label="Tutup form"></button><section class="modal-dialog magang-form-dialog" role="dialog" aria-modal="true" aria-labelledby="magangFormTitle"><header class="modal-header"><div class="modal-title-group"><span class="modal-title-icon" data-magang-form-icon>＋</span><div><p>SDM &amp; TELLER</p><h2 id="magangFormTitle" data-magang-form-title>Tambah Peserta Magang</h2></div></div><button class="modal-close" type="button" data-magang-form-close aria-label="Tutup form">×</button></header><form method="post" action="<?= site_url('sdm/data-magang') ?>" data-magang-form><?= csrf_field() ?><input type="hidden" name="return_to" value="<?= esc($returnUrl, 'attr') ?>"><div class="modal-body magang-form-body"><div class="modal-form-grid"><div class="form-group"><label for="magangName">Nama Peserta <span class="required">*</span></label><input id="magangName" name="nama_magang" maxlength="200" required></div><div class="form-group"><label for="magangContract">Nomor Kontrak Kerja <span class="required">*</span></label><input id="magangContract" name="nomor_kontrak_kerja" maxlength="200" required readonly placeholder="Pilih Awal Magang untuk membuat nomor kontrak"><small class="magang-contract-hint">Nomor urut dibuat otomatis oleh sistem, lalu bulan dan tahun mengikuti Awal Magang.</small></div><div class="form-group"><label for="magangUnitInput">Unit Kerja <span class="required">*</span></label><select id="magangUnitInput" name="unit_kerja" required><option value="">Pilih unit kerja</option><?php foreach ($units as $option): ?><option value="<?= esc($option, 'attr') ?>"><?= esc($option) ?></option><?php endforeach ?></select></div><div class="form-group"><label for="magangTypeInput">Jenis Magang <span class="required">*</span></label><select id="magangTypeInput" name="jenis_magang" required><option value="">Pilih jenis magang</option><?php foreach ($types as $option): ?><option value="<?= esc($option, 'attr') ?>"><?= esc($option) ?></option><?php endforeach ?></select></div><div class="form-group"><label for="magangStart">Awal Magang <span class="required">*</span></label><input id="magangStart" name="tanggal_mulai" type="date" required></div><div class="form-group"><label for="magangEnd">Selesai Magang</label><input id="magangEnd" name="tanggal_selesai" type="date"></div><div class="form-group modal-span-2"><label for="magangLink">Link PKK</label><input id="magangLink" name="link_pkk" type="url" maxlength="2048" placeholder="https://..."></div><div class="form-group modal-span-2"><label for="magangNote">Keterangan</label><textarea id="magangNote" name="keterangan" maxlength="500" rows="3" placeholder="Contoh: PKWT"></textarea></div></div></div><footer class="modal-footer"><button class="btn btn-ghost" type="button" data-magang-form-close>Batal</button><button class="btn btn-primary" type="submit">Simpan Data</button></footer></form></section></div>

<div class="input-modal" id="magangImportModal" hidden aria-hidden="true"><button type="button" class="modal-backdrop" data-magang-import-close aria-label="Tutup impor"></button><section class="modal-dialog magang-import-dialog" role="dialog" aria-modal="true" aria-labelledby="magangImportTitle"><header class="modal-header"><div class="modal-title-group"><span class="modal-title-icon">⇧</span><div><p>SDM &amp; TELLER</p><h2 id="magangImportTitle">Import Database Magang</h2></div></div><button class="modal-close" type="button" data-magang-import-close aria-label="Tutup impor">×</button></header><form method="post" action="<?= site_url('sdm/data-magang/import') ?>" enctype="multipart/form-data"><?= csrf_field() ?><input type="hidden" name="return_to" value="<?= esc($returnUrl, 'attr') ?>"><div class="modal-body"><p class="magang-import-copy">Gunakan sheet bernama <strong>Database Magang</strong>. Data dengan nomor kontrak yang sama akan diperbarui; data lain akan ditambahkan.</p><div class="form-group"><label for="magangImportFile">Berkas Excel (.xlsx, maksimal 5 MB) <span class="required">*</span></label><input id="magangImportFile" name="magang_excel" type="file" accept=".xlsx,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" required></div></div><footer class="modal-footer"><button class="btn btn-ghost" type="button" data-magang-import-close>Batal</button><button class="btn btn-primary" type="submit">Import Data</button></footer></form></section></div>

<div class="input-modal" id="magangDeleteModal" hidden aria-hidden="true"><button type="button" class="modal-backdrop" data-magang-delete-close aria-label="Tutup konfirmasi"></button><section class="modal-dialog delete-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="magangDeleteTitle"><header class="modal-header"><div class="modal-title-group"><span class="modal-title-icon">×</span><div><p>SDM &amp; TELLER</p><h2 id="magangDeleteTitle">Hapus Data Magang</h2></div></div><button class="modal-close" type="button" data-magang-delete-close aria-label="Tutup konfirmasi">×</button></header><form method="post" action="" data-magang-delete-form><?= csrf_field() ?><input type="hidden" name="return_to" value="<?= esc($returnUrl, 'attr') ?>"><div class="modal-body"><p>Hapus data peserta <strong data-magang-delete-name></strong>?</p><p class="magang-import-copy">Data akan disembunyikan dari daftar dan dapat dipulihkan oleh Administrator melalui Data Terhapus.</p></div><footer class="modal-footer"><button class="btn btn-ghost" type="button" data-magang-delete-close>Batal</button><button class="btn btn-danger" type="submit">Hapus Data</button></footer></form></section></div>

<script>
(() => {
    const records = <?= json_encode($modalRecords, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    const nextContractSequence = <?= (int) $nextContractSequence ?>;
    const romanMonths = ['', 'I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X', 'XI', 'XII'];
    const formModal = document.getElementById('magangFormModal');
    const form = formModal?.querySelector('[data-magang-form]');
    const importModal = document.getElementById('magangImportModal');
    const deleteModal = document.getElementById('magangDeleteModal');
    const deleteForm = deleteModal?.querySelector('[data-magang-delete-form]');
    const open = (modal) => { if (!modal) return; modal.hidden = false; modal.setAttribute('aria-hidden', 'false'); requestAnimationFrame(() => modal.classList.add('open')); };
    const close = (modal) => { if (!modal) return; modal.classList.remove('open'); modal.setAttribute('aria-hidden', 'true'); window.setTimeout(() => { modal.hidden = true; }, 180); };
    const setField = (name, value) => { const field = form?.elements.namedItem(name); if (field) field.value = value ?? ''; };
    const contractField = form?.elements.namedItem('nomor_kontrak_kerja');
    const startField = form?.elements.namedItem('tanggal_mulai');
    const updateGeneratedContract = () => {
        if (!contractField || !startField || form?.dataset.mode !== 'create') return;
        const [year, month] = String(startField.value || '').split('-').map(Number);
        contractField.value = year && month ? `${String(nextContractSequence).padStart(3, '0')}/PKKM/KW.6/${romanMonths[month]}/${year}` : '';
    };
    startField?.addEventListener('change', updateGeneratedContract);
    document.querySelectorAll('[data-magang-create]').forEach((button) => button.addEventListener('click', () => { form.reset(); form.dataset.mode = 'create'; form.action = <?= json_encode(site_url('sdm/data-magang')) ?>; startField.required = true; contractField.readOnly = true; contractField.placeholder = 'Pilih Awal Magang untuk membuat nomor kontrak'; updateGeneratedContract(); formModal.querySelector('[data-magang-form-title]').textContent = 'Tambah Peserta Magang'; formModal.querySelector('[data-magang-form-icon]').textContent = '＋'; open(formModal); }));
    document.querySelectorAll('[data-magang-edit]').forEach((button) => button.addEventListener('click', () => { const record = records[button.dataset.magangEdit]; if (!record) return; form.reset(); form.dataset.mode = 'edit'; form.action = <?= json_encode(site_url('sdm/data-magang')) ?> + '/' + record.id; Object.entries(record).forEach(([name, value]) => setField(name, value)); startField.required = false; contractField.readOnly = true; contractField.placeholder = ''; formModal.querySelector('[data-magang-form-title]').textContent = 'Ubah Peserta Magang'; formModal.querySelector('[data-magang-form-icon]').textContent = '✎'; open(formModal); }));
    document.querySelectorAll('[data-magang-form-close]').forEach((button) => button.addEventListener('click', () => close(formModal)));
    document.querySelectorAll('[data-magang-import]').forEach((button) => button.addEventListener('click', () => open(importModal)));
    document.querySelectorAll('[data-magang-import-close]').forEach((button) => button.addEventListener('click', () => close(importModal)));
    document.querySelectorAll('[data-magang-delete]').forEach((button) => button.addEventListener('click', () => { const record = records[button.dataset.magangDelete]; if (!record) return; deleteForm.action = <?= json_encode(site_url('sdm/data-magang')) ?> + '/' + record.id + '/hapus'; deleteModal.querySelector('[data-magang-delete-name]').textContent = record.nama_magang; open(deleteModal); }));
    document.querySelectorAll('[data-magang-delete-close]').forEach((button) => button.addEventListener('click', () => close(deleteModal)));
    document.addEventListener('keydown', (event) => { if (event.key !== 'Escape') return; [formModal, importModal, deleteModal].forEach((modal) => { if (modal && !modal.hidden) close(modal); }); });
})();
</script>
<?= $this->endSection() ?>
