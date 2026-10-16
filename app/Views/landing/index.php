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
$landingBackgroundVersion = $assetVersion('assets/images/landing-jamkrindo-office.png');
$landingLogoVersion = $assetVersion('assets/images/landing-jaksa-logo-photoroom.webp');
$landingHeaderLogoVersion = $assetVersion('assets/images/jaksa-sidebar-logo-selected.png');
$requiredMarkersVersion = $assetVersion($requiredMarkersAsset);
$urlMaskVersion = $assetVersion($urlMaskAsset);
$loginError        = session()->getFlashdata('login_error');
$logoutSuccess     = session()->getFlashdata('logout_success');
$sessionExpiryMessage = 'Sesi login telah berakhir setelah 2 jam. Silakan login kembali.';
$sessionExpired = $loginError === $sessionExpiryMessage;
$cardLoginError = $sessionExpired ? null : $loginError;
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
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="preload" as="image" type="image/png" href="<?= base_url('assets/images/landing-jamkrindo-office.png') ?>?v=<?= esc($landingBackgroundVersion, 'attr') ?>" fetchpriority="high">
    <link rel="preload" as="image" type="image/webp" href="<?= base_url('assets/images/landing-jaksa-logo-photoroom.webp') ?>?v=<?= esc($landingLogoVersion, 'attr') ?>" fetchpriority="high">
    <link rel="preload" as="image" type="image/png" href="<?= base_url('assets/images/jaksa-sidebar-logo-selected.png') ?>?v=<?= esc($landingHeaderLogoVersion, 'attr') ?>">
    <link rel="stylesheet" href="<?= base_url($landingCssAsset) ?>?v=<?= esc($landingCssVersion, 'attr') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/flowui-theme.css') ?>?v=<?= esc($flowuiThemeVersion, 'attr') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/landing-surabaya.css') ?>?v=<?= esc($landingSurabayaVersion, 'attr') ?>">
    <script src="<?= base_url($urlMaskAsset) ?>?v=<?= esc($urlMaskVersion, 'attr') ?>"></script>
    <script src="<?= base_url($requiredMarkersAsset) ?>?v=<?= esc($requiredMarkersVersion, 'attr') ?>" defer></script>
</head>

<body class="landing-page landing-surabaya-page">
    <a class="landing-skip-link" href="#beranda">Lewati ke konten utama</a>

    <header class="surabaya-landing-brandbar">
        <a class="surabaya-landing-brand" href="<?= site_url('/') ?>" aria-label="JAKSA">
            <img src="<?= base_url('assets/images/jaksa-sidebar-logo-selected.png') ?>?v=<?= esc($landingHeaderLogoVersion, 'attr') ?>" width="2138" height="736" alt="JAKSA" decoding="async">
            <span class="surabaya-landing-brand-title"><span>Jamkrindo Kanwil</span><span>Surabaya Operasional</span></span>
        </a>
        <nav class="surabaya-landing-links" aria-label="Navigasi utama">
            <a class="active" href="#beranda">Beranda</a>
            <a href="#tentang">Tentang</a>
            <a href="#panduan">Dashboard</a>
            <a href="#faq">SDM</a>
            <a href="#kontak">Kontak</a>
        </nav>
    </header>

    <?php if ($logoutSuccess || $sessionExpired): ?>
        <div class="landing-session-modal<?= $logoutSuccess ? ' success' : '' ?>" data-session-notice role="dialog" aria-modal="true" aria-labelledby="sessionNoticeTitle">
            <section class="landing-session-notice">
                <span class="landing-session-notice-icon" aria-hidden="true"><?= $logoutSuccess ? '✓' : '!' ?></span>
                <div>
                    <strong id="sessionNoticeTitle"><?= $logoutSuccess ? 'Berhasil keluar' : 'Sesi telah berakhir' ?></strong>
                    <p><?= esc($logoutSuccess ?: $sessionExpiryMessage) ?></p>
                </div>
                <button type="button" data-session-notice-close aria-label="Tutup notifikasi">×</button>
            </section>
        </div>
    <?php endif ?>

    <main class="surabaya-landing-main" id="beranda">
        <section class="surabaya-landing-copy" aria-labelledby="landingTitle">
            <h1 id="landingTitle">Satu Sistem,<br>Dukungan untuk<br>Kinerja <span class="landing-title-secondary">Kanwil Surabaya.</span></h1>
            <p>Mendukung pengelolaan dokumen dan administrasi internal secara lebih terintegrasi, efektif, dan aman.</p>
        </section>

        <section class="surabaya-login-card" aria-labelledby="surabayaLoginTitle">
            <img class="surabaya-login-logo" src="<?= base_url('assets/images/landing-jaksa-logo-photoroom.webp') ?>?v=<?= esc($landingLogoVersion, 'attr') ?>" width="640" height="220" alt="JAKSA" decoding="async">
            <p class="surabaya-login-title" aria-label="Jamkrindo Kanwil Surabaya Operasional"><span class="title-j">J</span><span class="title-a-orange">A</span>mkrindo <span class="title-k">K</span>anwil <span class="title-s">S</span>urabaya oper<span class="title-a-green">A</span>sional</p>
            <p class="surabaya-login-caption" id="surabayaLoginTitle">MASUK KE AKUN ANDA</p>

            <?php if ($cardLoginError): ?><div class="surabaya-login-alert" role="alert"><?= esc($cardLoginError) ?></div><?php endif ?>

            <?php if ($isLoggedIn): ?>
                <a class="surabaya-login-submit" href="<?= site_url('dashboard') ?>">Buka Dashboard <span aria-hidden="true">→</span></a>
            <?php else: ?>
                <form action="<?= site_url('login') ?>" method="post" class="surabaya-login-form">
                    <?= csrf_field() ?>
                    <div class="surabaya-field"><span class="surabaya-field-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="8" r="3.3"/><path d="M5.5 19c.55-3.18 2.7-4.8 6.5-4.8s5.95 1.62 6.5 4.8"/></svg></span><input id="surabayaUsername" name="username" type="text" value="<?= esc(old('username')) ?>" placeholder="Username" aria-label="Username" autocomplete="username" required><span class="surabaya-field-required" aria-hidden="true">*</span></div>
                    <div class="surabaya-field password"><span class="surabaya-field-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="5.5" y="10" width="13" height="9.5" rx="1.8"/><path d="M8.5 10V7.8a3.5 3.5 0 0 1 7 0V10"/></svg></span><input id="surabayaPassword" name="password" type="password" placeholder="Password" aria-label="Password" autocomplete="current-password" required><button type="button" data-surabaya-password-toggle aria-label="Tampilkan password"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M2.8 12s3.25-5 9.2-5 9.2 5 9.2 5-3.25 5-9.2 5-9.2-5-9.2-5Z"/><circle cx="12" cy="12" r="2.1"/></svg></button><span class="surabaya-field-required" aria-hidden="true">*</span></div>
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
            const sessionNotice = document.querySelector('[data-session-notice]');
            sessionNotice?.querySelector('[data-session-notice-close]')?.addEventListener('click', () => sessionNotice.remove());

            const surabayaPassword = document.getElementById('surabayaPassword');
            const surabayaPasswordToggle = document.querySelector('[data-surabaya-password-toggle]');
            surabayaPasswordToggle?.addEventListener('click', () => {
                if (!surabayaPassword) return;
                const visible = surabayaPassword.type === 'text';
                surabayaPassword.type = visible ? 'password' : 'text';
                surabayaPasswordToggle.classList.toggle('is-visible', !visible);
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
