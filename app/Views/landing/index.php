<?php $landingCssVersion = is_file(FCPATH . 'assets/app.css') ? (string) filemtime(FCPATH . 'assets/app.css') : '1'; ?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Jamkrindo Kanwil Surabaya, sistem pengelolaan dokumen operasional.">
    <title><?= esc($title ?? 'Jamkrindo Kanwil Surabaya') ?> | Document Management</title>
    <link rel="icon" type="image/svg+xml" href="<?= base_url('favicon.svg?v=2') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/app.css') ?>?v=<?= esc($landingCssVersion, 'attr') ?>">
    <script src="<?= base_url('assets/url-mask.js') ?>"></script>
</head>
<body class="landing-page landing-single-page">
    <a class="landing-skip-link" href="#beranda">Lewati ke konten utama</a>

    <header class="single-landing-header">
        <a class="single-landing-brand" href="<?= site_url('/') ?>" aria-label="Jamkrindo Kanwil Surabaya">
            <span class="single-brand-logo">
                <img src="<?= base_url('assets/jamkrindo-kanwil-surabaya.png') ?>" alt="Jamkrindo Kanwil Surabaya">
            </span>
        </a>
        <nav class="single-landing-nav" aria-label="Navigasi utama">
            <a class="active" href="#beranda">Beranda</a>
            <a href="#fitur-ringkas">Fitur</a>
            <a href="#fitur-ringkas">Alur Kerja</a>
            <a href="#fitur-ringkas">Keamanan</a>
        </nav>
        <a class="single-nav-login" href="<?= $isLoggedIn ? site_url('dashboard') : site_url('login') ?>">
            <?= $isLoggedIn ? 'Dashboard' : 'Masuk' ?><span aria-hidden="true">→</span>
        </a>
    </header>

    <main class="single-landing-main" id="beranda">
        <div class="single-landing-copy">
            <p class="single-landing-kicker"><span></span> REGISTER OPERASIONAL DIGITAL</p>
            <h1>Operasional.<br><em>Terhubung.</em></h1>
            <h2>Document Management System</h2>
            <p class="single-landing-description">Kelola penerimaan, distribusi, agenda, dan progres dokumen dalam satu sistem yang tertib, aman, serta mudah dipantau.</p>

            <div class="single-landing-actions">
                <a class="single-primary-action" href="<?= $isLoggedIn ? site_url('dashboard') : site_url('login') ?>">
                    <?= $isLoggedIn ? 'Buka Dashboard' : 'Masuk ke Sistem' ?><span aria-hidden="true">→</span>
                </a>
                <span class="single-access-note"><i aria-hidden="true">✓</i> Akses sesuai kewenangan</span>
            </div>

            <div class="single-feature-row" id="fitur-ringkas" aria-label="Keunggulan Jamkrindo Kanwil Surabaya">
                <article><span>01</span><div><strong>Register Digital</strong><small>Data dokumen terpusat</small></div></article>
                <article><span>02</span><div><strong>Distribusi Terarah</strong><small>Proses mudah dipantau</small></div></article>
                <article><span>03</span><div><strong>Agenda Terhubung</strong><small>Riwayat terdokumentasi</small></div></article>
            </div>

            <div class="single-landing-dots" aria-hidden="true"><i class="active"></i><i></i><i></i><i></i></div>
        </div>

        <div class="single-wave-art" aria-hidden="true">
            <svg viewBox="0 0 820 720" preserveAspectRatio="xMidYMid slice">
                <defs>
                    <linearGradient id="waveMain" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#126fc1"/><stop offset=".5" stop-color="#0ca8b0"/><stop offset="1" stop-color="#27bd8d"/></linearGradient>
                    <linearGradient id="waveDeep" x1="0" y1="1" x2="1" y2="0"><stop offset="0" stop-color="#124da7"/><stop offset="1" stop-color="#087fba"/></linearGradient>
                    <linearGradient id="waveSoft" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#18a8b2"/><stop offset="1" stop-color="#29c095"/></linearGradient>
                </defs>
                <path fill="#1262ad" d="M820 0V720H54c48-93 132-109 184-181 74-103 36-215 139-295 95-74 207-14 299-71C742 132 772 65 820 0Z"/>
                <path fill="url(#waveMain)" d="M820 28V720H115c58-74 143-93 194-171 69-105 27-202 117-270 92-69 196-5 277-62 58-40 77-109 117-189Z"/>
                <path fill="#147baa" opacity=".86" d="M820 287V720H248c59-60 107-130 190-145 85-15 138 31 221-6 68-31 103-100 161-163Z"/>
                <path fill="url(#waveSoft)" d="M820 321V720H306c51-63 99-108 173-116 79-9 126 28 202-9 61-30 92-91 139-151Z"/>
                <path fill="url(#waveDeep)" d="M820 552V720H526c51-58 99-107 168-112 50-4 84 15 126 7Z"/>
            </svg>
            <div class="single-wave-badge"><span>✓</span><div><small>SISTEM AKTIF</small><strong>Dokumen terpantau</strong></div></div>
        </div>
    </main>
</body>
</html>
