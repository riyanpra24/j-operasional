<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<section class="page-heading heading-actions vehicle-page-heading">
    <div><p class="eyebrow">BAGIAN UMUM 2 · MONITORING KENDARAAN</p><h1>Data Kendaraan</h1><p>Kelola identitas, penanggung jawab, kilometer, dan status kendaraan operasional.</p></div>
    <button type="button" class="btn btn-primary" data-vehicle-crud-create>＋ Tambah Kendaraan</button>
</section>

<?= view('monitoring_kendaraan/_tabs', compact('activePage')) ?>

<section class="panel filter-panel vehicle-filter-panel">
    <form action="<?= site_url('bagian-umum-2/monitoring-kendaraan/data-kendaraan') ?>" method="get" class="vehicle-filter-form">
        <div class="form-group"><label for="vehicleSearch">Cari kendaraan</label><div class="input-with-icon"><span>⌕</span><input id="vehicleSearch" name="q" value="<?= esc($filters['keyword']) ?>" placeholder="Nomor polisi, kendaraan, merek, driver, atau unit pengelola"></div></div>
        <div class="form-group"><label for="vehicleStatusFilter">Status</label><select id="vehicleStatusFilter" name="status"><option value="">Semua status</option><?php foreach ($statuses as $item): ?><option value="<?= esc($item) ?>" <?= $filters['status'] === $item ? 'selected' : '' ?>><?= esc($item) ?></option><?php endforeach ?></select></div>
        <div class="filter-actions"><button class="btn btn-secondary" type="submit">Terapkan</button><a class="btn btn-ghost" href="<?= site_url('bagian-umum-2/monitoring-kendaraan/data-kendaraan') ?>">Reset</a></div>
    </form>
</section>

