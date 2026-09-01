<div class="agendaris-delete-modal" id="dokumenKeluarDeleteModal" hidden aria-hidden="true">
    <button type="button" class="modal-backdrop" data-dokumen-keluar-delete-close aria-label="Batal hapus"></button>
    <section class="modal-dialog delete-modal-dialog" role="alertdialog" aria-modal="true" aria-labelledby="dokumenKeluarDeleteTitle">
        <div class="delete-modal-body"><span class="delete-warning-icon">!</span><h2 id="dokumenKeluarDeleteTitle">Hapus Surat Keluar?</h2><p>Surat nomor <strong data-dokumen-keluar-delete-label></strong> <?= (string) session()->get('auth_role') === 'admin' ? 'akan dihapus permanen dan tidak dapat dipulihkan.' : 'akan dihapus.' ?></p><div class="modal-alert" data-dokumen-keluar-delete-error hidden></div></div>
        <form method="post" action="" data-dokumen-keluar-delete-form class="delete-modal-actions"><?= csrf_field() ?><button type="button" class="btn btn-ghost" data-dokumen-keluar-delete-close>Batal</button><button type="submit" class="btn btn-delete" data-dokumen-keluar-delete-submit>Ya, hapus</button></form>
    </section>
</div>
