<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<section class="page-heading heading-actions vehicle-page-heading">
    <div><p class="eyebrow">BAGIAN UMUM 2 · MONITORING KENDARAAN</p><h1>Data Kendaraan</h1><p>Kelola identitas, penanggung jawab, kilometer, dan status kendaraan operasional.</p></div>
    <button type="button" class="btn btn-primary" data-vehicle-crud-create>＋ Tambah Kendaraan</button>
</section>

<?= view('monitoring_kendaraan/_tabs', compact('activePage')) ?>

<section class="panel filter-panel vehicle-filter-panel">
    <form action="<?= site_url('bagian-umum-2/monitoring-kendaraan/data-kendaraan') ?>" method="get" class="vehicle-filter-form">
        <div class="form-group"><label for="vehicleSearch">Cari kendaraan</label><div class="input-with-icon"><span>⌕</span><input id="vehicleSearch" name="q" value="<?= esc($filters['keyword']) ?>" placeholder="Nomor polisi, kendaraan, merek, unit, atau PIC"></div></div>
        <div class="form-group"><label for="vehicleStatusFilter">Status</label><select id="vehicleStatusFilter" name="status"><option value="">Semua status</option><?php foreach ($statuses as $item): ?><option value="<?= esc($item) ?>" <?= $filters['status'] === $item ? 'selected' : '' ?>><?= esc($item) ?></option><?php endforeach ?></select></div>
        <div class="filter-actions"><button class="btn btn-secondary" type="submit">Terapkan</button><a class="btn btn-ghost" href="<?= site_url('bagian-umum-2/monitoring-kendaraan/data-kendaraan') ?>">Reset</a></div>
    </form>
</section>

<section class="panel register-panel vehicle-register-panel" data-vehicle-crud-root data-base-url="<?= site_url('bagian-umum-2/monitoring-kendaraan/data-kendaraan') ?>" data-create-title="Tambah Kendaraan" data-edit-title="Edit Kendaraan">
    <div class="table-wrap"><table><thead><tr><th>No.</th><th>Nomor Polisi</th><th>Kendaraan</th><th>Tahun</th><th>PIC / Unit</th><th>Kilometer</th><th>Status</th><th>Aksi</th></tr></thead><tbody>
    <?php if ($records === []): ?><tr><td colspan="8"><div class="empty-state"><span>▤</span><strong>Belum ada data kendaraan</strong><p>Tambahkan kendaraan pertama untuk mulai melakukan monitoring.</p></div></td></tr>
    <?php else: ?><?php foreach ($records as $index => $record): ?>
        <?php $editData = array_intersect_key($record, array_flip(['id','nomor_polisi','nama_kendaraan','jenis','merek','tipe','tahun','warna','nomor_rangka','nomor_mesin','unit_pengguna','pic','kilometer','status'])); ?>
        <tr>
            <td><strong><?= (($pager->getCurrentPage('vehicles') - 1) * $filters['perPage']) + $index + 1 ?></strong></td>
            <td><strong class="vehicle-plate"><?= esc($record['nomor_polisi']) ?></strong></td>
            <td><div class="vehicle-name-cell"><strong><?= esc($record['nama_kendaraan']) ?></strong><span><?= esc(trim(($record['merek'] ?? '') . ' ' . ($record['tipe'] ?? '')) ?: $record['jenis']) ?></span></div></td>
            <td><?= esc($record['tahun'] ?: '-') ?></td>
            <td><div class="vehicle-name-cell"><strong><?= esc($record['pic'] ?: '-') ?></strong><span><?= esc($record['unit_pengguna'] ?: 'Unit belum diisi') ?></span></div></td>
            <td><strong><?= number_format((int) $record['kilometer'], 0, ',', '.') ?> km</strong></td>
            <td><span class="vehicle-status <?= esc(strtolower(str_replace(' ', '-', $record['status']))) ?>"><?= esc($record['status']) ?></span></td>
            <td><div class="action-buttons"><button type="button" class="icon-btn" data-vehicle-crud-edit='<?= esc(json_encode($editData), 'attr') ?>' title="Edit">✎</button><button type="button" class="icon-btn icon-btn-delete" data-vehicle-crud-delete='<?= esc(json_encode(['id'=>(int)$record['id'],'label'=>$record['nomor_polisi'].' · '.$record['nama_kendaraan']]), 'attr') ?>' title="Hapus">×</button></div></td>
        </tr>
    <?php endforeach ?><?php endif ?>
    </tbody></table></div>
    <div class="table-list-footer"><form method="get" class="table-length-form"><input type="hidden" name="q" value="<?= esc($filters['keyword']) ?>"><input type="hidden" name="status" value="<?= esc($filters['status']) ?>"><label for="vehiclePerPage">Tampilkan</label><select id="vehiclePerPage" name="per_page" onchange="this.form.submit()"><?php foreach ([10,20,50,100] as $option): ?><option value="<?= $option ?>" <?= $filters['perPage'] === $option ? 'selected' : '' ?>><?= $option ?></option><?php endforeach ?></select><span>data</span></form><div class="pagination-wrap"><?= $pager->links('vehicles') ?></div></div>
