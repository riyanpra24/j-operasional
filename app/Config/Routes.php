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
    $routes->post('login/admin-takeover', 'Auth::takeoverAdminSession', ['as' => 'login.admin_takeover']);
    $routes->post('session/heartbeat', 'Auth::heartbeat', ['as' => 'session.heartbeat']);
    $routes->post('logout', 'Auth::logout', ['as' => 'logout']);
});

// Halaman publik dan dashboard internal
$routes->get('/', 'Landing::index', ['as' => 'landing']);
$routes->get('welcome', 'Welcome::index', ['as' => 'welcome']);
$routes->get('dashboard', 'Dashboard::index', ['as' => 'dashboard']);

// Halaman awal modul SDM & Teller dan Akutansi
$routes->get('sdm', 'Sdm::index', ['as' => 'sdm.index']);
$routes->get('sdm/data-magang', 'Magang::index', ['as' => 'sdm.data_magang']);
$routes->post('sdm/data-magang', 'Magang::store', ['as' => 'sdm.data_magang.store']);
$routes->post('sdm/data-magang/import', 'Magang::import', ['as' => 'sdm.data_magang.import']);
$routes->post('sdm/data-magang/(:num)', 'Magang::update/$1', ['as' => 'sdm.data_magang.update']);
$routes->post('sdm/data-magang/(:num)/hapus', 'Magang::destroy/$1', ['as' => 'sdm.data_magang.delete']);
$routes->get('sdm/dokumen-masuk', 'Sdm::incomingDocuments', ['as' => 'sdm.dokumen_masuk']);
$routes->get('sdm/arsip-dokumen-masuk', 'Agendaris::suratMasuk', ['as' => 'sdm.arsip_dokumen_masuk']);
$routes->get('sdm/arsip-dokumen-masuk/(:num)', 'Agendaris::show/$1', ['as' => 'sdm.arsip_dokumen_masuk.show']);
$routes->get('sdm/arsip-dokumen-keluar', 'DokumenKeluar::index', ['as' => 'sdm.arsip_dokumen_keluar']);
$routes->get('sdm/arsip-dokumen-keluar/(:num)', 'DokumenKeluar::show/$1', ['as' => 'sdm.arsip_dokumen_keluar.show']);
$routes->post('sdm/dokumen-masuk/sinkronkan', 'Sdm::synchronizeIncomingDocuments', ['as' => 'sdm.dokumen_masuk.synchronize']);
$routes->get('sdm/riwayat', 'Sdm::incomingDocumentHistory', ['as' => 'sdm.riwayat']);
$routes->get('sdm/dashboard-kehadiran', 'Sdm::attendanceDashboard', ['as' => 'sdm.dashboard_kehadiran']);
$routes->get('sdm/kalender', 'Sdm::attendanceCalendar', ['as' => 'sdm.calendar']);
$routes->post('sdm/kalender', 'Sdm::updateAttendanceCalendar', ['as' => 'sdm.calendar.update']);
$routes->get('sdm/data-kehadiran', 'Sdm::attendanceRecords', ['as' => 'sdm.attendance_records']);
$routes->post('sdm/data-kehadiran/rekap', 'Sdm::recapSdmJatimAttendance', ['as' => 'sdm.attendance_records.recap']);
$routes->post('sdm/data-kehadiran/anomali', 'Sdm::updateSdmJatimAnomalies', ['as' => 'sdm.attendance_records.anomaly.update']);
$routes->post('sdm/data-kehadiran/hapus', 'Sdm::deleteSdmJatimAttendance', ['as' => 'sdm.attendance_records.delete']);
$routes->get('sdm/sdm-jatim', 'Sdm::sdmJatim', ['as' => 'sdm.jatim']);
$routes->post('sdm/dokumen-masuk/(:num)', 'Sdm::updateIncomingDocument/$1', ['as' => 'sdm.dokumen_masuk.update']);
$routes->get('akutansi', 'Akutansi::index', ['as' => 'akutansi.index']);
$routes->get('akutansi/laba-rugi', 'Akutansi::labaRugi', ['as' => 'akutansi.laba_rugi']);
$routes->post('akutansi/laba-rugi/upload-excel', 'Akutansi::importLabaRugi', ['as' => 'akutansi.laba_rugi.import']);
$routes->post('akutansi/laba-rugi/hapus', 'Akutansi::deleteLabaRugi', ['as' => 'akutansi.laba_rugi.delete']);
$routes->get('akutansi/simulasi-hitung', 'Akutansi::simulasiHitung', ['as' => 'akutansi.simulasi_hitung']);
$routes->post('akutansi/simulasi-hitung', 'Akutansi::uploadSimulasiHitung', ['as' => 'akutansi.simulasi_hitung.upload']);
$routes->get('akutansi/simulasi-hitung/unduh/(:segment)', 'Akutansi::downloadSimulasiHitung/$1', ['as' => 'akutansi.simulasi_hitung.download']);
$routes->post('akutansi/simulasi-hitung/hapus/(:segment)', 'Akutansi::deleteSimulasiHitung/$1', ['as' => 'akutansi.simulasi_hitung.delete']);
$routes->get('akutansi/export-dokumen', 'Akutansi::exportDokumen', ['as' => 'akutansi.export_dokumen']);
$routes->post('akutansi/export-dokumen', 'Akutansi::downloadExportDokumen', ['as' => 'akutansi.export_dokumen.download']);
$routes->get('akutansi/pengaturan-mapping-oracle', 'Akutansi::oracleMappings', ['as' => 'akutansi.oracle_mappings']);
$routes->post('akutansi/pengaturan-mapping-oracle/coa', 'Akutansi::saveOracleAccountMapping', ['as' => 'akutansi.oracle_mappings.account.save']);
$routes->post('akutansi/pengaturan-mapping-oracle/coa/hapus', 'Akutansi::deleteOracleAccountMapping', ['as' => 'akutansi.oracle_mappings.account.delete']);
$routes->get('akutansi/seting-rumus', 'Akutansi::formulaSettings', ['as' => 'akutansi.formula_settings']);
$routes->post('akutansi/seting-rumus/sumber/simpan', 'Akutansi::saveSourceAdjustmentRule', ['as' => 'akutansi.formula_settings.source.save']);
$routes->post('akutansi/seting-rumus/sumber/nonaktifkan', 'Akutansi::deactivateSourceAdjustmentRule', ['as' => 'akutansi.formula_settings.source.deactivate']);
$routes->post('akutansi/seting-rumus/sumber/hapus', 'Akutansi::deleteSourceAdjustmentRule', ['as' => 'akutansi.formula_settings.source.delete']);
$routes->post('akutansi/seting-rumus/pengajuan/setujui', 'Akutansi::approveSourceAdjustmentRequest', ['as' => 'akutansi.formula_settings.request.approve']);
$routes->post('akutansi/seting-rumus/pengajuan/tolak', 'Akutansi::rejectSourceAdjustmentRequest', ['as' => 'akutansi.formula_settings.request.reject']);
$routes->get('akutansi/rka-kanwil-surabaya', 'Akutansi::rkaKanwilSurabaya', ['as' => 'akutansi.rka_kanwil_surabaya']);
$routes->get('akutansi/rka-kanwil-surabaya/upload-excel', 'Akutansi::rkaUpload', ['as' => 'akutansi.rka_upload']);
$routes->get('akutansi/rka-kanwil-surabaya/seting-manual', 'Akutansi::rkaManual', ['as' => 'akutansi.rka_manual']);
$routes->get('akutansi/rka-kanwil-surabaya/template', 'Akutansi::downloadRkaTemplate', ['as' => 'akutansi.rka_template']);
$routes->get('akutansi/rka-kanwil-surabaya/manual-data', 'Akutansi::rkaManualData', ['as' => 'akutansi.rka_manual.data']);
$routes->post('akutansi/rka-kanwil-surabaya/upload-excel', 'Akutansi::importRka', ['as' => 'akutansi.rka_import']);
$routes->post('akutansi/rka-kanwil-surabaya/seting-manual', 'Akutansi::saveRkaManual', ['as' => 'akutansi.rka_manual.save']);
$routes->post('akutansi/rka-kanwil-surabaya/hapus', 'Akutansi::deleteRka', ['as' => 'akutansi.rka_delete']);

