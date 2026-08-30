<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<?php
$baseUrl = site_url('bagian-umum-1/dokumen-spk');
$formatDate = static fn (?string $value): string => $value ? date('d-m-Y', strtotime($value)) : 'Belum diisi';
$yearQuery = $filters['year'] > 0 ? (string) $filters['year'] : 'semua';
$popupRecords = [];
foreach ($records as $record) {
    $popupRecords[(string) $record['id']] = [
        'nomor_urut' => (int) $record['nomor_urut'],
        'nomor_dokumen' => $record['nomor_dokumen'],
        'tanggal_dokumen' => $record['tanggal_dokumen'] ?: '',
        'tanggal' => $record['tanggal_label'],
        'tahun' => (int) $record['tahun'],
        'perihal' => $record['perihal'],
        'link_berkas' => $record['link_berkas'] ?: '',
        'kelengkapan' => $record['kelengkapan_label'],
        'kelengkapan_class' => $record['kelengkapan_class'],
        'dibuat_oleh' => $record['created_by_name'] ?: 'Data awal sistem',
        'diperbarui' => $record['updated_label'],
    ];
}
?>

<section class="page-heading heading-actions spk-page-heading">
    <div>
        <p class="eyebrow">BAGIAN UMUM 1</p>
        <h1>Dokumen SPK</h1>
        <p>Register Surat Perintah Kerja beserta tautan dokumennya.</p>
    </div>
    <button class="btn btn-primary" type="button" data-spk-create data-spk-create-year="<?= (int) ($filters['year'] ?: date('Y')) ?>">＋ Tambah Dokumen</button>
</section>

<section class="panel filter-panel spk-filter-panel">
    <form action="<?= $baseUrl ?>" method="get" class="spk-filter-form">
        <div class="form-group search-group">
            <label for="spkSearch">Cari dokumen</label>
            <div class="input-with-icon"><span>⌕</span><input id="spkSearch" name="q" type="search" value="<?= esc($filters['keyword']) ?>" placeholder="Nomor SPK atau perihal"></div>
        </div>
        <div class="form-group">
            <label for="spkYear">Tahun</label>
            <select id="spkYear" name="tahun">
                <option value="semua" <?= $filters['year'] === 0 ? 'selected' : '' ?>>Semua tahun</option>
                <?php foreach ($years as $year): ?><option value="<?= $year ?>" <?= $filters['year'] === $year ? 'selected' : '' ?>><?= $year ?></option><?php endforeach ?>
            </select>
        </div>
        <div class="form-group">
            <label for="spkCompleteness">Kelengkapan</label>
            <select id="spkCompleteness" name="kelengkapan"><option value="">Semua kondisi</option><option value="lengkap" <?= $filters['completeness'] === 'lengkap' ? 'selected' : '' ?>>Lengkap</option><option value="belum_lengkap" <?= $filters['completeness'] === 'belum_lengkap' ? 'selected' : '' ?>>Belum Lengkap</option></select>
        </div>
        <input type="hidden" name="per_page" value="<?= $filters['perPage'] ?>">
        <div class="filter-actions"><button class="btn btn-secondary" type="submit">Terapkan</button><a class="btn btn-ghost" href="<?= $baseUrl ?>">Reset</a></div>
    </form>
</section>

