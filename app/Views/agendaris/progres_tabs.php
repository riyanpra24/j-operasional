<?php
/** @var string $activeTab */
/** @var array{masuk?: int, keluar?: int} $counts */
?>
<nav class="progress-document-tabs security-distribution-tabs" role="tablist" aria-label="Jenis progres dokumen">
    <a href="<?= site_url('agendaris/progres-dokumen') ?>" class="progress-document-tab <?= $activeTab === 'masuk' ? 'active' : '' ?>" role="tab" aria-selected="<?= $activeTab === 'masuk' ? 'true' : 'false' ?>">
        <span class="security-distribution-tab-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v12m0 0 5-5m-5 5-5-5M4 19v2h16v-2" /></svg>
        </span>
        <strong>Dokumen Masuk</strong>
        <small><?= number_format((int) ($counts['masuk'] ?? 0), 0, ',', '.') ?></small>
    </a>
    <a href="<?= site_url('agendaris/progres-dokumen-keluar') ?>" class="progress-document-tab <?= $activeTab === 'keluar' ? 'active' : '' ?>" role="tab" aria-selected="<?= $activeTab === 'keluar' ? 'true' : 'false' ?>">
        <span class="security-distribution-tab-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 21V9m0 0 5 5m-5-5-5 5M4 5V3h16v2" /></svg>
        </span>
        <strong>Dokumen Keluar</strong>
        <small><?= number_format((int) ($counts['keluar'] ?? 0), 0, ',', '.') ?></small>
    </a>
</nav>
