<?php
$loginError    = $loginErrorOverride ?? session()->getFlashdata('login_error');
$logoutSuccess = session()->getFlashdata('logout_success');
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Login Sistem Register Operasional">
    <title><?= esc($title ?? 'Login') ?> | J-Operasional</title>
    <link rel="icon" type="image/svg+xml" href="<?= base_url('favicon.svg?v=2') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/app.css') ?>">
    <script src="<?= base_url('assets/url-mask.js') ?>"></script>
</head>

<body class="login-page">
    <main class="login-shell">
        <section class="login-brand-panel" aria-label="Informasi aplikasi">
            <div class="login-brand">
                <span class="login-brand-mark">ϟ</span>
                <div>
                    <strong>J-Operasional</strong>
                    <small>Operasional Management</small>
                </div>
            </div>
            <div class="login-brand-copy">
                <span>PORTAL DIGITAL</span>
                <h1>Kelola aktivitas operasional dalam satu sistem.</h1>
                <p>Akses dashboard, pengelolaan, monitoring, dan kegiatan operasional sesuai kewenangan akun Anda.</p>
            </div>
            <small class="login-copyright">Register Operasional · <?= date('Y') ?></small>
        </section>

        <section class="login-form-panel">
            <div class="login-form-wrap">
                <span class="login-eyebrow">AKSES SISTEM</span>
                <h2>Selamat datang</h2>
                <p>Masukkan akun yang telah diberikan</p>

                <?php if ($loginError): ?>
                    <div class="login-alert danger" role="alert"><?= esc($loginError) ?></div>
                <?php endif ?>

                <?php if ($logoutSuccess): ?>
                    <div class="login-alert success" role="status"><?= esc($logoutSuccess) ?></div>
                <?php endif ?>

                <form action="<?= site_url('login') ?>" method="post" class="login-form">
                    <?= csrf_field() ?>
                    <div class="form-group">
                        <label for="username">Username</label>
                        <input
                            id="username"
                            name="username"
                            type="text"
                            value="<?= esc(old('username')) ?>"
                            placeholder="Masukkan username"
                            autocomplete="username"
                            required
                            autofocus>
                    </div>
                    <div class="form-group">
                        <label for="password">Password</label>
                        <div class="password-field">
                            <input
                                id="password"
                                name="password"
                                type="password"
                                placeholder="Masukkan password"
                                autocomplete="current-password"
                                required>
                            <button type="button" data-password-toggle aria-label="Tampilkan password">Lihat</button>
                        </div>
                    </div>
                    <button class="btn btn-primary login-submit" type="submit">Masuk ke sistem</button>
                </form>
                <p class="login-help">Hubungi IT Kanwil apabila mengalami kendala akses.</p>
            </div>
        </section>
    </main>
    <script>
        try {
            localStorage.removeItem('j-operasional-active-tab');
            sessionStorage.removeItem('j-operasional-current-tab');
            if (window.name.startsWith('j-operasional-tab:')) window.name = '';
        } catch (error) {}

        const toggle = document.querySelector('[data-password-toggle]');
        const password = document.getElementById('password');
        toggle?.addEventListener('click', () => {
            const visible = password.type === 'text';
            password.type = visible ? 'password' : 'text';
            toggle.textContent = visible ? 'Lihat' : 'Sembunyikan';
            toggle.setAttribute('aria-label', visible ? 'Tampilkan password' : 'Sembunyikan password');
        });
    </script>
</body>

</html>
