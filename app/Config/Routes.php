<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */

// Seluruh endpoint aplikasi didefinisikan secara eksplisit. Auto-routing
// dimatikan agar method controller yang tidak didaftarkan tidak dapat diakses.
$routes->setAutoRoute(false);

// Autentikasi
$routes->group('', static function (RouteCollection $routes): void {
    $routes->get('login', 'Auth::login', ['as' => 'login']);
    $routes->post('login', 'Auth::attempt', ['as' => 'login.attempt']);
    $routes->post('logout', 'Auth::logout', ['as' => 'logout']);
});

// Halaman publik dan dashboard internal
$routes->get('/', 'Landing::index', ['as' => 'landing']);
$routes->get('dashboard', 'Dashboard::index', ['as' => 'dashboard']);

// Pengelolaan akun (administrator)
$routes->group('kelola-akun', static function (RouteCollection $routes): void {
    $routes->get('/', 'KelolaAkun::index', ['as' => 'kelola_akun.index']);
    $routes->post('/', 'KelolaAkun::store', ['as' => 'kelola_akun.store']);
    $routes->get('(:num)', 'KelolaAkun::show/$1', ['as' => 'kelola_akun.show']);
    $routes->post('(:num)', 'KelolaAkun::update/$1', ['as' => 'kelola_akun.update']);
    $routes->post('(:num)/hapus', 'KelolaAkun::destroy/$1', ['as' => 'kelola_akun.destroy']);
});

// Security - Dokumen Masuk
$routes->group('dokumen-masuk', static function (RouteCollection $routes): void {
    $routes->get('/', 'DokumenMasuk::index', ['as' => 'dokumen_masuk.index']);
    $routes->get('(:num)', 'DokumenMasuk::show/$1', ['as' => 'dokumen_masuk.show']);
});

// Security - Dokumen Keluar (hanya baca)
$routes->group('dokumen-keluar', static function (RouteCollection $routes): void {
    $routes->get('/', 'DokumenKeluar::index', ['as' => 'dokumen_keluar.security_index']);
    $routes->get('(:num)', 'DokumenKeluar::show/$1', ['as' => 'dokumen_keluar.security_show']);
});

// Security - Distribusi Dokumen
$routes->group('distribusi-dokumen', static function (RouteCollection $routes): void {
    $routes->get('/', 'DistribusiDokumen::index', ['as' => 'distribusi_dokumen.index']);

    // CRUD Dokumen Masuk dikelola dari antrean Distribusi Dokumen.
    $routes->post('dokumen-masuk', 'DokumenMasuk::store', ['as' => 'distribusi_dokumen.incoming.store']);
    $routes->get('dokumen-masuk/(:num)', 'DokumenMasuk::show/$1', ['as' => 'distribusi_dokumen.incoming.show']);
    $routes->get('dokumen-masuk/(:num)/ubah', 'DokumenMasuk::edit/$1', ['as' => 'distribusi_dokumen.incoming.edit']);
    $routes->post('dokumen-masuk/(:num)', 'DokumenMasuk::update/$1', ['as' => 'distribusi_dokumen.incoming.update']);
    $routes->post('dokumen-masuk/(:num)/hapus', 'DokumenMasuk::destroy/$1', ['as' => 'distribusi_dokumen.incoming.destroy']);

    // Route Surat Keluar diletakkan sebelum parameter numerik agar struktur
    // modul mudah dibaca dan tidak ambigu.
    $routes->get('surat-keluar/(:num)', 'DistribusiDokumen::showOutgoing/$1', ['as' => 'distribusi_dokumen.outgoing.show']);
    $routes->post('surat-keluar/(:num)', 'DistribusiDokumen::completeOutgoing/$1', ['as' => 'distribusi_dokumen.outgoing.complete']);

    $routes->get('(:num)', 'DistribusiDokumen::show/$1', ['as' => 'distribusi_dokumen.show']);
    $routes->post('(:num)', 'DistribusiDokumen::complete/$1', ['as' => 'distribusi_dokumen.complete']);
});

// Agendaris
$routes->group('agendaris', static function (RouteCollection $routes): void {
    $routes->get('/', 'Agendaris::index', ['as' => 'agendaris.index']);

    $routes->group('surat-masuk', static function (RouteCollection $routes): void {
        $routes->get('/', 'Agendaris::suratMasuk', ['as' => 'agendaris.surat_masuk']);
        $routes->get('(:num)', 'Agendaris::show/$1', ['as' => 'agendaris.show']);
        $routes->post('(:num)/kembalikan', 'Agendaris::reopen/$1', ['as' => 'agendaris.reopen']);
    });

    $routes->group('surat-keluar', static function (RouteCollection $routes): void {
        $routes->get('/', 'DokumenKeluar::index', ['as' => 'dokumen_keluar.index']);
        $routes->get('(:num)', 'DokumenKeluar::show/$1', ['as' => 'dokumen_keluar.show']);
        $routes->post('(:num)/kembalikan', 'DokumenKeluar::reopen/$1', ['as' => 'dokumen_keluar.reopen']);
    });

    $routes->get('progres-dokumen', 'ProgresDokumen::masuk', ['as' => 'progres_dokumen.index']);

    $routes->group('progres-dokumen-masuk', static function (RouteCollection $routes): void {
        $routes->get('/', 'ProgresDokumen::masuk', ['as' => 'progres_dokumen_masuk.index']);
        $routes->post('/', 'Agendaris::store', ['as' => 'progres_dokumen_masuk.store']);
        $routes->get('generate-nomor', 'Agendaris::generateNomor', ['as' => 'progres_dokumen_masuk.generate_nomor']);
        $routes->post('sinkronkan', 'Agendaris::synchronize', ['as' => 'progres_dokumen_masuk.synchronize']);
        $routes->get('(:num)', 'Agendaris::show/$1', ['as' => 'progres_dokumen_masuk.show']);
        $routes->post('(:num)', 'Agendaris::update/$1', ['as' => 'progres_dokumen_masuk.update']);
        $routes->post('(:num)/hapus', 'Agendaris::destroy/$1', ['as' => 'progres_dokumen_masuk.destroy']);
    });

    $routes->group('progres-dokumen-keluar', static function (RouteCollection $routes): void {
        $routes->get('/', 'ProgresDokumen::index', ['as' => 'progres_dokumen_keluar.index']);
        $routes->post('/', 'ProgresDokumen::store', ['as' => 'progres_dokumen_keluar.store']);
        $routes->get('(:num)', 'ProgresDokumen::show/$1', ['as' => 'progres_dokumen_keluar.show']);
        $routes->post('(:num)', 'ProgresDokumen::update/$1', ['as' => 'progres_dokumen_keluar.update']);
        $routes->post('(:num)/hapus', 'ProgresDokumen::destroy/$1', ['as' => 'progres_dokumen_keluar.destroy']);
    });
});
