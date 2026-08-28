<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<?php
$formatDate = static fn (?string $value): string => $value ? date('d-m-Y', strtotime($value)) : '-';
$formatMoney = static fn ($value): string => $value !== null ? 'Rp ' . number_format((float) $value, 0, ',', '.') : '-';
$popupMode = session()->getFlashdata('pks_modal');
$popupData = session()->getFlashdata('pks_form_data') ?? [];
$popupEditId = session()->getFlashdata('pks_edit_id');
$popupErrors = session()->getFlashdata('errors') ?? [];
$summaryCards = [
    '' => ['label' => 'Total PKS', 'icon' => '▤', 'class' => 'total', 'count' => $summary['total']],
    'aktif' => ['label' => 'Aktif', 'icon' => '✓', 'class' => 'active', 'count' => $summary['aktif']],
    'segera' => ['label' => 'Segera Berakhir', 'icon' => '!', 'class' => 'warning', 'count' => $summary['segera']],
    'berakhir' => ['label' => 'Berakhir', 'icon' => '×', 'class' => 'expired', 'count' => $summary['berakhir']],
    'belum' => ['label' => 'Belum Lengkap', 'icon' => '…', 'class' => 'neutral', 'count' => $summary['belum']],
];
?>

<section class="page-heading heading-actions pks-page-heading">
    <div>
        <p class="eyebrow">BAGIAN UMUM 1</p>
        <h1>PKS Barang dan Jasa</h1>
        <p>Kelola mitra, masa berlaku, nilai kerja sama, addendum, dan item pekerjaan dalam satu tempat.</p>
    </div>
    <button type="button" class="btn btn-primary" data-pks-create>＋ Tambah PKS</button>
</section>

<section class="pks-summary-grid" aria-label="Ringkasan PKS">
    <?php foreach ($summaryCards as $statusKey => $card): ?>
        <?php
        $cardQuery = array_filter([
            'q' => $filters['keyword'],
            'status' => $statusKey,
            'per_page' => $filters['perPage'],
        ], static fn ($value): bool => $value !== '');
        $cardUrl = site_url('bagian-umum-1/pks-barang-jasa') . ($cardQuery === [] ? '' : '?' . http_build_query($cardQuery));
        $isActiveCard = $filters['status'] === $statusKey;
        ?>
        <a class="pks-summary-card <?= $isActiveCard ? 'is-active' : '' ?>" href="<?= esc($cardUrl) ?>" <?= $isActiveCard ? 'aria-current="page"' : '' ?>>
            <span class="pks-summary-icon <?= esc($card['class']) ?>"><?= esc($card['icon']) ?></span>
            <div><small><?= esc($card['label']) ?></small><strong><?= number_format($card['count'], 0, ',', '.') ?></strong></div>
        </a>
    <?php endforeach ?>
</section>

<section class="panel filter-panel">
    <form action="<?= site_url('bagian-umum-1/pks-barang-jasa') ?>" method="get" class="pks-filter-form">
        <?php if ($filters['status'] !== ''): ?><input type="hidden" name="status" value="<?= esc($filters['status']) ?>"><?php endif ?>
        <div class="form-group">
            <label for="pksSearch">Cari PKS</label>
            <div class="input-with-icon"><span>⌕</span><input id="pksSearch" name="q" value="<?= esc($filters['keyword']) ?>" placeholder="Nomor PKS, nama kerja sama, mitra, atau unit"></div>
        </div>
        <div class="filter-actions"><button type="submit" class="btn btn-secondary">Terapkan</button><a href="<?= site_url('bagian-umum-1/pks-barang-jasa') ?>" class="btn btn-ghost">Reset</a></div>
    </form>
</section>

