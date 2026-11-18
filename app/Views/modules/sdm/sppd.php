<?php

/** @var list<array<string, mixed>> $records */
/** @var list<string> $destinations */
/** @var array{keyword: string, tanggalSppd: string, tujuan: string, perPage: int} $filters */
/** @var \CodeIgniter\Pager\PagerInterface $pager */
$baseUrl = site_url('sdm/sppd');
$query = service('request')->getServer('QUERY_STRING');
$returnUrl = $baseUrl . ($query ? '?' . $query : '');
$popup = session()->getFlashdata('sppd_popup') ?: [];
$formatDate = static fn (?string $date): string => $date ? date('d/m/Y', strtotime($date)) : '—';
$modalRecords = [];
foreach ($records as $record) {
    $modalRecords[(string) $record['id']] = [
        'id' => (int) $record['id'],
        'nomor_sppd' => $record['nomor_sppd'],
        'tanggal_sppd' => $record['tanggal_sppd'],
        'nama' => $record['nama'],
        'jabatan' => $record['jabatan'],
        'tujuan' => $record['tujuan'],
        'keperluan' => $record['keperluan'],
        'tanggal_berangkat' => $record['tanggal_berangkat'],
        'tanggal_kembali' => $record['tanggal_kembali'],
        'total_hari' => (int) $record['total_hari'],
    ];
}
?>
<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<section class="panel filter-panel sppd-filter-panel">
    <form method="get" action="<?= $baseUrl ?>" class="sppd-filter-form">
        <div class="form-group search-group"><label for="sppdSearch">Cari SPPD</label><div class="input-with-icon"><span aria-hidden="true">⌕</span><input id="sppdSearch" type="search" name="q" value="<?= esc($filters['keyword'], 'attr') ?>" placeholder="SPPD, nama, jabatan, tujuan, atau keperluan"></div></div>
        <div class="form-group"><label for="sppdDateFilter">Tgl SPPD</label><input id="sppdDateFilter" type="date" name="tanggal_sppd" value="<?= esc($filters['tanggalSppd'], 'attr') ?>"></div>
        <div class="form-group"><label for="sppdDestinationFilter">Tujuan</label><select id="sppdDestinationFilter" name="tujuan"><option value="">Semua tujuan</option><?php foreach ($destinations as $destination): ?><option value="<?= esc($destination, 'attr') ?>" <?= $filters['tujuan'] === $destination ? 'selected' : '' ?>><?= esc($destination) ?></option><?php endforeach ?></select></div>
        <input type="hidden" name="per_page" value="<?= (int) $filters['perPage'] ?>">
        <div class="filter-actions"><button class="btn btn-secondary" type="submit">Filter</button><a href="<?= $baseUrl ?>" class="btn btn-ghost">Reset</a><button class="btn btn-primary" type="button" data-sppd-create>＋ Tambah SPPD</button></div>
    </form>
</section>

