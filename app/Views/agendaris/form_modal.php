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
            <div class="outgoing-distribution-steps" aria-label="Tahapan Dokumen Masuk">
                <span class="active" data-agendaris-step-indicator="1"><b>01</b> Informasi Surat</span>
                <i></i>
                <span data-agendaris-step-indicator="2"><b>02</b> Data Agendaris</span>
                <i></i>
                <span data-agendaris-step-indicator="3"><b>03</b> Disposisi</span>
                <i></i>
                <span data-agendaris-step-indicator="4"><b>04</b> Status Dokumen</span>
            </div>
            <div class="modal-body agendaris-modal-body outgoing-distribution-step" data-agendaris-step="1">
                <div class="modal-section-heading"><span>01</span><div><strong>Data surat</strong><small data-agendaris-form-note>Lengkapi seluruh informasi Surat Masuk</small></div></div>
                <div class="modal-form-grid agendaris-form-grid">
                    <div class="form-group"><label for="agenda_pengirim">Pengirim <span class="required">*</span></label><input id="agenda_pengirim" name="pengirim" maxlength="255" placeholder="Nama pengirim" required></div>
                    <div class="form-group"><label for="agenda_tanggal_diterima">Tanggal Diterima <span class="required">*</span></label><input id="agenda_tanggal_diterima" type="date" name="tanggal_diterima" required></div>
                    <div class="form-group"><label for="agenda_penerima">Penerima</label><input id="agenda_penerima" name="penerima" maxlength="255" placeholder="Nama penerima"></div>
                    <div class="form-group"><label for="agenda_pengambilan">Penyerahan</label><input id="agenda_pengambilan" name="pengambilan" maxlength="255" placeholder="Nama petugas atau keterangan penyerahan"></div>
                    <div class="form-group"><label for="agenda_jenis_surat">Jenis <span class="required">*</span></label><input id="agenda_jenis_surat" name="jenis" maxlength="100" placeholder="Jenis dokumen" required></div>
                    <div class="form-group"><label for="agenda_perihal_surat">Perihal Surat <span class="required">*</span></label><input id="agenda_perihal_surat" name="perihal_surat" maxlength="255" placeholder="Perihal surat" required></div>
                </div>
            </div>
            <div class="modal-body agendaris-modal-body outgoing-distribution-step" data-agendaris-step="2" hidden>
                <div class="modal-section-heading"><span>02</span><div><strong>Data Agendaris</strong><small>Lengkapi nomor surat dan data agenda dokumen</small></div></div>
                <div class="modal-form-grid agendaris-form-grid">
                    <div class="form-group"><label for="agenda_nomor_surat">Nomor Surat <span class="required">*</span></label><input id="agenda_nomor_surat" name="nomor_surat" maxlength="150" placeholder="Nomor surat" required></div>
                    <div class="form-group"><label for="agenda_tanggal_surat">Tanggal Surat <span class="required">*</span></label><input id="agenda_tanggal_surat" type="date" name="tanggal_surat" required></div>
                    <div class="form-group">
                        <label for="agenda_nomor_agendaris">Nomor Agendaris <small>(Opsional)</small></label>
                        <div class="agenda-number-control">
                            <input id="agenda_nomor_agendaris" name="nomor_agendaris" maxlength="50" placeholder="Belum dibuat" readonly aria-readonly="true">
                            <button type="button" class="btn btn-agenda-generate" data-agendaris-generate data-generate-url="<?= site_url('agendaris/progres-dokumen-masuk/generate-nomor') ?>">Generate Nomor</button>
                        </div>
                        <small>Nomor baru disimpan bersama formulir. Klik tombol yang sama sekali lagi untuk membatalkannya.</small>
                    </div>
                    <div class="form-group"><label for="agenda_tanggal_agendaris">Tanggal Agendaris <small>(Opsional)</small></label><input id="agenda_tanggal_agendaris" type="date" name="tanggal_agendaris"></div>
                    <div class="form-group modal-span-2 agenda-link-field" data-agendaris-link>
                        <label for="agenda_berkas_link">Link Berkas</label>
                        <input id="agenda_berkas_link" type="url" name="berkas_link" maxlength="2048" placeholder="https://...">
                        <small>Tempel tautan HTTPS dari OneDrive, SharePoint, atau penyimpanan dokumen lainnya</small>
                    </div>
                </div>
            </div>
            <div class="modal-body agendaris-modal-body outgoing-distribution-step" data-agendaris-step="3" hidden>
                <div class="modal-section-heading"><span>03</span><div><strong>Tracking Disposisi</strong><small>Catat posisi, status, waktu, dan instruksi penerusan dokumen</small></div></div>
                <div class="modal-form-grid agendaris-form-grid">
                    <div class="modal-span-2 disposition-form-timeline" aria-label="Tracking disposisi dokumen">
                        <?php for ($step = 1; $step <= 3; $step++): ?>
                            <article class="disposition-form-stage" data-disposition-form-stage="<?= $step ?>">
                                <div class="disposition-stage-marker"><span><?= str_pad((string) $step, 2, '0', STR_PAD_LEFT) ?></span><i></i></div>
                                <div class="disposition-stage-panel">
                                    <header><div><strong>Disposisi <?= $step ?></strong><small>Tahap penerusan dan pemantauan dokumen</small></div><span class="disposition-stage-state" data-disposition-state="<?= $step ?>">Belum ditentukan</span></header>
                                    <div class="disposition-stage-fields">
                                        <div class="form-group disposition-recipient-field"><label for="agenda_disposisi_<?= $step ?>">Tujuan / Penerima</label><input id="agenda_disposisi_<?= $step ?>" name="disposisi_<?= $step ?>" maxlength="255" placeholder="Nama petugas atau unit tujuan" data-disposition-recipient="<?= $step ?>"></div>
                                        <div class="form-group"><label for="agenda_disposisi_<?= $step ?>_status">Status</label><select id="agenda_disposisi_<?= $step ?>_status" name="disposisi_<?= $step ?>_status" data-disposition-status="<?= $step ?>"><option value="Menunggu">Menunggu</option><option value="Diterima">Diterima</option><option value="Diproses">Diproses</option><option value="Diteruskan">Diteruskan</option><option value="Selesai">Selesai</option></select></div>
                                        <div class="form-group"><label for="agenda_disposisi_<?= $step ?>_waktu">Tanggal</label><input id="agenda_disposisi_<?= $step ?>_waktu" type="date" name="disposisi_<?= $step ?>_waktu" data-disposition-time="<?= $step ?>"></div>
                                        <div class="form-group disposition-note-field"><label for="agenda_disposisi_<?= $step ?>_catatan">Catatan / Instruksi</label><textarea id="agenda_disposisi_<?= $step ?>_catatan" name="disposisi_<?= $step ?>_catatan" maxlength="1000" rows="2" placeholder="Instruksi atau hasil tindak lanjut"></textarea></div>
                                    </div>
                                </div>
                            </article>
                        <?php endfor ?>
                    </div>
                </div>
            </div>
            <div class="modal-body agendaris-modal-body outgoing-distribution-step" data-agendaris-step="4" hidden>
                <div class="modal-section-heading"><span>04</span><div><strong>Status Dokumen</strong><small>Tentukan progres akhir dan unduh lembar pengendalian</small></div></div>
                <div class="modal-form-grid agendaris-form-grid">
                    <div class="form-group modal-span-2"><label for="agenda_progres">Progres <span class="required">*</span></label><select id="agenda_progres" name="progres" required><option value="Menunggu Penyelesaian">Menunggu Penyelesaian</option><option value="Selesai">Selesai</option></select><small>Dokumen berstatus Selesai akan tampil di menu Dokumen Masuk.</small></div>
                    <div class="form-group modal-span-2 agenda-control-sheet-action">
                        <button type="button" class="btn btn-secondary" data-agendaris-download-sheet data-download-url="<?= site_url('agendaris/progres-dokumen-masuk/download-lembar-pengendalian') ?>">↓ Download Lembar Pengendalian</button>
                        <small>PDF akan diisi otomatis dari Nomor Agenda, Tanggal Agenda, Nomor Surat, Tanggal Surat, dan Perihal.</small>
                    </div>
                </div>
            </div>
            <footer class="modal-footer"><span class="modal-submit-status" data-agendaris-status></span><button type="button" class="btn btn-ghost" data-agendaris-form-close>Batal</button><button type="button" class="btn btn-secondary" data-agendaris-step-back hidden>← Kembali</button><button type="button" class="btn btn-primary" data-agendaris-step-next>Selanjutnya →</button><button type="submit" class="btn btn-primary" data-agendaris-submit hidden>Simpan Surat Masuk</button></footer>
        </form>
    </section>
</div>
