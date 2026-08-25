<div class="edit-modal" id="editDokumenModal" hidden aria-hidden="true">
    <button type="button" class="modal-backdrop" data-edit-close aria-label="Tutup edit"></button>
    <section class="modal-dialog edit-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="editModalTitle">
        <header class="modal-header">
            <div class="modal-title-group"><span class="modal-title-icon edit-title-icon">✎</span><div><p>PERUBAHAN DATA</p><h2 id="editModalTitle">Edit Dokumen</h2></div></div>
            <button type="button" class="modal-close" data-edit-close aria-label="Tutup edit">×</button>
        </header>

        <div class="detail-loading" data-edit-loading><span></span><strong>Memuat data dokumen...</strong></div>
        <form action="" method="post" id="modalEditForm" data-edit-form hidden>
            <?= csrf_field() ?>
            <div class="modal-alert" data-edit-errors hidden role="alert"></div>
            <div class="modal-body edit-modal-body">
                <div class="modal-section-heading"><span>01</span><div><strong>Data dokumen</strong><small>Perbarui informasi yang diperlukan</small></div></div>
                <div class="modal-form-grid edit-form-grid">
                    <div class="form-group"><label for="edit_pengirim">Pengirim <span class="required">*</span></label><input id="edit_pengirim" name="pengirim" maxlength="255" required></div>
                    <div class="form-group"><label for="edit_perihal_pilihan">Perihal <span class="required">*</span></label><select id="edit_perihal_pilihan" name="perihal_pilihan" data-perihal-select required><option value="">Pilih perihal</option><option value="Confidential Documents">Confidential Documents</option><option value="Lainnya">Lainnya</option></select></div>
                    <div class="form-group modal-span-2" data-perihal-custom hidden><label for="edit_perihal_lainnya">Perihal lainnya <span class="required">*</span></label><input id="edit_perihal_lainnya" name="perihal_lainnya" data-perihal-custom-input maxlength="255" placeholder="Ketik perihal dokumen"></div>
                    <div class="form-group"><label for="edit_penerima">Penerima <span class="required">*</span></label><input id="edit_penerima" name="penerima" maxlength="255" required></div>
                    <div class="form-group"><label for="edit_tanggal">Tanggal Diterima <span class="required">*</span></label><input id="edit_tanggal" type="date" name="tanggal" data-date-input required></div>
                    <div class="form-group"><label for="edit_hari">Hari</label><input id="edit_hari" data-day-output readonly tabindex="-1"><small>Dihitung otomatis</small></div>
                    <div class="form-group"><label for="edit_jenis">Jenis <span class="required">*</span></label><select id="edit_jenis" name="jenis" data-jenis-select required><option value="">Pilih jenis</option><option value="Surat">Surat</option><option value="Dokumen">Dokumen</option><option value="Paket">Paket</option><option value="Lainnya">Lainnya</option></select></div>
                    <div class="form-group modal-span-2" data-jenis-custom hidden><label for="edit_jenis_lainnya">Jenis lainnya <span class="required">*</span></label><input id="edit_jenis_lainnya" name="jenis_lainnya" data-jenis-custom-input maxlength="100" placeholder="Ketik jenis dokumen"></div>
                    <div class="form-group"><label for="edit_jumlah">Jumlah <span class="required">*</span></label><input id="edit_jumlah" type="number" min="1" name="jumlah" required></div>
                    <?= view('components/ekspedisi_selector', ['prefix' => 'edit']) ?>
                </div>
            </div>
            <footer class="modal-footer"><span class="modal-submit-status" data-edit-status></span><button type="button" class="btn btn-ghost" data-edit-close>Batal</button><button type="submit" class="btn btn-primary" data-edit-submit>Simpan perubahan</button></footer>
        </form>
    </section>
</div>