<section class="panel register-panel vehicle-register-panel" data-vehicle-crud-root data-base-url="<?= site_url('bagian-umum-2/monitoring-kendaraan/data-kendaraan') ?>" data-create-title="Tambah Kendaraan" data-edit-title="Edit Kendaraan">
    <div class="table-wrap"><table><thead><tr><th>No.</th><th>Nomor Polisi</th><th>Kendaraan</th><th>Status Kendaraan</th><th>Tahun</th><th>Unit Pengelola</th><th>Driver Pengguna</th><th>Kilometer</th><th>Status Operasional</th><th>Aksi</th></tr></thead><tbody>
    <?php if ($records === []): ?><tr><td colspan="10"><div class="empty-state"><span>▤</span><strong>Belum ada data kendaraan</strong><p>Tambahkan kendaraan pertama untuk mulai melakukan monitoring.</p></div></td></tr>
    <?php else: ?><?php foreach ($records as $index => $record): ?>
        <?php
        $editData = array_intersect_key($record, array_flip(['id','nomor_polisi','nama_kendaraan','nama_kendaraan_lainnya','jenis','status_kendaraan','merek','tipe','tahun','warna','nomor_rangka','nomor_mesin','unit_pengguna','unit_pengguna_lainnya','pic','pic_internal','kilometer','status','status_lainnya']));
        $isDeleted = ! empty($record['deleted_at']);
        $vehicleNameLabel = $record['nama_kendaraan'] === 'Lainnya' && ! empty($record['nama_kendaraan_lainnya']) ? $record['nama_kendaraan_lainnya'] : $record['nama_kendaraan'];
        $driverLabel = $record['unit_pengguna'] === 'Lainnya' && ! empty($record['unit_pengguna_lainnya']) ? $record['unit_pengguna_lainnya'] : $record['unit_pengguna'];
        $statusLabel = $record['status'] === 'Lainnya' && ! empty($record['status_lainnya']) ? $record['status_lainnya'] : $record['status'];
        $viewData = [
            'nomor_polisi' => $record['nomor_polisi'],
            'nama_kendaraan' => $vehicleNameLabel,
            'jenis' => $record['jenis'],
            'status_kendaraan' => $record['status_kendaraan'],
            'merek' => $record['merek'],
            'tipe' => $record['tipe'],
            'tahun' => $record['tahun'],
            'warna' => $record['warna'],
            'kilometer' => number_format((int) $record['kilometer'], 0, ',', '.') . ' km',
            'nomor_rangka' => $record['nomor_rangka'],
            'nomor_mesin' => $record['nomor_mesin'],
            'driver_pengguna' => $driverLabel,
            'unit_pengelola' => $record['pic'],
            'pic_internal' => $record['pic_internal'],
            'status_operasional' => $statusLabel,
        ];
        ?>
        <tr class="<?= $isDeleted ? 'vehicle-row-deleted' : '' ?>">
            <td><strong><?= (($pager->getCurrentPage('vehicles') - 1) * $filters['perPage']) + $index + 1 ?></strong></td>
            <td><strong class="vehicle-plate"><?= esc($record['nomor_polisi']) ?></strong></td>
            <td><div class="vehicle-name-cell"><strong><?= esc($vehicleNameLabel) ?></strong><span><?= esc(trim(($record['merek'] ?? '') . ' ' . ($record['tipe'] ?? '')) ?: $record['jenis']) ?></span></div></td>
            <td><span class="vehicle-ownership <?= $record['status_kendaraan'] === 'Kendaraan Sewa' ? 'rental' : 'asset' ?>"><?= esc($record['status_kendaraan']) ?></span></td>
            <td><?= esc($record['tahun'] ?: '-') ?></td>
            <td><?= esc($record['pic'] ?: 'Belum dipilih') ?></td>
            <td><?= esc($driverLabel ?: 'Belum dipilih') ?></td>
            <td><strong><?= number_format((int) $record['kilometer'], 0, ',', '.') ?> km</strong></td>
            <td><?php if ($isDeleted): ?><span class="vehicle-action dihapus">Dihapus</span><?php else: ?><span class="vehicle-status <?= esc(strtolower(str_replace(' ', '-', $record['status']))) ?>"><?= esc($statusLabel) ?></span><?php endif ?></td>
            <td><div class="action-buttons"><button type="button" class="icon-btn" data-vehicle-detail='<?= esc(json_encode($viewData), 'attr') ?>' title="Lihat detail" aria-label="Lihat detail kendaraan">⌕</button><?php if (! $isDeleted || $isAdmin): ?><button type="button" class="icon-btn" data-vehicle-crud-edit='<?= esc(json_encode($editData), 'attr') ?>' title="<?= $isDeleted ? 'Edit atau hapus permanen' : 'Edit' ?>">✎</button><?php endif ?></div></td>
        </tr>
    <?php endforeach ?><?php endif ?>
    </tbody></table></div>
    <div class="table-list-footer"><form method="get" class="table-length-form"><input type="hidden" name="q" value="<?= esc($filters['keyword']) ?>"><input type="hidden" name="status" value="<?= esc($filters['status']) ?>"><label for="vehiclePerPage">Tampilkan</label><select id="vehiclePerPage" name="per_page" onchange="this.form.submit()"><?php foreach ([10,20,50,100] as $option): ?><option value="<?= $option ?>" <?= $filters['perPage'] === $option ? 'selected' : '' ?>><?= $option ?></option><?php endforeach ?></select><span>data</span></form><div class="pagination-wrap"><?= $pager->links('vehicles') ?></div></div>
</section>