<section class="panel register-panel spk-table-panel">
    <div class="panel-header spk-register-header">
        <div><h2>Register Dokumen<?= $filters['year'] ? ' Tahun ' . $filters['year'] : '' ?></h2><p>Dokumen dengan tanggal atau link kosong otomatis ditandai belum lengkap.</p></div>
        <span><?= number_format($total, 0, ',', '.') ?> dokumen</span>
    </div>
    <div class="table-wrap">
        <table class="spk-register-table">
            <thead><tr><th>No.</th><th>Nomor SPK</th><th>Tanggal</th><th>Perihal</th><th>Berkas</th><th>Status</th><th>Aksi</th></tr></thead>
            <tbody>
            <?php if ($records === []): ?>
                <tr><td colspan="7"><div class="empty-state"><span>▤</span><strong>Dokumen tidak ditemukan</strong><p>Ubah filter atau tambahkan dokumen baru.</p><button class="btn btn-primary btn-sm" type="button" data-spk-create data-spk-create-year="<?= (int) ($filters['year'] ?: date('Y')) ?>">Tambah Dokumen</button></div></td></tr>
            <?php else: ?>
                <?php foreach ($records as $index => $record): ?>
                    <tr>
                        <td><strong><?= (($pager->getCurrentPage('dokumen_spk') - 1) * $filters['perPage']) + $index + 1 ?></strong><small class="spk-sequence">Register <?= (int) $record['nomor_urut'] ?></small></td>
                        <td><div class="spk-number-cell"><button type="button" class="spk-number-trigger" data-spk-view="<?= (int) $record['id'] ?>"><?= esc($record['nomor_dokumen']) ?></button><small>Tahun <?= (int) $record['tahun'] ?></small></div></td>
                        <td><div class="date-cell"><strong><?= $formatDate($record['tanggal_dokumen']) ?></strong><span><?= $record['tanggal_dokumen'] ? date('l', strtotime($record['tanggal_dokumen'])) : 'Perlu dilengkapi' ?></span></div></td>
                        <td><p class="spk-subject-cell"><?= esc($record['perihal']) ?></p></td>
                        <td><?php if ($record['link_berkas']): ?><a class="btn btn-secondary btn-sm spk-file-button" href="<?= esc($record['link_berkas'], 'attr') ?>" target="_blank" rel="noopener noreferrer">↗ Buka Berkas</a><?php else: ?><span class="spk-missing-value">Belum ada link</span><?php endif ?></td>
                        <td><span class="spk-completeness <?= esc($record['kelengkapan_class']) ?>"><?= esc($record['kelengkapan_label']) ?></span></td>
                        <td><div class="action-buttons"><button class="icon-btn" type="button" data-spk-view="<?= (int) $record['id'] ?>" title="Lihat detail">⌕</button><button class="icon-btn" type="button" data-spk-edit="<?= (int) $record['id'] ?>" title="Ubah dokumen">✎</button></div></td>
                    </tr>
                <?php endforeach ?>
            <?php endif ?>
            </tbody>
        </table>
    </div>
    <div class="table-list-footer">
        <form method="get" action="<?= $baseUrl ?>" class="table-length-form">
            <input type="hidden" name="q" value="<?= esc($filters['keyword']) ?>">
            <input type="hidden" name="tahun" value="<?= esc($yearQuery) ?>">
            <input type="hidden" name="kelengkapan" value="<?= esc($filters['completeness']) ?>">
            <label for="spkPerPage">Tampilkan</label><select id="spkPerPage" name="per_page" onchange="this.form.submit()"><?php foreach ([10, 20, 50, 100] as $size): ?><option value="<?= $size ?>" <?= $filters['perPage'] === $size ? 'selected' : '' ?>><?= $size ?></option><?php endforeach ?></select><span>data</span>
        </form>
        <?php if ($records !== []): ?><div class="pagination-wrap"><?= $pager->links('dokumen_spk') ?></div><?php endif ?>
    </div>
</section>

