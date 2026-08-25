<div class="detail-modal" id="detailDokumenModal" hidden aria-hidden="true">
    <button type="button" class="modal-backdrop" data-detail-close aria-label="Tutup detail"></button>
    <section class="modal-dialog detail-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="detailModalTitle">
        <header class="modal-header">
            <div class="modal-title-group">
                <span class="modal-title-icon detail-title-icon">▤</span>
                <div><p>DETAIL REGISTER</p><h2 id="detailModalTitle">Dokumen Masuk</h2></div>
            </div>
            <button type="button" class="modal-close" data-detail-close aria-label="Tutup detail">×</button>
        </header>

        <div class="detail-loading" data-detail-loading><span></span><strong>Memuat detail dokumen...</strong></div>
        <div class="detail-error" data-detail-error hidden><strong>Detail tidak dapat dimuat.</strong><button type="button" data-detail-retry>Coba lagi</button></div>

        <div class="detail-modal-content" data-detail-content hidden>
            <div class="detail-record-head">
                <div class="detail-record-times">
                    <p><span>Dicatat</span><strong data-detail-field="created_at"></strong></p>
                    <p><span>Terakhir diperbarui</span><strong data-detail-field="updated_at"></strong></p>
                </div>
            </div>
            <dl class="modal-detail-grid">
                <div><dt>Pengirim</dt><dd data-detail-field="pengirim"></dd></div>
                <div><dt>Perihal</dt><dd data-detail-field="perihal"></dd></div>
                <div><dt>Penerima</dt><dd data-detail-field="penerima"></dd></div>
                <div><dt>Jenis</dt><dd data-detail-field="jenis"></dd></div>
                <div><dt>Hari</dt><dd data-detail-field="hari"></dd></div>
                <div><dt>Tanggal Diterima</dt><dd data-detail-field="tanggal"></dd></div>
                <div><dt>Jumlah</dt><dd data-detail-field="jumlah"></dd></div>
                <div><dt>Ekspedisi</dt><dd data-detail-field="ekspedisi"></dd></div>
<<<<<<< HEAD
                <div class="pickup-detail-row"><dt>Penyerahan</dt><dd data-detail-field="pengambilan"></dd><small>Data dikunci setelah diproses melalui Distribusi Dokumen.</small></div>
                <div class="pickup-detail-row" data-penyerahan-time hidden><dt>Waktu Penyerahan</dt><dd data-detail-field="penyerahan_at"></dd><small>Tanggal dan waktu dicatat otomatis saat penyerahan disimpan.</small></div>
=======
                <div class="pickup-detail-row"><dt>Pengambilan</dt><dd data-detail-field="pengambilan"></dd><small class="pickup-time" data-detail-field="pengambilan_at"></small><small>Data dikunci setelah diproses melalui Distribusi Dokumen.</small></div>
>>>>>>> 3fb4f07c1d3125ff5fb057a935357640b39148e3
            </dl>
        </div>

        <footer class="modal-footer detail-modal-footer">
            <button type="button" class="btn btn-ghost" data-detail-close>Tutup</button>
            <a href="#" class="btn btn-primary" data-detail-edit data-open-edit-modal>✎ Ubah data</a>
        </footer>
    </section>
</div>
