<nav class="progress-document-tabs" role="tablist" aria-label="Jenis progres dokumen">
    <a href="<?= site_url('agendaris/progres-dokumen') ?>" class="progress-document-tab <?= $activeTab === 'masuk' ? 'active' : '' ?>" role="tab" aria-selected="<?= $activeTab === 'masuk' ? 'true' : 'false' ?>">
        <span aria-hidden="true">⇠</span>
        <strong>Progres Dokumen Masuk</strong>
        <small>Status penyerahan dokumen dari Security</small>
    </a>
    <a href="<?= site_url('agendaris/progres-dokumen-keluar') ?>" class="progress-document-tab <?= $activeTab === 'keluar' ? 'active' : '' ?>" role="tab" aria-selected="<?= $activeTab === 'keluar' ? 'true' : 'false' ?>">
        <span aria-hidden="true">⇢</span>
        <strong>Progres Dokumen Keluar</strong>
        <small>Status distribusi dan pengiriman dokumen</small>
    </a>
</nav>
