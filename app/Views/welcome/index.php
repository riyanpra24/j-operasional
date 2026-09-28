<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<section class="welcome-screen" data-welcome-scene aria-labelledby="welcome-title">
    <canvas class="welcome-liquid-canvas" data-welcome-fluid aria-hidden="true"></canvas>
    <div class="welcome-particles" data-welcome-particles aria-hidden="true"></div>
    <article class="welcome-card" data-welcome-card>
        <div class="welcome-brand" aria-label="JAKSA Operasional">
            <img class="welcome-brand-logo" src="<?= base_url('assets/images/landing-jaksa-logo-photoroom.png') ?>" alt="JAKSA">
            <p class="welcome-brand-name">JAKSA OPERASIONAL</p>
            <span class="welcome-brand-accent" aria-hidden="true"><i></i><i></i><i></i></span>
        </div>
        <h1 id="welcome-title"><span>Selamat Datang,</span><strong><?= esc($displayName !== '' ? $displayName : 'Pengguna') ?>!</strong></h1>
        <p class="welcome-message">Sistem siap membantu aktivitas operasional Anda hari ini.<br>Semoga Hari ini Harimu Produktif, Klik mulai dari halaman utama untuk memulai akses.</p>
        <div class="welcome-role">
            <span class="welcome-role-icon" aria-hidden="true"><svg viewBox="0 0 24 24">
                    <path d="M12 12a4.25 4.25 0 1 0 0-8.5A4.25 4.25 0 0 0 12 12Zm0 2.25c-4.14 0-7.5 2.18-7.5 4.88V21h15v-1.87c0-2.7-3.36-4.88-7.5-4.88Z" />
                </svg></span>
            <div><small>AKSES ANDA</small><strong><?= esc($roleLabel) ?></strong></div>
        </div>
        <a class="btn btn-primary welcome-start" data-welcome-start href="<?= esc($nextUrl, 'attr') ?>"><span>Mulai</span><b aria-hidden="true">→</b></a>
    </article>
</section>

<script>
    (() => {
        const startButton = document.querySelector('[data-welcome-start]');
        if (!startButton) return;

        const navigation = performance.getEntriesByType('navigation')[0];
        if (navigation?.type === 'back_forward') {
            window.history.forward();
            return;
        }

        // Simpan welcome sebagai satu langkah terlindungi: Back kembali ke sistem,
        // sedangkan Mulai mengganti halaman ini, bukan menambah riwayat baru.
        window.history.replaceState({ welcomeEntry: true }, '', window.location.href);
        window.history.pushState({ welcomeLock: true }, '', window.location.href);

        let isStarting = false;
        window.addEventListener('popstate', () => {
            if (!isStarting) window.history.go(1);
        });

        startButton.addEventListener('click', (event) => {
            event.preventDefault();
            isStarting = true;
            window.location.replace(startButton.href);
        });
    })();
</script>

<?= $this->endSection() ?>