// Bagian Umum 1
$routes->group('bagian-umum-1/dokumen-masuk', static function (RouteCollection $routes): void {
    $routes->get('/', 'Agendaris::suratMasuk', ['as' => 'bagian_umum_1.dokumen_masuk']);
    $routes->get('(:num)', 'Agendaris::show/$1', ['as' => 'bagian_umum_1.dokumen_masuk.show']);
});

$routes->group('bagian-umum-1/dokumen-keluar', static function (RouteCollection $routes): void {
    $routes->get('/', 'DokumenKeluar::index', ['as' => 'bagian_umum_1.dokumen_keluar']);
    $routes->get('(:num)', 'DokumenKeluar::show/$1', ['as' => 'bagian_umum_1.dokumen_keluar.show']);
});

$routes->group('bagian-umum-1/pks-barang-jasa', static function (RouteCollection $routes): void {
    $routes->get('/', 'PksBarangJasa::index', ['as' => 'pks.index']);
    $routes->get('tambah', 'PksBarangJasa::create', ['as' => 'pks.create']);
    $routes->post('/', 'PksBarangJasa::store', ['as' => 'pks.store']);
    $routes->get('(:num)', 'PksBarangJasa::show/$1', ['as' => 'pks.show']);
    $routes->get('(:num)/ubah', 'PksBarangJasa::edit/$1', ['as' => 'pks.edit']);
    $routes->post('(:num)', 'PksBarangJasa::update/$1', ['as' => 'pks.update']);
    $routes->post('(:num)/hapus', 'PksBarangJasa::destroy/$1', ['as' => 'pks.destroy']);
    $routes->post('(:num)/dokumen', 'PksBarangJasa::storeDocument/$1', ['as' => 'pks.document.store']);
    $routes->post('(:num)/dokumen/(:num)', 'PksBarangJasa::updateDocument/$1/$2', ['as' => 'pks.document.update']);
    $routes->post('(:num)/dokumen/(:num)/hapus', 'PksBarangJasa::destroyDocument/$1/$2', ['as' => 'pks.document.destroy']);
    $routes->post('(:num)/item', 'PksBarangJasa::storeItem/$1', ['as' => 'pks.item.store']);
    $routes->post('(:num)/item/(:num)', 'PksBarangJasa::updateItem/$1/$2', ['as' => 'pks.item.update']);
    $routes->post('(:num)/item/(:num)/hapus', 'PksBarangJasa::destroyItem/$1/$2', ['as' => 'pks.item.destroy']);
});

