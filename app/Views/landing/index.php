<?php
$landingCssVersion = is_file(FCPATH . 'assets/app.css') ? (string) filemtime(FCPATH . 'assets/app.css') : '1';
$loginError        = session()->getFlashdata('login_error');
$logoutSuccess     = session()->getFlashdata('logout_success');
$openLoginModal    = (bool) session()->getFlashdata('open_login_modal')
    || $loginError !== null
    || $logoutSuccess !== null
    || service('request')->getGet('login') === '1';
?>
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

<body class="landing-page geo-landing-page">
    <a class="landing-skip-link" href="#beranda">Lewati ke konten utama</a>

    <header class="geo-landing-header">
        <a class="geo-landing-brand" href="<?= site_url('/') ?>" aria-label="Jamkrindo Kanwil Surabaya">
            <span class="geo-brand-logo">
                <img src="<?= base_url('assets/jamkrindo-kanwil-surabaya.png') ?>" alt="Jamkrindo Kanwil Surabaya">
            </span>
        </a>
        <nav class="geo-landing-nav" aria-label="Navigasi utama">
            <a class="active" href="#beranda">Beranda</a>
            <a href="#fitur-ringkas">Fitur</a>
            <a href="#fitur-ringkas">Alur Kerja</a>
            <a href="#fitur-ringkas">Keamanan</a>
        </nav>
    </header>

    <main class="geo-landing-main" id="beranda">
        <section class="geo-landing-copy">
            <p class="geo-landing-kicker">PORTAL DIGITAL OPERASIONAL KANWIL SURABAYA</p>
            <h1 class="geo-landing-wordmark">
                <img src="<?= base_url('assets/images/jaksa-wordmark.png') ?>" alt="JAKSA">
            </h1>
            <h2 class="geo-landing-acronym" aria-label="Jamkrindo Kanwil Surabaya Operasional">
                <span><b>JA</b>mkrindo</span>
                <i aria-hidden="true">|</i>
                <span><b>K</b>anwil</span>
                <i aria-hidden="true">|</i>
                <span><b>S</b>urabaya</span>
                <i aria-hidden="true">|</i>
                <span>oper<b>A</b>sional</span>
            </h2>
            <p class="geo-landing-description" id="fitur-ringkas">Akses dashboard, pengelolaan, monitoring, dan kegiatan operasional dalam satu sistem operasional yang tertib, aman, serta mudah dipantau.</p>

            <div class="geo-landing-actions">
                <?php if ($isLoggedIn): ?>
                    <a class="geo-primary-action" href="<?= site_url('dashboard') ?>">Buka Dashboard<span aria-hidden="true">→</span></a>
                <?php else: ?>
                    <button class="geo-primary-action" type="button" data-login-open>Masuk ke Sistem<span aria-hidden="true">→</span></button>
                <?php endif ?>
            </div>
        </section>

        <aside class="geo-landing-art" aria-hidden="true">
            <div class="geo-tech-grid"></div>
            <div class="geo-system-visual">
                <div class="geo-system-orbit orbit-one"></div>
                <div class="geo-system-orbit orbit-two"></div>
                <span class="geo-system-connection connection-one"></span>
                <span class="geo-system-connection connection-two"></span>
                <span class="geo-system-connection connection-three"></span>
                <span class="geo-system-connection connection-four"></span>

                <div class="geo-core-card">
                    <span class="geo-core-icon">▦</span>
                    <strong>KANWIL SURABAYA</strong>
                    <small>OPERASIONAL</small>
                </div>

                <div class="geo-flow-node node-incoming"><b>01</b><span>Umum</span></div>
                <div class="geo-flow-node node-agenda"><b>02</b><span>Akuntansi</span></div>
                <div class="geo-flow-node node-distribution"><b>03</b><span>SDM</span></div>
                <div class="geo-flow-node node-archive"><b>04</b><span>Agendaris</span></div>
            </div>
            <span class="geo-decor-dot dot-one"></span>
            <span class="geo-decor-dot dot-two"></span>
            <span class="geo-decor-dot dot-three"></span>
        </aside>
    </main>

    <div
        class="landing-login-modal<?= $openLoginModal ? ' open' : '' ?>"
        data-login-modal
        data-open-on-load="<?= $openLoginModal ? 'true' : 'false' ?>"
        aria-hidden="<?= $openLoginModal ? 'false' : 'true' ?>"
        <?= $openLoginModal ? '' : 'hidden' ?>>
        <button type="button" class="landing-login-backdrop" data-login-close aria-label="Tutup formulir login"></button>
        <section class="landing-login-dialog" role="dialog" aria-modal="true" aria-labelledby="landingLoginTitle">
            <button type="button" class="landing-login-close" data-login-close aria-label="Tutup">×</button>

            <header class="landing-login-header">
                <span class="landing-login-mark" aria-hidden="true"></span>
                <div>
                    <small>JAMKRINDO KANWIL SURABAYA</small>
                    <strong id="landingLoginTitle">Masuk ke Sistem</strong>
                    <p>Gunakan akun operasional yang telah diberikan.</p>
                </div>
            </header>

            <div class="landing-login-body">
                <?php if ($loginError): ?>
                    <div class="landing-login-alert danger" role="alert"><?= esc($loginError) ?></div>
                <?php endif ?>

                <?php if ($logoutSuccess): ?>
                    <div class="landing-login-alert success" role="status"><?= esc($logoutSuccess) ?></div>
                <?php endif ?>

                <form action="<?= site_url('login') ?>" method="post" class="landing-login-form">
                    <?= csrf_field() ?>
                    <div class="form-group">
                        <label for="landingUsername">Username</label>
                        <input
                            id="landingUsername"
                            name="username"
                            type="text"
                            value="<?= esc(old('username')) ?>"
                            placeholder="Masukkan username"
                            autocomplete="username"
                            required>
                    </div>
                    <div class="form-group">
                        <label for="landingPassword">Password</label>
                        <div class="landing-password-field">
                            <input
                                id="landingPassword"
                                name="password"
                                type="password"
                                placeholder="Masukkan password"
                                autocomplete="current-password"
                                required>
                            <button type="button" data-landing-password-toggle aria-label="Tampilkan password">Lihat</button>
                        </div>
                    </div>
                    <button type="submit" class="landing-login-submit">Masuk ke Sistem <span aria-hidden="true">→</span></button>
                </form>
                <p class="landing-login-help">Hubungi IT Kanwil apabila mengalami kendala akses.</p>
            </div>
        </section>
    </div>

    <script>
        (() => {
            const modal = document.querySelector('[data-login-modal]');
            if (!modal) return;

            const username = document.getElementById('landingUsername');
            const password = document.getElementById('landingPassword');
            const passwordToggle = document.querySelector('[data-landing-password-toggle]');
            let previousFocus = null;

            const openModal = () => {
                previousFocus = document.activeElement;
                modal.hidden = false;
                modal.setAttribute('aria-hidden', 'false');
                document.body.classList.add('landing-modal-open');
                requestAnimationFrame(() => {
                    modal.classList.add('open');
                    username?.focus();
                });
            };

            const closeModal = () => {
                modal.classList.remove('open');
                modal.setAttribute('aria-hidden', 'true');
                document.body.classList.remove('landing-modal-open');
                window.setTimeout(() => {
                    modal.hidden = true;
                    previousFocus?.focus?.();
                }, 180);
            };

            document.querySelectorAll('[data-login-open]').forEach((button) => button.addEventListener('click', openModal));
            modal.querySelectorAll('[data-login-close]').forEach((button) => button.addEventListener('click', closeModal));

            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape' && !modal.hidden) closeModal();
            });

            passwordToggle?.addEventListener('click', () => {
                const visible = password?.type === 'text';
                if (!password) return;
                password.type = visible ? 'password' : 'text';
                passwordToggle.textContent = visible ? 'Lihat' : 'Sembunyikan';
                passwordToggle.setAttribute('aria-label', visible ? 'Tampilkan password' : 'Sembunyikan password');
            });

            if (modal.dataset.openOnLoad === 'true') {
                openModal();
                const url = new URL(window.location.href);
                if (url.searchParams.has('login')) {
                    url.searchParams.delete('login');
                    window.history.replaceState({}, '', url.pathname + url.search + url.hash);
                }
            }
        })();
    </script>
</body>

</html>