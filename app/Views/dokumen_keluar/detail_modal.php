<div class="agendaris-detail-modal" id="dokumenKeluarDetailModal" hidden aria-hidden="true">
    <button type="button" class="modal-backdrop" data-dokumen-keluar-detail-close aria-label="Tutup detail"></button>
    <section class="modal-dialog agendaris-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="dokumenKeluarDetailTitle">
        <header class="modal-header"><div class="modal-title-group"><span class="modal-title-icon">⇢</span><div><p>DETAIL AGENDARIS</p><h2 id="dokumenKeluarDetailTitle">Surat Keluar</h2></div></div><button type="button" class="modal-close" data-dokumen-keluar-detail-close aria-label="Tutup">×</button></header>
        <div class="detail-loading" data-dokumen-keluar-detail-loading><span></span><strong>Memuat data pengiriman...</strong></div>
        <div class="agendaris-detail-content" data-dokumen-keluar-detail-content hidden>
            <dl class="agendaris-detail-grid">
                <div><dt>Nomor Surat</dt><dd data-dokumen-keluar-field="nomor_surat"></dd></div><div><dt>Jenis Dokumen</dt><dd data-dokumen-keluar-field="jenis_surat"></dd></div>
                <div><dt>Pemohon</dt><dd data-dokumen-keluar-field="pemohon"></dd></div><div><dt>Pelaksana</dt><dd data-dokumen-keluar-field="pelaksana"></dd></div>
                <div class="span-2"><dt>UP</dt><dd data-dokumen-keluar-field="up"></dd></div>
                <div class="span-2"><dt>Tanggal Pengiriman</dt><dd data-dokumen-keluar-field="tanggal_pengiriman"></dd></div>
                <div><dt>Nomor Resi</dt><dd data-dokumen-keluar-field="nomor_resi"></dd></div><div><dt>Tanggal Diterima</dt><dd data-dokumen-keluar-field="tanggal_diterima"></dd></div>
                <div><dt>Penerima</dt><dd data-dokumen-keluar-field="penerima"></dd></div><div><dt>Security</dt><dd data-dokumen-keluar-field="security"></dd></div>
                <div><dt>Tanggal Diterima Security</dt><dd data-dokumen-keluar-field="tanggal_security"></dd></div><div><dt>Progres</dt><dd data-dokumen-keluar-field="progres"></dd></div>
                <div class="span-2"><dt>Waktu Pengambilan Ekspedisi</dt><dd data-dokumen-keluar-field="waktu_pengambilan_ekspedisi"></dd></div>
                <div class="span-2"><dt>Alamat Penerima</dt><dd data-dokumen-keluar-field="alamat_penerima"></dd></div>
                <div class="span-2"><dt>Dokumen</dt><dd><a href="#" target="_blank" rel="noopener noreferrer" data-dokumen-keluar-document-link hidden>Buka dokumen ↗</a><span data-dokumen-keluar-document-empty>-</span></dd></div>
            </dl>
        </div>
        <footer class="modal-footer"><button type="button" class="btn btn-ghost" data-dokumen-keluar-detail-close>Tutup</button><?php if (! ($readOnly ?? false)): ?><button type="button" class="btn btn-primary" data-dokumen-keluar-detail-edit>✎ Ubah data</button><?php endif ?></footer>
    </section>
</div>
