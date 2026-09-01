<div class="agendaris-delete-modal" id="agendarisDeleteModal" hidden aria-hidden="true">
    <button type="button" class="modal-backdrop" data-agendaris-delete-close aria-label="Batal hapus"></button>
    <section class="modal-dialog delete-modal-dialog" role="alertdialog" aria-modal="true" aria-labelledby="agendarisDeleteTitle">
        <div class="delete-modal-body"><span class="delete-warning-icon">!</span><h2 id="agendarisDeleteTitle">Hapus Surat Masuk?</h2><p>Surat nomor <strong data-agendaris-delete-label></strong> <?= (string) session()->get('auth_role') === 'admin' ? 'akan dihapus permanen dan tidak dapat dipulihkan.' : 'akan dihapus.' ?></p><div class="modal-alert" data-agendaris-delete-error hidden></div></div>
        <form method="post" action="" data-agendaris-delete-form class="delete-modal-actions"><?= csrf_field() ?><button type="button" class="btn btn-ghost" data-agendaris-delete-close>Batal</button><button type="submit" class="btn btn-delete" data-agendaris-delete-submit>Ya, hapus</button></form>
    </section>
</div>
