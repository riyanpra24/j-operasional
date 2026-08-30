<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<section class="page-heading heading-actions vehicle-page-heading">
    <div><p class="eyebrow">BAGIAN UMUM 2 · MONITORING KENDARAAN</p><h1>Servis dan Perawatan</h1><p>Catat pekerjaan bengkel, biaya, kilometer, dan jadwal servis berikutnya.</p></div>
    <?php if ($vehicles === []): ?><a class="btn btn-primary" href="<?= site_url('bagian-umum-2/monitoring-kendaraan/data-kendaraan') ?>">＋ Tambah Kendaraan Dahulu</a><?php else: ?><button type="button" class="btn btn-primary" data-vehicle-crud-create>＋ Tambah Servis</button><?php endif ?>
</section>

<?= view('monitoring_kendaraan/_tabs', compact('activePage')) ?>

<section class="panel filter-panel vehicle-filter-panel"><form method="get" class="vehicle-filter-form vehicle-filter-simple"><div class="form-group"><label for="maintenanceSearch">Cari servis</label><div class="input-with-icon"><span>⌕</span><input id="maintenanceSearch" name="q" value="<?= esc($filters['keyword']) ?>" placeholder="Nomor polisi, kendaraan, jenis perawatan, atau bengkel"></div></div><div class="filter-actions"><button class="btn btn-secondary" type="submit">Terapkan</button><a class="btn btn-ghost" href="<?= site_url('bagian-umum-2/monitoring-kendaraan/servis-perawatan') ?>">Reset</a></div></form></section>

<section class="panel register-panel vehicle-register-panel" data-vehicle-crud-root data-base-url="<?= site_url('bagian-umum-2/monitoring-kendaraan/servis-perawatan') ?>" data-create-title="Tambah Servis" data-edit-title="Edit Servis">
<div class="table-wrap"><table><thead><tr><th>No.</th><th>Kendaraan</th><th>Tanggal Servis</th><th>Jenis Perawatan</th><th>Bengkel</th><th>Kilometer</th><th>Biaya</th><th>Servis Berikutnya</th><th>Aksi</th></tr></thead><tbody>
<?php if ($records === []): ?><tr><td colspan="9"><div class="empty-state"><span>⚙</span><strong>Belum ada data servis</strong><p>Catatan servis dan perawatan kendaraan akan tampil di halaman ini.</p></div></td></tr>
<?php else: ?><?php foreach ($records as $index => $record): ?>
<?php $editData = array_intersect_key($record, array_flip(['id','vehicle_id','tanggal_servis','jenis_perawatan','bengkel','kilometer','biaya','servis_berikutnya_tanggal','servis_berikutnya_km','keterangan','link_berkas'])); ?>
<tr><td><strong><?= (($pager->getCurrentPage('maintenance') - 1) * $filters['perPage']) + $index + 1 ?></strong></td><td><div class="vehicle-name-cell"><strong><?= esc($record['nomor_polisi']) ?></strong><span><?= esc($record['nama_kendaraan']) ?></span></div></td><td><?= date('d-m-Y', strtotime($record['tanggal_servis'])) ?></td><td><strong><?= esc($record['jenis_perawatan']) ?></strong></td><td><?= esc($record['bengkel'] ?: '-') ?></td><td><?= $record['kilometer'] !== null ? number_format((int)$record['kilometer'],0,',','.') . ' km' : '-' ?></td><td><strong>Rp <?= number_format((float)$record['biaya'],0,',','.') ?></strong></td><td><div class="vehicle-name-cell"><strong><?= $record['servis_berikutnya_tanggal'] ? date('d-m-Y',strtotime($record['servis_berikutnya_tanggal'])) : '-' ?></strong><span><?= $record['servis_berikutnya_km'] ? number_format((int)$record['servis_berikutnya_km'],0,',','.') . ' km' : 'Kilometer belum ditentukan' ?></span></div></td><td><div class="action-buttons"><?php if ($record['link_berkas']): ?><a class="icon-btn" href="<?= esc($record['link_berkas']) ?>" target="_blank" rel="noopener noreferrer" title="Buka berkas">↗</a><?php endif ?><button type="button" class="icon-btn" data-vehicle-crud-edit='<?= esc(json_encode($editData), 'attr') ?>' title="Edit">✎</button><button type="button" class="icon-btn icon-btn-delete" data-vehicle-crud-delete='<?= esc(json_encode(['id'=>(int)$record['id'],'label'=>$record['nomor_polisi'].' · '.$record['jenis_perawatan']]), 'attr') ?>' title="Hapus">×</button></div></td></tr>
<?php endforeach ?><?php endif ?>
</tbody></table></div><div class="table-list-footer"><form method="get" class="table-length-form"><input type="hidden" name="q" value="<?= esc($filters['keyword']) ?>"><label for="maintenancePerPage">Tampilkan</label><select id="maintenancePerPage" name="per_page" onchange="this.form.submit()"><?php foreach ([10,20,50,100] as $option): ?><option value="<?= $option ?>" <?= $filters['perPage'] === $option ? 'selected' : '' ?>><?= $option ?></option><?php endforeach ?></select><span>data</span></form><div class="pagination-wrap"><?= $pager->links('maintenance') ?></div></div></section>