<div class="input-modal spk-edit-modal" id="spkEditModal" hidden aria-hidden="true">
    <button type="button" class="modal-backdrop" data-spk-edit-close aria-label="Tutup form edit"></button>
    <section class="modal-dialog spk-edit-dialog" role="dialog" aria-modal="true" aria-labelledby="spkEditTitle">
        <header class="modal-header">
            <div class="modal-title-group"><span class="modal-title-icon" data-spk-form-icon>✎</span><div><p data-spk-form-eyebrow>UBAH DOKUMEN SPK</p><h2 id="spkEditTitle" data-spk-form-title>Edit Data SPK</h2></div></div>
            <button type="button" class="modal-close" data-spk-edit-close aria-label="Tutup form edit">×</button>
        </header>
        <form method="post" action="" data-spk-edit-form>
            <?= csrf_field() ?>
            <div class="modal-alert" data-spk-edit-errors hidden role="alert"></div>
            <div class="modal-body spk-edit-body">
                <div class="modal-form-grid">
                    <div class="form-group"><label for="spkEditYear">Tahun Register <span class="required">*</span></label><input id="spkEditYear" name="tahun" type="number" min="2000" max="2100" required></div>
                    <div class="form-group"><label for="spkEditType">Jenis Dokumen</label><input id="spkEditType" value="SPK · Surat Perintah Kerja" readonly tabindex="-1"><input type="hidden" name="jenis_dokumen" value="SPK"></div>
                    <div class="form-group modal-span-2"><label for="spkEditNumber">Nomor SPK <span class="required">*</span></label><div class="spk-number-generator"><input id="spkEditNumber" name="nomor_dokumen" readonly required><button class="btn btn-secondary" type="button" data-spk-edit-generate>Generate Nomor SPK</button></div><small data-spk-edit-number-hint>Nomor urut dan bulan pembuatan nomor dikelola otomatis oleh sistem.</small></div>
                    <div class="form-group"><label for="spkEditDate">Tanggal Dokumen</label><input id="spkEditDate" name="tanggal_dokumen" type="date"></div>
                    <div class="form-group"><label for="spkEditLink">Link Berkas</label><input id="spkEditLink" name="link_berkas" type="url" maxlength="2048" placeholder="https://..."></div>
                    <div class="form-group modal-span-2"><label for="spkEditSubject">Perihal <span class="required">*</span></label><textarea id="spkEditSubject" name="perihal" maxlength="5000" rows="5" required></textarea></div>
                </div>
            </div>
            <footer class="modal-footer"><span class="modal-submit-status" data-spk-edit-status></span><button type="button" class="btn btn-ghost" data-spk-edit-close>Batal</button><button type="submit" class="btn btn-primary" data-spk-edit-submit>Simpan Perubahan</button></footer>
        </form>
    </section>
</div>

<div class="input-modal spk-view-modal" id="spkDetailModal" hidden aria-hidden="true">
    <button type="button" class="modal-backdrop" data-spk-modal-close aria-label="Tutup detail"></button>
    <section class="modal-dialog spk-view-dialog" role="dialog" aria-modal="true" aria-labelledby="spkDetailTitle">
        <header class="modal-header">
            <div class="modal-title-group"><span class="modal-title-icon">▤</span><div><p>DETAIL DOKUMEN SPK</p><h2 id="spkDetailTitle" data-spk-detail="nomor_dokumen">-</h2></div></div>
            <button type="button" class="modal-close" data-spk-modal-close aria-label="Tutup detail">×</button>
        </header>
        <div class="modal-body spk-view-body">
            <div class="spk-popup-status">
                <div><span class="spk-type-badge spk">SPK</span><small>Surat Perintah Kerja</small></div>
                <span class="spk-completeness" data-spk-detail-status>-</span>
            </div>
            <dl class="spk-detail-grid spk-popup-grid">
                <div><dt>Nomor Urut</dt><dd data-spk-detail="nomor_urut">-</dd></div>
                <div><dt>Tahun Register</dt><dd data-spk-detail="tahun">-</dd></div>
                <div><dt>Tanggal Dokumen</dt><dd data-spk-detail="tanggal">-</dd></div>
                <div><dt>Dibuat Oleh</dt><dd data-spk-detail="dibuat_oleh">-</dd></div>
                <div class="wide"><dt>Perihal</dt><dd data-spk-detail="perihal">-</dd></div>
                <div class="wide"><dt>Terakhir Diperbarui</dt><dd data-spk-detail="diperbarui">-</dd></div>
            </dl>
        </div>
        <footer class="modal-footer"><a class="btn btn-secondary" href="#" target="_blank" rel="noopener noreferrer" data-spk-file-link hidden>↗ Buka Berkas</a><button type="button" class="btn btn-primary" data-spk-modal-close>Tutup</button></footer>
    </section>
</div>

