<div class="distribution-action-modal" id="distributionActionModal" hidden aria-hidden="true">
    <button type="button" class="modal-backdrop" data-distribution-close aria-label="Tutup proses distribusi"></button>
    <section class="modal-dialog distribution-action-dialog" role="dialog" aria-modal="true" aria-labelledby="distributionActionTitle">
        <header class="modal-header">
            <div class="modal-title-group"><span class="modal-title-icon distribution-title-icon">⇢</span><div><p>PROSES DISTRIBUSI</p><h2 id="distributionActionTitle">Pengambilan Dokumen</h2></div></div>
            <button type="button" class="modal-close" data-distribution-close aria-label="Tutup proses">×</button>
        </header>

        <div class="detail-loading" data-distribution-loading><span></span><strong>Memuat data dokumen...</strong></div>
        <form method="post" action="" data-distribution-form hidden>
            <?= csrf_field() ?>
            <div class="modal-alert" data-distribution-errors hidden role="alert"></div>
            <div class="modal-body">
                <div class="modal-section-heading"><span>01</span><div><strong>Informasi dokumen</strong><small>Data berikut dikunci dan tidak dapat diubah dari proses distribusi</small></div></div>
                <div class="modal-form-grid locked-document-grid">
                    <div class="form-group modal-span-2"><label>Pengirim <i>🔒</i></label><input data-distribution-field="pengirim" readonly tabindex="-1"></div>
                    <div class="form-group modal-span-2"><label>Perihal <i>🔒</i></label><input data-distribution-field="perihal" readonly tabindex="-1"></div>
                    <div class="form-group modal-span-2"><label>Penerima <i>🔒</i></label><input data-distribution-field="penerima" readonly tabindex="-1"></div>
                    <div class="form-group"><label>Hari <i>🔒</i></label><input data-distribution-field="hari" readonly tabindex="-1"></div>
                    <div class="form-group"><label>Tanggal Diterima <i>🔒</i></label><input data-distribution-field="tanggal" readonly tabindex="-1"></div>
                    <div class="form-group"><label>Jenis <i>🔒</i></label><input data-distribution-field="jenis" readonly tabindex="-1"></div>
                    <div class="form-group"><label>Jumlah <i>🔒</i></label><input data-distribution-field="jumlah" readonly tabindex="-1"></div>
                    <div class="form-group"><label>Satuan Jumlah <i>🔒</i></label><input data-distribution-field="satuan_jumlah" readonly tabindex="-1"></div>
                    <div class="form-group modal-span-2"><label>Ekspedisi <i>🔒</i></label><input data-distribution-field="ekspedisi" readonly tabindex="-1"></div>
                </div>

                <div class="modal-section-heading pickup-heading"><span>02</span><div><strong>Pengambilan</strong><small>Field ini hanya dapat diisi satu kali dari menu Distribusi Dokumen</small></div></div>
                <div class="form-group pickup-field"><label for="distribution_pengambilan">Pengambilan <span class="required">*</span></label><input id="distribution_pengambilan" name="pengambilan" maxlength="255" placeholder="Nama petugas atau keterangan pengambilan" required><small>Setelah disimpan, dokumen keluar dari antrean distribusi.</small></div>
            </div>
            <footer class="modal-footer"><span class="modal-submit-status" data-distribution-status></span><button type="button" class="btn btn-ghost" data-distribution-close>Batal</button><button type="submit" class="btn btn-primary" data-distribution-submit>Simpan pengambilan</button></footer>
        </form>
    </section>
</div>
