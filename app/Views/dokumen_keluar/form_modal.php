<div class="agendaris-form-modal" id="dokumenKeluarFormModal" hidden aria-hidden="true">
    <button type="button" class="modal-backdrop" data-dokumen-keluar-form-close aria-label="Tutup form"></button>
    <section class="modal-dialog agendaris-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="dokumenKeluarFormTitle">
        <header class="modal-header"><div class="modal-title-group"><span class="modal-title-icon">＋</span><div><p>AGENDARIS</p><h2 id="dokumenKeluarFormTitle" data-dokumen-keluar-form-title>Tambah Surat Keluar</h2></div></div><button type="button" class="modal-close" data-dokumen-keluar-form-close aria-label="Tutup">×</button></header>
        <form action="<?= site_url('agendaris/surat-keluar') ?>" method="post" data-dokumen-keluar-form>
            <?= csrf_field() ?>
            <div class="modal-alert" data-dokumen-keluar-errors hidden role="alert"></div>
            <div class="modal-body agendaris-modal-body">
                <div class="modal-section-heading"><span>01</span><div><strong>Data pengiriman</strong><small>Lengkapi informasi dokumen yang dikirim</small></div></div>
                <div class="modal-form-grid agendaris-form-grid">
                    <div class="form-group"><label for="keluar_nomor_surat">Nomor Surat <span class="required">*</span></label><input id="keluar_nomor_surat" name="nomor_surat" maxlength="150" placeholder="Nomor surat" required></div>
                    <div class="form-group"><label for="keluar_jenis_surat">Jenis Surat <span class="required">*</span></label><input id="keluar_jenis_surat" name="jenis_surat" maxlength="100" placeholder="Jenis surat" required></div>
                    <div class="form-group"><label for="keluar_pemohon">Pemohon</label><input id="keluar_pemohon" name="pemohon" maxlength="255" placeholder="Nama pemohon atau unit"></div>
                    <div class="form-group"><label for="keluar_pelaksana">Pelaksana</label><input id="keluar_pelaksana" name="pelaksana" maxlength="255" placeholder="Nama pelaksana"></div>
                    <div class="form-group"><label for="keluar_up">UP</label><input id="keluar_up" name="up" maxlength="255" placeholder="Nama atau unit tujuan UP"></div>
                    <div class="form-group"><label for="keluar_tanggal_pengiriman">Tanggal Pengiriman <span class="required">*</span></label><input id="keluar_tanggal_pengiriman" type="date" name="tanggal_pengiriman" required></div>
                    <div class="form-group modal-span-2"><label for="keluar_alamat_penerima">Alamat Penerima <span class="required">*</span></label><textarea id="keluar_alamat_penerima" name="alamat_penerima" maxlength="2000" placeholder="Alamat lengkap penerima" required></textarea></div>
                    <div class="form-group modal-span-2 agenda-link-field">
                        <label for="keluar_dokumen_link">Link Berkas</label>
                        <input id="keluar_dokumen_link" type="url" name="dokumen_link" maxlength="2048" placeholder="https://...">
                        <small>Tempel tautan HTTPS dari OneDrive, SharePoint, atau penyimpanan dokumen lainnya</small>
                    </div>
                </div>
            </div>
            <footer class="modal-footer"><span class="modal-submit-status" data-dokumen-keluar-status></span><button type="button" class="btn btn-ghost" data-dokumen-keluar-form-close>Batal</button><button type="submit" class="btn btn-primary" data-dokumen-keluar-submit>Simpan Surat Keluar</button></footer>
        </form>
    </section>
</div>
