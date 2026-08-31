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
<?php
$editData = array_intersect_key($record, array_flip(['id','vehicle_id','tanggal_servis','jenis_perawatan','bengkel','kilometer','biaya','servis_berikutnya_tanggal','anggaran_servis','nama_perusahaan','keterangan','link_berkas']));
$isDeleted = ! empty($record['deleted_at']) || ! empty($record['vehicle_deleted_at']);
$vehicleDisplayName = $record['nama_kendaraan'] === 'Lainnya' && ! empty($record['nama_kendaraan_lainnya']) ? $record['nama_kendaraan_lainnya'] : $record['nama_kendaraan'];
$deleteLabel = $record['nomor_polisi'] . ' · ' . $record['jenis_perawatan'];
$editData['delete_label'] = $deleteLabel;
$viewData = [
    'kendaraan' => $record['nomor_polisi'] . ' · ' . $vehicleDisplayName,
    'tanggal_servis' => date('d-m-Y', strtotime($record['tanggal_servis'])),
    'servis_berikutnya' => $record['servis_berikutnya_tanggal'] ? date('d-m-Y', strtotime($record['servis_berikutnya_tanggal'])) : '-',
    'jenis_perawatan' => $record['jenis_perawatan'],
    'bengkel' => $record['bengkel'],
    'kilometer' => $record['kilometer'] !== null ? number_format((int) $record['kilometer'], 0, ',', '.') . ' km' : '-',
    'biaya' => 'Rp ' . number_format((float) $record['biaya'], 0, ',', '.'),
    'anggaran_servis' => $record['anggaran_servis'],
    'nama_perusahaan' => $record['nama_perusahaan'],
    'link_berkas' => $record['link_berkas'],
    'keterangan' => $record['keterangan'],
];
?>
<tr class="<?= $isDeleted ? 'vehicle-row-deleted' : '' ?>"><td><strong><?= (($pager->getCurrentPage('maintenance') - 1) * $filters['perPage']) + $index + 1 ?></strong></td><td><div class="vehicle-name-cell"><strong><?= esc($record['nomor_polisi']) ?></strong><span><?= esc($vehicleDisplayName) ?></span><?php if ($isDeleted): ?><small class="vehicle-deleted-note"><?= ! empty($record['deleted_at']) ? 'Data servis dihapus' : 'Kendaraan dihapus' ?></small><?php endif ?></div></td><td><?= date('d-m-Y', strtotime($record['tanggal_servis'])) ?></td><td><strong><?= esc($record['jenis_perawatan']) ?></strong></td><td><?= esc($record['bengkel'] ?: '-') ?></td><td><?= $record['kilometer'] !== null ? number_format((int)$record['kilometer'],0,',','.') . ' km' : '-' ?></td><td><strong>Rp <?= number_format((float)$record['biaya'],0,',','.') ?></strong></td><td><strong><?= $record['servis_berikutnya_tanggal'] ? date('d-m-Y',strtotime($record['servis_berikutnya_tanggal'])) : '-' ?></strong></td><td><div class="action-buttons"><button type="button" class="icon-btn" data-maintenance-detail='<?= esc(json_encode($viewData), 'attr') ?>' title="Lihat detail" aria-label="Lihat detail servis">⌕</button><?php if ($record['link_berkas']): ?><a class="icon-btn" href="<?= esc($record['link_berkas']) ?>" target="_blank" rel="noopener noreferrer" title="Buka berkas">↗</a><?php endif ?><?php if (! $isDeleted || $isAdmin): ?><button type="button" class="icon-btn" data-vehicle-crud-edit='<?= esc(json_encode($editData), 'attr') ?>' title="<?= $isDeleted ? 'Edit atau hapus permanen' : 'Edit' ?>">✎</button><?php endif ?></div></td></tr>
<?php endforeach ?><?php endif ?>
</tbody></table></div><div class="table-list-footer"><form method="get" class="table-length-form"><input type="hidden" name="q" value="<?= esc($filters['keyword']) ?>"><label for="maintenancePerPage">Tampilkan</label><select id="maintenancePerPage" name="per_page" onchange="this.form.submit()"><?php foreach ([10,20,50,100] as $option): ?><option value="<?= $option ?>" <?= $filters['perPage'] === $option ? 'selected' : '' ?>><?= $option ?></option><?php endforeach ?></select><span>data</span></form><div class="pagination-wrap"><?= $pager->links('maintenance') ?></div></div></section>

