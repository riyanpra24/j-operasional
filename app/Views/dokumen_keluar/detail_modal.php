<div class="agendaris-detail-modal" id="dokumenKeluarDetailModal" hidden aria-hidden="true">
    <button type="button" class="modal-backdrop" data-dokumen-keluar-detail-close aria-label="Tutup detail"></button>
    <section class="modal-dialog agendaris-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="dokumenKeluarDetailTitle">
        <header class="modal-header"><div class="modal-title-group"><span class="modal-title-icon">⇢</span><div><p><?= ($securityView ?? false) ? 'DETAIL SECURITY' : 'DETAIL AGENDARIS' ?></p><h2 id="dokumenKeluarDetailTitle">Surat Keluar</h2></div></div><button type="button" class="modal-close" data-dokumen-keluar-detail-close aria-label="Tutup">×</button></header>
        <div class="detail-loading" data-dokumen-keluar-detail-loading><span></span><strong>Memuat data pengiriman...</strong></div>
        <div class="agendaris-detail-content" data-dokumen-keluar-detail-content hidden>
            <?php if (! ($securityView ?? false)): ?>
            <section class="agendaris-detail-section" aria-labelledby="outgoingDetailInformationHeading">
                <div class="modal-section-heading agendaris-detail-section-heading"><span>01</span><div><strong id="outgoingDetailInformationHeading">Informasi Surat Keluar</strong><small>Identitas dan tujuan dokumen</small></div></div>
                <dl class="agendaris-detail-grid">
                    <div><dt>Nomor Surat</dt><dd data-dokumen-keluar-field="nomor_surat"></dd></div><div><dt>Jenis Dokumen</dt><dd data-dokumen-keluar-field="jenis_surat"></dd></div>
                    <div><dt>Pemohon</dt><dd data-dokumen-keluar-field="pemohon"></dd></div><div><dt>Pelaksana</dt><dd data-dokumen-keluar-field="pelaksana"></dd></div>
                    <div class="span-2"><dt>UP</dt><dd data-dokumen-keluar-field="up"></dd></div>
                    <div class="span-2"><dt>Alamat Penerima</dt><dd data-dokumen-keluar-field="alamat_penerima"></dd></div>
                </dl>
            </section>
            <section class="agendaris-detail-section" aria-labelledby="outgoingDetailDistributionHeading">
                <div class="modal-section-heading agendaris-detail-section-heading"><span>02</span><div><strong id="outgoingDetailDistributionHeading">Data Distribusi</strong><small>Informasi pengiriman dan proses Security</small></div></div>
                <dl class="agendaris-detail-grid">
                    <div><dt>Tanggal Pengiriman</dt><dd data-dokumen-keluar-field="tanggal_pengiriman"></dd></div><div><dt>Nomor Resi</dt><dd data-dokumen-keluar-field="nomor_resi"></dd></div>
                    <div><dt>Tanggal Diterima</dt><dd data-dokumen-keluar-field="tanggal_diterima"></dd></div><div><dt>Penerima</dt><dd data-dokumen-keluar-field="penerima"></dd></div>
                    <div><dt>Security</dt><dd data-dokumen-keluar-field="security"></dd></div><div><dt>Tanggal Diterima Security</dt><dd data-dokumen-keluar-field="tanggal_security"></dd></div>
                    <div><dt>Progres Security</dt><dd data-dokumen-keluar-field="progres"></dd></div><div><dt>Waktu Pengambilan Ekspedisi</dt><dd data-dokumen-keluar-field="waktu_pengambilan_ekspedisi"></dd></div>
                </dl>
                <section class="security-handover-history" data-dokumen-keluar-handover-history hidden>
                    <div class="modal-section-heading"><span>⇄</span><div><strong>Riwayat Serah Terima Security</strong><small>Perpindahan penanggung jawab Dokumen Keluar antar-shift</small></div></div>
                    <div class="security-handover-history-list" data-dokumen-keluar-handover-history-list></div>
                </section>
            </section>
            <section class="agendaris-detail-section" aria-labelledby="outgoingDetailCompletionHeading">
                <div class="modal-section-heading agendaris-detail-section-heading"><span>03</span><div><strong id="outgoingDetailCompletionHeading">Penyelesaian Agendaris</strong><small>Status akhir dan tautan dokumen</small></div></div>
                <dl class="agendaris-detail-grid">
                    <div><dt>Status Penyelesaian</dt><dd data-dokumen-keluar-field="status_agendaris"></dd></div><div><dt>Waktu Selesai</dt><dd data-dokumen-keluar-field="waktu_selesai_agendaris"></dd></div>
                    <div class="span-2"><dt>Dokumen</dt><dd><a href="#" target="_blank" rel="noopener noreferrer" data-dokumen-keluar-document-link hidden>Buka dokumen ↗</a><span data-dokumen-keluar-document-empty>-</span></dd></div>
                </dl>
            </section>
            <?php else: ?>
            <dl class="agendaris-detail-grid">
                <div><dt>Nomor Surat</dt><dd data-dokumen-keluar-field="nomor_surat"></dd></div><div><dt>Jenis Dokumen</dt><dd data-dokumen-keluar-field="jenis_surat"></dd></div>
                <div><dt>Pemohon</dt><dd data-dokumen-keluar-field="pemohon"></dd></div><div><dt>Pelaksana</dt><dd data-dokumen-keluar-field="pelaksana"></dd></div>
                <div class="span-2"><dt>UP</dt><dd data-dokumen-keluar-field="up"></dd></div>
                <div class="span-2"><dt>Tanggal Pengiriman</dt><dd data-dokumen-keluar-field="tanggal_pengiriman"></dd></div>
                <div><dt>Nomor Resi</dt><dd data-dokumen-keluar-field="nomor_resi"></dd></div><div><dt>Tanggal Diterima</dt><dd data-dokumen-keluar-field="tanggal_diterima"></dd></div>
                <div><dt>Penerima</dt><dd data-dokumen-keluar-field="penerima"></dd></div><div><dt>Security</dt><dd data-dokumen-keluar-field="security"></dd></div>
                <div><dt>Tanggal Diterima Security</dt><dd data-dokumen-keluar-field="tanggal_security"></dd></div><div><dt>Progres Security</dt><dd data-dokumen-keluar-field="progres"></dd></div>
                <div><dt>Status Penyelesaian Agendaris</dt><dd data-dokumen-keluar-field="status_agendaris"></dd></div><div><dt>Waktu Selesai Agendaris</dt><dd data-dokumen-keluar-field="waktu_selesai_agendaris"></dd></div>
                <div class="span-2"><dt>Waktu Pengambilan Ekspedisi</dt><dd data-dokumen-keluar-field="waktu_pengambilan_ekspedisi"></dd></div>
                <div class="span-2"><dt>Alamat Penerima</dt><dd data-dokumen-keluar-field="alamat_penerima"></dd></div>
                <div class="span-2"><dt>Dokumen</dt><dd><a href="#" target="_blank" rel="noopener noreferrer" data-dokumen-keluar-document-link hidden>Buka dokumen ↗</a><span data-dokumen-keluar-document-empty>-</span></dd></div>
            </dl>
            <section class="security-handover-history" data-dokumen-keluar-handover-history hidden>
                <div class="modal-section-heading"><span>⇄</span><div><strong>Riwayat Serah Terima Security</strong><small>Perpindahan penanggung jawab Dokumen Keluar antar-shift</small></div></div>
                <div class="security-handover-history-list" data-dokumen-keluar-handover-history-list></div>
            </section>
            <?php endif ?>
        </div>
        <footer class="modal-footer"><button type="button" class="btn btn-ghost" data-dokumen-keluar-detail-close>Tutup</button><?php if (! ($readOnly ?? false)): ?><button type="button" class="btn btn-primary" data-dokumen-keluar-detail-edit>✎ Ubah data</button><?php endif ?></footer>
    </section>
</div>