<div class="account-modal vehicle-crud-modal" id="vehicleDetailModal" hidden aria-hidden="true"><button type="button" class="modal-backdrop" data-vehicle-detail-close aria-label="Tutup detail"></button><section class="modal-dialog vehicle-crud-dialog vehicle-detail-dialog" role="dialog" aria-modal="true" aria-labelledby="vehicleDetailTitle"><header class="modal-header"><div class="modal-title-group"><span class="modal-title-icon">⌕</span><div><p>MONITORING KENDARAAN</p><h2 id="vehicleDetailTitle">Detail Kendaraan</h2></div></div><button type="button" class="modal-close" data-vehicle-detail-close>×</button></header><div class="modal-body vehicle-detail-body">
    <section class="vehicle-detail-section"><header><span>01</span><div><strong>Identitas Kendaraan</strong><small>Informasi utama kendaraan</small></div></header><dl class="modal-detail-grid"><div><dt>Nomor Polisi</dt><dd data-vehicle-detail-field="nomor_polisi">-</dd></div><div><dt>Nama Kendaraan</dt><dd data-vehicle-detail-field="nama_kendaraan">-</dd></div><div><dt>Jenis</dt><dd data-vehicle-detail-field="jenis">-</dd></div><div><dt>Status Kendaraan</dt><dd data-vehicle-detail-field="status_kendaraan">-</dd></div><div><dt>Merek</dt><dd data-vehicle-detail-field="merek">-</dd></div><div><dt>Tipe</dt><dd data-vehicle-detail-field="tipe">-</dd></div></dl></section>
    <section class="vehicle-detail-section"><header><span>02</span><div><strong>Data Teknis</strong><small>Spesifikasi dan kilometer kendaraan</small></div></header><dl class="modal-detail-grid"><div><dt>Tahun</dt><dd data-vehicle-detail-field="tahun">-</dd></div><div><dt>Warna</dt><dd data-vehicle-detail-field="warna">-</dd></div><div><dt>Kilometer</dt><dd data-vehicle-detail-field="kilometer">-</dd></div><div><dt>Nomor Rangka</dt><dd data-vehicle-detail-field="nomor_rangka">-</dd></div><div class="detail-wide"><dt>Nomor Mesin</dt><dd data-vehicle-detail-field="nomor_mesin">-</dd></div></dl></section>
    <section class="vehicle-detail-section"><header><span>03</span><div><strong>Pengguna &amp; Status</strong><small>Driver, pengelola, dan status operasional</small></div></header><dl class="modal-detail-grid"><div><dt>Driver Pengguna</dt><dd data-vehicle-detail-field="driver_pengguna">-</dd></div><div><dt>Unit Pengelola</dt><dd data-vehicle-detail-field="unit_pengelola">-</dd></div><div><dt>PIC Internal</dt><dd data-vehicle-detail-field="pic_internal">-</dd></div><div><dt>Status Operasional</dt><dd data-vehicle-detail-field="status_operasional">-</dd></div></dl></section>
</div><footer class="modal-footer"><button type="button" class="btn btn-secondary" data-vehicle-detail-close>Tutup</button></footer></section></div>

