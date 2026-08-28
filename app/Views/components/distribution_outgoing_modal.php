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
                    <div class="form-group"><label>Jumlah Dokumen <i>🔒</i></label><input data-outgoing-distribution-field="jumlah_dokumen" readonly tabindex="-1"></div>
                    <div class="form-group"><label>Nama Ekspedisi <i>🔒</i></label><input data-outgoing-distribution-field="nama_ekspedisi" readonly tabindex="-1"></div>
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
                    <div class="form-group" data-outgoing-security-field><label for="outgoing_security">Security <span class="required">*</span></label><select id="outgoing_security" name="security" required><option value="">Pilih petugas Security</option><?php foreach (\Config\SecurityPersonnel::NAMES as $securityName): ?><option value="<?= esc($securityName, 'attr') ?>"><?= esc($securityName) ?></option><?php endforeach ?></select><small data-outgoing-security-lock-note hidden>Gunakan tombol Serah Terima untuk mengganti Security.</small></div>
                    <div class="form-group"><label for="outgoing_tanggal_security">Tanggal Diterima Security <span class="required">*</span></label><input id="outgoing_tanggal_security" type="date" name="tanggal_security" required></div>
                    <div class="form-group modal-span-2"><label for="outgoing_progres">Progres <span class="required">*</span></label><select id="outgoing_progres" name="progres" required><option value="Menunggu Ekspedisi">Menunggu Ekspedisi</option><option value="Diambil Ekspedisi">Diambil Ekspedisi</option></select></div>
                </div>
                <div class="shift-handover-info outgoing-handover-info" data-outgoing-handover-info hidden><span>Penanggung jawab Security</span><strong data-outgoing-handover-current></strong></div>
                <section class="shift-handover-form outgoing-handover-form" data-outgoing-handover-panel hidden>
                    <div class="modal-section-heading"><span>03</span><div><strong>Serah Terima Shift</strong><small>Pilih Security yang menerima tanggung jawab Dokumen Keluar</small></div></div>
                    <div class="form-group"><label for="outgoing_security_tujuan">Security Baru <span class="required">*</span></label><select id="outgoing_security_tujuan" name="security_tujuan" data-outgoing-handover-select disabled><option value="">Pilih Security penerima</option><?php foreach (\Config\SecurityPersonnel::NAMES as $securityName): ?><option value="<?= esc($securityName, 'attr') ?>"><?= esc($securityName) ?></option><?php endforeach ?></select><small>Security yang dipilih akan menggantikan Security lama.</small></div>
                    <div class="shift-handover-actions"><button type="button" class="btn btn-ghost" data-outgoing-handover-cancel>Batal serah terima</button><button type="submit" class="btn btn-primary" data-outgoing-handover-submit data-outgoing-action="handover">Konfirmasi Serah Terima</button></div>
                </section>
                <section class="security-handover-history outgoing-handover-history" data-outgoing-handover-history hidden>
                    <div class="modal-section-heading"><span>⇄</span><div><strong>Riwayat Serah Terima Security</strong><small>Perpindahan penanggung jawab Dokumen Keluar</small></div></div>
                    <div class="security-handover-history-list" data-outgoing-handover-history-list></div>
                </section>
            </div>
            <footer class="modal-footer"><span class="modal-submit-status" data-outgoing-distribution-status></span><button type="button" class="btn btn-ghost" data-outgoing-distribution-close>Batal</button><button type="button" class="btn btn-secondary" data-outgoing-step-back hidden>← Kembali</button><button type="button" class="btn btn-primary" data-outgoing-step-next>Selanjutnya →</button><button type="button" class="btn btn-shift-handover" data-outgoing-handover-open hidden>⇄ Serah Terima</button><button type="submit" class="btn btn-primary" data-outgoing-distribution-submit data-outgoing-action="save" hidden>Simpan distribusi</button></footer>
        </form>
    </section>
</div>
