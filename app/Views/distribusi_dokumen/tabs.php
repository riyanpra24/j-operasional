<nav class="progress-document-tabs" role="tablist" aria-label="Jenis distribusi dokumen">
    <a href="<?= site_url('distribusi-dokumen?tab=masuk') ?>" class="progress-document-tab <?= $activeTab === 'masuk' ? 'active' : '' ?>" role="tab" aria-selected="<?= $activeTab === 'masuk' ? 'true' : 'false' ?>">
        <span aria-hidden="true">⇠</span>
        <strong>Distribusi Dokumen Masuk</strong>
        <small>Kelola penerimaan dan penyerahan dokumen</small>
    </a>
    <a href="<?= site_url('distribusi-dokumen?tab=keluar') ?>" class="progress-document-tab <?= $activeTab === 'keluar' ? 'active' : '' ?>" role="tab" aria-selected="<?= $activeTab === 'keluar' ? 'true' : 'false' ?>">
        <span aria-hidden="true">⇢</span>
        <strong>Distribusi Dokumen Keluar</strong>
        <small>Kelola distribusi dan pengiriman dokumen</small>
    </a>
</nav>