<div class="account-modal vehicle-crud-modal" id="vehicleCrudFormModal" hidden aria-hidden="true"><button type="button" class="modal-backdrop" data-vehicle-crud-form-close aria-label="Tutup form"></button><section class="modal-dialog vehicle-crud-dialog" role="dialog" aria-modal="true" aria-labelledby="vehicleCrudFormTitle"><header class="modal-header"><div class="modal-title-group"><span class="modal-title-icon" data-crud-form-icon>＋</span><div><p>MONITORING KENDARAAN</p><h2 id="vehicleCrudFormTitle" data-crud-form-title>Tambah Kendaraan</h2></div></div><button type="button" class="modal-close" data-vehicle-crud-form-close>×</button></header>
<form action="<?= site_url('bagian-umum-2/monitoring-kendaraan/data-kendaraan') ?>" method="post" data-vehicle-crud-form>
    <?= csrf_field() ?>
    <div class="modal-body vehicle-wizard-body">
        <ol class="vehicle-form-stepper" aria-label="Tahapan form kendaraan">
            <li class="active" data-vehicle-step-indicator="0"><span>01</span><div><strong>Identitas Kendaraan</strong><small>Data utama kendaraan</small></div></li>
            <li data-vehicle-step-indicator="1"><span>02</span><div><strong>Data Teknis</strong><small>Detail kendaraan</small></div></li>
            <li data-vehicle-step-indicator="2"><span>03</span><div><strong>Pengguna & Status</strong><small>Driver, unit, dan status</small></div></li>
        </ol>

        <section class="modal-form-grid vehicle-form-grid vehicle-form-step" data-vehicle-form-step="0">
            <div class="form-group"><label for="vehiclePlate">Nomor Polisi <span>*</span></label><input id="vehiclePlate" name="nomor_polisi" maxlength="20" placeholder="L 1234 ABC" required></div>
            <div class="form-group"><label for="vehicleName">Nama Kendaraan <span>*</span></label><select id="vehicleName" name="nama_kendaraan" required><option value="">Pilih nama kendaraan</option><?php foreach ($vehicleNames as $item): ?><option value="<?= esc($item) ?>"><?= esc($item) ?></option><?php endforeach ?></select></div>
            <div class="form-group modal-span-2" data-vehicle-other-name hidden><label for="vehicleOtherName">Nama Kendaraan Lainnya <span>*</span></label><input id="vehicleOtherName" name="nama_kendaraan_lainnya" maxlength="150" placeholder="Tuliskan nama kendaraan"></div>
            <div class="form-group"><label for="vehicleType">Jenis <span>*</span></label><select id="vehicleType" name="jenis" required><option value="">Pilih jenis kendaraan</option><?php foreach ($vehicleTypes as $item): ?><option value="<?= esc($item) ?>"><?= esc($item) ?></option><?php endforeach ?></select></div>
            <div class="form-group"><label for="vehicleOwnershipStatus">Status Kendaraan <span>*</span></label><select id="vehicleOwnershipStatus" name="status_kendaraan" required><option value="">Pilih status kendaraan</option><?php foreach ($vehicleOwnershipStatuses as $item): ?><option value="<?= esc($item) ?>"><?= esc($item) ?></option><?php endforeach ?></select></div>
            <div class="form-group"><label for="vehicleBrand">Merek</label><input id="vehicleBrand" name="merek" maxlength="100"></div>
            <div class="form-group"><label for="vehicleModel">Tipe</label><input id="vehicleModel" name="tipe" maxlength="100"></div>
        </section>

        <section class="modal-form-grid vehicle-form-grid vehicle-form-step" data-vehicle-form-step="1" hidden>
            <div class="form-group"><label for="vehicleYear">Tahun</label><input id="vehicleYear" name="tahun" type="number" min="1900" max="<?= (int) date('Y') + 1 ?>"></div>
            <div class="form-group"><label for="vehicleColor">Warna</label><input id="vehicleColor" name="warna" maxlength="60"></div>
            <div class="form-group modal-span-2"><label for="vehicleKm">Kilometer</label><input id="vehicleKm" name="kilometer" type="number" min="0" value="0" inputmode="numeric"><small id="vehicleKmHelp">Isi kilometer awal kendaraan.</small></div>
            <div class="form-group"><label for="vehicleFrame">Nomor Rangka</label><input id="vehicleFrame" name="nomor_rangka" maxlength="100"></div>
            <div class="form-group"><label for="vehicleEngine">Nomor Mesin</label><input id="vehicleEngine" name="nomor_mesin" maxlength="100"></div>
        </section>

        <section class="modal-form-grid vehicle-form-grid vehicle-form-step" data-vehicle-form-step="2" hidden>
            <div class="form-group"><label for="vehicleDriver">Driver Pengguna</label><select id="vehicleDriver" name="unit_pengguna"><option value="">Pilih driver pengguna</option><?php foreach ($drivers as $driver): ?><option value="<?= esc($driver) ?>"><?= esc($driver) ?></option><?php endforeach ?></select></div>
            <div class="form-group"><label for="vehicleManagementUnit">Unit Pengelola</label><select id="vehicleManagementUnit" name="pic"><option value="">Pilih unit pengelola</option><?php foreach ($managementUnits as $unit): ?><option value="<?= esc($unit) ?>"><?= esc($unit) ?></option><?php endforeach ?></select></div>
            <div class="form-group modal-span-2" data-vehicle-other-driver hidden><label for="vehicleOtherDriver">Nama Driver Lainnya <span>*</span></label><input id="vehicleOtherDriver" name="unit_pengguna_lainnya" maxlength="150" placeholder="Tuliskan nama driver pengguna"></div>
            <div class="form-group"><label for="vehicleStatus">Status Operasional <span>*</span></label><select id="vehicleStatus" name="status" required><?php foreach ($statuses as $item): ?><option value="<?= esc($item) ?>"><?= esc($item) ?></option><?php endforeach ?></select></div>
            <div class="form-group"><label for="vehicleInternalPic">PIC Internal</label><input id="vehicleInternalPic" name="pic_internal" readonly aria-readonly="true" placeholder="Terisi otomatis sesuai unit"><small>Ditentukan otomatis berdasarkan Unit Pengelola.</small></div>
            <div class="form-group modal-span-2" data-vehicle-other-status hidden><label for="vehicleOtherStatus">Status Lainnya <span>*</span></label><input id="vehicleOtherStatus" name="status_lainnya" maxlength="100" placeholder="Tuliskan status kendaraan"></div>
        </section>
    </div>
    <footer class="modal-footer vehicle-wizard-footer">
        <button type="button" class="btn btn-ghost" data-vehicle-crud-form-close>Batal</button>
        <button type="button" class="btn btn-delete" data-crud-edit-delete data-vehicle-crud-delete hidden><?= $isAdmin ? 'Hapus permanen' : 'Hapus' ?></button>
        <span class="vehicle-wizard-footer-spacer"></span>
        <button type="button" class="btn btn-secondary" data-vehicle-step-back hidden>Kembali</button>
        <button type="button" class="btn btn-primary" data-vehicle-step-next>Lanjut</button>
        <button type="submit" class="btn btn-primary" data-crud-submit hidden>Simpan data</button>
    </footer>
