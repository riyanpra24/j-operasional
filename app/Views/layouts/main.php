<?php
$uri = service('uri');
$segment = $uri->getSegment(1);
$securityActive = in_array($segment, ['dokumen-masuk', 'dokumen-keluar', 'distribusi-dokumen'], true);
$agendarisActive = $segment === 'agendaris';
$accountManagementActive = $segment === 'kelola-akun';
$agendarisPage = $agendarisActive && $uri->getTotalSegments() >= 2 ? $uri->getSegment(2) : 'surat-masuk';
$currentRole = (string) session()->get('auth_role');
$displayName = (string) session()->get('auth_display_name');
$canAccessSecurity = in_array($currentRole, ['admin', 'security'], true);
$canAccessAgendaris = in_array($currentRole, ['admin', 'agendaris'], true);
$roleLabel = \Config\UserRoles::label($currentRole);
$initial = strtoupper(substr($displayName !== '' ? $displayName : 'U', 0, 1));
$success = session()->getFlashdata('success');
$syncSuccess = session()->getFlashdata('sync_success');
$successToast = $syncSuccess ?: $success;
$successToastTitle = $syncSuccess ? 'Sinkronisasi berhasil' : 'Berhasil';
$error   = session()->getFlashdata('error');
$errors  = session()->getFlashdata('errors') ?? [];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Sistem register dokumen masuk operasional">
    <title><?= esc($title ?? 'Operasional') ?> | Register Operasional</title>
    <link rel="icon" type="image/svg+xml" href="<?= base_url('favicon.svg?v=2') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/app.css') ?>">
    <script>
        try {
            if (window.innerWidth > 900 && localStorage.getItem('j-operasional-sidebar') === 'collapsed') {
                document.documentElement.classList.add('sidebar-collapsed');
            }
        } catch (error) {}
    </script>
