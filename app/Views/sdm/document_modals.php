<div class="agendaris-detail-modal" id="sdmDocumentViewModal" hidden aria-hidden="true">
    <button type="button" class="modal-backdrop" data-sdm-document-view-close aria-label="Tutup detail"></button>
    <section class="modal-dialog agendaris-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="sdmDocumentViewTitle">
        <header class="modal-header">
            <div class="modal-title-group"><span class="modal-title-icon">▦</span><div><p>DETAIL SDM &amp; TELLER</p><h2 id="sdmDocumentViewTitle">Surat Masuk</h2></div></div>
            <button type="button" class="modal-close" data-sdm-document-view-close aria-label="Tutup">×</button>
        </header>
        <div class="agendaris-detail-content">
            <section class="agendaris-detail-section" aria-labelledby="sdmDetailSuratHeading">
                <div class="modal-section-heading agendaris-detail-section-heading"><span>01</span><div><strong id="sdmDetailSuratHeading">Informasi Surat Masuk</strong><small>Informasi penerimaan dan identitas dokumen</small></div></div>
                <dl class="agendaris-detail-grid">
                    <div class="span-2"><dt>Sumber Data</dt><dd data-sdm-detail="sumber_data">-</dd></div>
                    <div><dt>Pengirim</dt><dd data-sdm-detail="pengirim">-</dd></div>
                    <div><dt>Penerima</dt><dd data-sdm-detail="penerima">-</dd></div>
                    <div><dt>Penyerahan</dt><dd data-sdm-detail="pengambilan">-</dd></div>
                    <div data-sdm-penyerahan-time hidden><dt>Waktu Penyerahan</dt><dd data-sdm-detail="penyerahan_at">-</dd></div>
                    <div><dt>Jenis</dt><dd data-sdm-detail="jenis">-</dd></div>
                    <div><dt>Tanggal Diterima</dt><dd data-sdm-detail="tanggal_diterima">-</dd></div>
                </dl>
            </section>
            <section class="agendaris-detail-section" aria-labelledby="sdmDetailAgendaHeading">
                <div class="modal-section-heading agendaris-detail-section-heading"><span>02</span><div><strong id="sdmDetailAgendaHeading">Data Agendaris</strong><small>Nomor surat, data agenda, dan tautan dokumen</small></div></div>
                <dl class="agendaris-detail-grid">
                    <div class="span-2"><dt>Perihal Surat</dt><dd data-sdm-detail="perihal_surat">-</dd></div>
                    <div><dt>Nomor Surat</dt><dd data-sdm-detail="nomor_surat">-</dd></div>
                    <div><dt>Tanggal Surat</dt><dd data-sdm-detail="tanggal_surat">-</dd></div>
                    <div><dt>Nomor Agendaris</dt><dd data-sdm-detail="nomor_agendaris">-</dd></div>
                    <div><dt>Tanggal Agendaris</dt><dd data-sdm-detail="tanggal_agendaris">-</dd></div>
                    <div class="span-2"><dt>Link Berkas</dt><dd><a class="agenda-file-link" href="#" target="_blank" rel="noopener noreferrer" data-sdm-detail-link hidden>Buka berkas ↗</a><span data-sdm-detail-no-link>Belum ada link berkas</span></dd></div>
                </dl>
            </section>
            <section class="agendaris-detail-section disposition-detail-section" aria-labelledby="sdmDetailDispositionHeading">
                <div class="modal-section-heading agendaris-detail-section-heading"><span>03</span><div><strong id="sdmDetailDispositionHeading">Tracking Disposisi</strong><small>Posisi, status, waktu, dan catatan setiap tahap</small></div></div>
                <div class="disposition-detail-timeline disposition-history-compact" data-sdm-disposition-history></div>
            </section>
            <section class="agendaris-detail-section" aria-labelledby="sdmDetailStatusHeading">
                <div class="modal-section-heading agendaris-detail-section-heading"><span>04</span><div><strong id="sdmDetailStatusHeading">Status Dokumen</strong><small>Status akhir penyelesaian Dokumen Masuk</small></div></div>
                <dl class="agendaris-detail-grid"><div class="span-2"><dt>Progres</dt><dd data-sdm-detail="progres">-</dd></div></dl>
            </section>
        </div>
        <footer class="modal-footer"><button type="button" class="btn btn-ghost" data-sdm-document-view-close>Tutup</button><button type="button" class="btn btn-primary" data-sdm-detail-edit>✎ Ubah data</button></footer>
    </section>
