<div class="delete-modal" id="deleteDokumenModal" hidden aria-hidden="true">
    <button type="button" class="modal-backdrop" data-delete-close aria-label="Batal hapus"></button>
    <section class="modal-dialog delete-modal-dialog" role="alertdialog" aria-modal="true" aria-labelledby="deleteModalTitle" aria-describedby="deleteModalDescription">
        <div class="delete-modal-body">
            <span class="delete-warning-icon">!</span>
            <h2 id="deleteModalTitle" data-delete-title>Hapus dokumen?</h2>
            <p id="deleteModalDescription" data-delete-description>Dokumen <strong data-delete-label></strong> akan dihapus permanen dari database.</p>
            <div class="modal-alert" data-delete-error hidden></div>
        </div>
        <form method="post" action="" data-delete-form class="delete-modal-actions">
            <?= csrf_field() ?>
            <button type="button" class="btn btn-ghost" data-delete-close>Batal</button>
            <button type="submit" class="btn btn-delete" data-delete-submit>Ya, hapus dokumen</button>
        </form>
    </section>
</div>
