<?php

/** @var bool $isLoggedIn */
/** @var string|null $title */

$resolveOptimizedAsset = static function (string $source, string $optimized): string {
    $sourcePath = FCPATH . $source;
    $optimizedPath = FCPATH . $optimized;

    return is_file($optimizedPath)
        && (! is_file($sourcePath) || filemtime($optimizedPath) >= filemtime($sourcePath))
        ? $optimized
        : $source;
};
$assetVersion = static function (string $asset): string {
    $path = FCPATH . $asset;
    return is_file($path) ? (md5_file($path) ?: '1') : '1';
};
$landingCssAsset = $resolveOptimizedAsset('assets/app.css', 'assets/app.min.css');
$requiredMarkersAsset = $resolveOptimizedAsset('assets/required-markers.js', 'assets/required-markers.min.js');
$urlMaskAsset = $resolveOptimizedAsset('assets/url-mask.js', 'assets/url-mask.min.js');
$landingCssVersion = $assetVersion($landingCssAsset);
$flowuiThemeVersion = $assetVersion('assets/flowui-theme.css');
$landingSurabayaVersion = $assetVersion('assets/landing-surabaya.css');
$landingBackgroundVersion = $assetVersion('assets/images/landing-surabaya-background-v2.png');
$landingLogoVersion = $assetVersion('assets/images/landing-jaksa-logo-photoroom.png');
$requiredMarkersVersion = $assetVersion($requiredMarkersAsset);
$urlMaskVersion = $assetVersion($urlMaskAsset);
$loginError        = session()->getFlashdata('login_error');
$logoutSuccess     = session()->getFlashdata('logout_success');
$openAdminTakeover = (bool) session()->getFlashdata('open_admin_takeover_modal');
$adminTakeoverError = session()->getFlashdata('admin_takeover_error');
$adminTakeoverName = (string) session()->get('admin_takeover_display_name');
$adminTakeoverDevice = (string) session()->get('admin_takeover_device');
$adminTakeoverIp = (string) session()->get('admin_takeover_ip');
$adminTakeoverLastSeen = (string) session()->get('admin_takeover_last_seen_at');
$openLoginModal = false;
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Jamkrindo Kanwil Surabaya, sistem pengelolaan dokumen operasional.">
    <title>JAKSA | Jamkrindo Kanwil Surabaya Operasional</title>
    <link rel="icon" type="image/png" href="<?= base_url('assets/images/jaksa-favicon.png?v=1') ?>">
    <link rel="preload" as="image" type="image/png" href="<?= base_url('assets/images/landing-surabaya-background-v2.png') ?>?v=<?= esc($landingBackgroundVersion, 'attr') ?>" fetchpriority="high">
    <link rel="preload" as="image" type="image/png" href="<?= base_url('assets/images/landing-jaksa-logo-photoroom.png') ?>?v=<?= esc($landingLogoVersion, 'attr') ?>" fetchpriority="high">
    <link rel="stylesheet" href="<?= base_url($landingCssAsset) ?>?v=<?= esc($landingCssVersion, 'attr') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/flowui-theme.css') ?>?v=<?= esc($flowuiThemeVersion, 'attr') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/landing-surabaya.css') ?>?v=<?= esc($landingSurabayaVersion, 'attr') ?>">
    <script src="<?= base_url($urlMaskAsset) ?>?v=<?= esc($urlMaskVersion, 'attr') ?>"></script>
    <script src="<?= base_url($requiredMarkersAsset) ?>?v=<?= esc($requiredMarkersVersion, 'attr') ?>" defer></script>
</head>