<section class="panel register-panel pks-table-panel">
    <div class="table-wrap">
        <table>
            <thead><tr><th>No.</th><th>Nomor PKS</th><th>Nama Kerja Sama</th><th>Mitra</th><th>Dokumen Terakhir</th><th>Masa Berlaku</th><th>Nilai Terakhir</th><th>Status</th><th>Aksi</th></tr></thead>
            <tbody>
            <?php if ($records === []): ?>
                <tr><td colspan="9"><div class="empty-state"><span>▤</span><strong>Belum ada data PKS</strong><p>Tambahkan PKS pertama atau ubah kata kunci pencarian.</p><button class="btn btn-primary btn-sm" type="button" data-pks-create>Tambah PKS</button></div></td></tr>
            <?php else: ?>
                <?php foreach ($records as $index => $record): ?>
                    <tr>
                        <td><strong><?= (($pager->getCurrentPage('pks') - 1) * $filters['perPage']) + $index + 1 ?></strong></td>
                        <td><a class="pks-code-link" href="<?= site_url('bagian-umum-1/pks-barang-jasa/' . $record['id']) ?>"><?= esc($record['kode_internal']) ?></a></td>
                        <td><div class="pks-name-cell"><strong><?= esc($record['nama_kerjasama']) ?></strong><span><?= esc($record['unit_pengelola'] ?: 'Unit belum diisi') ?></span></div></td>
                        <td><?= esc($record['nama_mitra']) ?></td>
                        <td><div class="pks-name-cell"><strong><?= esc($record['nomor_dokumen_terakhir'] ?: 'Belum ada') ?></strong><span><?= (int) $record['jumlah_dokumen'] ?> riwayat</span></div></td>
                        <td><div class="date-cell"><strong><?= $formatDate($record['periode_selesai_terakhir']) ?></strong><span><?= $record['periode_mulai_terakhir'] ? $formatDate($record['periode_mulai_terakhir']) . ' s.d.' : 'Periode belum diisi' ?></span></div></td>
                        <td><strong><?= $formatMoney($record['nilai_terakhir']) ?></strong></td>
                        <td><span class="pks-status <?= esc($record['status_class']) ?>"><?= esc($record['status_label']) ?></span></td>
                        <td><div class="action-buttons">
                            <a class="icon-btn" href="<?= site_url('bagian-umum-1/pks-barang-jasa/' . $record['id']) ?>" title="Lihat detail" aria-label="Lihat detail">⌕</a>
                            <?php $editData = array_intersect_key($record, array_flip(['id','kode_internal','nama_kerjasama','unit_pengelola','pic_internal','nama_mitra','alamat','nama_kontak','jabatan_kontak','telepon','email'])); ?>
                            <button type="button" class="icon-btn" data-pks-edit='<?= esc(json_encode($editData), 'attr') ?>' title="Ubah" aria-label="Ubah PKS">✎</button>
                        </div></td>
                    </tr>
                <?php endforeach ?>
            <?php endif ?>
            </tbody>
        </table>
    </div>
    <div class="table-list-footer">
        <form method="get" action="<?= site_url('bagian-umum-1/pks-barang-jasa') ?>" class="table-length-form">
            <input type="hidden" name="q" value="<?= esc($filters['keyword']) ?>">
            <?php if ($filters['status'] !== ''): ?><input type="hidden" name="status" value="<?= esc($filters['status']) ?>"><?php endif ?>
            <label for="pksPerPage">Tampilkan</label><select id="pksPerPage" name="per_page" onchange="this.form.submit()">
                <?php foreach ([10, 20, 50, 100] as $size): ?><option value="<?= $size ?>" <?= $filters['perPage'] === $size ? 'selected' : '' ?>><?= $size ?></option><?php endforeach ?>
            </select><span>data</span>
        </form>
        <?php if ($records !== []): ?><div class="pagination-wrap"><?= $pager->links('pks') ?></div><?php endif ?>
    </div>
</section>

