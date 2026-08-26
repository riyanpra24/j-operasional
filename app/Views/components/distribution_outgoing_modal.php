<div class="distribution-action-modal" id="outgoingDistributionModal" hidden aria-hidden="true">
    <button type="button" class="modal-backdrop" data-outgoing-distribution-close aria-label="Tutup distribusi Surat Keluar"></button>
    <section class="modal-dialog distribution-action-dialog" role="dialog" aria-modal="true" aria-labelledby="outgoingDistributionTitle">
        <header class="modal-header"><div class="modal-title-group"><span class="modal-title-icon distribution-title-icon">⇢</span><div><p>PROSES DISTRIBUSI</p><h2 id="outgoingDistributionTitle">Distribusi Surat Keluar</h2></div></div><button type="button" class="modal-close" data-outgoing-distribution-close aria-label="Tutup">×</button></header>
        <div class="detail-loading" data-outgoing-distribution-loading><span></span><strong>Memuat Surat Keluar...</strong></div>
        <form method="post" action="" data-outgoing-distribution-form hidden>
            <?= csrf_field() ?>
            <div class="modal-alert" data-outgoing-distribution-errors hidden role="alert"></div>
            <div class="outgoing-distribution-steps" aria-label="Tahapan distribusi">
                <span class="active" data-outgoing-step-indicator="1"><b>01</b> Informasi Surat</span>
                <i></i>
                <span data-outgoing-step-indicator="2"><b>02</b> Data Distribusi</span>
            </div>
            <div class="modal-body outgoing-distribution-step" data-outgoing-step="1">
                <div class="modal-section-heading"><span>01</span><div><strong>Informasi Surat Keluar</strong><small>Data sumber dikunci dan hanya dapat diubah dari menu Surat Keluar</small></div></div>
                <div class="modal-form-grid locked-document-grid">
                    <div class="form-group"><label>Nomor Surat <i>🔒</i></label><input data-outgoing-distribution-field="nomor_surat" readonly tabindex="-1"></div>
                    <div class="form-group"><label>Jenis Dokumen <i>🔒</i></label><input data-outgoing-distribution-field="jenis_surat" readonly tabindex="-1"></div>
                    <div class="form-group"><label>Pemohon <i>🔒</i></label><input data-outgoing-distribution-field="pemohon" readonly tabindex="-1"></div>
                    <div class="form-group"><label>Pelaksana <i>🔒</i></label><input data-outgoing-distribution-field="pelaksana" readonly tabindex="-1"></div>
                    <div class="form-group"><label>UP <i>🔒</i></label><input data-outgoing-distribution-field="up" readonly tabindex="-1"></div>
                    <div class="form-group"><label>Tanggal Pengiriman <i>🔒</i></label><input data-outgoing-distribution-field="tanggal_pengiriman" readonly tabindex="-1"></div>
                    <div class="form-group modal-span-2"><label>Alamat Penerima <i>🔒</i></label><textarea data-outgoing-distribution-field="alamat_penerima" readonly tabindex="-1"></textarea></div>
                </div>
            </div>
            <div class="modal-body outgoing-distribution-step" data-outgoing-step="2" hidden>
                <div class="modal-section-heading"><span>02</span><div><strong>Data Distribusi</strong><small>Field berikut khusus dikelola dari menu Distribusi Dokumen</small></div></div>
                <div class="modal-form-grid">
                    <div class="form-group"><label for="outgoing_security">Security <span class="required">*</span></label><select id="outgoing_security" name="security" required><option value="">Pilih petugas Security</option><option value="Yanto Pujoyuwono">Yanto Pujoyuwono</option><option value="M. Aziz Dwi Pratomo">M. Aziz Dwi Pratomo</option><option value="Ach. Fathur Rozi">Ach. Fathur Rozi</option><option value="Yayak Andriyani">Yayak Andriyani</option></select></div>
                    <div class="form-group"><label for="outgoing_tanggal_security">Tanggal Diterima Security <span class="required">*</span></label><input id="outgoing_tanggal_security" type="date" name="tanggal_security" required></div>
                    <div class="form-group modal-span-2"><label for="outgoing_progres">Progres <span class="required">*</span></label><select id="outgoing_progres" name="progres" required><option value="Menunggu Ekspedisi">Menunggu Ekspedisi</option><option value="Diambil Ekspedisi">Diambil Ekspedisi</option></select></div>
                </div>
            </div>
            <footer class="modal-footer"><span class="modal-submit-status" data-outgoing-distribution-status></span><button type="button" class="btn btn-ghost" data-outgoing-distribution-close>Batal</button><button type="button" class="btn btn-secondary" data-outgoing-step-back hidden>← Kembali</button><button type="button" class="btn btn-primary" data-outgoing-step-next>Selanjutnya →</button><button type="submit" class="btn btn-primary" data-outgoing-distribution-submit hidden>Simpan distribusi</button></footer>
        </form>
    </section>
</div>
