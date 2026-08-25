<div class="input-modal" id="inputDokumenModal" hidden aria-hidden="true">
    <button type="button" class="modal-backdrop" data-modal-close aria-label="Tutup popup"></button>
    <section class="modal-dialog modal-dialog-compact" role="dialog" aria-modal="true" aria-labelledby="inputModalTitle">
        <header class="modal-header">
            <div class="modal-title-group">
                <span class="modal-title-icon">＋</span>
                <div><p>REGISTER DIGITAL</p><h2 id="inputModalTitle">Input Dokumen Masuk</h2></div>
            </div>
            <button type="button" class="modal-close" data-modal-close aria-label="Tutup popup">×</button>
        </header>

        <form action="<?= site_url('dokumen-masuk') ?>" method="post" id="modalDokumenForm">
            <?= csrf_field() ?>
            <div class="modal-alert" data-modal-errors hidden role="alert"></div>
            <div class="modal-body input-modal-body">
                <div class="modal-section-heading"><span>01</span><div><strong>Data dokumen</strong><small>Lengkapi informasi dokumen yang diterima</small></div></div>
                <div class="modal-form-grid input-form-grid">
                    <div class="form-group">
                        <label for="modal_pengirim">Pengirim <span class="required">*</span></label>
                        <input id="modal_pengirim" name="pengirim" maxlength="255" placeholder="Nama orang, unit, atau instansi pengirim" required>
                    </div>
                    <div class="form-group">
                        <label for="modal_perihal_pilihan">Perihal <span class="required">*</span></label>
                        <select id="modal_perihal_pilihan" name="perihal_pilihan" data-perihal-select required>
                            <option value="">Pilih perihal</option>
                            <option value="Confidential Documents">Confidential Documents</option>
                            <option value="Lainnya">Lainnya</option>
                        </select>
                    </div>
                    <div class="form-group modal-span-2" data-perihal-custom hidden>
                        <label for="modal_perihal_lainnya">Perihal lainnya <span class="required">*</span></label>
                        <input id="modal_perihal_lainnya" name="perihal_lainnya" data-perihal-custom-input maxlength="255" placeholder="Ketik perihal dokumen">
                    </div>
                    <div class="form-group">
                        <label for="modal_penerima">Penerima <span class="required">*</span></label>
                        <select id="modal_penerima" name="penerima" required>
                            <option value="">Pilih penerima</option>
                            <option value="Yanto Pujoyuwono">Yanto Pujoyuwono</option>
                            <option value="M. Aziz Dwi Pratomo">M. Aziz Dwi Pratomo</option>
                            <option value="Ach. Fathur Rozi">Ach. Fathur Rozi</option>
                            <option value="Yayak Andriyani">Yayak Andriyani</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="modal_tanggal">Tanggal Diterima <span class="required">*</span></label>
                        <input id="modal_tanggal" type="date" name="tanggal" value="<?= date('Y-m-d') ?>" data-date-input required>
                    </div>
                    <div class="form-group">
                        <label for="modal_hari">Hari</label>
                        <input id="modal_hari" value="" data-day-output readonly tabindex="-1">
                        <small>Dihitung otomatis dari tanggal</small>
                    </div>
                    <div class="form-group">
                        <label for="modal_jenis">Jenis <span class="required">*</span></label>
                        <select id="modal_jenis" name="jenis" data-jenis-select required>
                            <option value="">Pilih jenis</option>
                            <option value="Surat">Surat</option>
                            <option value="Dokumen">Dokumen</option>
                            <option value="Paket">Paket</option>
                            <option value="Lainnya">Lainnya</option>
                        </select>
                    </div>
                    <div class="form-group modal-span-2" data-jenis-custom hidden>
                        <label for="modal_jenis_lainnya">Jenis lainnya <span class="required">*</span></label>
                        <input id="modal_jenis_lainnya" name="jenis_lainnya" data-jenis-custom-input maxlength="100" placeholder="Ketik jenis dokumen">
                    </div>
                    <div class="form-group">
                        <label for="modal_jumlah">Jumlah <span class="required">*</span></label>
                        <input id="modal_jumlah" type="number" min="1" name="jumlah" value="1" required>
                    </div>
                    <?= view('components/ekspedisi_selector', ['prefix' => 'modal']) ?>
                </div>
            </div>
            <footer class="modal-footer">
                <span class="modal-submit-status" data-modal-status></span>
                <button type="button" class="btn btn-ghost" data-modal-close>Batal</button>
                <button type="submit" class="btn btn-primary" data-modal-submit>Simpan dokumen</button>
            </footer>
        </form>
    </section>
</div>