<div class="pks-main-modal" id="pksMainModal" hidden aria-hidden="true">
    <button type="button" class="modal-backdrop" data-pks-modal-close aria-label="Tutup form PKS"></button>
    <section class="modal-dialog pks-main-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="pksMainModalTitle">
        <header class="modal-header">
            <div class="modal-title-group"><span class="modal-title-icon pks-modal-icon" data-pks-modal-icon>＋</span><div><p>BAGIAN UMUM 1</p><h2 id="pksMainModalTitle" data-pks-modal-title>Tambah PKS Barang dan Jasa</h2></div></div>
            <button type="button" class="modal-close" data-pks-modal-close aria-label="Tutup">×</button>
        </header>
        <form action="<?= site_url('bagian-umum-1/pks-barang-jasa') ?>" method="post" data-pks-main-form>
            <?= csrf_field() ?>
            <div class="modal-body">
                <?php if ($popupErrors !== []): ?><div class="modal-alert pks-popup-errors" role="alert"><strong>Periksa data berikut:</strong><ul><?php foreach ($popupErrors as $popupError): ?><li><?= esc($popupError) ?></li><?php endforeach ?></ul></div><?php endif ?>
                <div class="pks-wizard-steps" aria-label="Tahapan pengisian PKS">
                    <button type="button" data-pks-step-indicator="1"><span>01</span><div><strong>Informasi PKS</strong><small>Data kerja sama</small></div></button>
                    <i aria-hidden="true"></i>
                    <button type="button" data-pks-step-indicator="2"><span>02</span><div><strong>Data Mitra</strong><small>Penyedia barang/jasa</small></div></button>
                    <i aria-hidden="true"></i>
                    <button type="button" data-pks-step-indicator="3"><span>03</span><div><strong>Konfirmasi</strong><small>Periksa sebelum simpan</small></div></button>
                </div>

                <section class="pks-wizard-panel" data-pks-step-panel="1">
                    <div class="modal-section-heading"><span>01</span><div><strong>Identitas Kerja Sama</strong><small>Informasi utama untuk mengenali dan mencari PKS</small></div></div>
                    <div class="modal-form-grid pks-popup-grid">
                        <div class="form-group"><label for="popupKodeInternal">Nomor PKS <span class="required">*</span></label><input id="popupKodeInternal" name="kode_internal" maxlength="80" placeholder="Contoh: PKS/BJ/2026/001" required><small>Nomor unik untuk mengidentifikasi PKS.</small></div>
                        <div class="form-group"><label for="popupUnitPengelola">Unit Pengelola</label><select id="popupUnitPengelola" name="unit_pengelola"><option value="">Pilih unit pengelola</option><option value="Bagian Umum 1">Bagian Umum 1</option><option value="Bagian Umum 2">Bagian Umum 2</option></select></div>
                        <div class="form-group modal-span-2"><label for="popupNamaKerjasama">Nama Kerja Sama <span class="required">*</span></label><input id="popupNamaKerjasama" name="nama_kerjasama" maxlength="250" placeholder="Contoh: Pengadaan Jasa Kebersihan Kantor" required></div>
                        <div class="form-group"><label for="popupPicInternal">PIC Internal</label><input id="popupPicInternal" name="pic_internal" readonly aria-readonly="true" placeholder="Terisi otomatis sesuai unit"><small>Ditentukan otomatis berdasarkan Unit Pengelola.</small></div>
                    </div>
                </section>

                <section class="pks-wizard-panel" data-pks-step-panel="2" hidden>
                    <div class="modal-section-heading"><span>02</span><div><strong>Data Mitra</strong><small>Identitas penyedia dan kontak yang dapat dihubungi</small></div></div>
                    <div class="modal-form-grid pks-popup-grid">
                        <div class="form-group modal-span-2"><label for="popupNamaMitra">Nama Mitra / Penyedia <span class="required">*</span></label><input id="popupNamaMitra" name="nama_mitra" maxlength="200" placeholder="Nama perusahaan atau penyedia" required></div>
                        <div class="form-group modal-span-2"><label for="popupAlamatMitra">Alamat</label><textarea id="popupAlamatMitra" name="alamat" placeholder="Alamat lengkap mitra"></textarea></div>
                        <div class="form-group"><label for="popupNamaKontak">Nama Kontak</label><input id="popupNamaKontak" name="nama_kontak" maxlength="150" placeholder="PIC pihak mitra"></div>
                        <div class="form-group"><label for="popupJabatanKontak">Jabatan Kontak</label><input id="popupJabatanKontak" name="jabatan_kontak" maxlength="150" placeholder="Jabatan PIC mitra"></div>
                        <div class="form-group"><label for="popupTelepon">Telepon</label><input id="popupTelepon" name="telepon" maxlength="50" placeholder="Nomor telepon atau WhatsApp"></div>
                        <div class="form-group"><label for="popupEmail">Email</label><input id="popupEmail" name="email" type="email" maxlength="150" placeholder="email@perusahaan.co.id"></div>
                    </div>
                </section>

                <section class="pks-wizard-panel" data-pks-step-panel="3" hidden>
                    <div class="modal-section-heading"><span>03</span><div><strong>Konfirmasi Data</strong><small>Pastikan data berikut sudah sesuai sebelum disimpan</small></div></div>
                    <div class="pks-review-card">
                        <div><small>Nomor PKS</small><strong data-pks-review="kode_internal">-</strong></div>
                        <div><small>Unit Pengelola</small><strong data-pks-review="unit_pengelola">-</strong></div>
                        <div class="wide"><small>Nama Kerja Sama</small><strong data-pks-review="nama_kerjasama">-</strong></div>
                        <div><small>PIC Internal</small><strong data-pks-review="pic_internal">-</strong></div>
                        <div><small>Nama Mitra</small><strong data-pks-review="nama_mitra">-</strong></div>
                        <div><small>Kontak Mitra</small><strong data-pks-review="kontak_mitra">-</strong></div>
                        <div><small>Telepon / Email</small><strong data-pks-review="komunikasi_mitra">-</strong></div>
                    </div>
                    <div class="pks-review-note"><span>i</span><p>Setelah data utama disimpan, Anda akan diarahkan ke halaman detail untuk menambahkan dokumen PKS, addendum, link berkas, serta item barang dan jasa.</p></div>
                </section>
            </div>
            <footer class="modal-footer"><button type="button" class="btn btn-ghost" data-pks-modal-close>Batal</button><button type="button" class="btn btn-secondary" data-pks-step-back hidden>← Kembali</button><button type="button" class="btn btn-primary" data-pks-step-next>Selanjutnya →</button><button type="submit" class="btn btn-primary" data-pks-modal-submit hidden>Simpan & Lanjutkan</button></footer>
        </form>
    </section>