<div class="account-modal vehicle-crud-modal" id="vehicleCrudFormModal" hidden aria-hidden="true"><button type="button" class="modal-backdrop" data-vehicle-crud-form-close></button><section class="modal-dialog vehicle-crud-dialog" role="dialog" aria-modal="true"><header class="modal-header"><div class="modal-title-group"><span class="modal-title-icon" data-crud-form-icon>＋</span><div><p>MONITORING KENDARAAN</p><h2 data-crud-form-title>Tambah Servis</h2></div></div><button type="button" class="modal-close" data-vehicle-crud-form-close>×</button></header><form method="post" action="<?= site_url('bagian-umum-2/monitoring-kendaraan/servis-perawatan') ?>" data-vehicle-crud-form><?= csrf_field() ?><div class="modal-body"><div class="modal-form-grid vehicle-form-grid">
<div class="form-group modal-span-2"><label for="maintenanceVehicle">Kendaraan <span>*</span></label><select id="maintenanceVehicle" name="vehicle_id" required><option value="">Pilih kendaraan</option><?php foreach ($vehicles as $vehicle): ?><option value="<?= (int)$vehicle['id'] ?>"><?= esc($vehicle['nomor_polisi'].' · '.$vehicle['nama_kendaraan']) ?></option><?php endforeach ?></select></div>
<div class="form-group"><label for="maintenanceDate">Tanggal Servis <span>*</span></label><input id="maintenanceDate" name="tanggal_servis" type="date" value="<?= date('Y-m-d') ?>" required><small data-service-date-lock hidden>Tanggal yang sudah tersimpan hanya dapat diubah oleh Administrator.</small></div><div class="form-group"><label for="maintenanceType">Jenis Perawatan <span>*</span></label><input id="maintenanceType" name="jenis_perawatan" maxlength="150" placeholder="Servis berkala / ganti oli" required></div>
<div class="form-group"><label for="maintenanceWorkshop">Bengkel</label><input id="maintenanceWorkshop" name="bengkel" maxlength="150"></div><div class="form-group"><label for="maintenanceKm">Kilometer Saat Servis</label><input id="maintenanceKm" name="kilometer" type="number" min="0"></div>
<div class="form-group"><label for="maintenanceCost">Biaya <span>*</span></label><input id="maintenanceCost" name="biaya" type="number" min="0" step="1" value="0" required></div><div class="form-group"><label for="maintenanceNextDate">Servis Berikutnya</label><input id="maintenanceNextDate" name="servis_berikutnya_tanggal" type="date" readonly><small>Terisi otomatis 3 bulan setelah Tanggal Servis.</small></div>
<div class="form-group"><label for="maintenanceNextKm">KM Servis Berikutnya</label><input id="maintenanceNextKm" name="servis_berikutnya_km" type="number" min="0"></div><div class="form-group"><label for="maintenanceLink">Link Berkas</label><input id="maintenanceLink" name="link_berkas" type="url" maxlength="2048" placeholder="https://..."></div>
<div class="form-group modal-span-2"><label for="maintenanceNotes">Keterangan</label><textarea id="maintenanceNotes" name="keterangan" maxlength="5000" rows="3"></textarea></div>
</div></div><footer class="modal-footer"><button type="button" class="btn btn-ghost" data-vehicle-crud-form-close>Batal</button><button type="submit" class="btn btn-primary" data-crud-submit>Simpan data</button></footer></form></section></div>
<div class="account-modal vehicle-crud-modal" id="vehicleCrudDeleteModal" hidden aria-hidden="true"><button type="button" class="modal-backdrop" data-vehicle-crud-delete-close></button><section class="modal-dialog delete-modal-dialog" role="alertdialog" aria-modal="true"><div class="delete-modal-body"><span class="delete-warning-icon">!</span><h2>Hapus Data Servis?</h2><p><strong data-crud-delete-name></strong> akan dihapus dari riwayat servis.</p></div><form method="post" action="" data-crud-delete-form class="delete-modal-actions"><?= csrf_field() ?><button type="button" class="btn btn-ghost" data-vehicle-crud-delete-close>Batal</button><button type="submit" class="btn btn-delete">Ya, hapus</button></form></section></div>
<script>
(() => {
    const canEditExistingDate = <?= json_encode((string) session()->get('auth_role') === 'admin') ?>;
    const serviceDate = document.getElementById('maintenanceDate');
    const nextDate = document.getElementById('maintenanceNextDate');
    const lockHint = document.querySelector('[data-service-date-lock]');
    const calculateNextDate = value => {
        if (!value || !/^\d{4}-\d{2}-\d{2}$/.test(value)) return '';
        const [year, month, day] = value.split('-').map(Number);
        const targetMonthIndex = (month - 1) + 3;
        const targetYear = year + Math.floor(targetMonthIndex / 12);
        const targetMonth = targetMonthIndex % 12;
        const lastDay = new Date(Date.UTC(targetYear, targetMonth + 1, 0)).getUTCDate();
        return `${targetYear}-${String(targetMonth + 1).padStart(2, '0')}-${String(Math.min(day, lastDay)).padStart(2, '0')}`;
    };
    const synchronizeNextDate = () => { nextDate.value = calculateNextDate(serviceDate.value); };
    serviceDate.addEventListener('change', synchronizeNextDate);
    window.addEventListener('vehicle-crud:prepared', event => {
        const editing = event.detail.mode === 'edit';
        serviceDate.disabled = editing && !canEditExistingDate;
        serviceDate.title = serviceDate.disabled ? 'Tanggal Servis hanya dapat diubah oleh Administrator.' : '';
        lockHint.hidden = !serviceDate.disabled;
        synchronizeNextDate();
    });
})();
</script>
<?= view('monitoring_kendaraan/_crud_script') ?>
<?= $this->endSection() ?>