<script>
(() => {
    const modal = document.getElementById('spkDetailModal');
    if (!modal) return;
    const records = <?= json_encode($popupRecords, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    const closeButtons = modal.querySelectorAll('[data-spk-modal-close]');
    const fileLink = modal.querySelector('[data-spk-file-link]');
    const status = modal.querySelector('[data-spk-detail-status]');
    let lastTrigger = null;

    const closeModal = () => {
        modal.classList.remove('open');
        modal.setAttribute('aria-hidden', 'true');
        window.setTimeout(() => { modal.hidden = true; lastTrigger?.focus(); }, 180);
    };
    const openModal = (trigger) => {
        const record = records[trigger.dataset.spkView];
        if (!record) return;
        lastTrigger = trigger;
        modal.querySelectorAll('[data-spk-detail]').forEach((field) => {
            field.textContent = String(record[field.dataset.spkDetail] ?? '-');
        });
        status.textContent = record.kelengkapan;
        status.className = 'spk-completeness ' + record.kelengkapan_class;
        if (record.link_berkas) {
            fileLink.href = record.link_berkas;
            fileLink.hidden = false;
        } else {
            fileLink.removeAttribute('href');
            fileLink.hidden = true;
        }
        modal.hidden = false;
        modal.setAttribute('aria-hidden', 'false');
        requestAnimationFrame(() => modal.classList.add('open'));
        window.setTimeout(() => modal.querySelector('.modal-close')?.focus(), 180);
    };

    document.querySelectorAll('[data-spk-view]').forEach((trigger) => trigger.addEventListener('click', () => openModal(trigger)));
    closeButtons.forEach((button) => button.addEventListener('click', closeModal));
    document.addEventListener('keydown', (event) => { if (event.key === 'Escape' && !modal.hidden) closeModal(); });
})();
</script>

<script>
(() => {
    const modal = document.getElementById('spkEditModal');
    if (!modal) return;
    const records = <?= json_encode($popupRecords, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    const baseUrl = <?= json_encode($baseUrl, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    const generateUrl = <?= json_encode($baseUrl . '/generate-nomor', JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    const form = modal.querySelector('[data-spk-edit-form]');
    const errorsBox = modal.querySelector('[data-spk-edit-errors]');
    const statusText = modal.querySelector('[data-spk-edit-status]');
    const submitButton = modal.querySelector('[data-spk-edit-submit]');
    const generateButton = modal.querySelector('[data-spk-edit-generate]');
    const numberHint = modal.querySelector('[data-spk-edit-number-hint]');
    const formIcon = modal.querySelector('[data-spk-form-icon]');
    const formEyebrow = modal.querySelector('[data-spk-form-eyebrow]');
    const formTitle = modal.querySelector('[data-spk-form-title]');
    const fields = {
        year: document.getElementById('spkEditYear'),
        number: document.getElementById('spkEditNumber'),
        date: document.getElementById('spkEditDate'),
        link: document.getElementById('spkEditLink'),
        subject: document.getElementById('spkEditSubject'),
    };
    let currentId = 0;
    let lastTrigger = null;

    const escapeHtml = (value) => String(value ?? '')
        .replaceAll('&', '&amp;').replaceAll('<', '&lt;').replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;').replaceAll("'", '&#039;');
    const updateCsrf = (result) => {
        if (!result?.csrfToken || !result?.csrfHash) return;
        const token = form.querySelector(`[name="${CSS.escape(result.csrfToken)}"]`);
        if (token) token.value = result.csrfHash;
    };
    const showErrors = (message, errors = {}) => {
        const items = Object.values(errors);
        errorsBox.innerHTML = '<strong>' + escapeHtml(message) + '</strong>' + (items.length ? '<ul>' + items.map((item) => '<li>' + escapeHtml(item) + '</li>').join('') + '</ul>' : '');
        errorsBox.hidden = false;
    };
    const closeModal = () => {
        modal.classList.remove('open');
        modal.setAttribute('aria-hidden', 'true');
        window.setTimeout(() => { modal.hidden = true; lastTrigger?.focus(); }, 180);
    };
    const showModal = (trigger) => {
        lastTrigger = trigger;
        errorsBox.hidden = true;
        errorsBox.innerHTML = '';
        statusText.textContent = '';
        modal.hidden = false;
        modal.setAttribute('aria-hidden', 'false');
        requestAnimationFrame(() => modal.classList.add('open'));
        window.setTimeout(() => fields.year.focus(), 180);
    };
    const openEditModal = (trigger) => {
        const record = records[trigger.dataset.spkEdit];
        if (!record) return;
        currentId = Number(trigger.dataset.spkEdit);
        form.action = baseUrl + '/' + currentId;
        formIcon.textContent = '✎';
        formEyebrow.textContent = 'UBAH DOKUMEN SPK';
        formTitle.textContent = 'Edit Data SPK';
        submitButton.textContent = 'Simpan Perubahan';
        fields.year.value = record.tahun;
        fields.number.value = record.nomor_dokumen;
        fields.date.value = record.tanggal_dokumen;
        fields.link.value = record.link_berkas;
        fields.subject.value = record.perihal;
        numberHint.textContent = 'Nomor SPK tersimpan tidak berubah ketika Tanggal Dokumen diisi atau diperbarui.';
        numberHint.classList.remove('is-warning');
        showModal(trigger);
    };
    const openCreateModal = (trigger) => {
        currentId = 0;
        form.action = baseUrl;
        formIcon.textContent = '＋';
        formEyebrow.textContent = 'TAMBAH DOKUMEN SPK';
        formTitle.textContent = 'Tambah Data SPK';
        submitButton.textContent = 'Simpan Dokumen';
        fields.year.value = trigger.dataset.spkCreateYear || String(new Date().getFullYear());
        fields.number.value = '';
        fields.date.value = '';
        fields.link.value = '';
        fields.subject.value = '';
        numberHint.textContent = 'Klik Generate Nomor SPK setelah Tahun Register sesuai.';
        numberHint.classList.remove('is-warning');
        showModal(trigger);
    };
    const requireRegeneration = () => {
        if (modal.hidden || !fields.number.value) return;
        fields.number.value = '';
        numberHint.textContent = 'Tahun berubah. Generate ulang nomor SPK sebelum menyimpan.';
        numberHint.classList.add('is-warning');
    };
    const generateNumber = async () => {
        const year = Number.parseInt(fields.year.value, 10);
        if (!Number.isInteger(year) || year < 2000 || year > 2100) {
            fields.number.value = '';
            numberHint.textContent = 'Isi Tahun Register dengan benar terlebih dahulu.';
            numberHint.classList.add('is-warning');
            return;
        }
        generateButton.disabled = true;
        generateButton.textContent = 'Membuat nomor...';
        try {
            const params = new URLSearchParams({ tahun: String(year), id: String(currentId) });
            const response = await fetch(generateUrl + '?' + params.toString(), { headers: { Accept: 'application/json' } });
            const result = await response.json();
            if (!response.ok || !result.success) throw new Error(result.message || 'Nomor SPK gagal dibuat.');
            fields.number.value = result.number;
            numberHint.textContent = 'Nomor urut ' + result.sequence + ' berhasil disesuaikan otomatis.';
            numberHint.classList.remove('is-warning');
        } catch (error) {
            fields.number.value = '';
            numberHint.textContent = error.message || 'Nomor SPK gagal dibuat.';
            numberHint.classList.add('is-warning');
        } finally {
            generateButton.disabled = false;
            generateButton.textContent = 'Generate Nomor SPK';
        }
    };

    document.querySelectorAll('[data-spk-create]').forEach((trigger) => trigger.addEventListener('click', () => openCreateModal(trigger)));
    document.querySelectorAll('[data-spk-edit]').forEach((trigger) => trigger.addEventListener('click', () => openEditModal(trigger)));
    modal.querySelectorAll('[data-spk-edit-close]').forEach((button) => button.addEventListener('click', closeModal));
    fields.year.addEventListener('input', requireRegeneration);
    generateButton.addEventListener('click', generateNumber);
    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        if (!form.reportValidity()) return;
        errorsBox.hidden = true;
        statusText.textContent = 'Menyimpan perubahan...';
        submitButton.disabled = true;
        try {
            const response = await fetch(form.action, {
                method: 'POST',
                body: new FormData(form),
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            });
            const contentType = response.headers.get('content-type') || '';
            const result = contentType.includes('application/json') ? await response.json() : { success: false, message: 'Respons server tidak valid. Muat ulang halaman dan coba kembali.' };
            updateCsrf(result);
            if (!response.ok || !result.success) {
                showErrors(result.message || 'Perubahan gagal disimpan.', result.errors || {});
                statusText.textContent = '';
                return;
            }
            statusText.textContent = 'Berhasil disimpan';
            window.location.reload();
        } catch (error) {
            showErrors(error.message || 'Tidak dapat terhubung ke server.');
            statusText.textContent = '';
        } finally {
            submitButton.disabled = false;
        }
    });
    document.addEventListener('keydown', (event) => { if (event.key === 'Escape' && !modal.hidden) closeModal(); });
})();
</script>

<?= $this->endSection() ?>