</div>

<div class="agendaris-form-modal" id="sdmDocumentEditModal" data-max-disposition-steps="<?= (int) \Config\Disposition::MAX_STEPS ?>" hidden aria-hidden="true">
    <button type="button" class="modal-backdrop" data-sdm-document-edit-close aria-label="Tutup edit"></button>
    <section class="modal-dialog agendaris-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="sdmDocumentEditTitle">
        <header class="modal-header">
            <div class="modal-title-group"><span class="modal-title-icon">✎</span><div><p>SDM &amp; TELLER</p><h2 id="sdmDocumentEditTitle">Edit Surat Masuk</h2></div></div>
            <button type="button" class="modal-close" data-sdm-document-edit-close aria-label="Tutup">×</button>
        </header>
        <form method="post" action="" class="sdm-document-edit-form" data-sdm-document-edit-form>
            <?= csrf_field() ?>
            <input type="hidden" name="add_disposition" value="0" data-sdm-add-disposition-value>
            <div class="modal-alert" data-sdm-document-edit-errors hidden role="alert"></div>
            <div class="outgoing-distribution-steps" aria-label="Tahapan edit Dokumen Masuk">
                <span class="active" data-sdm-step-indicator="1"><b>01</b> Informasi Surat</span><i></i>
                <span data-sdm-step-indicator="2"><b>02</b> Data Agendaris</span><i></i>
                <span data-sdm-step-indicator="3"><b>03</b> Disposisi</span><i></i>
                <span data-sdm-step-indicator="4"><b>04</b> Status Dokumen</span>
            </div>

            <div class="modal-body agendaris-modal-body outgoing-distribution-step sdm-document-edit-body" data-sdm-edit-step="1">
                <div class="modal-section-heading"><span>01</span><div><strong>Data surat</strong><small>Informasi surat berasal dari Agendaris dan hanya dapat dilihat</small></div></div>
                <div class="modal-form-grid agendaris-form-grid">
                    <div class="form-group field-source-locked"><label for="sdm_edit_pengirim">Pengirim</label><input id="sdm_edit_pengirim" data-sdm-edit-field="pengirim" readonly aria-readonly="true"></div>
                    <div class="form-group field-source-locked"><label for="sdm_edit_tanggal_diterima">Tanggal Diterima</label><input id="sdm_edit_tanggal_diterima" type="date" data-sdm-edit-field="tanggal_diterima" readonly aria-readonly="true"></div>
                    <div class="form-group field-source-locked"><label for="sdm_edit_penerima">Penerima</label><input id="sdm_edit_penerima" data-sdm-edit-field="penerima" readonly aria-readonly="true"></div>
                    <div class="form-group field-source-locked"><label for="sdm_edit_pengambilan">Penyerahan</label><input id="sdm_edit_pengambilan" data-sdm-edit-field="pengambilan" readonly aria-readonly="true"></div>
                    <div class="form-group field-source-locked"><label for="sdm_edit_jenis">Jenis</label><input id="sdm_edit_jenis" data-sdm-edit-field="jenis" readonly aria-readonly="true"></div>
                    <div class="form-group field-source-locked"><label for="sdm_edit_perihal">Perihal Surat</label><input id="sdm_edit_perihal" data-sdm-edit-field="perihal_surat" readonly aria-readonly="true"></div>
                </div>
            </div>

            <div class="modal-body agendaris-modal-body outgoing-distribution-step sdm-document-edit-body" data-sdm-edit-step="2" hidden>
                <div class="modal-section-heading"><span>02</span><div><strong>Data Agendaris</strong><small>Data agenda dikunci agar identitas surat tetap konsisten</small></div></div>
                <div class="modal-form-grid agendaris-form-grid">
                    <div class="form-group field-source-locked"><label for="sdm_edit_nomor_surat">Nomor Surat</label><input id="sdm_edit_nomor_surat" data-sdm-edit-field="nomor_surat" readonly aria-readonly="true"></div>
                    <div class="form-group field-source-locked"><label for="sdm_edit_tanggal_surat">Tanggal Surat</label><input id="sdm_edit_tanggal_surat" type="date" data-sdm-edit-field="tanggal_surat" readonly aria-readonly="true"></div>
                    <div class="form-group field-source-locked"><label for="sdm_edit_nomor_agendaris">Nomor Agendaris</label><input id="sdm_edit_nomor_agendaris" data-sdm-edit-field="nomor_agendaris" readonly aria-readonly="true"></div>
                    <div class="form-group field-source-locked"><label for="sdm_edit_tanggal_agendaris">Tanggal Agendaris</label><input id="sdm_edit_tanggal_agendaris" type="date" data-sdm-edit-field="tanggal_agendaris" readonly aria-readonly="true"></div>
                    <div class="form-group modal-span-2 field-source-locked"><label for="sdm_edit_berkas">Link Berkas</label><input id="sdm_edit_berkas" data-sdm-edit-field="berkas_link" readonly aria-readonly="true" placeholder="Belum ada link berkas"></div>
                </div>
            </div>

            <div class="modal-body agendaris-modal-body outgoing-distribution-step sdm-document-edit-body" data-sdm-edit-step="3" hidden>
                <div class="modal-section-heading"><span>03</span><div><strong>Tracking Disposisi</strong><small>Perbarui tahap aktif atau teruskan kepada penerima berikutnya</small></div></div>
                <section class="disposition-edit-history" data-sdm-edit-history-section hidden aria-label="Riwayat disposisi sebelumnya">
                    <header><div><strong>Riwayat Disposisi Sebelumnya</strong><small>Tahap yang telah dilewati sebelum disposisi aktif</small></div><span>Baca saja</span></header>
                    <div class="disposition-detail-timeline disposition-history-compact" data-sdm-edit-history></div>
                </section>
                <div class="disposition-form-timeline">
                    <article class="disposition-form-stage">
                        <div class="disposition-stage-marker"><span data-sdm-current-step>01</span><i></i></div>
                        <div class="disposition-stage-panel">
                            <header><div><strong>Disposisi Terakhir</strong><small>Tahap yang saat ini diterima oleh akun Anda</small></div><span class="disposition-stage-state filled">Aktif</span></header>
                            <div class="disposition-stage-fields">
                                <div class="form-group disposition-recipient-field field-source-locked"><label for="sdm_current_recipient">Tujuan / Penerima</label><input id="sdm_current_recipient" data-sdm-current-recipient readonly aria-readonly="true"></div>
                                <div class="form-group"><label for="sdm_current_status">Status <span class="required">*</span></label><select id="sdm_current_status" name="current_status" required><?php foreach ($statusOptions as $status): ?><option value="<?= esc($status, 'attr') ?>"><?= esc($status) ?></option><?php endforeach ?></select></div>
                                <div class="form-group"><label for="sdm_current_date">Tanggal</label><input id="sdm_current_date" type="date" name="current_date"></div>
                                <div class="form-group disposition-note-field"><label for="sdm_current_note">Catatan / Instruksi</label><textarea id="sdm_current_note" name="current_note" maxlength="1000" rows="2" placeholder="Instruksi atau hasil tindak lanjut"></textarea></div>
                            </div>
                        </div>
                    </article>
                    <article class="disposition-form-stage sdm-next-disposition" data-sdm-next-disposition hidden>
                        <div class="disposition-stage-marker"><span data-sdm-next-step>02</span><i></i></div>
                        <div class="disposition-stage-panel">
                            <header><div><strong>Disposisi Berikutnya</strong><small>Dokumen akan diteruskan kepada penerima baru</small></div><span class="disposition-stage-state">Baru</span></header>
                            <div class="disposition-stage-fields">
                                <div class="form-group disposition-recipient-field"><label for="sdm_next_recipient">Tujuan / Penerima <span class="required">*</span></label><select id="sdm_next_recipient" name="next_recipient"><option value="">Pilih tujuan / penerima</option><?php foreach ($recipientOptions as $recipient): ?><option value="<?= esc($recipient, 'attr') ?>"><?= esc($recipient) ?></option><?php endforeach ?></select></div>
                                <div class="form-group"><label for="sdm_next_status">Status <span class="required">*</span></label><select id="sdm_next_status" name="next_status"><?php foreach ($statusOptions as $status): ?><option value="<?= esc($status, 'attr') ?>"><?= esc($status) ?></option><?php endforeach ?></select></div>
                                <div class="form-group"><label for="sdm_next_date">Tanggal <span class="required">*</span></label><input id="sdm_next_date" type="date" name="next_date"></div>
                                <div class="form-group disposition-note-field"><label for="sdm_next_note">Catatan / Instruksi</label><textarea id="sdm_next_note" name="next_note" maxlength="1000" rows="2" placeholder="Instruksi untuk penerima berikutnya"></textarea></div>
                            </div>
                        </div>
                    </article>
                </div>
                <div class="sdm-add-disposition-control"><button type="button" class="btn btn-outline" data-sdm-add-disposition>＋ Tambah Disposisi</button><small data-sdm-disposition-capacity hidden>Lima tahap disposisi sudah terisi dan tidak dapat ditambah lagi.</small></div>
            </div>

            <div class="modal-body agendaris-modal-body outgoing-distribution-step sdm-document-edit-body" data-sdm-edit-step="4" hidden>
                <div class="modal-section-heading"><span>04</span><div><strong>Status Dokumen</strong><small>Periksa kembali sebelum menyimpan perubahan disposisi</small></div></div>
                <div class="modal-form-grid agendaris-form-grid">
                    <div class="form-group modal-span-2 field-source-locked"><label for="sdm_edit_progres">Progres</label><input id="sdm_edit_progres" data-sdm-edit-field="progres" readonly aria-readonly="true"></div>
                    <div class="form-group modal-span-2 sdm-edit-confirmation"><strong>Data utama surat tidak akan berubah.</strong><small>Hanya status, tanggal, catatan, dan penerusan disposisi yang akan disimpan dari akun SDM &amp; Teller.</small></div>
                </div>
            </div>

            <footer class="modal-footer"><span class="modal-submit-status" data-sdm-edit-status></span><button type="button" class="btn btn-ghost" data-sdm-document-edit-close>Batal</button><button type="button" class="btn btn-secondary" data-sdm-step-back hidden>← Kembali</button><button type="button" class="btn btn-primary" data-sdm-step-next>Selanjutnya →</button><button type="submit" class="btn btn-primary" data-sdm-document-edit-submit hidden>Simpan perubahan</button></footer>
        </form>
    </section>
