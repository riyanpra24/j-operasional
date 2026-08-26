<div class="agendaris-form-modal" id="agendarisFormModal" hidden aria-hidden="true">
    <button type="button" class="modal-backdrop" data-agendaris-form-close aria-label="Tutup form"></button>
    <section class="modal-dialog agendaris-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="agendarisFormTitle">
        <header class="modal-header">
            <div class="modal-title-group"><span class="modal-title-icon">＋</span><div><p>AGENDARIS</p><h2 id="agendarisFormTitle" data-agendaris-form-title>Tambah Surat Masuk</h2></div></div>
            <button type="button" class="modal-close" data-agendaris-form-close aria-label="Tutup">×</button>
        </header>
        <form action="<?= site_url('agendaris/progres-dokumen-masuk') ?>" method="post" data-agendaris-form>
            <?= csrf_field() ?>
            <div class="modal-alert" data-agendaris-errors hidden role="alert"></div>
            <div class="modal-body agendaris-modal-body">
                <div class="modal-section-heading"><span>01</span><div><strong>Data surat</strong><small data-agendaris-form-note>Lengkapi seluruh informasi Surat Masuk</small></div></div>
                <div class="modal-form-grid agendaris-form-grid">
                    <div class="form-group"><label for="agenda_pengirim">Pengirim <span class="required">*</span></label><input id="agenda_pengirim" name="pengirim" maxlength="255" placeholder="Nama pengirim" required></div>
                    <div class="form-group"><label for="agenda_tanggal_diterima">Tanggal Diterima <span class="required">*</span></label><input id="agenda_tanggal_diterima" type="date" name="tanggal_diterima" required></div>
                    <div class="form-group"><label for="agenda_penerima">Penerima</label><input id="agenda_penerima" name="penerima" maxlength="255" placeholder="Nama penerima"></div>
                    <div class="form-group"><label for="agenda_pengambilan">Pengambilan</label><input id="agenda_pengambilan" name="pengambilan" maxlength="255" placeholder="Nama pengambil"></div>
                    <div class="form-group"><label for="agenda_jenis_surat">Jenis <span class="required">*</span></label><input id="agenda_jenis_surat" name="jenis" maxlength="100" placeholder="Jenis dokumen" required></div>
                    <div class="form-group"><label for="agenda_tanggal_surat">Tanggal Surat <span class="required">*</span></label><input id="agenda_tanggal_surat" type="date" name="tanggal_surat" required></div>
                    <div class="form-group"><label for="agenda_nomor_surat">Nomor Surat <span class="required">*</span></label><input id="agenda_nomor_surat" name="nomor_surat" maxlength="150" placeholder="Nomor surat" required></div>
                    <div class="form-group"><label for="agenda_perihal_surat">Perihal Surat <span class="required">*</span></label><input id="agenda_perihal_surat" name="perihal_surat" maxlength="255" placeholder="Perihal surat" required></div>
                    <div class="form-group">
                        <label for="agenda_nomor_agendaris">Nomor Agendaris <small>(Opsional)</small></label>
                        <div class="agenda-number-control">
                            <input id="agenda_nomor_agendaris" name="nomor_agendaris" maxlength="50" placeholder="Belum dibuat" readonly aria-readonly="true">
                            <button type="button" class="btn btn-secondary" data-agendaris-generate data-generate-url="<?= site_url('agendaris/progres-dokumen-masuk/generate-nomor') ?>">Generate Nomor</button>
                        </div>
                        <small>Nomor dibuat berurutan dengan format AGD/KW/VI/001.</small>
                    </div>
                    <div class="form-group"><label for="agenda_tanggal_agendaris">Tanggal Agendaris <small>(Opsional)</small></label><input id="agenda_tanggal_agendaris" type="date" name="tanggal_agendaris"></div>
                    <div class="form-group modal-span-2"><label for="agenda_progres">Progres <span class="required">*</span></label><select id="agenda_progres" name="progres" required><option value="Menunggu Penyelesaian">Menunggu Penyelesaian</option><option value="Selesai">Selesai</option></select><small>Dokumen berstatus Selesai akan tampil di menu Dokumen Masuk.</small></div>
                    <div class="form-group modal-span-2 agenda-link-field" data-agendaris-link>
                        <label for="agenda_berkas_link">Link Berkas</label>
                        <input id="agenda_berkas_link" type="url" name="berkas_link" maxlength="2048" placeholder="https://...">
                        <small>Tempel tautan HTTPS dari OneDrive, SharePoint, atau penyimpanan dokumen lainnya</small>
                    </div>
                </div>
            </div>
            <footer class="modal-footer"><span class="modal-submit-status" data-agendaris-status></span><button type="button" class="btn btn-ghost" data-agendaris-form-close>Batal</button><button type="submit" class="btn btn-primary" data-agendaris-submit>Simpan Surat Masuk</button></footer>
        </form>
    </section>
</div>