<section class="panel register-panel sppd-register-panel table-title-flat" aria-labelledby="sppdTableTitle">
    <header class="panel-header table-title-flat__header"><div><p>SDM &amp; TELLER</p><h2 id="sppdTableTitle">Data SPPD</h2></div><span><?= number_format($total, 0, ',', '.') ?> data</span></header>
    <div class="table-wrap table-title-flat__table"><table class="sppd-table"><thead><tr><th>No. SPPD</th><th>Tgl SPPD</th><th>Nama</th><th>Jabatan</th><th>Tujuan</th><th>Keperluan</th><th>Tgl Berangkat</th><th>Tgl Kembali</th><th>Total Hari</th><th>Aksi</th></tr></thead><tbody>
        <?php if ($records === []): ?>
            <tr><td colspan="10"><div class="empty-state"><span aria-hidden="true">▤</span><strong>Belum ada data SPPD</strong><p>Tambahkan SPPD untuk mulai mencatat perjalanan dinas.</p><button class="btn btn-primary btn-sm" type="button" data-sppd-create>Tambah SPPD</button></div></td></tr>
        <?php else: foreach ($records as $record): ?>
            <tr data-sppd-record="<?= esc(json_encode($modalRecords[(string) $record['id']], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT), 'attr') ?>">
                <td><strong><?= esc($record['nomor_sppd']) ?></strong></td><td><?= $formatDate($record['tanggal_sppd']) ?></td><td><?= esc($record['nama']) ?></td><td><?= esc($record['jabatan']) ?></td><td><?= esc($record['tujuan']) ?></td><td class="sppd-purpose-cell"><?= esc($record['keperluan']) ?></td><td><?= $formatDate($record['tanggal_berangkat']) ?></td><td><?= $formatDate($record['tanggal_kembali']) ?></td><td><strong><?= number_format((int) $record['total_hari'], 0, ',', '.') ?> hari</strong></td>
                <td><div class="table-actions"><button class="icon-btn" type="button" data-sppd-view title="Lihat SPPD" aria-label="Lihat SPPD <?= esc($record['nomor_sppd'], 'attr') ?>"><?= view('components/table_action_icon', ['icon' => 'view']) ?></button><button class="icon-btn" type="button" data-sppd-edit title="Ubah SPPD" aria-label="Ubah SPPD <?= esc($record['nomor_sppd'], 'attr') ?>"><?= view('components/table_action_icon', ['icon' => 'edit']) ?></button><button class="icon-btn icon-btn-delete" type="button" data-sppd-delete title="Hapus SPPD" aria-label="Hapus SPPD <?= esc($record['nomor_sppd'], 'attr') ?>"><?= view('components/table_action_icon', ['icon' => 'delete']) ?></button></div></td>
            </tr>
        <?php endforeach; endif ?>
    </tbody></table></div>
    <div class="table-list-footer"><form method="get" action="<?= $baseUrl ?>" class="table-length-form"><input type="hidden" name="q" value="<?= esc($filters['keyword'], 'attr') ?>"><input type="hidden" name="tanggal_sppd" value="<?= esc($filters['tanggalSppd'], 'attr') ?>"><input type="hidden" name="tujuan" value="<?= esc($filters['tujuan'], 'attr') ?>"><label for="sppdPerPage">Tampilkan</label><select id="sppdPerPage" name="per_page" onchange="this.form.submit()"><?php foreach ([10, 20, 50, 100] as $size): ?><option value="<?= $size ?>" <?= $filters['perPage'] === $size ? 'selected' : '' ?>><?= $size ?></option><?php endforeach ?></select><span>data</span></form><?php if ($records !== []): ?><div class="pagination-wrap"><?= $pager->links('sppd', 'table_full') ?></div><?php endif ?></div>
</section>

<div class="input-modal" id="sppdFormModal" hidden aria-hidden="true"><button type="button" class="modal-backdrop" data-sppd-form-close aria-label="Tutup formulir SPPD"></button><section class="modal-dialog sppd-form-dialog" role="dialog" aria-modal="true" aria-labelledby="sppdFormTitle"><header class="modal-header"><div class="modal-title-group"><span class="modal-title-icon" data-sppd-form-icon>＋</span><div><p>SDM &amp; TELLER</p><h2 id="sppdFormTitle" data-sppd-form-title>Tambah SPPD</h2></div></div><button class="modal-close" type="button" data-sppd-form-close aria-label="Tutup formulir">×</button></header><form method="post" action="<?= $baseUrl ?>" data-sppd-form><?= csrf_field() ?><input type="hidden" name="return_to" value="<?= esc($returnUrl, 'attr') ?>"><div class="modal-body"><div class="modal-alert" data-sppd-form-errors hidden></div><div class="modal-form-grid"><div class="form-group"><label for="sppdNumber">SPPD <span class="required">*</span></label><input id="sppdNumber" name="nomor_sppd" maxlength="100" required placeholder="Contoh: 001/SPPD/KW.6/2026"></div><div class="form-group"><label for="sppdDate">Tgl SPPD <span class="required">*</span></label><input id="sppdDate" type="date" name="tanggal_sppd" required value="<?= date('Y-m-d') ?>"></div><div class="form-group"><label for="sppdName">Nama <span class="required">*</span></label><input id="sppdName" name="nama" maxlength="150" required placeholder="Nama pegawai"></div><div class="form-group"><label for="sppdPosition">Jabatan <span class="required">*</span></label><input id="sppdPosition" name="jabatan" maxlength="150" required placeholder="Jabatan pegawai"></div><div class="form-group"><label for="sppdDestination">Tujuan <span class="required">*</span></label><input id="sppdDestination" name="tujuan" maxlength="200" required placeholder="Kota atau lokasi tujuan"></div><div class="form-group"><label for="sppdPurpose">Keperluan <span class="required">*</span></label><input id="sppdPurpose" name="keperluan" maxlength="500" required placeholder="Keperluan perjalanan dinas"></div><div class="form-group"><label for="sppdDeparture">Tgl Berangkat <span class="required">*</span></label><input id="sppdDeparture" type="date" name="tanggal_berangkat" required></div><div class="form-group"><label for="sppdReturn">Tgl Kembali <span class="required">*</span></label><input id="sppdReturn" type="date" name="tanggal_kembali" required></div><div class="form-group modal-span-2 sppd-total-preview"><span>Total Hari</span><strong data-sppd-total>0 hari</strong><small>Dihitung otomatis dari tgl berangkat sampai tgl kembali.</small></div></div></div><footer class="modal-footer"><button class="btn btn-ghost" type="button" data-sppd-form-close>Batal</button><button class="btn btn-primary" type="submit" data-sppd-form-submit>Simpan SPPD</button></footer></form></section></div>

