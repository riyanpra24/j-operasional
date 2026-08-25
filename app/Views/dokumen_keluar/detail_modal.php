<div class="agendaris-detail-modal" id="dokumenKeluarDetailModal" hidden aria-hidden="true">
    <button type="button" class="modal-backdrop" data-dokumen-keluar-detail-close aria-label="Tutup detail"></button>
    <section class="modal-dialog agendaris-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="dokumenKeluarDetailTitle">
        <header class="modal-header"><div class="modal-title-group"><span class="modal-title-icon">⇢</span><div><p>DETAIL AGENDARIS</p><h2 id="dokumenKeluarDetailTitle">Surat Keluar</h2></div></div><button type="button" class="modal-close" data-dokumen-keluar-detail-close aria-label="Tutup">×</button></header>
        <div class="detail-loading" data-dokumen-keluar-detail-loading><span></span><strong>Memuat data pengiriman...</strong></div>
        <div class="agendaris-detail-content" data-dokumen-keluar-detail-content hidden>
            <dl class="agendaris-detail-grid">
                <div><dt>Nomor Surat</dt><dd data-dokumen-keluar-field="nomor_surat"></dd></div><div><dt>Jenis Surat</dt><dd data-dokumen-keluar-field="jenis_surat"></dd></div>
                <div><dt>Pemohon</dt><dd data-dokumen-keluar-field="pemohon"></dd></div><div><dt>Pelaksana</dt><dd data-dokumen-keluar-field="pelaksana"></dd></div>
                <div class="span-2"><dt>UP</dt><dd data-dokumen-keluar-field="up"></dd></div>
                <div class="span-2"><dt>Tanggal Pengiriman</dt><dd data-dokumen-keluar-field="tanggal_pengiriman"></dd></div>
                <div class="span-2"><dt>Alamat Penerima</dt><dd data-dokumen-keluar-field="alamat_penerima"></dd></div>
                <div class="span-2"><dt>Dokumen</dt><dd><a href="#" target="_blank" rel="noopener noreferrer" data-dokumen-keluar-document-link hidden>Buka dokumen ↗</a><span data-dokumen-keluar-document-empty>-</span></dd></div>
            </dl>
        </div>
        <footer class="modal-footer"><button type="button" class="btn btn-ghost" data-dokumen-keluar-detail-close>Tutup</button><?php if (! ($readOnly ?? false)): ?><button type="button" class="btn btn-primary" data-dokumen-keluar-detail-edit>✎ Ubah data</button><?php endif ?></footer>
    </section>
</div>