<div class="account-modal vehicle-crud-modal" id="maintenanceDetailModal" hidden aria-hidden="true"><button type="button" class="modal-backdrop" data-maintenance-detail-close aria-label="Tutup detail"></button><section class="modal-dialog vehicle-crud-dialog vehicle-detail-dialog" role="dialog" aria-modal="true" aria-labelledby="maintenanceDetailTitle"><header class="modal-header"><div class="modal-title-group"><span class="modal-title-icon">⌕</span><div><p>MONITORING KENDARAAN</p><h2 id="maintenanceDetailTitle">Detail Servis</h2></div></div><button type="button" class="modal-close" data-maintenance-detail-close>×</button></header><div class="modal-body vehicle-detail-body">
    <section class="vehicle-detail-section"><header><span>01</span><div><strong>Jadwal Servis</strong><small>Kendaraan dan tanggal pelaksanaan</small></div></header><dl class="modal-detail-grid"><div class="detail-wide"><dt>Kendaraan</dt><dd data-maintenance-detail-field="kendaraan">-</dd></div><div><dt>Tanggal Servis</dt><dd data-maintenance-detail-field="tanggal_servis">-</dd></div><div><dt>Servis Berikutnya</dt><dd data-maintenance-detail-field="servis_berikutnya">-</dd></div></dl></section>
    <section class="vehicle-detail-section"><header><span>02</span><div><strong>Detail Perawatan</strong><small>Pekerjaan, bengkel, kilometer, dan biaya</small></div></header><dl class="modal-detail-grid"><div><dt>Jenis Perawatan</dt><dd data-maintenance-detail-field="jenis_perawatan">-</dd></div><div><dt>Bengkel</dt><dd data-maintenance-detail-field="bengkel">-</dd></div><div><dt>KM Saat Servis</dt><dd data-maintenance-detail-field="kilometer">-</dd></div><div><dt>Biaya</dt><dd data-maintenance-detail-field="biaya">-</dd></div></dl></section>
    <section class="vehicle-detail-section"><header><span>03</span><div><strong>Anggaran &amp; Dokumen</strong><small>Sumber anggaran dan informasi pendukung</small></div></header><dl class="modal-detail-grid"><div><dt>Anggaran Service</dt><dd data-maintenance-detail-field="anggaran_servis">-</dd></div><div><dt>Nama Perusahaan</dt><dd data-maintenance-detail-field="nama_perusahaan">-</dd></div><div class="detail-wide"><dt>Link Berkas</dt><dd><a data-maintenance-detail-link target="_blank" rel="noopener noreferrer" hidden>Buka berkas ↗</a><span data-maintenance-detail-no-link>-</span></dd></div><div class="detail-wide"><dt>Keterangan</dt><dd data-maintenance-detail-field="keterangan">-</dd></div></dl></section>
</div><footer class="modal-footer"><button type="button" class="btn btn-secondary" data-maintenance-detail-close>Tutup</button></footer></section></div>

<div class="account-modal vehicle-crud-modal" id="vehicleCrudFormModal" hidden aria-hidden="true"><button type="button" class="modal-backdrop" data-vehicle-crud-form-close></button><section class="modal-dialog vehicle-crud-dialog" role="dialog" aria-modal="true" aria-labelledby="maintenanceFormTitle"><header class="modal-header"><div class="modal-title-group"><span class="modal-title-icon" data-crud-form-icon>＋</span><div><p>MONITORING KENDARAAN</p><h2 id="maintenanceFormTitle" data-crud-form-title>Tambah Servis</h2></div></div><button type="button" class="modal-close" data-vehicle-crud-form-close>×</button></header><form method="post" action="<?= site_url('bagian-umum-2/monitoring-kendaraan/servis-perawatan') ?>" data-vehicle-crud-form><?= csrf_field() ?><div class="modal-body vehicle-wizard-body">
<ol class="vehicle-form-stepper maintenance-form-stepper" aria-label="Tahapan form servis">
    <li class="active" data-maintenance-step-indicator="0"><span>01</span><div><strong>Informasi Servis</strong><small>Kendaraan, jadwal, dan perawatan</small></div></li>
    <li data-maintenance-step-indicator="1"><span>02</span><div><strong>Biaya &amp; Dokumen</strong><small>Kilometer, anggaran, dan berkas</small></div></li>