<div class="input-modal" id="sppdViewModal" hidden aria-hidden="true"><button type="button" class="modal-backdrop" data-sppd-view-close aria-label="Tutup rincian SPPD"></button><section class="modal-dialog sppd-view-dialog" role="dialog" aria-modal="true" aria-labelledby="sppdViewTitle"><header class="modal-header"><div class="modal-title-group"><span class="modal-title-icon">▤</span><div><p>SDM &amp; TELLER</p><h2 id="sppdViewTitle">Rincian SPPD</h2></div></div><button class="modal-close" type="button" data-sppd-view-close aria-label="Tutup rincian">×</button></header><div class="modal-body"><dl class="detail-list grid-two sppd-detail-list"><div><dt>SPPD</dt><dd data-sppd-view="nomor_sppd"></dd></div><div><dt>Tgl SPPD</dt><dd data-sppd-view="tanggal_sppd"></dd></div><div><dt>Nama</dt><dd data-sppd-view="nama"></dd></div><div><dt>Jabatan</dt><dd data-sppd-view="jabatan"></dd></div><div><dt>Tujuan</dt><dd data-sppd-view="tujuan"></dd></div><div><dt>Total Hari</dt><dd data-sppd-view="total_hari"></dd></div><div><dt>Tgl Berangkat</dt><dd data-sppd-view="tanggal_berangkat"></dd></div><div><dt>Tgl Kembali</dt><dd data-sppd-view="tanggal_kembali"></dd></div><div class="full-detail"><dt>Keperluan</dt><dd data-sppd-view="keperluan"></dd></div></dl></div><footer class="modal-footer"><button class="btn btn-secondary" type="button" data-sppd-view-close>Tutup</button></footer></section></div>

<div class="input-modal" id="sppdDeleteModal" hidden aria-hidden="true"><button type="button" class="modal-backdrop" data-sppd-delete-close aria-label="Batal hapus SPPD"></button><section class="modal-dialog delete-modal-dialog" role="alertdialog" aria-modal="true" aria-labelledby="sppdDeleteTitle"><div class="delete-modal-body"><span class="delete-warning-icon" aria-hidden="true">!</span><h2 id="sppdDeleteTitle">Hapus SPPD?</h2><p>Data <strong data-sppd-delete-label></strong> akan dihapus dari daftar.</p></div><form method="post" action="" data-sppd-delete-form class="delete-modal-actions"><?= csrf_field() ?><input type="hidden" name="return_to" value="<?= esc($returnUrl, 'attr') ?>"><button class="btn btn-ghost" type="button" data-sppd-delete-close>Batal</button><button class="btn btn-delete" type="submit">Ya, hapus</button></form></section></div>

