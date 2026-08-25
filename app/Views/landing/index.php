<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="J-Operasional, sistem pengelolaan dokumen operasional yang tertib, aman, dan terhubung.">
    <title><?= esc($title ?? 'J-Operasional') ?> | Document Management</title>
    <link rel="icon" type="image/svg+xml" href="<?= base_url('favicon.svg?v=2') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/app.css') ?>">
    <script src="<?= base_url('assets/url-mask.js') ?>"></script>
</head>
<body class="landing-page">
    <a class="landing-skip-link" href="#konten-utama">Lewati ke konten utama</a>

    <header class="landing-header">
        <div class="landing-container landing-nav">
            <a class="landing-brand" href="<?= site_url('/') ?>" aria-label="Halaman utama J-Operasional">
                <span class="landing-brand-mark" aria-hidden="true">ϟ</span>
                <span><strong>J-Operasional</strong><small>Document Management</small></span>
            </a>
            <nav class="landing-nav-links" aria-label="Navigasi landing page">
                <a href="#fitur">Fitur</a>
                <a href="#alur">Alur kerja</a>
                <a href="#keamanan">Keamanan</a>
            </nav>
            <a class="landing-nav-action" href="<?= $isLoggedIn ? site_url('dashboard') : site_url('login') ?>">
                <?= $isLoggedIn ? 'Buka dashboard' : 'Masuk sistem' ?>
                <span aria-hidden="true">→</span>
            </a>
        </div>
    </header>

    <main id="konten-utama">
        <section class="landing-hero">
            <div class="landing-orb landing-orb-one" aria-hidden="true"></div>
            <div class="landing-orb landing-orb-two" aria-hidden="true"></div>
            <div class="landing-container landing-hero-grid">
                <div class="landing-hero-copy">
                    <span class="landing-eyebrow"><i aria-hidden="true"></i> REGISTER OPERASIONAL DIGITAL</span>
                    <h1>Dokumen tertib.<br><em>Proses terhubung.</em></h1>
                    <p>Kelola pencatatan, distribusi, agenda, dan progres dokumen dalam satu alur kerja yang jelas untuk mendukung operasional yang lebih akurat dan dapat dipantau.</p>
                    <div class="landing-hero-actions">
                        <a class="landing-btn landing-btn-primary" href="<?= $isLoggedIn ? site_url('dashboard') : site_url('login') ?>">
                            <?= $isLoggedIn ? 'Lanjut ke dashboard' : 'Masuk ke aplikasi' ?>
                            <span aria-hidden="true">→</span>
                        </a>
                        <a class="landing-btn landing-btn-secondary" href="#fitur">Pelajari fitur</a>
                    </div>
                    <div class="landing-trust-row" aria-label="Keunggulan sistem">
                        <span><b aria-hidden="true">✓</b> Akses berbasis role</span>
                        <span><b aria-hidden="true">✓</b> Data terpusat</span>
                        <span><b aria-hidden="true">✓</b> Alur terdokumentasi</span>
                    </div>
                </div>

                <div class="landing-product-visual" aria-label="Ilustrasi dashboard J-Operasional">
                    <div class="landing-visual-glow" aria-hidden="true"></div>
                    <div class="landing-browser-card">
                        <div class="landing-browser-bar">
                            <span class="landing-browser-dots" aria-hidden="true"><i></i><i></i><i></i></span>
                            <span class="landing-browser-address">operasional / dashboard</span>
                            <span class="landing-browser-live"><i></i> Aktif</span>
                        </div>
                        <div class="landing-browser-body">
                            <aside class="landing-preview-sidebar" aria-hidden="true">
                                <span class="landing-preview-logo">ϟ</span>
                                <i class="active"></i><i></i><i></i><i></i>
                            </aside>
                            <div class="landing-preview-main">
                                <div class="landing-preview-heading"><div><small>RINGKASAN OPERASIONAL</small><strong>Dashboard Dokumen</strong></div><span>Hari ini</span></div>
                                <div class="landing-preview-stats">
                                    <article><span class="teal">▤</span><div><small>Dokumen masuk</small><strong>128</strong></div></article>
                                    <article><span class="blue">→</span><div><small>Dalam distribusi</small><strong>24</strong></div></article>
                                    <article><span class="orange">✓</span><div><small>Selesai</small><strong>96</strong></div></article>
                                </div>
                                <div class="landing-preview-content">
                                    <article class="landing-preview-chart">
                                        <div><strong>Aktivitas dokumen</strong><small>6 bulan terakhir</small></div>
                                        <svg viewBox="0 0 360 130" role="img" aria-label="Grafik aktivitas dokumen">
                                            <defs><linearGradient id="landingChartFill" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#11afa8" stop-opacity=".28"/><stop offset="1" stop-color="#11afa8" stop-opacity="0"/></linearGradient></defs>
                                            <path class="grid" d="M0 26H360M0 65H360M0 104H360"/>
                                            <path class="area" d="M0 102 C42 84,58 91,91 62 S146 47,181 68 S231 91,270 51 S323 31,360 44 V130H0Z"/>
                                            <path class="line" d="M0 102 C42 84,58 91,91 62 S146 47,181 68 S231 91,270 51 S323 31,360 44"/>
                                            <circle cx="91" cy="62" r="4"/><circle cx="181" cy="68" r="4"/><circle cx="270" cy="51" r="4"/><circle cx="360" cy="44" r="4"/>
                                        </svg>
                                    </article>
                                    <article class="landing-preview-progress">
                                        <strong>Status distribusi</strong>
                                        <div class="landing-progress-ring"><span>75%</span></div>
                                        <small>Dokumen selesai diproses</small>
                                    </article>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="landing-floating-note note-security"><span>◈</span><div><strong>Security</strong><small>Dokumen diterima</small></div><b>✓</b></div>
                    <div class="landing-floating-note note-agenda"><span>▦</span><div><strong>Agendaris</strong><small>Agenda diperbarui</small></div><b>✓</b></div>
                </div>
            </div>
        </section>

        <section class="landing-feature-section" id="fitur">
            <div class="landing-container">
                <div class="landing-section-heading">
                    <span class="landing-eyebrow"><i aria-hidden="true"></i> SATU SISTEM TERINTEGRASI</span>
                    <h2>Dibangun untuk alur dokumen operasional</h2>
                    <p>Setiap unit bekerja pada data yang sama dengan kewenangan dan tahapan yang jelas.</p>
                </div>
                <div class="landing-feature-grid">
                    <article>
                        <span class="landing-feature-icon teal">▤</span>
                        <small>01</small>
                        <h3>Register Dokumen</h3>
                        <p>Catat dokumen masuk dan keluar secara konsisten, lengkap, dan mudah ditemukan kembali.</p>
                        <ul><li>Pencarian dan filter</li><li>Detail riwayat data</li></ul>
                    </article>
                    <article>
                        <span class="landing-feature-icon blue">⇢</span>
                        <small>02</small>
                        <h3>Distribusi Terarah</h3>
                        <p>Pantau proses penyerahan dan distribusi dokumen dari Security sampai unit terkait.</p>
                        <ul><li>Status proses terkini</li><li>Pencatatan waktu otomatis</li></ul>
                    </article>
                    <article>
                        <span class="landing-feature-icon orange">▦</span>
                        <small>03</small>
                        <h3>Agenda Terhubung</h3>
                        <p>Kelola agenda surat dan progres dokumen tanpa penginputan berulang yang tidak diperlukan.</p>
                        <ul><li>Sinkronisasi data</li><li>Pelacakan progres</li></ul>
                    </article>
                </div>
            </div>
        </section>

        <section class="landing-workflow-section" id="alur">
            <div class="landing-container landing-workflow-grid">
                <div class="landing-workflow-copy">
                    <span class="landing-eyebrow"><i aria-hidden="true"></i> ALUR KERJA</span>
                    <h2>Dari penerimaan hingga selesai, setiap langkah tercatat.</h2>
                    <p>Informasi bergerak mengikuti proses dokumen sehingga koordinasi antarpetugas menjadi lebih sederhana.</p>
                    <a href="<?= $isLoggedIn ? site_url('dashboard') : site_url('login') ?>">Mulai gunakan sistem <span aria-hidden="true">→</span></a>
                </div>
                <ol class="landing-workflow-list">
                    <li><span>01</span><div><strong>Dokumen diterima</strong><p>Security mencatat informasi utama dokumen ke register digital.</p></div></li>
                    <li><span>02</span><div><strong>Dokumen didistribusikan</strong><p>Penyerahan dicatat dan data diteruskan sesuai alur operasional.</p></div></li>
                    <li><span>03</span><div><strong>Agenda dilengkapi</strong><p>Agendaris melengkapi informasi surat dan memantau progresnya.</p></div></li>
                </ol>
            </div>
        </section>

        <section class="landing-security-section" id="keamanan">
            <div class="landing-container landing-security-card">
                <div class="landing-security-icon" aria-hidden="true">◇</div>
                <div><span>AKSES TERKENDALI</span><h2>Informasi tampil sesuai kewenangan akun.</h2><p>Administrator, Security, dan Agendaris memperoleh menu sesuai tanggung jawab masing-masing.</p></div>
                <a class="landing-btn landing-btn-light" href="<?= $isLoggedIn ? site_url('dashboard') : site_url('login') ?>"><?= $isLoggedIn ? 'Buka dashboard' : 'Masuk sistem' ?> <span aria-hidden="true">→</span></a>
            </div>
        </section>
    </main>

    <footer class="landing-footer">
        <div class="landing-container">
            <a class="landing-brand landing-footer-brand" href="<?= site_url('/') ?>"><span class="landing-brand-mark">ϟ</span><span><strong>J-Operasional</strong><small>Document Management</small></span></a>
            <p>Sistem Register Operasional Digital</p>
            <small>© <?= date('Y') ?> J-Operasional</small>
        </div>
    </footer>
</body>
</html>
