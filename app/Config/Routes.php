<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */
$routes->get('login', 'Auth::login', ['as' => 'login']);
$routes->post('login', 'Auth::attempt', ['as' => 'login.attempt']);
$routes->post('logout', 'Auth::logout', ['as' => 'logout']);

$routes->get('/', 'Dashboard::index', ['as' => 'dashboard']);
$routes->get('kelola-akun', 'KelolaAkun::index', ['as' => 'kelola_akun.index']);
$routes->post('kelola-akun', 'KelolaAkun::store', ['as' => 'kelola_akun.store']);
$routes->get('kelola-akun/(:num)', 'KelolaAkun::show/$1', ['as' => 'kelola_akun.show']);
$routes->post('kelola-akun/(:num)', 'KelolaAkun::update/$1', ['as' => 'kelola_akun.update']);
$routes->post('kelola-akun/(:num)/hapus', 'KelolaAkun::destroy/$1', ['as' => 'kelola_akun.destroy']);
$routes->get('agendaris', 'Agendaris::index', ['as' => 'agendaris.index']);
$routes->get('agendaris/surat-masuk', 'Agendaris::suratMasuk', ['as' => 'agendaris.surat_masuk']);
$routes->get('agendaris/progres-dokumen', 'ProgresDokumen::index', ['as' => 'progres_dokumen.index']);
$routes->post('agendaris/progres-dokumen', 'ProgresDokumen::store', ['as' => 'progres_dokumen.store']);
$routes->get('agendaris/progres-dokumen/(:num)', 'ProgresDokumen::show/$1', ['as' => 'progres_dokumen.show']);
$routes->post('agendaris/progres-dokumen/(:num)', 'ProgresDokumen::update/$1', ['as' => 'progres_dokumen.update']);
$routes->post('agendaris/progres-dokumen/(:num)/hapus', 'ProgresDokumen::destroy/$1', ['as' => 'progres_dokumen.destroy']);
$routes->post('agendaris/surat-masuk', 'Agendaris::store', ['as' => 'agendaris.store']);
$routes->post('agendaris/surat-masuk/sinkronkan', 'Agendaris::synchronize', ['as' => 'agendaris.synchronize']);
$routes->get('agendaris/surat-masuk/(:num)', 'Agendaris::show/$1', ['as' => 'agendaris.show']);
$routes->post('agendaris/surat-masuk/(:num)', 'Agendaris::update/$1', ['as' => 'agendaris.update']);
$routes->post('agendaris/surat-masuk/(:num)/hapus', 'Agendaris::destroy/$1', ['as' => 'agendaris.destroy']);
$routes->get('agendaris/surat-keluar', 'DokumenKeluar::index', ['as' => 'dokumen_keluar.index']);
$routes->post('agendaris/surat-keluar', 'DokumenKeluar::store', ['as' => 'dokumen_keluar.store']);
$routes->get('agendaris/surat-keluar/(:num)', 'DokumenKeluar::show/$1', ['as' => 'dokumen_keluar.show']);
$routes->post('agendaris/surat-keluar/(:num)', 'DokumenKeluar::update/$1', ['as' => 'dokumen_keluar.update']);
$routes->post('agendaris/surat-keluar/(:num)/hapus', 'DokumenKeluar::destroy/$1', ['as' => 'dokumen_keluar.destroy']);
$routes->get('distribusi-dokumen', 'DistribusiDokumen::index', ['as' => 'distribusi_dokumen.index']);
$routes->get('distribusi-dokumen/(:num)', 'DistribusiDokumen::show/$1', ['as' => 'distribusi_dokumen.show']);
$routes->post('distribusi-dokumen/(:num)', 'DistribusiDokumen::complete/$1', ['as' => 'distribusi_dokumen.complete']);
$routes->get('distribusi-dokumen/surat-keluar/(:num)', 'DistribusiDokumen::showOutgoing/$1', ['as' => 'distribusi_dokumen.outgoing.show']);
$routes->post('distribusi-dokumen/surat-keluar/(:num)', 'DistribusiDokumen::completeOutgoing/$1', ['as' => 'distribusi_dokumen.outgoing.complete']);
$routes->get('dokumen-keluar', 'DokumenKeluar::index', ['as' => 'dokumen_keluar.security_index']);
$routes->get('dokumen-keluar/(:num)', 'DokumenKeluar::show/$1', ['as' => 'dokumen_keluar.security_show']);

$routes->group('dokumen-masuk', static function ($routes) {
    $routes->get('/', 'DokumenMasuk::index', ['as' => 'dokumen_masuk.index']);
    $routes->get('tambah', 'DokumenMasuk::create', ['as' => 'dokumen_masuk.create']);
    $routes->post('/', 'DokumenMasuk::store', ['as' => 'dokumen_masuk.store']);
    $routes->get('(:num)', 'DokumenMasuk::show/$1', ['as' => 'dokumen_masuk.show']);
    $routes->get('(:num)/ubah', 'DokumenMasuk::edit/$1', ['as' => 'dokumen_masuk.edit']);
    $routes->post('(:num)', 'DokumenMasuk::update/$1', ['as' => 'dokumen_masuk.update']);
    $routes->post('(:num)/hapus', 'DokumenMasuk::destroy/$1', ['as' => 'dokumen_masuk.destroy']);
});