<body class="landing-page landing-surabaya-page">
    <a class="landing-skip-link" href="#beranda">Lewati ke konten utama</a>

    <header class="surabaya-landing-header">
        <nav class="surabaya-landing-nav" aria-label="Navigasi utama">
            <a class="surabaya-landing-brand" href="<?= site_url('/') ?>" aria-label="JAKSA">
                <img src="<?= base_url('assets/images/landing-jaksa-logo-photoroom.png') ?>?v=<?= esc($landingLogoVersion, 'attr') ?>" width="2138" height="735" alt="JAKSA" decoding="async">
            </a>
            <div class="surabaya-landing-links">
                <a class="active" href="#beranda">Beranda</a>
                <a href="#tentang">Tentang</a>
                <a href="#panduan">Dashboard</a>
                <a href="#faq">SDM</a>
                <a href="#kontak">Kontak</a>
            </div>
        </nav>
    </header>

    <main class="surabaya-landing-main" id="beranda">
        <section class="surabaya-landing-copy" aria-labelledby="landingTitle">
            <p class="surabaya-landing-kicker">Jamkrindo<span>Kanwil Surabaya Application</span></p>
            <h1 id="landingTitle"><span class="landing-title-primary">Satu Sistem,<br>Dukungan untuk</span><br><span class="landing-title-secondary">Kinerja Kanwil Surabaya.</span></h1>
            <p>Mendukung pengelolaan dokumen dan administrasi internal secara lebih terintegrasi, efektif, dan aman.</p>
        </section>

        <section class="surabaya-login-card" aria-labelledby="surabayaLoginTitle">
            <img class="surabaya-login-logo" src="<?= base_url('assets/images/landing-jaksa-logo-photoroom.png') ?>?v=<?= esc($landingLogoVersion, 'attr') ?>" width="2138" height="735" alt="JAKSA" decoding="async">
            <p class="surabaya-login-caption" id="surabayaLoginTitle">MASUK KE AKUN ANDA</p>

            <?php if ($loginError): ?><div class="surabaya-login-alert" role="alert"><?= esc($loginError) ?></div><?php endif ?>
            <?php if ($logoutSuccess): ?><div class="surabaya-login-alert success" role="status"><?= esc($logoutSuccess) ?></div><?php endif ?>

            <?php if ($isLoggedIn): ?>
                <a class="surabaya-login-submit" href="<?= site_url('dashboard') ?>">Buka Dashboard <span aria-hidden="true">→</span></a>
            <?php else: ?>
                <form action="<?= site_url('login') ?>" method="post" class="surabaya-login-form">
                    <?= csrf_field() ?>
                    <label class="surabaya-field" for="surabayaUsername"><span aria-hidden="true">♙</span><input id="surabayaUsername" name="username" type="text" value="<?= esc(old('username')) ?>" placeholder="Username" autocomplete="username" required></label>
                    <label class="surabaya-field password" for="surabayaPassword"><span aria-hidden="true">♧</span><input id="surabayaPassword" name="password" type="password" placeholder="Password" autocomplete="current-password" required><button type="button" data-surabaya-password-toggle aria-label="Tampilkan password">◉</button></label>
                    <p class="surabaya-login-hint">Masukan User dan Password yang Telah Diberikan</p>
                    <div class="surabaya-login-options">
                        <label><input type="checkbox" name="remember" value="1"> <span>Ingat saya</span></label>
                        <a href="#kontak">Lupa password?</a>
                    </div>
                    <button type="submit" class="surabaya-login-submit">Masuk <span aria-hidden="true">→</span></button>
                </form>
            <?php endif ?>
            <p class="surabaya-login-secure"><span></span>AKSES AMAN UNTUK LINGKUNGAN INTERNAL<span></span></p>
        </section>
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

    <div
        class="landing-login-modal<?= $openAdminTakeover ? ' open' : '' ?>"
        data-admin-takeover-modal
        data-open-on-load="<?= $openAdminTakeover ? 'true' : 'false' ?>"
        aria-hidden="<?= $openAdminTakeover ? 'false' : 'true' ?>"
        <?= $openAdminTakeover ? '' : 'hidden' ?>>
        <button type="button" class="landing-login-backdrop" data-admin-takeover-close aria-label="Tutup konfirmasi sesi admin"></button>
        <section class="landing-login-dialog admin-takeover-dialog" role="alertdialog" aria-modal="true" aria-labelledby="adminTakeoverTitle">
            <button type="button" class="landing-login-close" data-admin-takeover-close aria-label="Tutup">×</button>
            <header class="landing-login-header">
                <span class="landing-login-mark admin-takeover-mark" aria-hidden="true">!</span>
                <div>
                    <small>PERINGATAN SESI ADMIN</small>
                    <strong id="adminTakeoverTitle">Akun digunakan di perangkat lain</strong>
                    <p>Masukkan PIN khusus untuk mengeluarkan sesi lama dan melanjutkan login di perangkat ini.</p>
                </div>
            </header>
            <div class="landing-login-body">
                <div class="admin-takeover-device">
                    <strong><?= esc($adminTakeoverName !== '' ? $adminTakeoverName : 'Administrator') ?></strong>
                    <span><?= esc($adminTakeoverDevice !== '' ? $adminTakeoverDevice : 'Perangkat tidak dikenal') ?></span>
                    <?php if ($adminTakeoverIp !== ''): ?><small>IP <?= esc($adminTakeoverIp) ?></small><?php endif ?>
                    <?php if ($adminTakeoverLastSeen !== ''): ?><small>Terakhir aktif <?= date('d-m-Y H:i', strtotime($adminTakeoverLastSeen)) ?> WIB</small><?php endif ?>
                </div>
                <?php if ($adminTakeoverError): ?><div class="landing-login-alert danger" role="alert"><?= esc($adminTakeoverError) ?></div><?php endif ?>
                <form action="<?= site_url('login/admin-takeover') ?>" method="post" class="landing-login-form">
                    <?= csrf_field() ?>
                    <div class="form-group">
                        <label for="adminTakeoverPin">PIN login admin</label>
                        <div class="landing-password-field">
                            <input id="adminTakeoverPin" name="admin_login_pin" type="password" inputmode="numeric" pattern="[0-9]{6}" minlength="6" maxlength="6" autocomplete="one-time-code" placeholder="Masukkan 6 angka" required>
                            <button type="button" data-admin-pin-toggle aria-label="Tampilkan PIN">Lihat</button>
                        </div>
                    </div>
                    <button type="submit" class="landing-login-submit admin-takeover-submit">Keluarkan perangkat lain <span aria-hidden="true">→</span></button>
                </form>
                <p class="landing-login-help">Tindakan ini langsung menonaktifkan sesi admin pada perangkat sebelumnya.</p>
            </div>
        </section>
    </div>

    <script>
        (() => {
            const surabayaPassword = document.getElementById('surabayaPassword');
            const surabayaPasswordToggle = document.querySelector('[data-surabaya-password-toggle]');
            surabayaPasswordToggle?.addEventListener('click', () => {
                if (!surabayaPassword) return;
                const visible = surabayaPassword.type === 'text';
                surabayaPassword.type = visible ? 'password' : 'text';
                surabayaPasswordToggle.textContent = visible ? '◉' : '⊘';
                surabayaPasswordToggle.setAttribute('aria-label', visible ? 'Tampilkan password' : 'Sembunyikan password');
            });

            const modal = document.querySelector('[data-login-modal]');
            if (!modal) return;

            const username = document.getElementById('landingUsername');
            const password = document.getElementById('landingPassword');
            const passwordToggle = document.querySelector('[data-landing-password-toggle]');
            const loginForm = modal.querySelector('.landing-login-form');
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

            loginForm?.addEventListener('submit', (event) => {
                if (loginForm.dataset.submitting === 'true') {
                    event.preventDefault();
                    return;
                }
                loginForm.dataset.submitting = 'true';
                const submitButton = loginForm.querySelector('button[type="submit"]');
                if (submitButton) {
                    submitButton.disabled = true;
                    submitButton.textContent = 'Memproses...';
                }
            });

            if (modal.dataset.openOnLoad === 'true') {
                openModal();
                const url = new URL(window.location.href);
                if (url.searchParams.has('login')) {
                    url.searchParams.delete('login');
                    window.history.replaceState({}, '', url.pathname + url.search + url.hash);
                }
            }

            const takeoverModal = document.querySelector('[data-admin-takeover-modal]');
            const takeoverPin = document.getElementById('adminTakeoverPin');
            const takeoverPinToggle = document.querySelector('[data-admin-pin-toggle]');
            const takeoverForm = takeoverModal?.querySelector('.landing-login-form');
            const openTakeover = () => {
                if (!takeoverModal) return;
                takeoverModal.hidden = false;
                takeoverModal.setAttribute('aria-hidden', 'false');
                document.body.classList.add('landing-modal-open');
                requestAnimationFrame(() => {
                    takeoverModal.classList.add('open');
                    takeoverPin?.focus();
                });
            };
            const closeTakeover = () => {
                if (!takeoverModal) return;
                takeoverModal.classList.remove('open');
                takeoverModal.setAttribute('aria-hidden', 'true');
                document.body.classList.remove('landing-modal-open');
                window.setTimeout(() => {
                    takeoverModal.hidden = true;
                }, 180);
            };
            takeoverModal?.querySelectorAll('[data-admin-takeover-close]').forEach(button => button.addEventListener('click', closeTakeover));
            takeoverPinToggle?.addEventListener('click', () => {
                if (!takeoverPin) return;
                const visible = takeoverPin.type === 'text';
                takeoverPin.type = visible ? 'password' : 'text';
                takeoverPinToggle.textContent = visible ? 'Lihat' : 'Sembunyikan';
                takeoverPinToggle.setAttribute('aria-label', visible ? 'Tampilkan PIN' : 'Sembunyikan PIN');
            });
            takeoverForm?.addEventListener('submit', (event) => {
                if (takeoverForm.dataset.submitting === 'true') {
                    event.preventDefault();
                    return;
                }
                takeoverForm.dataset.submitting = 'true';
                const submitButton = takeoverForm.querySelector('button[type="submit"]');
                if (submitButton) {
                    submitButton.disabled = true;
                    submitButton.textContent = 'Memproses...';
                }
            });
            if (takeoverModal?.dataset.openOnLoad === 'true') openTakeover();
        })();
    </script>
</body>

</html>
