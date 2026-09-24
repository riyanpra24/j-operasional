<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<section class="welcome-screen" data-welcome-scene aria-labelledby="welcome-title">
    <div class="welcome-glow welcome-glow-one" aria-hidden="true"></div>
    <div class="welcome-glow welcome-glow-two" aria-hidden="true"></div>
    <div class="welcome-orbit welcome-orbit-one" aria-hidden="true"></div>
    <div class="welcome-orbit welcome-orbit-two" aria-hidden="true"></div>
    <div class="welcome-particles" data-welcome-particles aria-hidden="true"></div>
    <article class="welcome-card" data-welcome-card>
        <button class="welcome-emblem" data-welcome-emblem data-welcome-depth="1.8" data-welcome-drag data-welcome-return type="button" aria-label="Geser logo untuk berinteraksi">
            <span class="welcome-emblem-ring"></span>
            <span class="welcome-emblem-mark" aria-hidden="true">J</span>
            <i class="welcome-emblem-spark welcome-emblem-spark-one" aria-hidden="true">✦</i>
            <i class="welcome-emblem-spark welcome-emblem-spark-two" aria-hidden="true">✦</i>
        </button>
        <p class="welcome-kicker" data-welcome-depth=".5" data-welcome-drag data-welcome-return><span></span>JAKSA OPERASIONAL</p>
        <h1 id="welcome-title" data-welcome-depth="1.25" data-welcome-drag data-welcome-return title="Geser untuk berinteraksi">Selamat Datang, <?= esc($displayName !== '' ? $displayName : 'Pengguna') ?>!</h1>
        <p class="welcome-message" data-welcome-depth=".8" data-welcome-drag data-welcome-return>Sistem siap membantu aktivitas operasional Anda hari ini. Pilih menu di samping atau mulai dari halaman utama sesuai akses Anda.</p>
        <div class="welcome-role" data-welcome-depth="1.05" data-welcome-drag data-welcome-return><span aria-hidden="true">✓</span><div><small>AKSES ANDA</small><strong><?= esc($roleLabel) ?></strong></div></div>
        <p class="welcome-status" data-welcome-depth=".65" data-welcome-drag data-welcome-return role="status" aria-live="polite"><span class="welcome-status-pulse" aria-hidden="true"></span><span data-welcome-status>Menyiapkan ruang kerja Anda</span></p>
        <a class="btn btn-primary welcome-start" href="<?= esc($nextUrl, 'attr') ?>"><span>Mulai</span><b aria-hidden="true">→</b></a>
    </article>
</section>

<?= $this->endSection() ?>