</form></section></div>

<div class="account-modal vehicle-crud-modal" id="vehicleCrudDeleteModal" hidden aria-hidden="true"><button type="button" class="modal-backdrop" data-vehicle-crud-delete-close></button><section class="modal-dialog delete-modal-dialog" role="alertdialog" aria-modal="true"><div class="delete-modal-body"><span class="delete-warning-icon">!</span><h2><?= $isAdmin ? 'Hapus Kendaraan Permanen?' : 'Hapus Kendaraan?' ?></h2><p><strong data-crud-delete-name></strong> <?= $isAdmin ? 'akan dihapus permanen dari database beserta data servis dan dokumennya.' : 'akan hilang dari akun Bagian Umum 2, tetapi tetap tersimpan dan dapat dilihat oleh Administrator.' ?></p></div><form method="post" action="" data-crud-delete-form class="delete-modal-actions"><?= csrf_field() ?><button type="button" class="btn btn-ghost" data-vehicle-crud-delete-close>Batal</button><button type="submit" class="btn btn-delete"><?= $isAdmin ? 'Ya, hapus permanen' : 'Ya, hapus' ?></button></form></section></div>

<?= view('monitoring_kendaraan/_crud_script') ?>
<script>
(() => {
    const modal = document.getElementById('vehicleDetailModal');
    if (!modal) return;

    const openModal = data => {
        modal.querySelectorAll('[data-vehicle-detail-field]').forEach(field => {
            const value = data[field.dataset.vehicleDetailField];
            field.textContent = value === null || value === undefined || value === '' ? '-' : String(value);
        });
        modal.hidden = false;
        modal.setAttribute('aria-hidden', 'false');
        requestAnimationFrame(() => modal.classList.add('open'));
        document.body.classList.add('modal-open');
    };
    const closeModal = () => {
        modal.classList.remove('open');
        modal.setAttribute('aria-hidden', 'true');
        window.setTimeout(() => {
            modal.hidden = true;
            if (!document.querySelector('.vehicle-crud-modal.open')) document.body.classList.remove('modal-open');
        }, 180);
    };

    document.querySelectorAll('[data-vehicle-detail]').forEach(button => button.addEventListener('click', () => {
        openModal(JSON.parse(button.dataset.vehicleDetail));
    }));
    modal.querySelectorAll('[data-vehicle-detail-close]').forEach(button => button.addEventListener('click', closeModal));
    document.addEventListener('keydown', event => {
        if (event.key === 'Escape' && !modal.hidden) closeModal();
    });
})();
</script>
<script>
(() => {
    const status = document.getElementById('vehicleStatus');
    const statusGroup = document.querySelector('[data-vehicle-other-status]');
    const statusInput = document.getElementById('vehicleOtherStatus');
    const vehicleName = document.getElementById('vehicleName');
    const nameGroup = document.querySelector('[data-vehicle-other-name]');
    const nameInput = document.getElementById('vehicleOtherName');
    const managementUnit = document.getElementById('vehicleManagementUnit');
    const internalPic = document.getElementById('vehicleInternalPic');
    const driver = document.getElementById('vehicleDriver');
    const driverGroup = document.querySelector('[data-vehicle-other-driver]');
    const driverInput = document.getElementById('vehicleOtherDriver');
    const kilometer = document.getElementById('vehicleKm');
    const kilometerHelp = document.getElementById('vehicleKmHelp');
    const form = document.querySelector('[data-vehicle-crud-form]');
    const steps = Array.from(document.querySelectorAll('[data-vehicle-form-step]'));
    const indicators = Array.from(document.querySelectorAll('[data-vehicle-step-indicator]'));
    const backButton = document.querySelector('[data-vehicle-step-back]');
    const nextButton = document.querySelector('[data-vehicle-step-next]');
    const submitButton = form?.querySelector('[data-crud-submit]');
    if (!status || !statusGroup || !statusInput || !vehicleName || !nameGroup || !nameInput || !managementUnit || !internalPic || !driver || !driverGroup || !driverInput || !kilometer || !kilometerHelp || !form || steps.length === 0) return;

    let currentStep = 0;

    const synchronize = () => {
        const custom = status.value === 'Lainnya';
        statusGroup.hidden = !custom;
        statusInput.disabled = !custom;
        statusInput.required = custom;
        if (!custom) statusInput.value = '';

        const customName = vehicleName.value === 'Lainnya';
        nameGroup.hidden = !customName;
        nameInput.disabled = !customName;
        nameInput.required = customName;
        if (!customName) nameInput.value = '';

        const customDriver = driver.value === 'Lainnya';
        driverGroup.hidden = !customDriver;
        driverInput.disabled = !customDriver;
        driverInput.required = customDriver;
        if (!customDriver) driverInput.value = '';

        internalPic.value = {
            'Bagian Umum 1': 'Angger Wicaksono',
            'Bagian Umum 2': 'Agil Halis Kesawa',
        }[managementUnit.value] || '';
    };

    const showStep = (index) => {
        currentStep = Math.max(0, Math.min(index, steps.length - 1));
        steps.forEach((step, stepIndex) => { step.hidden = stepIndex !== currentStep; });
        indicators.forEach((indicator, stepIndex) => {
            indicator.classList.toggle('active', stepIndex === currentStep);
            indicator.classList.toggle('complete', stepIndex < currentStep);
            if (stepIndex === currentStep) indicator.setAttribute('aria-current', 'step');
            else indicator.removeAttribute('aria-current');
        });
        if (backButton) backButton.hidden = currentStep === 0;
        if (nextButton) nextButton.hidden = currentStep === steps.length - 1;
        if (submitButton) submitButton.hidden = currentStep !== steps.length - 1;
        steps[currentStep].querySelector('input:not([disabled]), select:not([disabled]), textarea:not([disabled])')?.focus({preventScroll: true});
    };

    const validateCurrentStep = () => {
        const controls = Array.from(steps[currentStep].querySelectorAll('input:not([disabled]), select:not([disabled]), textarea:not([disabled])'));
        const invalid = controls.find(control => !control.checkValidity());
        if (!invalid) return true;
        invalid.reportValidity();
        invalid.focus();
        return false;
    };

    status.addEventListener('change', synchronize);
    vehicleName.addEventListener('change', synchronize);
    driver.addEventListener('change', synchronize);
    managementUnit.addEventListener('change', synchronize);
    backButton?.addEventListener('click', () => showStep(currentStep - 1));
    nextButton?.addEventListener('click', () => {
        if (validateCurrentStep()) showStep(currentStep + 1);
    });
    form.addEventListener('submit', event => {
        if (currentStep === steps.length - 1) return;
        event.preventDefault();
        if (validateCurrentStep()) showStep(currentStep + 1);
    });
    window.addEventListener('vehicle-crud:prepared', event => {
        const isCreate = event.detail?.mode === 'create';
        kilometer.readOnly = !isCreate;
        kilometer.setAttribute('aria-readonly', isCreate ? 'false' : 'true');
        kilometerHelp.textContent = isCreate
            ? 'Isi kilometer awal kendaraan.'
            : 'Terisi otomatis dari KM Saat Servis tertinggi dan tidak dapat diubah dari data kendaraan.';
        synchronize();
        showStep(0);
    });
    synchronize();
    showStep(0);
})();
</script>
<?= $this->endSection() ?>