</div>

<script>
(() => {
    const modal = document.getElementById('pksMainModal');
    const form = modal.querySelector('[data-pks-main-form]');
    const panels = [...modal.querySelectorAll('[data-pks-step-panel]')];
    const indicators = [...modal.querySelectorAll('[data-pks-step-indicator]')];
    const backButton = modal.querySelector('[data-pks-step-back]');
    const nextButton = modal.querySelector('[data-pks-step-next]');
    const submitButton = modal.querySelector('[data-pks-modal-submit]');
    const baseUrl = <?= json_encode(site_url('bagian-umum-1/pks-barang-jasa')) ?>;
    const fieldNames = ['kode_internal','nama_kerjasama','unit_pengelola','pic_internal','nama_mitra','alamat','nama_kontak','jabatan_kontak','telepon','email'];
    const unitField = form.elements.unit_pengelola;
    const picField = form.elements.pic_internal;
    const picByUnit = { 'Bagian Umum 1': 'Angger Wicaksono', 'Bagian Umum 2': 'Agil Halis Kesawa' };
    const syncPicInternal = () => { picField.value = picByUnit[unitField.value] ?? ''; };
    let currentStep = 1;
    const updateReview = () => {
        const value = name => String(form.elements[name]?.value ?? '').trim();
        const review = {
            kode_internal: value('kode_internal'), unit_pengelola: value('unit_pengelola'), nama_kerjasama: value('nama_kerjasama'),
            pic_internal: value('pic_internal'), nama_mitra: value('nama_mitra'),
            kontak_mitra: [value('nama_kontak'), value('jabatan_kontak')].filter(Boolean).join(' · '),
            komunikasi_mitra: [value('telepon'), value('email')].filter(Boolean).join(' · '),
        };
        Object.entries(review).forEach(([name, content]) => { const target = modal.querySelector(`[data-pks-review="${name}"]`); if (target) target.textContent = content || '-'; });
    };
    const goToStep = step => {
        currentStep = Math.max(1, Math.min(3, step));
        panels.forEach(panel => { panel.hidden = Number(panel.dataset.pksStepPanel) !== currentStep; });
        indicators.forEach(indicator => { const number = Number(indicator.dataset.pksStepIndicator); indicator.classList.toggle('active', number === currentStep); indicator.classList.toggle('complete', number < currentStep); });
        backButton.hidden = currentStep === 1;
        nextButton.hidden = currentStep === 3;
        submitButton.hidden = currentStep !== 3;
        if (currentStep === 3) updateReview();
        modal.querySelector('.modal-body').scrollTop = 0;
    };
    const validateCurrentStep = () => {
        const panel = panels.find(item => Number(item.dataset.pksStepPanel) === currentStep);
        const fields = [...panel.querySelectorAll('input,select,textarea')];
        for (const field of fields) { if (!field.checkValidity()) { field.reportValidity(); field.focus(); return false; } }
        return true;
    };
    const open = () => { modal.hidden = false; modal.setAttribute('aria-hidden','false'); requestAnimationFrame(() => modal.classList.add('open')); document.body.classList.add('modal-open'); setTimeout(() => panels.find(panel => !panel.hidden)?.querySelector('input,select,textarea')?.focus(), 100); };
    const close = () => { modal.classList.remove('open'); modal.setAttribute('aria-hidden','true'); setTimeout(() => { modal.hidden = true; document.body.classList.remove('modal-open'); }, 180); };
    const prepare = (data = {}, id = null) => {
        form.reset();
        form.action = id ? `${baseUrl}/${id}` : baseUrl;
        fieldNames.forEach(name => { form.elements[name].value = data[name] ?? ''; });
        syncPicInternal();
        modal.querySelector('[data-pks-modal-title]').textContent = id ? 'Ubah PKS Barang dan Jasa' : 'Tambah PKS Barang dan Jasa';
        modal.querySelector('[data-pks-modal-icon]').textContent = id ? '✎' : '＋';
        submitButton.textContent = id ? 'Simpan Perubahan' : 'Simpan & Lanjutkan';
        goToStep(1);
        open();
    };
    document.querySelectorAll('[data-pks-create]').forEach(button => button.addEventListener('click', () => prepare()));
    document.querySelectorAll('[data-pks-edit]').forEach(button => button.addEventListener('click', () => { const data = JSON.parse(button.dataset.pksEdit); prepare(data, data.id); }));
    unitField.addEventListener('change', syncPicInternal);
    document.querySelectorAll('[data-pks-modal-close]').forEach(button => button.addEventListener('click', close));
    nextButton.addEventListener('click', () => { if (validateCurrentStep()) goToStep(currentStep + 1); });
    backButton.addEventListener('click', () => goToStep(currentStep - 1));
    indicators.forEach(indicator => indicator.addEventListener('click', () => { const target = Number(indicator.dataset.pksStepIndicator); if (target < currentStep || (target === currentStep + 1 && validateCurrentStep())) goToStep(target); }));
    document.addEventListener('keydown', event => { if (event.key === 'Escape' && !modal.hidden) close(); });
    const failedMode = <?= json_encode($popupMode) ?>;
    const failedData = <?= json_encode($popupData, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    const failedEditId = <?= json_encode($popupEditId) ?>;
    const failedStep = <?= json_encode(array_intersect(array_keys($popupErrors), ['nama_mitra','alamat','nama_kontak','jabatan_kontak','telepon','email']) !== [] ? 2 : 1) ?>;
    if (failedMode === 'create') { prepare(failedData); goToStep(failedStep); }
    if (failedMode === 'edit' && failedEditId) { prepare(failedData, failedEditId); goToStep(failedStep); }
})();
</script>
<?= $this->endSection() ?>
