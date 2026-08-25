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

// Dashboard
$routes->get('/', 'Dashboard::index', ['as' => 'dashboard']);

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
    $routes->get('tambah', 'DokumenMasuk::create', ['as' => 'dokumen_masuk.create']);
    $routes->post('/', 'DokumenMasuk::store', ['as' => 'dokumen_masuk.store']);
    $routes->get('(:num)', 'DokumenMasuk::show/$1', ['as' => 'dokumen_masuk.show']);
    $routes->get('(:num)/ubah', 'DokumenMasuk::edit/$1', ['as' => 'dokumen_masuk.edit']);
    $routes->post('(:num)', 'DokumenMasuk::update/$1', ['as' => 'dokumen_masuk.update']);
    $routes->post('(:num)/hapus', 'DokumenMasuk::destroy/$1', ['as' => 'dokumen_masuk.destroy']);
});

// Security - Dokumen Keluar (hanya baca)
$routes->group('dokumen-keluar', static function (RouteCollection $routes): void {
    $routes->get('/', 'DokumenKeluar::index', ['as' => 'dokumen_keluar.security_index']);
    $routes->get('(:num)', 'DokumenKeluar::show/$1', ['as' => 'dokumen_keluar.security_show']);
});

// Security - Distribusi Dokumen
$routes->group('distribusi-dokumen', static function (RouteCollection $routes): void {
    $routes->get('/', 'DistribusiDokumen::index', ['as' => 'distribusi_dokumen.index']);

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
        $routes->post('/', 'Agendaris::store', ['as' => 'agendaris.store']);
        $routes->post('sinkronkan', 'Agendaris::synchronize', ['as' => 'agendaris.synchronize']);
        $routes->get('(:num)', 'Agendaris::show/$1', ['as' => 'agendaris.show']);
        $routes->post('(:num)', 'Agendaris::update/$1', ['as' => 'agendaris.update']);
        $routes->post('(:num)/hapus', 'Agendaris::destroy/$1', ['as' => 'agendaris.destroy']);
    });

    $routes->group('surat-keluar', static function (RouteCollection $routes): void {
        $routes->get('/', 'DokumenKeluar::index', ['as' => 'dokumen_keluar.index']);
        $routes->post('/', 'DokumenKeluar::store', ['as' => 'dokumen_keluar.store']);
        $routes->get('(:num)', 'DokumenKeluar::show/$1', ['as' => 'dokumen_keluar.show']);
        $routes->post('(:num)', 'DokumenKeluar::update/$1', ['as' => 'dokumen_keluar.update']);
        $routes->post('(:num)/hapus', 'DokumenKeluar::destroy/$1', ['as' => 'dokumen_keluar.destroy']);
    });

    $routes->group('progres-dokumen', static function (RouteCollection $routes): void {
        $routes->get('/', 'ProgresDokumen::index', ['as' => 'progres_dokumen.index']);
        $routes->post('/', 'ProgresDokumen::store', ['as' => 'progres_dokumen.store']);
        $routes->get('(:num)', 'ProgresDokumen::show/$1', ['as' => 'progres_dokumen.show']);
        $routes->post('(:num)', 'ProgresDokumen::update/$1', ['as' => 'progres_dokumen.update']);
        $routes->post('(:num)/hapus', 'ProgresDokumen::destroy/$1', ['as' => 'progres_dokumen.destroy']);
    });
});