$routes->get(
    'bagian-umum-1/pengadaan-barang-jasa',
    'PengadaanBarangJasa::index',
    ['as' => 'pengadaan_barang_jasa.index'],
);

$routes->group('bagian-umum-1/dokumen-spk', static function (RouteCollection $routes): void {
    $routes->get('/', 'DokumenSpk::index', ['as' => 'dokumen_spk.index']);
    $routes->get('generate-nomor', 'DokumenSpk::generateNumber', ['as' => 'dokumen_spk.generate_number']);
    $routes->post('/', 'DokumenSpk::store', ['as' => 'dokumen_spk.store']);
    $routes->post('(:num)', 'DokumenSpk::update/$1', ['as' => 'dokumen_spk.update']);
    $routes->post('(:num)/hapus', 'DokumenSpk::destroy/$1', ['as' => 'dokumen_spk.destroy']);
});

$routes->get(
    'bagian-umum-1/non-belanja-modal',
    'NonBelanjaModal::index',
    ['as' => 'non_belanja_modal.index'],
);

// Bagian Umum 2
$routes->get(
    'bagian-umum-2',
    'BagianUmum2::index',
    ['as' => 'bagian_umum_2.index'],
);

$routes->group('bagian-umum-2/dokumen-masuk', static function (RouteCollection $routes): void {
    $routes->get('/', 'Agendaris::suratMasuk', ['as' => 'bagian_umum_2.dokumen_masuk']);
    $routes->get('(:num)', 'Agendaris::show/$1', ['as' => 'bagian_umum_2.dokumen_masuk.show']);
});

$routes->group('bagian-umum-2/dokumen-keluar', static function (RouteCollection $routes): void {
    $routes->get('/', 'DokumenKeluar::index', ['as' => 'bagian_umum_2.dokumen_keluar']);
    $routes->get('(:num)', 'DokumenKeluar::show/$1', ['as' => 'bagian_umum_2.dokumen_keluar.show']);
});

