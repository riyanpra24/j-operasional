<?php
$uri = service('uri');
$segment = $uri->getSegment(1);
$securityActive = in_array($segment, ['dokumen-masuk', 'dokumen-keluar', 'distribusi-dokumen'], true);
$agendarisActive = $segment === 'agendaris';
$accountManagementActive = in_array($segment, ['kelola-akun', 'data-terhapus'], true);
$accountManagementPage = $segment === 'data-terhapus'
    ? 'deleted-data'
    : ($accountManagementActive && $uri->getTotalSegments() >= 2 && $uri->getSegment(2) === 'session-account'
        ? 'session-account'
        : 'add-account');
$generalSectionActive = $segment === 'bagian-umum-1';
$generalSectionPage = $generalSectionActive && $uri->getTotalSegments() >= 2
    ? $uri->getSegment(2)
    : 'pks-barang-jasa';
$generalSectionTwoActive = $segment === 'bagian-umum-2';
$sdmActive = $segment === 'sdm';
$akutansiActive = $segment === 'akutansi';
$agendarisPage = $agendarisActive && $uri->getTotalSegments() >= 2 ? $uri->getSegment(2) : 'surat-masuk';
$currentRole = (string) session()->get('auth_role');
$displayName = (string) session()->get('auth_display_name');
$authUsername = (string) session()->get('auth_username');
$canAccessSecurity = in_array($currentRole, ['admin', 'security'], true);
$canAccessAgendaris = in_array($currentRole, ['admin', 'agendaris'], true);
$canAccessGeneralSection = in_array($currentRole, ['admin', 'umum_1'], true);
$canAccessGeneralSectionTwo = in_array($currentRole, ['admin', 'umum_2'], true);
$canAccessSdm = in_array($currentRole, ['admin', 'sdm'], true);
$canAccessAkutansi = in_array($currentRole, ['admin', 'akutansi'], true);
$incomingArchive = $segment === 'dokumen-masuk';
$incomingWorkspace = in_array($segment, ['dashboard', 'dokumen-masuk', 'distribusi-dokumen'], true);
$roleLabel = \Config\UserRoles::label($currentRole);
$initial = strtoupper(substr($displayName !== '' ? $displayName : 'U', 0, 1));
$success = session()->getFlashdata('success');
$syncSuccess = session()->getFlashdata('sync_success');
$successToast = $syncSuccess ?: $success;
$successToastTitle = $syncSuccess ? 'Sinkronisasi berhasil' : 'Berhasil';
$error   = session()->getFlashdata('error');
$errors  = session()->getFlashdata('errors') ?? [];
$appCssVersion = is_file(FCPATH . 'assets/app.css') ? (string) filemtime(FCPATH . 'assets/app.css') : '1';
$appJsVersion  = is_file(FCPATH . 'assets/app.js') ? (string) filemtime(FCPATH . 'assets/app.js') : '1';
$requiredMarkersVersion = is_file(FCPATH . 'assets/required-markers.js') ? (string) filemtime(FCPATH . 'assets/required-markers.js') : '1';
$authExpiresAt = (int) session()->get('auth_expires_at');
$roleNotifications = [
    'total' => 0,
    'incoming_count' => 0,
    'outgoing_count' => 0,
    'items' => [],
];
$notificationEnabled = in_array($currentRole, ['security', 'agendaris'], true);
$notificationRoleLabel = strtoupper($roleLabel);
$notificationAllUrl = '#';
$deletedDataCount = 0;
if ($currentRole === 'admin') {
    try {
        foreach (['dokumen_masuk', 'agendaris', 'dokumen_keluar', 'dokumen_spk', 'pks_kerjasama', 'pks_dokumen_kerjasama', 'pks_item_kerjasama', 'users', 'vehicles', 'vehicle_maintenance', 'vehicle_documents'] as $deletedTable) {
            $deletedDataCount += db_connect()->table($deletedTable)->where('deleted_at IS NOT NULL', null, false)->countAllResults();
        }
    } catch (\Throwable) {
        $deletedDataCount = 0;
    }
}
if ($currentRole === 'security') {
    $roleNotifications = (new \App\Libraries\SecurityNotificationService())->summary();
    $notificationAllUrl = site_url('distribusi-dokumen');
} elseif ($currentRole === 'agendaris') {
    $roleNotifications = (new \App\Libraries\AgendarisNotificationService())->summary();
    $notificationAllUrl = site_url('agendaris/progres-dokumen');
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Sistem register dokumen masuk operasional">
    <title>JAKSA | Jamkrindo Kanwil Surabaya Operasional</title>
    <link rel="icon" type="image/png" href="<?= base_url('assets/images/jaksa-favicon.png?v=1') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/app.css') ?>?v=<?= esc($appCssVersion, 'attr') ?>">
    <script src="<?= base_url('assets/url-mask.js') ?>"></script>
    <script src="<?= base_url('assets/required-markers.js') ?>?v=<?= esc($requiredMarkersVersion, 'attr') ?>" defer></script>
    <script>
        try {
            if (window.innerWidth > 900 && localStorage.getItem('j-operasional-sidebar') === 'collapsed') {
                document.documentElement.classList.add('sidebar-collapsed');
            }
        } catch (error) {}
    </script>
</head>
<body
    data-auth-expires-at="<?= $authExpiresAt ?>"
    data-login-url="<?= esc(site_url('login'), 'attr') ?>"
    data-landing-url="<?= esc(site_url('/') . '?login=1', 'attr') ?>"
    data-logout-url="<?= esc(site_url('logout'), 'attr') ?>"
    data-csrf-name="<?= esc(csrf_token(), 'attr') ?>"
    data-csrf-hash="<?= esc(csrf_hash(), 'attr') ?>">
    <?php if ($successToast): ?>
        <div class="success-toast" role="status" aria-live="polite" data-success-toast>
            <span class="success-toast-icon" aria-hidden="true">✓</span>
            <div><strong><?= esc($successToastTitle) ?></strong><small><?= esc($successToast) ?></small></div>
            <button type="button" data-success-toast-close aria-label="Tutup notifikasi">×</button>
        </div>
    <?php endif ?>
    <div class="app-shell">
        <aside class="sidebar" id="sidebar">
            <a href="<?= site_url('dashboard') ?>" class="brand brand-jaksa" aria-label="Dashboard JAKSA — Jamkrindo Kanwil Surabaya Operasional">
                <img class="brand-jaksa-wordmark" src="<?= base_url('assets/images/jaksa-wordmark.png') ?>" alt="JAKSA">
                <span class="brand-jaksa-expansion" aria-label="Jamkrindo Kanwil Surabaya Operasional">
                    <b>JA</b>mkrindo <i>·</i> <b>K</b>anwil <i>·</i> <b>S</b>urabaya <i>·</i> oper<b>A</b>sional
                </span>
            </a>

            <nav class="main-nav" aria-label="Navigasi utama">
                <p class="nav-label">MENU UTAMA</p>
                <a href="<?= site_url('dashboard') ?>" class="nav-link <?= $segment === 'dashboard' ? 'active' : '' ?>" title="Dashboard">
                    <span class="nav-icon icon-dashboard" aria-hidden="true"><i>◆</i></span>
                    <span class="nav-link-text">Dashboard</span>
                </a>
                <?php if ($canAccessSecurity): ?>
                <div class="nav-group <?= $securityActive ? 'open' : '' ?>" data-nav-group>
                    <button type="button" class="nav-link nav-parent <?= $securityActive ? 'active' : '' ?>" data-nav-toggle aria-expanded="<?= $securityActive ? 'true' : 'false' ?>" aria-controls="securitySubmenu" title="Security">
                        <span class="nav-icon image-nav-icon security-nav-icon" aria-hidden="true"><img src="<?= base_url('assets/images/security-policeman.png') ?>" alt=""></span>
                        <span class="nav-link-text">Security</span>
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
                        <span class="nav-icon image-nav-icon agendaris-nav-icon" aria-hidden="true"><img src="<?= base_url('assets/images/agendaris-agenda.png') ?>" alt=""></span>
                        <span class="nav-link-text">Agendaris</span>
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
                        <a href="<?= site_url('agendaris/progres-dokumen') ?>" class="nav-sublink <?= $agendarisActive && in_array($agendarisPage, ['progres-dokumen', 'progres-dokumen-masuk', 'progres-dokumen-keluar'], true) ? 'active' : '' ?>">
                            <span aria-hidden="true">●</span>
                            Progres Dokumen
                        </a>
                    </div>
                </div>
                <?php endif ?>
                <?php if ($canAccessGeneralSection): ?>
                <div class="nav-group <?= $generalSectionActive ? 'open' : '' ?>" data-nav-group>
                    <button type="button" class="nav-link nav-parent <?= $generalSectionActive ? 'active' : '' ?>" data-nav-toggle aria-expanded="<?= $generalSectionActive ? 'true' : 'false' ?>" aria-controls="generalSectionSubmenu" title="Bagian Umum 1">
                        <span class="nav-icon image-nav-icon general-nav-icon" aria-hidden="true"></span>
                        <span class="nav-link-text">Bagian Umum 1</span>
                        <span class="nav-chevron" aria-hidden="true">⌄</span>
                    </button>
                    <div class="nav-submenu" id="generalSectionSubmenu" data-nav-submenu <?= $generalSectionActive ? '' : 'hidden' ?>>
                        <a href="<?= site_url('bagian-umum-1/pks-barang-jasa') ?>" class="nav-sublink <?= $generalSectionActive && $generalSectionPage === 'pks-barang-jasa' ? 'active' : '' ?>">
                            <span aria-hidden="true">●</span>
                            PKS Barang dan Jasa
                        </a>
                        <a href="<?= site_url('bagian-umum-1/dokumen-spk') ?>" class="nav-sublink <?= $generalSectionActive && $generalSectionPage === 'dokumen-spk' ? 'active' : '' ?>">
                            <span aria-hidden="true">●</span>
                            Dokumen SPK
                        </a>
                        <a href="<?= site_url('bagian-umum-1/pengadaan-barang-jasa') ?>" class="nav-sublink <?= $generalSectionActive && $generalSectionPage === 'pengadaan-barang-jasa' ? 'active' : '' ?>">
                            <span aria-hidden="true">●</span>
                            Pengadaan Barang Jasa
                        </a>
                    </div>
                </div>
                <?php endif ?>
                <?php if ($canAccessGeneralSectionTwo): ?>
                <div class="nav-group <?= $generalSectionTwoActive ? 'open' : '' ?>" data-nav-group>
                    <button type="button" class="nav-link nav-parent <?= $generalSectionTwoActive ? 'active' : '' ?>" data-nav-toggle aria-expanded="<?= $generalSectionTwoActive ? 'true' : 'false' ?>" aria-controls="generalSectionTwoSubmenu" title="Bagian Umum 2">
                        <span class="nav-icon image-nav-icon general-nav-icon" aria-hidden="true"></span>
                        <span class="nav-link-text">Bagian Umum 2</span>
                        <span class="nav-chevron" aria-hidden="true">⌄</span>
                    </button>
                    <div class="nav-submenu" id="generalSectionTwoSubmenu" data-nav-submenu <?= $generalSectionTwoActive ? '' : 'hidden' ?>>
                        <a href="<?= site_url('bagian-umum-2/monitoring-kendaraan/data-kendaraan') ?>" class="nav-sublink <?= $generalSectionTwoActive && $uri->getSegment(2) === 'monitoring-kendaraan' ? 'active' : '' ?>">
                            <span aria-hidden="true">●</span>
                            Monitoring Kendaraan
                        </a>
                    </div>
                </div>
                <?php endif ?>
                <?php if ($canAccessSdm): ?>
                <a href="<?= site_url('sdm') ?>" class="nav-link <?= $sdmActive ? 'active' : '' ?>" title="SDM &amp; Teller">
                    <span class="nav-icon image-nav-icon sdm-nav-icon" aria-hidden="true"><img src="<?= base_url('assets/images/people.png') ?>" alt=""></span>
                    <span class="nav-link-text">SDM &amp; Teller</span>
                </a>
                <?php endif ?>
                <?php if ($canAccessAkutansi): ?>
                <a href="<?= site_url('akutansi') ?>" class="nav-link <?= $akutansiActive ? 'active' : '' ?>" title="Akutansi">
                    <span class="nav-icon image-nav-icon accounting-nav-icon" aria-hidden="true"><img src="<?= base_url('assets/images/indonesian-rupiah.png') ?>" alt=""></span>
                    <span class="nav-link-text">Akutansi</span>
                </a>
                <?php endif ?>
                <?php if ($currentRole === 'admin'): ?>
                <div class="nav-group <?= $accountManagementActive ? 'open' : '' ?>" data-nav-group>
                    <button type="button" class="nav-link nav-parent <?= $accountManagementActive ? 'active' : '' ?>" data-nav-toggle aria-expanded="<?= $accountManagementActive ? 'true' : 'false' ?>" aria-controls="accountManagementSubmenu" title="Kelola Akun">
                        <span class="nav-icon" aria-hidden="true"><i>♙</i></span>
                        <span class="nav-link-text">Kelola Akun</span>
                        <span class="nav-chevron" aria-hidden="true">⌄</span>
                    </button>
                    <div class="nav-submenu" id="accountManagementSubmenu" data-nav-submenu <?= $accountManagementActive ? '' : 'hidden' ?>>
                        <a href="<?= site_url('kelola-akun') ?>" class="nav-sublink <?= $accountManagementActive && $accountManagementPage === 'add-account' ? 'active' : '' ?>">
                            <span aria-hidden="true">●</span>
                            Add Account
                        </a>
                        <a href="<?= site_url('kelola-akun/session-account') ?>" class="nav-sublink <?= $accountManagementActive && $accountManagementPage === 'session-account' ? 'active' : '' ?>">
                            <span aria-hidden="true">●</span>
                            Session Account
                        </a>
                        <a href="<?= site_url('data-terhapus') ?>" class="nav-sublink <?= $accountManagementPage === 'deleted-data' ? 'active' : '' ?>">
                            <span aria-hidden="true">●</span>
                            Data Terhapus
                            <?php if ($deletedDataCount > 0): ?><b class="nav-deleted-badge" title="<?= $deletedDataCount ?> data dihapus role lain"><?= $deletedDataCount > 99 ? '99+' : $deletedDataCount ?></b><?php endif ?>
                        </a>
                    </div>
                </div>
                <?php endif ?>
                <p class="nav-label nav-label-spaced">KONFIGURASI</p>
                <span class="nav-link nav-link-muted" title="Database Aktif">
                    <span class="nav-icon" aria-hidden="true"><i>◉</i></span>
                    <span class="nav-link-text">Database Aktif</span>
                    <span class="nav-status-dot"></span>
                </span>
            </nav>

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
                    <?php if ($notificationEnabled): ?>
                    <div class="topbar-notification" data-notification-menu>
                        <button class="topbar-icon topbar-notification-trigger" type="button" data-notification-toggle aria-label="Notifikasi <?= esc($roleLabel, 'attr') ?>: <?= (int) $roleNotifications['total'] ?> antrean perlu ditindaklanjuti" aria-expanded="false" aria-controls="roleNotificationMenu">
                            <svg class="topbar-bell-icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                                <path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9"></path>
                                <path d="M10 21h4"></path>
                            </svg>
                            <?php if ($roleNotifications['total'] > 0): ?><b class="topbar-notification-badge"><?= $roleNotifications['total'] > 99 ? '99+' : (int) $roleNotifications['total'] ?></b><?php endif ?>
                        </button>
                        <section class="topbar-notification-dropdown" id="roleNotificationMenu" data-notification-dropdown hidden aria-label="Daftar notifikasi <?= esc($roleLabel, 'attr') ?>">
                            <header>
                                <div><small>NOTIFIKASI <?= esc($notificationRoleLabel) ?></small><strong>Antrean tindakan</strong></div>
                                <span><?= (int) $roleNotifications['total'] ?> perlu diproses</span>
                            </header>
                            <div class="topbar-notification-summary">
                                <span><b><?= (int) $roleNotifications['incoming_count'] ?></b> Dokumen Masuk</span>
                                <span><b><?= (int) $roleNotifications['outgoing_count'] ?></b> Dokumen Keluar</span>
                            </div>
                            <div class="topbar-notification-list">
                                <?php if ($roleNotifications['items'] === []): ?>
                                    <div class="topbar-notification-empty"><span>✓</span><strong>Semua antrean sudah selesai</strong><p>Belum ada dokumen yang perlu ditindaklanjuti.</p></div>
                                <?php else: ?>
                                    <?php foreach ($roleNotifications['items'] as $notification): ?>
                                    <a class="topbar-notification-item <?= esc($notification['type']) ?>" href="<?= esc($notification['url']) ?>">
                                        <span class="topbar-notification-item-icon" aria-hidden="true"><?= $notification['type'] === 'incoming' ? '↓' : '↑' ?></span>
                                        <span class="topbar-notification-item-copy">
                                            <strong><?= esc($notification['title']) ?></strong>
                                            <small><?= esc($notification['description']) ?></small>
                                            <time><?= esc($notification['time']) ?></time>
                                        </span>
                                        <i aria-hidden="true">›</i>
                                    </a>
                                    <?php endforeach ?>
                                <?php endif ?>
                            </div>
                            <footer><a href="<?= esc($notificationAllUrl) ?>">Buka seluruh antrean <span aria-hidden="true">→</span></a></footer>
                        </section>
                    </div>
                    <?php else: ?>
                    <button class="topbar-icon" type="button" aria-label="Notifikasi belum tersedia untuk role ini">
                        <svg class="topbar-bell-icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                            <path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9"></path>
                            <path d="M10 21h4"></path>
                        </svg>
                    </button>
                    <?php endif ?>
                    <span class="date-pill" aria-label="Tanggal dan waktu sistem Asia Jakarta">
                        <span class="date-pill-date" data-system-date><?= date('d M Y') ?></span>
                        <time class="date-pill-time" datetime="<?= date('c') ?>">
                            <span data-system-time><?= date('H:i:s') ?></span>
                            <small>WIB</small>
                        </time>
                    </span>
                    <div class="topbar-profile" data-profile-menu>
                        <button class="topbar-profile-trigger" type="button" data-profile-toggle aria-expanded="false" aria-controls="topbarProfileMenu">
                            <span class="topbar-profile-avatar" aria-hidden="true"><?= esc($initial) ?></span>
                            <span class="topbar-profile-copy">
                                <strong><?= esc($displayName) ?></strong>
                                <small><?= esc($roleLabel) ?></small>
                            </span>
                            <span class="topbar-profile-chevron" aria-hidden="true">⌄</span>
                        </button>
                        <div class="topbar-profile-dropdown" id="topbarProfileMenu" data-profile-dropdown hidden>
                            <header>
                                <span class="topbar-profile-avatar topbar-profile-avatar-large" aria-hidden="true"><?= esc($initial) ?></span>
                                <div>
                                    <small>PROFIL PENGGUNA</small>
                                    <strong><?= esc($displayName) ?></strong>
                                    <span><?= esc($roleLabel) ?></span>
                                </div>
                            </header>
                            <dl class="topbar-profile-details">
                                <div><dt>Username</dt><dd><?= esc($authUsername) ?></dd></div>
                                <div><dt>Hak akses</dt><dd><?= esc($roleLabel) ?></dd></div>
                            </dl>
                            <form action="<?= site_url('logout') ?>" method="post">
                                <?= csrf_field() ?>
                                <button class="topbar-logout" type="submit">
                                    <span aria-hidden="true">↪</span>
                                    Keluar dari Sistem
                                </button>
                            </form>
                        </div>
                    </div>
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
    <?php if ($canAccessSecurity && $incomingWorkspace): ?>
        <?php if (! $incomingArchive): ?><?= view('components/input_modal') ?><?php endif ?>
        <?= view('components/detail_modal', ['readOnly' => $incomingArchive]) ?>
        <?php if (! $incomingArchive): ?><?= view('components/edit_modal') ?><?= view('components/delete_modal') ?><?php endif ?>
    <?php endif ?>
    <script src="<?= base_url('assets/app.js') ?>?v=<?= esc($appJsVersion, 'attr') ?>"></script>
</body>
</html>