</head>
<body>
    <?php if ($successToast): ?>
        <div class="success-toast" role="status" aria-live="polite" data-success-toast>
            <span class="success-toast-icon" aria-hidden="true">✓</span>
            <div><strong><?= esc($successToastTitle) ?></strong><small><?= esc($successToast) ?></small></div>
            <button type="button" data-success-toast-close aria-label="Tutup notifikasi">×</button>
        </div>
    <?php endif ?>
    <div class="app-shell">
        <aside class="sidebar" id="sidebar">
            <a href="<?= site_url('dashboard') ?>" class="brand" aria-label="Dashboard Operasional">
                <span class="brand-mark">ϟ</span>
                <span><strong>J-Operasional</strong><small>Document Management</small></span>
            </a>

            <nav class="main-nav" aria-label="Navigasi utama">
                <p class="nav-label">MENU UTAMA</p>
                <a href="<?= site_url('dashboard') ?>" class="nav-link <?= $segment === 'dashboard' ? 'active' : '' ?>" title="Dashboard">
                    <span class="nav-icon icon-dashboard" aria-hidden="true">◆</span>
                    Dashboard
                </a>
                <?php if ($canAccessSecurity): ?>
                <div class="nav-group <?= $securityActive ? 'open' : '' ?>" data-nav-group>
                    <button type="button" class="nav-link nav-parent <?= $securityActive ? 'active' : '' ?>" data-nav-toggle aria-expanded="<?= $securityActive ? 'true' : 'false' ?>" aria-controls="securitySubmenu" title="Security">
                        <span class="nav-icon" aria-hidden="true">◈</span>
                        <span>Security</span>
                        <span class="nav-chevron" aria-hidden="true">⌄</span>
                    </button>
                    <div class="nav-submenu" id="securitySubmenu" data-nav-submenu <?= $securityActive ? '' : 'hidden' ?>>
                        <a href="<?= site_url('dokumen-masuk') ?>" class="nav-sublink <?= $segment === 'dokumen-masuk' ? 'active' : '' ?>">
                            <span aria-hidden="true">●</span>
                            Dokumen Masuk
                        </a>
                        <a href="<?= site_url('dokumen-keluar') ?>" class="nav-sublink <?= $segment === 'dokumen-keluar' ? 'active' : '' ?>">
                            <span aria-hidden="true">●</span>
                            Dokumen Keluar
                        </a>
                        <a href="<?= site_url('distribusi-dokumen') ?>" class="nav-sublink <?= $segment === 'distribusi-dokumen' ? 'active' : '' ?>">
                            <span aria-hidden="true">●</span>
                            Distribusi Dokumen
                        </a>
                    </div>
                </div>
                <?php endif ?>
                <?php if ($canAccessAgendaris): ?>
                <div class="nav-group <?= $agendarisActive ? 'open' : '' ?>" data-nav-group>
                    <button type="button" class="nav-link nav-parent <?= $agendarisActive ? 'active' : '' ?>" data-nav-toggle aria-expanded="<?= $agendarisActive ? 'true' : 'false' ?>" aria-controls="agendarisSubmenu" title="Agendaris">
                        <span class="nav-icon" aria-hidden="true">▦</span>
                        <span>Agendaris</span>
                        <span class="nav-chevron" aria-hidden="true">⌄</span>
                    </button>
                    <div class="nav-submenu" id="agendarisSubmenu" data-nav-submenu <?= $agendarisActive ? '' : 'hidden' ?>>
                        <a href="<?= site_url('agendaris/surat-masuk') ?>" class="nav-sublink <?= $agendarisActive && $agendarisPage === 'surat-masuk' ? 'active' : '' ?>">
                            <span aria-hidden="true">●</span>
                            Dokumen Masuk
                        </a>
                        <a href="<?= site_url('agendaris/surat-keluar') ?>" class="nav-sublink <?= $agendarisActive && $agendarisPage === 'surat-keluar' ? 'active' : '' ?>">
                            <span aria-hidden="true">●</span>
                            Dokumen Keluar
                        </a>
                        <a href="<?= site_url('agendaris/progres-dokumen') ?>" class="nav-sublink <?= $agendarisActive && $agendarisPage === 'progres-dokumen' ? 'active' : '' ?>">
                            <span aria-hidden="true">●</span>
                            Progres Dokumen
                        </a>
                    </div>
                </div>
                <?php endif ?>
                <?php if ($currentRole === 'admin'): ?>
                <a href="<?= site_url('kelola-akun') ?>" class="nav-link <?= $accountManagementActive ? 'active' : '' ?>" title="Kelola Akun">
                    <span class="nav-icon" aria-hidden="true">♙</span>
                    Kelola Akun
                </a>
                <?php endif ?>
                <p class="nav-label nav-label-spaced">KONFIGURASI</p>
                <span class="nav-link nav-link-muted" title="Database Aktif">
                    <span class="nav-icon" aria-hidden="true">◉</span>
                    Database Aktif
                    <span class="nav-status-dot"></span>
                </span>
            </nav>

            <div class="sidebar-account">
                <span class="sidebar-account-avatar"><?= esc($initial) ?></span>
                <div>
                    <strong><?= esc($displayName) ?></strong>
                    <small><?= esc($roleLabel) ?></small>
                </div>
                <form action="<?= site_url('logout') ?>" method="post">
                    <?= csrf_field() ?>
                    <button type="submit" title="Keluar" aria-label="Keluar dari sistem">↪</button>
                </form>
            </div>
            <div class="sidebar-pattern" aria-hidden="true">OPERASIONAL</div>
        </aside>

        <button class="sidebar-backdrop" type="button" data-sidebar-close aria-label="Tutup menu"></button>

        <div class="content-shell">
            <header class="topbar">
                <button class="menu-toggle" type="button" data-sidebar-toggle aria-label="Tutup menu" aria-controls="sidebar" aria-expanded="true"><span aria-hidden="true">☰</span></button>
                <div class="topbar-title">
                    <span>OPERASIONAL / <?= strtoupper(esc($title ?? 'Dashboard')) ?></span>
                    <strong><?= esc($title ?? 'Dashboard') ?></strong>
                </div>
                <div class="topbar-meta">
                    <button class="topbar-icon" type="button" aria-label="Notifikasi">♢<i></i></button>
                    <span class="date-pill"><?= date('d M Y') ?></span>
                </div>
            </header>

            <main class="main-content">
                <?php if ($error): ?>
                    <div class="alert alert-danger" role="alert">
                        <strong>Terjadi kendala</strong>
                        <span><?= esc($error) ?></span>
                        <button type="button" class="alert-close" aria-label="Tutup">×</button>
                    </div>
                <?php endif ?>

                <?php if ($errors): ?>
                    <div class="alert alert-danger" role="alert">
                        <strong>Periksa kembali data berikut:</strong>
                        <ul>
                            <?php foreach ($errors as $validationError): ?>
                                <li><?= esc($validationError) ?></li>
                            <?php endforeach ?>
                        </ul>
                    </div>
                <?php endif ?>

                <?= $this->renderSection('content') ?>
            </main>

            <footer class="footer">
                <span>Register Operasional · <?= date('Y') ?></span>
                <span>Waktu sistem: Asia/Jakarta</span>
            </footer>
        </div>
    </div>
    <?php if ($canAccessSecurity): ?>
        <?= view('components/input_modal') ?>
        <?= view('components/detail_modal') ?>
        <?= view('components/edit_modal') ?>
        <?= view('components/delete_modal') ?>
    <?php endif ?>
    <script src="<?= base_url('assets/app.js') ?>"></script>
</body>
</html>