<script>
(() => {
    const records = <?= json_encode($modalRecords, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    const popupState = <?= json_encode($popup, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    const baseUrl = <?= json_encode($baseUrl) ?>;
    const formModal = document.getElementById('sppdFormModal');
    const viewModal = document.getElementById('sppdViewModal');
    const deleteModal = document.getElementById('sppdDeleteModal');
    const form = formModal?.querySelector('[data-sppd-form]');
    const errors = formModal?.querySelector('[data-sppd-form-errors]');
    const total = formModal?.querySelector('[data-sppd-total]');
    const dateLabel = (value) => value ? new Date(`${value}T00:00:00`).toLocaleDateString('id-ID') : '—';
    const open = (modal) => { if (!modal) return; modal.hidden = false; modal.setAttribute('aria-hidden', 'false'); requestAnimationFrame(() => modal.classList.add('open')); };
    const close = (modal) => { if (!modal) return; modal.classList.remove('open'); modal.setAttribute('aria-hidden', 'true'); window.setTimeout(() => { modal.hidden = true; }, 180); };
    const showErrors = (messages = []) => { if (!errors) return; errors.replaceChildren(); errors.hidden = messages.length === 0; if (!messages.length) return; const list = document.createElement('ul'); messages.forEach((message) => { const item = document.createElement('li'); item.textContent = message; list.append(item); }); errors.append(list); };
    const totalDays = () => { const start = form?.elements.namedItem('tanggal_berangkat')?.value; const end = form?.elements.namedItem('tanggal_kembali')?.value; if (!start || !end || end < start) { if (total) total.textContent = '0 hari'; return; } const days = Math.round((new Date(`${end}T00:00:00`) - new Date(`${start}T00:00:00`)) / 86400000) + 1; if (total) total.textContent = `${days} hari`; };
    const openForm = (record = null, messages = [], values = null) => { const isEditing = Number.isInteger(Number(record?.id)) && Number(record.id) > 0; const fieldValues = values || record || { tanggal_sppd: new Date().toISOString().slice(0, 10) }; form?.reset(); form.action = isEditing ? `${baseUrl}/${record.id}` : baseUrl; formModal.querySelector('[data-sppd-form-title]').textContent = isEditing ? 'Ubah SPPD' : 'Tambah SPPD'; formModal.querySelector('[data-sppd-form-icon]').textContent = isEditing ? '✎' : '＋'; formModal.querySelector('[data-sppd-form-submit]').textContent = isEditing ? 'Simpan Perubahan' : 'Simpan SPPD'; Object.entries(fieldValues).forEach(([name, value]) => { const field = form?.elements.namedItem(name); if (field) field.value = value ?? ''; }); showErrors(messages); totalDays(); open(formModal); };
    const recordFor = (button) => { try { return JSON.parse(button.closest('[data-sppd-record]')?.dataset.sppdRecord || 'null'); } catch { return null; } };
    document.addEventListener('click', (event) => { const create = event.target.closest('[data-sppd-create]'); if (create) openForm(); const view = event.target.closest('[data-sppd-view]'); if (view) { const record = recordFor(view); if (!record) return; viewModal.querySelectorAll('[data-sppd-view]').forEach((field) => { const key = field.dataset.sppdView; const value = record[key]; field.textContent = ['tanggal_sppd', 'tanggal_berangkat', 'tanggal_kembali'].includes(key) ? dateLabel(value) : (key === 'total_hari' ? `${value || 0} hari` : (value || '—')); }); open(viewModal); } const edit = event.target.closest('[data-sppd-edit]'); if (edit) { const record = recordFor(edit); if (record) openForm(record); } const remove = event.target.closest('[data-sppd-delete]'); if (remove) { const record = recordFor(remove); if (!record) return; deleteModal.querySelector('[data-sppd-delete-label]').textContent = record.nomor_sppd; deleteModal.querySelector('[data-sppd-delete-form]').action = `${baseUrl}/${record.id}/hapus`; open(deleteModal); } });
    form?.addEventListener('change', (event) => { if (['tanggal_berangkat', 'tanggal_kembali'].includes(event.target.name)) totalDays(); });
    document.querySelectorAll('[data-sppd-form-close]').forEach((button) => button.addEventListener('click', () => close(formModal))); document.querySelectorAll('[data-sppd-view-close]').forEach((button) => button.addEventListener('click', () => close(viewModal))); document.querySelectorAll('[data-sppd-delete-close]').forEach((button) => button.addEventListener('click', () => close(deleteModal)));
    const popupValues = popupState && !Array.isArray(popupState) && popupState.values && typeof popupState.values === 'object' && !Array.isArray(popupState.values) ? popupState.values : null;
    const popupErrors = popupState && !Array.isArray(popupState) && Array.isArray(popupState.errors) ? popupState.errors : [];
    if ((popupValues && Object.keys(popupValues).length > 0) || popupErrors.length > 0) {
        const popupRecord = Number.isInteger(Number(popupState.id)) && Number(popupState.id) > 0 ? { ...popupValues, id: popupState.id } : null;
        openForm(popupRecord, popupErrors, popupValues);
    }
})();
</script>
<?= $this->endSection() ?>