</section>

<div class="account-modal vehicle-crud-modal" id="vehicleCrudFormModal" hidden aria-hidden="true"><button type="button" class="modal-backdrop" data-vehicle-crud-form-close aria-label="Tutup form"></button><section class="modal-dialog vehicle-crud-dialog" role="dialog" aria-modal="true" aria-labelledby="vehicleCrudFormTitle"><header class="modal-header"><div class="modal-title-group"><span class="modal-title-icon" data-crud-form-icon>＋</span><div><p>MONITORING KENDARAAN</p><h2 id="vehicleCrudFormTitle" data-crud-form-title>Tambah Kendaraan</h2></div></div><button type="button" class="modal-close" data-vehicle-crud-form-close>×</button></header>
<form action="<?= site_url('bagian-umum-2/monitoring-kendaraan/data-kendaraan') ?>" method="post" data-vehicle-crud-form><?= csrf_field() ?><div class="modal-body"><div class="modal-form-grid vehicle-form-grid">
    <div class="form-group"><label for="vehiclePlate">Nomor Polisi <span>*</span></label><input id="vehiclePlate" name="nomor_polisi" maxlength="20" placeholder="L 1234 ABC" required></div>
    <div class="form-group"><label for="vehicleName">Nama Kendaraan <span>*</span></label><input id="vehicleName" name="nama_kendaraan" maxlength="150" placeholder="Mobil Operasional 01" required></div>
    <div class="form-group"><label for="vehicleType">Jenis <span>*</span></label><input id="vehicleType" name="jenis" maxlength="80" placeholder="Mobil / Motor" required></div>
    <div class="form-group"><label for="vehicleBrand">Merek</label><input id="vehicleBrand" name="merek" maxlength="100"></div>
    <div class="form-group"><label for="vehicleModel">Tipe</label><input id="vehicleModel" name="tipe" maxlength="100"></div>
    <div class="form-group"><label for="vehicleYear">Tahun</label><input id="vehicleYear" name="tahun" type="number" min="1900" max="<?= (int) date('Y') + 1 ?>"></div>
    <div class="form-group"><label for="vehicleColor">Warna</label><input id="vehicleColor" name="warna" maxlength="60"></div>
    <div class="form-group"><label for="vehicleKm">Kilometer <span>*</span></label><input id="vehicleKm" name="kilometer" type="number" min="0" value="0" required></div>
    <div class="form-group"><label for="vehicleFrame">Nomor Rangka</label><input id="vehicleFrame" name="nomor_rangka" maxlength="100"></div>
    <div class="form-group"><label for="vehicleEngine">Nomor Mesin</label><input id="vehicleEngine" name="nomor_mesin" maxlength="100"></div>
    <div class="form-group"><label for="vehicleUnit">Unit Pengguna</label><input id="vehicleUnit" name="unit_pengguna" maxlength="150"></div>
    <div class="form-group"><label for="vehiclePic">PIC / Penanggung Jawab</label><input id="vehiclePic" name="pic" maxlength="150"></div>
    <div class="form-group modal-span-2"><label for="vehicleStatus">Status <span>*</span></label><select id="vehicleStatus" name="status" required><?php foreach ($statuses as $item): ?><option value="<?= esc($item) ?>"><?= esc($item) ?></option><?php endforeach ?></select></div>
</div></div><footer class="modal-footer"><button type="button" class="btn btn-ghost" data-vehicle-crud-form-close>Batal</button><button type="submit" class="btn btn-primary" data-crud-submit>Simpan data</button></footer></form></section></div>

<div class="account-modal vehicle-crud-modal" id="vehicleCrudDeleteModal" hidden aria-hidden="true"><button type="button" class="modal-backdrop" data-vehicle-crud-delete-close></button><section class="modal-dialog delete-modal-dialog" role="alertdialog" aria-modal="true"><div class="delete-modal-body"><span class="delete-warning-icon">!</span><h2>Hapus Kendaraan?</h2><p><strong data-crud-delete-name></strong> akan dihapus dari daftar monitoring.</p></div><form method="post" action="" data-crud-delete-form class="delete-modal-actions"><?= csrf_field() ?><button type="button" class="btn btn-ghost" data-vehicle-crud-delete-close>Batal</button><button type="submit" class="btn btn-delete">Ya, hapus</button></form></section></div>

<?= view('monitoring_kendaraan/_crud_script') ?>
<?= $this->endSection() ?>