</ol>
<section class="modal-form-grid vehicle-form-grid vehicle-form-step" data-maintenance-form-step="0">
    <div class="form-group modal-span-2"><label for="maintenanceVehicle">Kendaraan <span>*</span></label><select id="maintenanceVehicle" name="vehicle_id" required><option value="">Pilih kendaraan</option><?php foreach ($vehicles as $vehicle): ?><option value="<?= (int)$vehicle['id'] ?>"><?= esc($vehicle['nomor_polisi'].' · '.$vehicle['nama_tampilan']) ?></option><?php endforeach ?></select></div>
    <div class="form-group"><label for="maintenanceDate">Tanggal Servis <span>*</span></label><input id="maintenanceDate" name="tanggal_servis" type="date" value="<?= date('Y-m-d') ?>" required><small data-service-date-lock hidden>Tanggal yang sudah tersimpan hanya dapat diubah oleh Administrator.</small></div><div class="form-group"><label for="maintenanceNextDate">Servis Berikutnya</label><input id="maintenanceNextDate" name="servis_berikutnya_tanggal" type="date" readonly><small>Terisi otomatis 3 bulan setelah Tanggal Servis.</small></div>
    <div class="form-group"><label for="maintenanceType">Jenis Perawatan <span>*</span></label><input id="maintenanceType" name="jenis_perawatan" maxlength="150" placeholder="Servis berkala / ganti oli" required></div><div class="form-group"><label for="maintenanceWorkshop">Bengkel</label><input id="maintenanceWorkshop" name="bengkel" maxlength="150"></div>
</section>
<section class="modal-form-grid vehicle-form-grid vehicle-form-step" data-maintenance-form-step="1" hidden>
    <div class="form-group"><label for="maintenanceKm">KM Saat Servis <span>*</span></label><input id="maintenanceKm" name="kilometer" type="number" min="0" required></div><div class="form-group"><label for="maintenanceCost">Biaya <span>*</span></label><input id="maintenanceCost" name="biaya" type="number" min="0" step="1" value="0" required></div>
    <div class="form-group"><label for="maintenanceBudget">Anggaran Service <span>*</span></label><select id="maintenanceBudget" name="anggaran_servis" required><option value="">Pilih anggaran service</option><?php foreach ($serviceBudgets as $budget): ?><option value="<?= esc($budget) ?>"><?= esc($budget) ?></option><?php endforeach ?></select></div><div class="form-group"><label for="maintenanceCompany">Nama Perusahaan <span>*</span></label><input id="maintenanceCompany" name="nama_perusahaan" maxlength="150" readonly aria-readonly="true" placeholder="Pilih anggaran service terlebih dahulu"><small id="maintenanceCompanyHelp">Terisi otomatis untuk anggaran Kantor.</small></div>
    <div class="form-group modal-span-2"><label for="maintenanceLink">Link Berkas</label><input id="maintenanceLink" name="link_berkas" type="url" maxlength="2048" placeholder="https://..."></div>
    <div class="form-group modal-span-2"><label for="maintenanceNotes">Keterangan</label><textarea id="maintenanceNotes" name="keterangan" maxlength="5000" rows="3"></textarea></div>
