<div class="agendaris-detail-modal" id="progressDocumentDetailModal" hidden aria-hidden="true">
    <button type="button" class="modal-backdrop" data-progress-detail-close aria-label="Tutup detail"></button>
    <section class="modal-dialog agendaris-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="progressDocumentDetailTitle">
        <header class="modal-header">
            <div class="modal-title-group"><span class="modal-title-icon">⇢</span><div><p>DETAIL AGENDARIS</p><h2 id="progressDocumentDetailTitle">Progres Dokumen Keluar</h2></div></div>
            <button type="button" class="modal-close" data-progress-detail-close aria-label="Tutup">×</button>
        </header>
        <div class="detail-loading" data-progress-detail-loading><span></span><strong>Memuat Progres Dokumen Keluar...</strong></div>
        <div class="agendaris-detail-content" data-progress-detail-content hidden>
            <section class="agendaris-detail-section" aria-labelledby="progressDetailInformationHeading">
                <div class="modal-section-heading agendaris-detail-section-heading"><span>01</span><div><strong id="progressDetailInformationHeading">Informasi Dokumen Keluar</strong><small>Identitas dan tujuan dokumen</small></div></div>
                <dl class="agendaris-detail-grid">
                    <div><dt>Nomor Surat</dt><dd data-progress-field="nomor_surat"></dd></div>
                    <div><dt>Jenis Dokumen</dt><dd data-progress-field="jenis_surat"></dd></div>
                    <div><dt>Pemohon</dt><dd data-progress-field="pemohon"></dd></div>
                    <div><dt>Pelaksana</dt><dd data-progress-field="pelaksana"></dd></div>
                    <div class="span-2"><dt>UP</dt><dd data-progress-field="up"></dd></div>
                    <div class="span-2"><dt>Alamat Penerima</dt><dd data-progress-field="alamat_penerima"></dd></div>
                </dl>
            </section>
            <section class="agendaris-detail-section" aria-labelledby="progressDetailDistributionHeading">
                <div class="modal-section-heading agendaris-detail-section-heading"><span>02</span><div><strong id="progressDetailDistributionHeading">Data Distribusi</strong><small>Informasi pengiriman dan proses Security</small></div></div>
                <dl class="agendaris-detail-grid">
                    <div><dt>Tanggal Pengiriman</dt><dd data-progress-field="tanggal_pengiriman"></dd></div>
                    <div><dt>Nomor Resi</dt><dd data-progress-field="nomor_resi"></dd></div>
                    <div><dt>Tanggal Diterima</dt><dd data-progress-field="tanggal_diterima"></dd></div>
                    <div><dt>Penerima</dt><dd data-progress-field="penerima"></dd></div>
                    <div><dt>Security</dt><dd data-progress-field="security"></dd></div>
                    <div><dt>Tanggal Diterima Security</dt><dd data-progress-field="tanggal_security"></dd></div>
                    <div><dt>Progres Security</dt><dd data-progress-field="progres"></dd></div>
                    <div><dt>Waktu Pengambilan Ekspedisi</dt><dd data-progress-field="waktu_pengambilan_ekspedisi"></dd></div>
                </dl>
            </section>
            <section class="agendaris-detail-section" aria-labelledby="progressDetailCompletionHeading">
                <div class="modal-section-heading agendaris-detail-section-heading"><span>03</span><div><strong id="progressDetailCompletionHeading">Penyelesaian Agendaris</strong><small>Status akhir proses Dokumen Keluar</small></div></div>
                <dl class="agendaris-detail-grid">
                    <div><dt>Status Penyelesaian</dt><dd data-progress-field="status_agendaris"></dd></div>
                    <div><dt>Waktu Selesai</dt><dd data-progress-field="waktu_selesai_agendaris"></dd></div>
                    <div class="span-2"><dt>Link Berkas</dt><dd><a href="#" target="_blank" rel="noopener noreferrer" data-progress-document-link hidden>Buka berkas ↗</a><span data-progress-document-empty>Belum ada link berkas</span></dd></div>
                </dl>
            </section>
        </div>
        <footer class="modal-footer"><button type="button" class="btn btn-ghost" data-progress-detail-close>Tutup</button><button type="button" class="btn btn-primary" data-progress-detail-edit>✎ Ubah data</button></footer>
    </section>
</div>