$routes->group('bagian-umum-2/monitoring-kendaraan', static function (RouteCollection $routes): void {
    $routes->get('/', 'MonitoringKendaraan::index', ['as' => 'monitoring_kendaraan.index']);
    $routes->get('data-kendaraan', 'MonitoringKendaraan::vehicles', ['as' => 'monitoring_kendaraan.vehicles']);
    $routes->post('data-kendaraan', 'MonitoringKendaraan::storeVehicle', ['as' => 'monitoring_kendaraan.vehicles.store']);
    $routes->post('data-kendaraan/(:num)', 'MonitoringKendaraan::updateVehicle/$1', ['as' => 'monitoring_kendaraan.vehicles.update']);
    $routes->post('data-kendaraan/(:num)/hapus', 'MonitoringKendaraan::destroyVehicle/$1', ['as' => 'monitoring_kendaraan.vehicles.destroy']);
    $routes->get('servis-perawatan', 'MonitoringKendaraan::maintenance', ['as' => 'monitoring_kendaraan.maintenance']);
    $routes->post('servis-perawatan', 'MonitoringKendaraan::storeMaintenance', ['as' => 'monitoring_kendaraan.maintenance.store']);
    $routes->post('servis-perawatan/(:num)', 'MonitoringKendaraan::updateMaintenance/$1', ['as' => 'monitoring_kendaraan.maintenance.update']);
    $routes->post('servis-perawatan/(:num)/hapus', 'MonitoringKendaraan::destroyMaintenance/$1', ['as' => 'monitoring_kendaraan.maintenance.destroy']);
    $routes->get('dokumen-kendaraan', 'MonitoringKendaraan::documents', ['as' => 'monitoring_kendaraan.documents']);
    $routes->post('dokumen-kendaraan', 'MonitoringKendaraan::storeDocument', ['as' => 'monitoring_kendaraan.documents.store']);
    $routes->post('dokumen-kendaraan/(:num)', 'MonitoringKendaraan::updateDocument/$1', ['as' => 'monitoring_kendaraan.documents.update']);
    $routes->post('dokumen-kendaraan/(:num)/hapus', 'MonitoringKendaraan::destroyDocument/$1', ['as' => 'monitoring_kendaraan.documents.destroy']);
    $routes->get('riwayat-laporan', 'MonitoringKendaraan::reports', ['as' => 'monitoring_kendaraan.reports']);
});

// Pengelolaan akun (administrator)
$routes->group('kelola-akun', static function (RouteCollection $routes): void {
    $routes->get('/', 'KelolaAkun::index', ['as' => 'kelola_akun.index']);
    $routes->get('session-account', 'KelolaAkun::sessions', ['as' => 'kelola_akun.sessions']);
    $routes->post('session-account/(:num)/reset', 'KelolaAkun::resetSession/$1', ['as' => 'kelola_akun.sessions.reset']);
    $routes->post('/', 'KelolaAkun::store', ['as' => 'kelola_akun.store']);
    $routes->get('(:num)', 'KelolaAkun::show/$1', ['as' => 'kelola_akun.show']);
    $routes->post('(:num)', 'KelolaAkun::update/$1', ['as' => 'kelola_akun.update']);
    $routes->post('(:num)/hapus', 'KelolaAkun::destroy/$1', ['as' => 'kelola_akun.destroy']);
});


// Pusat pemulihan data (khusus administrator)
$routes->get('data-terhapus', 'DeletedData::index', ['as' => 'deleted_data.index']);
$routes->post('data-terhapus/hapus-semua-permanen', 'DeletedData::destroyAll', ['as' => 'deleted_data.destroy_all']);
$routes->post('data-terhapus/(:segment)/(:num)/pulihkan', 'DeletedData::restore/$1/$2', ['as' => 'deleted_data.restore']);
$routes->post('data-terhapus/(:segment)/(:num)/hapus-permanen', 'DeletedData::destroy/$1/$2', ['as' => 'deleted_data.destroy']);

// Security - Dokumen Masuk
$routes->group('dokumen-masuk', static function (RouteCollection $routes): void {
    $routes->get('/', 'DokumenMasuk::index', ['as' => 'dokumen_masuk.index']);
    $routes->get('(:num)', 'DokumenMasuk::show/$1', ['as' => 'dokumen_masuk.show']);
    $routes->post('(:num)/kembalikan', 'DokumenMasuk::reopen/$1', ['as' => 'dokumen_masuk.reopen']);
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
        $routes->post('download-lembar-pengendalian', 'Agendaris::downloadControlSheet', ['as' => 'progres_dokumen_masuk.download_control_sheet']);
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