</section>
</div><footer class="modal-footer vehicle-wizard-footer"><button type="button" class="btn btn-ghost" data-vehicle-crud-form-close>Batal</button><button type="button" class="btn btn-delete" data-crud-edit-delete data-vehicle-crud-delete hidden><?= $isAdmin ? 'Hapus permanen' : 'Hapus' ?></button><span class="vehicle-wizard-footer-spacer"></span><button type="button" class="btn btn-secondary" data-maintenance-step-back hidden>Kembali</button><button type="button" class="btn btn-primary" data-maintenance-step-next>Lanjut</button><button type="submit" class="btn btn-primary" data-crud-submit hidden>Simpan data</button></footer></form></section></div>
<div class="account-modal vehicle-crud-modal" id="vehicleCrudDeleteModal" hidden aria-hidden="true"><button type="button" class="modal-backdrop" data-vehicle-crud-delete-close></button><section class="modal-dialog delete-modal-dialog" role="alertdialog" aria-modal="true"><div class="delete-modal-body"><span class="delete-warning-icon">!</span><h2><?= $isAdmin ? 'Hapus Data Servis Permanen?' : 'Hapus Data Servis?' ?></h2><p><strong data-crud-delete-name></strong> <?= $isAdmin ? 'akan dihapus permanen dari database.' : 'akan hilang dari akun Bagian Umum 2, tetapi tetap tersimpan dan dapat dilihat oleh Administrator.' ?></p></div><form method="post" action="" data-crud-delete-form class="delete-modal-actions"><?= csrf_field() ?><button type="button" class="btn btn-ghost" data-vehicle-crud-delete-close>Batal</button><button type="submit" class="btn btn-delete"><?= $isAdmin ? 'Ya, hapus permanen' : 'Ya, hapus' ?></button></form></section></div>
<script>
(() => {
    const modal = document.getElementById('maintenanceDetailModal');
    const link = modal?.querySelector('[data-maintenance-detail-link]');
    const noLink = modal?.querySelector('[data-maintenance-detail-no-link]');
    if (!modal || !link || !noLink) return;

    const openModal = data => {
        modal.querySelectorAll('[data-maintenance-detail-field]').forEach(field => {
            const value = data[field.dataset.maintenanceDetailField];
            field.textContent = value === null || value === undefined || value === '' ? '-' : String(value);
        });
        const hasLink = typeof data.link_berkas === 'string' && data.link_berkas !== '';
        link.hidden = !hasLink;
        noLink.hidden = hasLink;
        if (hasLink) link.href = data.link_berkas;
        else link.removeAttribute('href');
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

    document.querySelectorAll('[data-maintenance-detail]').forEach(button => button.addEventListener('click', () => {
        openModal(JSON.parse(button.dataset.maintenanceDetail));
    }));
    modal.querySelectorAll('[data-maintenance-detail-close]').forEach(button => button.addEventListener('click', closeModal));
    document.addEventListener('keydown', event => {
        if (event.key === 'Escape' && !modal.hidden) closeModal();
    });
})();
</script>
<script>
(() => {
    const canEditExistingDate = <?= json_encode((string) session()->get('auth_role') === 'admin') ?>;
    const form = document.querySelector('[data-vehicle-crud-form]');
    const serviceDate = document.getElementById('maintenanceDate');
    const nextDate = document.getElementById('maintenanceNextDate');
    const budget = document.getElementById('maintenanceBudget');
    const company = document.getElementById('maintenanceCompany');
    const companyHelp = document.getElementById('maintenanceCompanyHelp');
    const lockHint = document.querySelector('[data-service-date-lock]');
    const steps = Array.from(document.querySelectorAll('[data-maintenance-form-step]'));
    const indicators = Array.from(document.querySelectorAll('[data-maintenance-step-indicator]'));
    const backButton = document.querySelector('[data-maintenance-step-back]');
    const nextButton = document.querySelector('[data-maintenance-step-next]');
    const submitButton = form?.querySelector('[data-crud-submit]');
    if (!form || steps.length === 0) return;

    let currentStep = 0;
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
    const synchronizeCompany = (selectionChanged = false) => {
        if (budget.value === 'Kantor') {
            company.value = 'PT. Jaminan Kredit Indonesia (Persero)';
            company.readOnly = true;
            company.setAttribute('aria-readonly', 'true');
            company.required = true;
            company.placeholder = '';
            companyHelp.textContent = 'Terisi otomatis untuk anggaran Kantor.';
            return;
        }

        if (budget.value === 'Asuransi') {
            if (selectionChanged && company.value === 'PT. Jaminan Kredit Indonesia (Persero)') company.value = '';
            company.readOnly = false;
            company.setAttribute('aria-readonly', 'false');
            company.required = true;
            company.placeholder = 'Masukkan nama perusahaan asuransi';
            companyHelp.textContent = 'Isi nama perusahaan asuransi secara manual.';
            return;
        }

        company.value = '';
        company.readOnly = true;
        company.setAttribute('aria-readonly', 'true');
        company.required = false;
        company.placeholder = 'Pilih anggaran service terlebih dahulu';
        companyHelp.textContent = 'Terisi otomatis untuk anggaran Kantor.';
    };
    const showStep = index => {
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
    serviceDate.addEventListener('change', synchronizeNextDate);
    budget.addEventListener('change', () => synchronizeCompany(true));
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
        const editing = event.detail.mode === 'edit';
        synchronizeCompany();
        serviceDate.disabled = editing && !canEditExistingDate;
        serviceDate.title = serviceDate.disabled ? 'Tanggal Servis hanya dapat diubah oleh Administrator.' : '';
        lockHint.hidden = !serviceDate.disabled;
        synchronizeNextDate();
        showStep(0);
    });
    showStep(0);
})();
</script>
<?= view('monitoring_kendaraan/_crud_script') ?>
<?= $this->endSection() ?>