</div>

<script>
(() => {
    const viewModal = document.getElementById('sdmDocumentViewModal');
    const editModal = document.getElementById('sdmDocumentEditModal');
    const editForm = editModal?.querySelector('[data-sdm-document-edit-form]');
    const errorBox = editModal?.querySelector('[data-sdm-document-edit-errors]');
    const submitButton = editModal?.querySelector('[data-sdm-document-edit-submit]');
    const submitStatus = editModal?.querySelector('[data-sdm-edit-status]');
    const addButton = editModal?.querySelector('[data-sdm-add-disposition]');
    const addValue = editModal?.querySelector('[data-sdm-add-disposition-value]');
    const nextSection = editModal?.querySelector('[data-sdm-next-disposition]');
    const capacityMessage = editModal?.querySelector('[data-sdm-disposition-capacity]');
    const editHistorySection = editModal?.querySelector('[data-sdm-edit-history-section]');
    const editHistory = editModal?.querySelector('[data-sdm-edit-history]');
    const stepPanels = Array.from(editModal?.querySelectorAll('[data-sdm-edit-step]') || []);
    const stepIndicators = Array.from(editModal?.querySelectorAll('[data-sdm-step-indicator]') || []);
    const stepBack = editModal?.querySelector('[data-sdm-step-back]');
    const stepNext = editModal?.querySelector('[data-sdm-step-next]');
    const detailEdit = viewModal?.querySelector('[data-sdm-detail-edit]');
    const maxDispositionSteps = Number(editModal?.dataset.maxDispositionSteps || 5);
    let currentStep = 1;
    let currentDocument = null;

    const parseData = (button, attribute) => {
        try { return JSON.parse(button.getAttribute(attribute) || '{}'); } catch (error) { return {}; }
    };
    const formatDate = (value, withTime = false) => {
        if (!value) return '-';
        const normalized = String(value).replace(' ', 'T');
        const date = new Date(normalized);
        if (Number.isNaN(date.getTime())) return value;
        return new Intl.DateTimeFormat('id-ID', withTime ? { day:'2-digit', month:'2-digit', year:'numeric', hour:'2-digit', minute:'2-digit' } : { day:'2-digit', month:'2-digit', year:'numeric' }).format(date);
    };
    const statusClass = (status) => ({ Menunggu:'pending', Diterima:'received', Diproses:'active', Diteruskan:'forwarded', Selesai:'completed' }[status] || 'empty');
    const openModal = (modal) => {
        modal.hidden = false;
        modal.setAttribute('aria-hidden', 'false');
        requestAnimationFrame(() => modal.classList.add('open'));
        document.body.style.overflow = 'hidden';
    };
    const closeModal = (modal, unlockBody = true) => {
        modal.classList.remove('open');
        modal.setAttribute('aria-hidden', 'true');
        if (unlockBody) document.body.style.overflow = '';
        window.setTimeout(() => { modal.hidden = true; }, 180);
    };
    const setDetail = (name, value) => {
        const field = viewModal?.querySelector(`[data-sdm-detail="${name}"]`);
        if (field) field.textContent = value || '-';
    };
    const setEditField = (name, value) => {
        const field = editModal?.querySelector(`[data-sdm-edit-field="${name}"]`);
        if (field) field.value = value || '';
    };
    const showStep = (step) => {
        currentStep = Math.max(1, Math.min(4, step));
        stepPanels.forEach((panel) => { panel.hidden = Number(panel.dataset.sdmEditStep) !== currentStep; });
        stepIndicators.forEach((indicator) => indicator.classList.toggle('active', Number(indicator.dataset.sdmStepIndicator) === currentStep));
        stepBack.hidden = currentStep === 1;
        stepNext.hidden = currentStep === 4;
        submitButton.hidden = currentStep !== 4;
    };
    const renderTimeline = (dispositions = []) => {
        const history = viewModal.querySelector('[data-sdm-disposition-history]');
        history.replaceChildren();
        for (let step = 1; step <= maxDispositionSteps; step += 1) {
            const item = dispositions.find((entry) => Number(entry.step) === step);
            const article = document.createElement('article');
            article.className = `disposition-detail-item${item ? ' filled' : ''}`;
            const dot = document.createElement('span');
            dot.className = 'disposition-detail-dot';
            dot.textContent = String(step).padStart(2, '0');
            const card = document.createElement('div');
            card.className = 'disposition-detail-card';
            const header = document.createElement('header');
            const identity = document.createElement('div');
            const name = document.createElement('h3');
            const time = document.createElement('time');
            name.textContent = item?.recipient || 'Belum diisi';
            time.textContent = item ? formatDate(item.date, true) : 'Waktu belum ditentukan';
            identity.append(name, time);
            const badge = document.createElement('span');
            badge.className = `disposition-status-badge ${statusClass(item?.status || '')}`;
            badge.textContent = item?.status || 'Belum ditentukan';
            const note = document.createElement('p');
            note.textContent = item?.note || 'Belum ada catatan / instruksi';
            header.append(identity, badge);
            card.append(header, note);
            article.append(dot, card);
            history.appendChild(article);
        }
    };
    const renderEditHistory = (dispositions = [], latestStep = 1) => {
        if (!editHistory || !editHistorySection) return;
        const previous = dispositions.filter((item) => Number(item.step) < Number(latestStep));
        editHistory.replaceChildren();
        editHistorySection.hidden = previous.length === 0;
        previous.forEach((item) => {
            const article = document.createElement('article');
            article.className = 'disposition-detail-item filled';
            const dot = document.createElement('span');
            dot.className = 'disposition-detail-dot';
            dot.textContent = String(item.step).padStart(2, '0');
            const card = document.createElement('div');
            card.className = 'disposition-detail-card';
            const header = document.createElement('header');
            const identity = document.createElement('div');
            const name = document.createElement('h3');
            const time = document.createElement('time');
            name.textContent = item.recipient || '-';
            time.textContent = formatDate(item.date, true);
            identity.append(name, time);
            const badge = document.createElement('span');
            badge.className = `disposition-status-badge ${statusClass(item.status || '')}`;
            badge.textContent = item.status || 'Menunggu';
            const note = document.createElement('p');
            note.textContent = item.note || 'Belum ada catatan / instruksi';
            header.append(identity, badge);
            card.append(header, note);
            article.append(dot, card);
            editHistory.appendChild(article);
        });
    };
    const openView = (data) => {
        currentDocument = data;
        if (detailEdit) detailEdit.hidden = !data.can_edit_disposition;
        ['sumber_data','pengirim','penerima','pengambilan','jenis','perihal_surat','nomor_surat','nomor_agendaris','progres'].forEach((name) => setDetail(name, data[name]));
        ['tanggal_surat','tanggal_diterima','tanggal_agendaris'].forEach((name) => setDetail(name, formatDate(data[name])));
        setDetail('penyerahan_at', formatDate(data.penyerahan_at, true));
        const handoverTime = viewModal.querySelector('[data-sdm-penyerahan-time]');
        handoverTime.hidden = !data.penyerahan_at;
        const link = viewModal.querySelector('[data-sdm-detail-link]');
        const noLink = viewModal.querySelector('[data-sdm-detail-no-link]');
        link.hidden = !data.berkas_link;
        link.href = data.berkas_link || '#';
        noLink.hidden = Boolean(data.berkas_link);
        renderTimeline(data.dispositions || []);
        openModal(viewModal);
    };
    const openEdit = (data) => {
        currentDocument = data;
        editForm.reset();
        editForm.action = data.update_url || '';
        errorBox.hidden = true;
        addValue.value = '0';
        nextSection.hidden = true;
        addButton.textContent = '＋ Tambah Disposisi';
        addButton.hidden = !data.can_add_disposition;
        capacityMessage.hidden = Boolean(data.can_add_disposition);
        ['pengirim','tanggal_diterima','penerima','pengambilan','jenis','perihal_surat','nomor_surat','tanggal_surat','nomor_agendaris','tanggal_agendaris','berkas_link','progres'].forEach((name) => setEditField(name, data[name]));
        editModal.querySelector('[data-sdm-current-step]').textContent = String(data.latest_step || 1).padStart(2, '0');
        editModal.querySelector('[data-sdm-next-step]').textContent = String(data.next_step || 2).padStart(2, '0');
        editModal.querySelector('[data-sdm-current-recipient]').value = data.latest_recipient || '';
        editForm.elements.current_status.value = data.latest_status || 'Menunggu';
        editForm.elements.current_date.value = String(data.latest_date || '').slice(0, 10);
        editForm.elements.current_note.value = data.latest_note || '';
        editForm.elements.next_status.value = 'Menunggu';
        renderEditHistory(data.dispositions || [], data.latest_step || 1);
        showStep(1);
        openModal(editModal);
    };

    document.querySelectorAll('[data-sdm-document-view]').forEach((button) => button.addEventListener('click', () => openView(parseData(button, 'data-sdm-document-view'))));
    document.querySelectorAll('[data-sdm-document-edit]').forEach((button) => button.addEventListener('click', () => openEdit(parseData(button, 'data-sdm-document-edit'))));
    detailEdit?.addEventListener('click', () => {
        if (!currentDocument) return;
        closeModal(viewModal, false);
        window.setTimeout(() => openEdit(currentDocument), 180);
    });
    stepBack?.addEventListener('click', () => showStep(currentStep - 1));
    stepNext?.addEventListener('click', () => showStep(currentStep + 1));
    addButton?.addEventListener('click', () => {
        if (!currentDocument?.can_add_disposition) {
            addValue.value = '0';
            nextSection.hidden = true;
            addButton.hidden = true;
            capacityMessage.hidden = false;
            return;
        }
        const adding = addValue.value !== '1';
        addValue.value = adding ? '1' : '0';
        nextSection.hidden = !adding;
        addButton.textContent = adding ? '× Batalkan Disposisi Tambahan' : '＋ Tambah Disposisi';
        if (adding) {
            editForm.elements.next_date.value ||= new Date().toISOString().slice(0, 10);
            editForm.elements.next_recipient.focus();
        }
    });

    editForm?.addEventListener('submit', async (event) => {
        event.preventDefault();
        errorBox.hidden = true;
        submitButton.disabled = true;
        submitButton.textContent = 'Menyimpan...';
        if (submitStatus) submitStatus.textContent = 'Memproses perubahan disposisi';
        try {
            const response = await fetch(editForm.action, { method:'POST', body:new FormData(editForm), credentials:'same-origin', headers:{ 'X-Requested-With':'XMLHttpRequest', 'Accept':'application/json' } });
            const result = await response.json();
            if (result.csrf?.name && result.csrf?.hash) {
                const csrfField = Array.from(editForm.elements).find((field) => field.name === result.csrf.name);
                if (csrfField) csrfField.value = result.csrf.hash;
            }
            if (!response.ok || !result.success) throw result;
            window.location.reload();
        } catch (error) {
            const errors = Array.isArray(error.errors) ? error.errors : [error.message || 'Perubahan disposisi belum berhasil disimpan.'];
            errorBox.replaceChildren();
            const strong = document.createElement('strong'); strong.textContent = 'Periksa kembali data berikut:'; errorBox.appendChild(strong);
            const list = document.createElement('ul'); errors.forEach((message) => { const item = document.createElement('li'); item.textContent = message; list.appendChild(item); }); errorBox.appendChild(list);
            errorBox.hidden = false;
            showStep(3);
            submitButton.disabled = false;
            submitButton.textContent = 'Simpan perubahan';
            if (submitStatus) submitStatus.textContent = '';
        }
    });

    viewModal?.querySelectorAll('[data-sdm-document-view-close]').forEach((button) => button.addEventListener('click', () => closeModal(viewModal)));
    editModal?.querySelectorAll('[data-sdm-document-edit-close]').forEach((button) => button.addEventListener('click', () => closeModal(editModal)));
})();
</script>
