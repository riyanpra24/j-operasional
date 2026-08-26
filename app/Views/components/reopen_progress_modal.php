<div class="delete-modal" id="reopenProgressModal" hidden aria-hidden="true">
    <button type="button" class="modal-backdrop" data-reopen-progress-close aria-label="Batalkan"></button>
    <section class="modal-dialog delete-modal-dialog" role="alertdialog" aria-modal="true" aria-labelledby="reopenProgressTitle" aria-describedby="reopenProgressDescription">
        <div class="delete-modal-body">
            <span class="delete-warning-icon">!</span>
            <h2 id="reopenProgressTitle"><?= esc($reopenTitle ?? 'Kembalikan ke Progres Dokumen?') ?></h2>
            <p id="reopenProgressDescription"><strong data-reopen-progress-label></strong> <?= esc($reopenDescription ?? 'akan hilang dari arsip dan kembali ke Progres Dokumen agar dapat diedit.') ?></p>
            <div class="modal-alert" data-reopen-progress-error hidden></div>
        </div>
        <form method="post" action="" data-reopen-progress-form class="delete-modal-actions">
            <?= csrf_field() ?>
            <button type="button" class="btn btn-ghost" data-reopen-progress-close>Batal</button>
            <button type="submit" class="btn btn-primary" data-reopen-progress-submit><?= esc($reopenSubmitLabel ?? 'Ya, kembalikan ke progres') ?></button>
        </form>
    </section>
</div>
