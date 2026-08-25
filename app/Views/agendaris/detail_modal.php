<div class="agendaris-detail-modal" id="agendarisDetailModal" hidden aria-hidden="true">
    <button type="button" class="modal-backdrop" data-agendaris-detail-close aria-label="Tutup detail"></button>
    <section class="modal-dialog agendaris-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="agendarisDetailTitle">
        <header class="modal-header"><div class="modal-title-group"><span class="modal-title-icon">▦</span><div><p>DETAIL AGENDARIS</p><h2 id="agendarisDetailTitle">Surat Masuk</h2></div></div><button type="button" class="modal-close" data-agendaris-detail-close aria-label="Tutup">×</button></header>
        <div class="detail-loading" data-agendaris-detail-loading><span></span><strong>Memuat data surat...</strong></div>
        <div class="agendaris-detail-content" data-agendaris-detail-content hidden>
            <dl class="agendaris-detail-grid">
                <div class="span-2"><dt>Sumber Data</dt><dd data-agendaris-field="sumber_data"></dd></div>
                <div><dt>Tanggal Surat</dt><dd data-agendaris-field="tanggal_surat"></dd></div>
                <div><dt>Nomor Surat</dt><dd data-agendaris-field="nomor_surat"></dd></div>
                <div class="span-2"><dt>Perihal Surat</dt><dd data-agendaris-field="perihal_surat"></dd></div>
                <div><dt>Pengirim</dt><dd data-agendaris-field="pengirim"></dd></div>
                <div><dt>Penerima</dt><dd data-agendaris-field="penerima"></dd></div>
                <div><dt>Penyerahan</dt><dd data-agendaris-field="pengambilan"></dd></div>
                <div data-agendaris-penyerahan-time hidden><dt>Waktu Penyerahan</dt><dd data-agendaris-field="penyerahan_at"></dd></div>
                <div><dt>Jenis</dt><dd data-agendaris-field="jenis"></dd></div>
                <div><dt>Tanggal Diterima</dt><dd data-agendaris-field="tanggal_diterima"></dd></div>
                <div class="span-2"><dt>Link Berkas</dt><dd><a class="agenda-file-link" href="#" target="_blank" rel="noopener noreferrer" data-agendaris-detail-link hidden>Buka berkas ↗</a><span data-agendaris-detail-no-link>Belum ada link berkas</span></dd></div>
            </dl>
        </div>
        <footer class="modal-footer"><button type="button" class="btn btn-ghost" data-agendaris-detail-close>Tutup</button><button type="button" class="btn btn-primary" data-agendaris-detail-edit>✎ Ubah data</button></footer>
    </section>
</div>
