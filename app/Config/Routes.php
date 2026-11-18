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
$routes->get('manager-operasional/rekap-pks-barang-jasa', '\App\Modules\ManagerOperasional\Controllers\ManagerOperasional::rekapPks', ['as' => 'manager_operasional.rekap_pks']);
$routes->get('dashboard/coming-soon-preview', 'Dashboard::comingSoonPreview', ['as' => 'dashboard.coming_soon_preview']);

// Halaman awal modul SDM & Teller dan Akutansi
$routes->get('sdm', '\App\Modules\Sdm\Controllers\Sdm::index', ['as' => 'sdm.index']);
$routes->get('sdm/sppd', '\App\Modules\Sdm\Controllers\Sppd::index', ['as' => 'sdm.sppd']);
$routes->post('sdm/sppd', '\App\Modules\Sdm\Controllers\Sppd::store', ['as' => 'sdm.sppd.store']);
$routes->post('sdm/sppd/(:num)', '\App\Modules\Sdm\Controllers\Sppd::update/$1', ['as' => 'sdm.sppd.update']);
$routes->post('sdm/sppd/(:num)/hapus', '\App\Modules\Sdm\Controllers\Sppd::destroy/$1', ['as' => 'sdm.sppd.delete']);
$routes->get('sdm/data-magang', '\App\Modules\Sdm\Controllers\Magang::index', ['as' => 'sdm.data_magang']);
$routes->post('sdm/data-magang', '\App\Modules\Sdm\Controllers\Magang::store', ['as' => 'sdm.data_magang.store']);
$routes->post('sdm/data-magang/import', '\App\Modules\Sdm\Controllers\Magang::import', ['as' => 'sdm.data_magang.import']);
$routes->get('sdm/data-magang/export', '\App\Modules\Sdm\Controllers\Magang::export', ['as' => 'sdm.data_magang.export']);
$routes->post('sdm/data-magang/hapus-semua', '\App\Modules\Sdm\Controllers\Magang::destroyAll', ['as' => 'sdm.data_magang.delete_all']);
$routes->post('sdm/data-magang/(:num)', '\App\Modules\Sdm\Controllers\Magang::update/$1', ['as' => 'sdm.data_magang.update']);
$routes->post('sdm/data-magang/(:num)/hapus', '\App\Modules\Sdm\Controllers\Magang::destroy/$1', ['as' => 'sdm.data_magang.delete']);
$routes->get('sdm/dokumen-masuk', '\App\Modules\Sdm\Controllers\Sdm::incomingDocuments', ['as' => 'sdm.dokumen_masuk']);
$routes->get('sdm/arsip-dokumen-masuk', '\App\Modules\Agendaris\Controllers\Agendaris::suratMasuk', ['as' => 'sdm.arsip_dokumen_masuk']);
$routes->get('sdm/arsip-dokumen-masuk/(:num)', '\App\Modules\Agendaris\Controllers\Agendaris::show/$1', ['as' => 'sdm.arsip_dokumen_masuk.show']);
$routes->get('sdm/arsip-dokumen-keluar', '\App\Modules\Shared\Documents\Controllers\DokumenKeluar::index', ['as' => 'sdm.arsip_dokumen_keluar']);
$routes->get('sdm/arsip-dokumen-keluar/(:num)', '\App\Modules\Shared\Documents\Controllers\DokumenKeluar::show/$1', ['as' => 'sdm.arsip_dokumen_keluar.show']);
$routes->post('sdm/dokumen-masuk/sinkronkan', '\App\Modules\Sdm\Controllers\Sdm::synchronizeIncomingDocuments', ['as' => 'sdm.dokumen_masuk.synchronize']);
$routes->get('sdm/dashboard-kehadiran', '\App\Modules\Sdm\Controllers\Sdm::attendanceDashboard', ['as' => 'sdm.dashboard_kehadiran']);
$routes->get('sdm/kalender', '\App\Modules\Sdm\Controllers\Sdm::attendanceCalendar', ['as' => 'sdm.calendar']);
$routes->post('sdm/kalender', '\App\Modules\Sdm\Controllers\Sdm::updateAttendanceCalendar', ['as' => 'sdm.calendar.update']);
$routes->get('sdm/data-kehadiran', '\App\Modules\Sdm\Controllers\Sdm::attendanceRecords', ['as' => 'sdm.attendance_records']);
$routes->post('sdm/data-kehadiran/rekap', '\App\Modules\Sdm\Controllers\Sdm::recapSdmJatimAttendance', ['as' => 'sdm.attendance_records.recap']);
$routes->post('sdm/data-kehadiran/anomali', '\App\Modules\Sdm\Controllers\Sdm::updateSdmJatimAnomalies', ['as' => 'sdm.attendance_records.anomaly.update']);
$routes->post('sdm/data-kehadiran/export', '\App\Modules\Sdm\Controllers\Sdm::exportSdmJatimAttendance', ['as' => 'sdm.attendance_records.export']);
$routes->post('sdm/data-kehadiran/hapus', '\App\Modules\Sdm\Controllers\Sdm::deleteSdmJatimAttendance', ['as' => 'sdm.attendance_records.delete']);
$routes->get('sdm/data-lembur', '\App\Modules\Sdm\Controllers\Sdm::attendanceOvertime', ['as' => 'sdm.attendance_overtime']);
$routes->post('sdm/data-lembur/upload', '\App\Modules\Sdm\Controllers\Sdm::recapSdmOvertime', ['as' => 'sdm.attendance_overtime.upload']);
$routes->post('sdm/data-lembur/hapus', '\App\Modules\Sdm\Controllers\Sdm::deleteSdmOvertime', ['as' => 'sdm.attendance_overtime.delete']);
$routes->get('sdm/sdm-jatim', '\App\Modules\Sdm\Controllers\Sdm::sdmJatim', ['as' => 'sdm.jatim']);
$routes->get('sdm/sdm-jatim/export', '\App\Modules\Sdm\Controllers\Sdm::exportSdmJatimDocument', ['as' => 'sdm.jatim.export']);
$routes->post('sdm/sdm-jatim', '\App\Modules\Sdm\Controllers\Sdm::createSdmJatim', ['as' => 'sdm.jatim.create']);
$routes->get('sdm/sdm-jatim/template', '\App\Modules\Sdm\Controllers\Sdm::downloadSdmJatimTemplate', ['as' => 'sdm.jatim.template']);
$routes->post('sdm/sdm-jatim/import', '\App\Modules\Sdm\Controllers\Sdm::importSdmJatim', ['as' => 'sdm.jatim.import']);
$routes->post('sdm/sdm-jatim/hapus-semua', '\App\Modules\Sdm\Controllers\Sdm::deleteAllSdmJatim', ['as' => 'sdm.jatim.delete_all']);
$routes->post('sdm/sdm-jatim/(:num)', '\App\Modules\Sdm\Controllers\Sdm::updateSdmJatim/$1', ['as' => 'sdm.jatim.update']);
$routes->post('sdm/sdm-jatim/(:num)/hapus', '\App\Modules\Sdm\Controllers\Sdm::deleteSdmJatim/$1', ['as' => 'sdm.jatim.delete']);
$routes->post('sdm/dokumen-masuk/(:num)', '\App\Modules\Sdm\Controllers\Sdm::updateIncomingDocument/$1', ['as' => 'sdm.dokumen_masuk.update']);
$routes->get('akutansi', '\App\Modules\Akuntansi\Controllers\Akutansi::index', ['as' => 'akutansi.index']);
$routes->get('akutansi/dashboard', '\App\Modules\Akuntansi\Controllers\Akutansi::dashboard', ['as' => 'akutansi.dashboard']);
$routes->get('akutansi/monitoring-anggaran', '\App\Modules\Akuntansi\Controllers\Akutansi::monitoringAnggaran', ['as' => 'akutansi.monitoring_anggaran']);
$routes->get('akutansi/monitoring-anggaran/export', '\App\Modules\Akuntansi\Controllers\Akutansi::exportMonitoringAnggaran', ['as' => 'akutansi.monitoring_anggaran.export']);
$routes->post('akutansi/monitoring-anggaran/penjelasan', '\App\Modules\Akuntansi\Controllers\Akutansi::saveMonitoringExplanations', ['as' => 'akutansi.monitoring_anggaran.penjelasan']);
$routes->get('akutansi/bukti-bayar', '\App\Modules\Akuntansi\Controllers\BuktiBayar::index', ['as' => 'akutansi.bukti_bayar']);
$routes->post('akutansi/bukti-bayar', '\App\Modules\Akuntansi\Controllers\BuktiBayar::store', ['as' => 'akutansi.bukti_bayar.store']);
$routes->get('akutansi/bukti-bayar/(:num)', '\App\Modules\Akuntansi\Controllers\BuktiBayar::show/$1', ['as' => 'akutansi.bukti_bayar.show']);
$routes->get('akutansi/bukti-bayar/(:num)/berkas', '\App\Modules\Akuntansi\Controllers\BuktiBayar::downloadBerkas/$1', ['as' => 'akutansi.bukti_bayar.berkas']);
$routes->post('akutansi/bukti-bayar/(:num)', '\App\Modules\Akuntansi\Controllers\BuktiBayar::update/$1', ['as' => 'akutansi.bukti_bayar.update']);
$routes->post('akutansi/bukti-bayar/(:num)/hapus', '\App\Modules\Akuntansi\Controllers\BuktiBayar::destroy/$1', ['as' => 'akutansi.bukti_bayar.delete']);
$routes->get('akutansi/laba-rugi', '\App\Modules\Akuntansi\Controllers\Akutansi::labaRugi', ['as' => 'akutansi.laba_rugi']);
$routes->get('akutansi/rekap-laba-rugi', '\App\Modules\Akuntansi\Controllers\Akutansi::rekapLabaRugi', ['as' => 'akutansi.rekap_laba_rugi']);
$routes->post('akutansi/rekap-laba-rugi/edit', '\App\Modules\Akuntansi\Controllers\Akutansi::updateRekapLabaRugi', ['as' => 'akutansi.rekap_laba_rugi.edit']);
$routes->get('akutansi/bopo-ytd', '\App\Modules\Akuntansi\Controllers\Akutansi::bopoYtd', ['as' => 'akutansi.bopo_ytd']);
$routes->post('akutansi/laba-rugi/upload-excel', '\App\Modules\Akuntansi\Controllers\Akutansi::importLabaRugi', ['as' => 'akutansi.laba_rugi.import']);
$routes->get('akutansi/laba-rugi/editor-data', '\App\Modules\Akuntansi\Controllers\Akutansi::labaRugiEditorData', ['as' => 'akutansi.laba_rugi.editor_data']);
$routes->get('akutansi/laba-rugi/bopo-data', '\App\Modules\Akuntansi\Controllers\Akutansi::labaRugiBopoData', ['as' => 'akutansi.laba_rugi.bopo_data']);
$routes->post('akutansi/laba-rugi/edit-volume', '\App\Modules\Akuntansi\Controllers\Akutansi::updateLabaRugiVolume', ['as' => 'akutansi.laba_rugi.edit_volume']);
$routes->post('akutansi/laba-rugi/hapus', '\App\Modules\Akuntansi\Controllers\Akutansi::deleteLabaRugi', ['as' => 'akutansi.laba_rugi.delete']);
$routes->get('akutansi/simulasi-hitung', '\App\Modules\Akuntansi\Controllers\Akutansi::simulasiHitung', ['as' => 'akutansi.simulasi_hitung']);
$routes->post('akutansi/simulasi-hitung', '\App\Modules\Akuntansi\Controllers\Akutansi::uploadSimulasiHitung', ['as' => 'akutansi.simulasi_hitung.upload']);
$routes->get('akutansi/simulasi-hitung/unduh/(:segment)', '\App\Modules\Akuntansi\Controllers\Akutansi::downloadSimulasiHitung/$1', ['as' => 'akutansi.simulasi_hitung.download']);
$routes->post('akutansi/simulasi-hitung/hapus/(:segment)', '\App\Modules\Akuntansi\Controllers\Akutansi::deleteSimulasiHitung/$1', ['as' => 'akutansi.simulasi_hitung.delete']);
$routes->get('akutansi/export-dokumen', '\App\Modules\Akuntansi\Controllers\Akutansi::exportDokumen', ['as' => 'akutansi.export_dokumen']);
$routes->post('akutansi/export-dokumen', '\App\Modules\Akuntansi\Controllers\Akutansi::downloadExportDokumen', ['as' => 'akutansi.export_dokumen.download']);
$routes->get('akutansi/rka-kanwil-surabaya', '\App\Modules\Akuntansi\Controllers\Akutansi::rkaKanwilSurabaya', ['as' => 'akutansi.rka_kanwil_surabaya']);
$routes->get('akutansi/rka-kanwil-surabaya/upload-excel', '\App\Modules\Akuntansi\Controllers\Akutansi::rkaUpload', ['as' => 'akutansi.rka_upload']);
$routes->get('akutansi/rka-kanwil-surabaya/seting-manual', '\App\Modules\Akuntansi\Controllers\Akutansi::rkaManual', ['as' => 'akutansi.rka_manual']);
$routes->get('akutansi/rka-kanwil-surabaya/template', '\App\Modules\Akuntansi\Controllers\Akutansi::downloadRkaTemplate', ['as' => 'akutansi.rka_template']);
$routes->get('akutansi/rka-kanwil-surabaya/manual-data', '\App\Modules\Akuntansi\Controllers\Akutansi::rkaManualData', ['as' => 'akutansi.rka_manual.data']);
$routes->post('akutansi/rka-kanwil-surabaya/upload-excel', '\App\Modules\Akuntansi\Controllers\Akutansi::importRka', ['as' => 'akutansi.rka_import']);
$routes->post('akutansi/rka-kanwil-surabaya/seting-manual', '\App\Modules\Akuntansi\Controllers\Akutansi::saveRkaManual', ['as' => 'akutansi.rka_manual.save']);
$routes->post('akutansi/rka-kanwil-surabaya/hapus', '\App\Modules\Akuntansi\Controllers\Akutansi::deleteRka', ['as' => 'akutansi.rka_delete']);

// Bagian Umum 1
$routes->get('bagian-umum-1/data-aset', '\App\Modules\BagianUmum\Controllers\DataAset::index', ['as' => 'bagian_umum_1.data_aset']);
$routes->get('bagian-umum-1/data-aset/tambah', '\App\Modules\BagianUmum\Controllers\DataAset::create');
$routes->post('bagian-umum-1/data-aset', '\App\Modules\BagianUmum\Controllers\DataAset::store');
$routes->post('bagian-umum-1/data-aset/impor', '\App\Modules\BagianUmum\Controllers\DataAset::import');
$routes->get('bagian-umum-1/data-aset/(:num)', '\App\Modules\BagianUmum\Controllers\DataAset::show/$1');
$routes->get('bagian-umum-1/data-aset/(:num)/ubah', '\App\Modules\BagianUmum\Controllers\DataAset::edit/$1');
$routes->post('bagian-umum-1/data-aset/(:num)', '\App\Modules\BagianUmum\Controllers\DataAset::update/$1');
$routes->post('bagian-umum-1/data-aset/(:num)/hapus', '\App\Modules\BagianUmum\Controllers\DataAset::destroy/$1');

$routes->group('bagian-umum-1/dokumen-masuk', static function (RouteCollection $routes): void {
    $routes->get('/', '\App\Modules\Agendaris\Controllers\Agendaris::suratMasuk', ['as' => 'bagian_umum_1.dokumen_masuk']);
    $routes->get('(:num)', '\App\Modules\Agendaris\Controllers\Agendaris::show/$1', ['as' => 'bagian_umum_1.dokumen_masuk.show']);
});

$routes->group('bagian-umum-1/dokumen-keluar', static function (RouteCollection $routes): void {
    $routes->get('/', '\App\Modules\Shared\Documents\Controllers\DokumenKeluar::index', ['as' => 'bagian_umum_1.dokumen_keluar']);
    $routes->get('(:num)', '\App\Modules\Shared\Documents\Controllers\DokumenKeluar::show/$1', ['as' => 'bagian_umum_1.dokumen_keluar.show']);
});

$routes->group('bagian-umum-1/pks-barang-jasa', static function (RouteCollection $routes): void {
    $routes->get('/', '\App\Modules\Shared\Pks\Controllers\PksBarangJasa::index', ['as' => 'pks.index']);
    $routes->get('tambah', '\App\Modules\Shared\Pks\Controllers\PksBarangJasa::create', ['as' => 'pks.create']);
    $routes->post('/', '\App\Modules\Shared\Pks\Controllers\PksBarangJasa::store', ['as' => 'pks.store']);
    $routes->get('(:num)', '\App\Modules\Shared\Pks\Controllers\PksBarangJasa::show/$1', ['as' => 'pks.show']);
    $routes->get('(:num)/ubah', '\App\Modules\Shared\Pks\Controllers\PksBarangJasa::edit/$1', ['as' => 'pks.edit']);
    $routes->post('(:num)', '\App\Modules\Shared\Pks\Controllers\PksBarangJasa::update/$1', ['as' => 'pks.update']);
    $routes->post('(:num)/hapus', '\App\Modules\Shared\Pks\Controllers\PksBarangJasa::destroy/$1', ['as' => 'pks.destroy']);
    $routes->post('(:num)/dokumen', '\App\Modules\Shared\Pks\Controllers\PksBarangJasa::storeDocument/$1', ['as' => 'pks.document.store']);
    $routes->post('(:num)/dokumen/(:num)', '\App\Modules\Shared\Pks\Controllers\PksBarangJasa::updateDocument/$1/$2', ['as' => 'pks.document.update']);
    $routes->post('(:num)/dokumen/(:num)/hapus', '\App\Modules\Shared\Pks\Controllers\PksBarangJasa::destroyDocument/$1/$2', ['as' => 'pks.document.destroy']);
    $routes->post('(:num)/item', '\App\Modules\Shared\Pks\Controllers\PksBarangJasa::storeItem/$1', ['as' => 'pks.item.store']);
    $routes->post('(:num)/item/(:num)', '\App\Modules\Shared\Pks\Controllers\PksBarangJasa::updateItem/$1/$2', ['as' => 'pks.item.update']);
    $routes->post('(:num)/item/(:num)/hapus', '\App\Modules\Shared\Pks\Controllers\PksBarangJasa::destroyItem/$1/$2', ['as' => 'pks.item.destroy']);
});

$routes->get(
    'bagian-umum-1/pengadaan-barang-jasa',
    '\App\Modules\BagianUmum\Controllers\PengadaanBarangJasa::index',
    ['as' => 'pengadaan_barang_jasa.index'],
);
$routes->group('bagian-umum-2/pks-barang-jasa', static function (RouteCollection $routes): void {
    $routes->get('/', '\App\Modules\Shared\Pks\Controllers\PksBarangJasa::index', ['as' => 'pks_umum_2.index']);
    $routes->get('tambah', '\App\Modules\Shared\Pks\Controllers\PksBarangJasa::create', ['as' => 'pks_umum_2.create']);
    $routes->post('/', '\App\Modules\Shared\Pks\Controllers\PksBarangJasa::store', ['as' => 'pks_umum_2.store']);
    $routes->get('(:num)', '\App\Modules\Shared\Pks\Controllers\PksBarangJasa::show/$1', ['as' => 'pks_umum_2.show']);
    $routes->get('(:num)/ubah', '\App\Modules\Shared\Pks\Controllers\PksBarangJasa::edit/$1', ['as' => 'pks_umum_2.edit']);
    $routes->post('(:num)', '\App\Modules\Shared\Pks\Controllers\PksBarangJasa::update/$1', ['as' => 'pks_umum_2.update']);
    $routes->post('(:num)/hapus', '\App\Modules\Shared\Pks\Controllers\PksBarangJasa::destroy/$1', ['as' => 'pks_umum_2.destroy']);
    $routes->post('(:num)/dokumen', '\App\Modules\Shared\Pks\Controllers\PksBarangJasa::storeDocument/$1', ['as' => 'pks_umum_2.document.store']);
    $routes->post('(:num)/dokumen/(:num)', '\App\Modules\Shared\Pks\Controllers\PksBarangJasa::updateDocument/$1/$2', ['as' => 'pks_umum_2.document.update']);
    $routes->post('(:num)/dokumen/(:num)/hapus', '\App\Modules\Shared\Pks\Controllers\PksBarangJasa::destroyDocument/$1/$2', ['as' => 'pks_umum_2.document.destroy']);
    $routes->post('(:num)/item', '\App\Modules\Shared\Pks\Controllers\PksBarangJasa::storeItem/$1', ['as' => 'pks_umum_2.item.store']);
    $routes->post('(:num)/item/(:num)', '\App\Modules\Shared\Pks\Controllers\PksBarangJasa::updateItem/$1/$2', ['as' => 'pks_umum_2.item.update']);
    $routes->post('(:num)/item/(:num)/hapus', '\App\Modules\Shared\Pks\Controllers\PksBarangJasa::destroyItem/$1/$2', ['as' => 'pks_umum_2.item.destroy']);
});

$routes->group('bagian-umum-1/dokumen-spk', static function (RouteCollection $routes): void {
    $routes->get('/', '\App\Modules\BagianUmum\Controllers\DokumenSpk::index', ['as' => 'dokumen_spk.index']);
    $routes->get('generate-nomor', '\App\Modules\BagianUmum\Controllers\DokumenSpk::generateNumber', ['as' => 'dokumen_spk.generate_number']);
    $routes->post('/', '\App\Modules\BagianUmum\Controllers\DokumenSpk::store', ['as' => 'dokumen_spk.store']);
    $routes->post('(:num)', '\App\Modules\BagianUmum\Controllers\DokumenSpk::update/$1', ['as' => 'dokumen_spk.update']);
    $routes->post('(:num)/hapus', '\App\Modules\BagianUmum\Controllers\DokumenSpk::destroy/$1', ['as' => 'dokumen_spk.destroy']);
});

$routes->get(
    'bagian-umum-1/non-belanja-modal',
    '\App\Modules\BagianUmum\Controllers\NonBelanjaModal::index',
    ['as' => 'non_belanja_modal.index'],
);

// Bagian Umum 2
$routes->get(
    'bagian-umum-2',
    '\App\Modules\BagianUmum\Controllers\BagianUmum2::index',
    ['as' => 'bagian_umum_2.index'],
);

$routes->group('bagian-umum-2/dokumen-masuk', static function (RouteCollection $routes): void {
    $routes->get('/', '\App\Modules\Agendaris\Controllers\Agendaris::suratMasuk', ['as' => 'bagian_umum_2.dokumen_masuk']);
    $routes->get('(:num)', '\App\Modules\Agendaris\Controllers\Agendaris::show/$1', ['as' => 'bagian_umum_2.dokumen_masuk.show']);
});

$routes->group('bagian-umum-2/dokumen-keluar', static function (RouteCollection $routes): void {
    $routes->get('/', '\App\Modules\Shared\Documents\Controllers\DokumenKeluar::index', ['as' => 'bagian_umum_2.dokumen_keluar']);
    $routes->get('(:num)', '\App\Modules\Shared\Documents\Controllers\DokumenKeluar::show/$1', ['as' => 'bagian_umum_2.dokumen_keluar.show']);
});

$routes->group('bagian-umum-2/monitoring-kendaraan', static function (RouteCollection $routes): void {
    $routes->get('/', '\App\Modules\BagianUmum\Controllers\MonitoringKendaraan::index', ['as' => 'monitoring_kendaraan.index']);
    $routes->get('data-kendaraan', '\App\Modules\BagianUmum\Controllers\MonitoringKendaraan::vehicles', ['as' => 'monitoring_kendaraan.vehicles']);
    $routes->post('data-kendaraan', '\App\Modules\BagianUmum\Controllers\MonitoringKendaraan::storeVehicle', ['as' => 'monitoring_kendaraan.vehicles.store']);
    $routes->post('data-kendaraan/(:num)', '\App\Modules\BagianUmum\Controllers\MonitoringKendaraan::updateVehicle/$1', ['as' => 'monitoring_kendaraan.vehicles.update']);
    $routes->post('data-kendaraan/(:num)/hapus', '\App\Modules\BagianUmum\Controllers\MonitoringKendaraan::destroyVehicle/$1', ['as' => 'monitoring_kendaraan.vehicles.destroy']);
    $routes->get('servis-perawatan', '\App\Modules\BagianUmum\Controllers\MonitoringKendaraan::maintenance', ['as' => 'monitoring_kendaraan.maintenance']);
    $routes->post('servis-perawatan', '\App\Modules\BagianUmum\Controllers\MonitoringKendaraan::storeMaintenance', ['as' => 'monitoring_kendaraan.maintenance.store']);
    $routes->post('servis-perawatan/(:num)', '\App\Modules\BagianUmum\Controllers\MonitoringKendaraan::updateMaintenance/$1', ['as' => 'monitoring_kendaraan.maintenance.update']);
    $routes->post('servis-perawatan/(:num)/hapus', '\App\Modules\BagianUmum\Controllers\MonitoringKendaraan::destroyMaintenance/$1', ['as' => 'monitoring_kendaraan.maintenance.destroy']);
    $routes->get('dokumen-kendaraan', '\App\Modules\BagianUmum\Controllers\MonitoringKendaraan::documents', ['as' => 'monitoring_kendaraan.documents']);
    $routes->post('dokumen-kendaraan', '\App\Modules\BagianUmum\Controllers\MonitoringKendaraan::storeDocument', ['as' => 'monitoring_kendaraan.documents.store']);
    $routes->post('dokumen-kendaraan/(:num)', '\App\Modules\BagianUmum\Controllers\MonitoringKendaraan::updateDocument/$1', ['as' => 'monitoring_kendaraan.documents.update']);
    $routes->post('dokumen-kendaraan/(:num)/hapus', '\App\Modules\BagianUmum\Controllers\MonitoringKendaraan::destroyDocument/$1', ['as' => 'monitoring_kendaraan.documents.destroy']);
    $routes->get('riwayat-laporan', '\App\Modules\BagianUmum\Controllers\MonitoringKendaraan::reports', ['as' => 'monitoring_kendaraan.reports']);
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
    $routes->get('/', '\App\Modules\Shared\Documents\Controllers\DokumenMasuk::index', ['as' => 'dokumen_masuk.index']);
    $routes->get('(:num)', '\App\Modules\Shared\Documents\Controllers\DokumenMasuk::show/$1', ['as' => 'dokumen_masuk.show']);
    $routes->post('(:num)/kembalikan', '\App\Modules\Shared\Documents\Controllers\DokumenMasuk::reopen/$1', ['as' => 'dokumen_masuk.reopen']);
});

// Security - Dokumen Keluar (hanya baca)
$routes->group('dokumen-keluar', static function (RouteCollection $routes): void {
    $routes->get('/', '\App\Modules\Shared\Documents\Controllers\DokumenKeluar::index', ['as' => 'dokumen_keluar.security_index']);
    $routes->get('(:num)', '\App\Modules\Shared\Documents\Controllers\DokumenKeluar::show/$1', ['as' => 'dokumen_keluar.security_show']);
});

// Security - Distribusi Dokumen
$routes->group('distribusi-dokumen', static function (RouteCollection $routes): void {
    $routes->get('/', '\App\Modules\Shared\Documents\Controllers\DistribusiDokumen::index', ['as' => 'distribusi_dokumen.index']);

    // CRUD Dokumen Masuk dikelola dari antrean Distribusi Dokumen.
    $routes->post('dokumen-masuk', '\App\Modules\Shared\Documents\Controllers\DokumenMasuk::store', ['as' => 'distribusi_dokumen.incoming.store']);
    $routes->get('dokumen-masuk/(:num)', '\App\Modules\Shared\Documents\Controllers\DokumenMasuk::show/$1', ['as' => 'distribusi_dokumen.incoming.show']);
    $routes->get('dokumen-masuk/(:num)/ubah', '\App\Modules\Shared\Documents\Controllers\DokumenMasuk::edit/$1', ['as' => 'distribusi_dokumen.incoming.edit']);
    $routes->post('dokumen-masuk/(:num)', '\App\Modules\Shared\Documents\Controllers\DokumenMasuk::update/$1', ['as' => 'distribusi_dokumen.incoming.update']);
    $routes->post('dokumen-masuk/(:num)/hapus', '\App\Modules\Shared\Documents\Controllers\DokumenMasuk::destroy/$1', ['as' => 'distribusi_dokumen.incoming.destroy']);

    // Route Surat Keluar diletakkan sebelum parameter numerik agar struktur
    // modul mudah dibaca dan tidak ambigu.
    $routes->get('surat-keluar/(:num)', '\App\Modules\Shared\Documents\Controllers\DistribusiDokumen::showOutgoing/$1', ['as' => 'distribusi_dokumen.outgoing.show']);
    $routes->post('surat-keluar/(:num)', '\App\Modules\Shared\Documents\Controllers\DistribusiDokumen::completeOutgoing/$1', ['as' => 'distribusi_dokumen.outgoing.complete']);

    $routes->get('(:num)', '\App\Modules\Shared\Documents\Controllers\DistribusiDokumen::show/$1', ['as' => 'distribusi_dokumen.show']);
    $routes->post('(:num)', '\App\Modules\Shared\Documents\Controllers\DistribusiDokumen::complete/$1', ['as' => 'distribusi_dokumen.complete']);
});

// Agendaris
$routes->group('agendaris', static function (RouteCollection $routes): void {
    $routes->get('/', '\App\Modules\Agendaris\Controllers\Agendaris::index', ['as' => 'agendaris.index']);
    $routes->get('dashboard', '\App\Modules\Agendaris\Controllers\Agendaris::dashboard', ['as' => 'agendaris.dashboard']);

    $routes->group('surat-masuk', static function (RouteCollection $routes): void {
        $routes->get('/', '\App\Modules\Agendaris\Controllers\Agendaris::suratMasuk', ['as' => 'agendaris.surat_masuk']);
        $routes->get('(:num)', '\App\Modules\Agendaris\Controllers\Agendaris::show/$1', ['as' => 'agendaris.show']);
        $routes->post('(:num)/kembalikan', '\App\Modules\Agendaris\Controllers\Agendaris::reopen/$1', ['as' => 'agendaris.reopen']);
    });

    $routes->group('surat-keluar', static function (RouteCollection $routes): void {
        $routes->get('/', '\App\Modules\Shared\Documents\Controllers\DokumenKeluar::index', ['as' => 'dokumen_keluar.index']);
        $routes->get('(:num)', '\App\Modules\Shared\Documents\Controllers\DokumenKeluar::show/$1', ['as' => 'dokumen_keluar.show']);
        $routes->post('(:num)/kembalikan', '\App\Modules\Shared\Documents\Controllers\DokumenKeluar::reopen/$1', ['as' => 'dokumen_keluar.reopen']);
    });

    $routes->get('progres-dokumen', '\App\Modules\Agendaris\Controllers\ProgresDokumen::masuk', ['as' => 'progres_dokumen.index']);

    $routes->group('progres-dokumen-masuk', static function (RouteCollection $routes): void {
        $routes->get('/', '\App\Modules\Agendaris\Controllers\ProgresDokumen::masuk', ['as' => 'progres_dokumen_masuk.index']);
        $routes->post('/', '\App\Modules\Agendaris\Controllers\Agendaris::store', ['as' => 'progres_dokumen_masuk.store']);
        $routes->get('generate-nomor', '\App\Modules\Agendaris\Controllers\Agendaris::generateNomor', ['as' => 'progres_dokumen_masuk.generate_nomor']);
        $routes->post('download-lembar-pengendalian', '\App\Modules\Agendaris\Controllers\Agendaris::downloadControlSheet', ['as' => 'progres_dokumen_masuk.download_control_sheet']);
        $routes->post('sinkronkan', '\App\Modules\Agendaris\Controllers\Agendaris::synchronize', ['as' => 'progres_dokumen_masuk.synchronize']);
        $routes->get('(:num)', '\App\Modules\Agendaris\Controllers\Agendaris::show/$1', ['as' => 'progres_dokumen_masuk.show']);
        $routes->post('(:num)', '\App\Modules\Agendaris\Controllers\Agendaris::update/$1', ['as' => 'progres_dokumen_masuk.update']);
        $routes->post('(:num)/hapus', '\App\Modules\Agendaris\Controllers\Agendaris::destroy/$1', ['as' => 'progres_dokumen_masuk.destroy']);
    });

    $routes->group('progres-dokumen-keluar', static function (RouteCollection $routes): void {
        $routes->get('/', '\App\Modules\Agendaris\Controllers\ProgresDokumen::index', ['as' => 'progres_dokumen_keluar.index']);
        $routes->post('/', '\App\Modules\Agendaris\Controllers\ProgresDokumen::store', ['as' => 'progres_dokumen_keluar.store']);
        $routes->get('(:num)', '\App\Modules\Agendaris\Controllers\ProgresDokumen::show/$1', ['as' => 'progres_dokumen_keluar.show']);
        $routes->post('(:num)', '\App\Modules\Agendaris\Controllers\ProgresDokumen::update/$1', ['as' => 'progres_dokumen_keluar.update']);
        $routes->post('(:num)/hapus', '\App\Modules\Agendaris\Controllers\ProgresDokumen::destroy/$1', ['as' => 'progres_dokumen_keluar.destroy']);
    });

    $routes->group('souvenir-inventaris', static function (RouteCollection $routes): void {
        $routes->get('/', '\App\Modules\Agendaris\Controllers\SouvenirInventaris::index', ['as' => 'agendaris.souvenir_inventaris']);
        $routes->get('tambah', '\App\Modules\Agendaris\Controllers\SouvenirInventaris::create', ['as' => 'agendaris.souvenir_inventaris.create']);
        $routes->post('/', '\App\Modules\Agendaris\Controllers\SouvenirInventaris::store', ['as' => 'agendaris.souvenir_inventaris.store']);
        $routes->post('pengambilan', '\App\Modules\Agendaris\Controllers\SouvenirInventaris::storePickup', ['as' => 'agendaris.souvenir_inventaris.pickup.store']);
        $routes->post('pengambilan/(:num)', '\App\Modules\Agendaris\Controllers\SouvenirInventaris::updatePickup/$1', ['as' => 'agendaris.souvenir_inventaris.pickup.update']);
        $routes->post('pengambilan/(:num)/hapus', '\App\Modules\Agendaris\Controllers\SouvenirInventaris::destroyPickup/$1', ['as' => 'agendaris.souvenir_inventaris.pickup.destroy']);
        $routes->get('pengambilan/(:num)/bukti', '\App\Modules\Agendaris\Controllers\SouvenirInventaris::downloadPickupProof/$1', ['as' => 'agendaris.souvenir_inventaris.pickup.proof']);
        $routes->post('pengembalian', '\App\Modules\Agendaris\Controllers\SouvenirInventaris::storeReturn', ['as' => 'agendaris.souvenir_inventaris.return.store']);
        $routes->post('pengembalian/(:num)', '\App\Modules\Agendaris\Controllers\SouvenirInventaris::updateReturn/$1', ['as' => 'agendaris.souvenir_inventaris.return.update']);
        $routes->post('pengembalian/(:num)/hapus', '\App\Modules\Agendaris\Controllers\SouvenirInventaris::destroyReturn/$1', ['as' => 'agendaris.souvenir_inventaris.return.destroy']);
        $routes->get('pengembalian/(:num)/bukti', '\App\Modules\Agendaris\Controllers\SouvenirInventaris::downloadReturnProof/$1', ['as' => 'agendaris.souvenir_inventaris.return.proof']);
        $routes->post('peminjaman-inventaris', '\App\Modules\Agendaris\Controllers\SouvenirInventaris::storeInventoryLoan', ['as' => 'agendaris.souvenir_inventaris.loan.store']);
        $routes->post('peminjaman-inventaris/(:num)', '\App\Modules\Agendaris\Controllers\SouvenirInventaris::updateInventoryLoan/$1', ['as' => 'agendaris.souvenir_inventaris.loan.update']);
        $routes->post('peminjaman-inventaris/(:num)/hapus', '\App\Modules\Agendaris\Controllers\SouvenirInventaris::destroyInventoryLoan/$1', ['as' => 'agendaris.souvenir_inventaris.loan.destroy']);
        $routes->get('peminjaman-inventaris/(:num)/bukti', '\App\Modules\Agendaris\Controllers\SouvenirInventaris::downloadInventoryLoanProof/$1', ['as' => 'agendaris.souvenir_inventaris.loan.proof']);
        $routes->post('pengembalian-inventaris', '\App\Modules\Agendaris\Controllers\SouvenirInventaris::storeInventoryReturn', ['as' => 'agendaris.souvenir_inventaris.inventory_return.store']);
        $routes->post('pengembalian-inventaris/(:num)', '\App\Modules\Agendaris\Controllers\SouvenirInventaris::updateInventoryReturn/$1', ['as' => 'agendaris.souvenir_inventaris.inventory_return.update']);
        $routes->post('pengembalian-inventaris/(:num)/hapus', '\App\Modules\Agendaris\Controllers\SouvenirInventaris::destroyInventoryReturn/$1', ['as' => 'agendaris.souvenir_inventaris.inventory_return.destroy']);
        $routes->get('pengembalian-inventaris/(:num)/bukti', '\App\Modules\Agendaris\Controllers\SouvenirInventaris::downloadInventoryReturnProof/$1', ['as' => 'agendaris.souvenir_inventaris.inventory_return.proof']);
        $routes->get('(:num)', '\App\Modules\Agendaris\Controllers\SouvenirInventaris::show/$1', ['as' => 'agendaris.souvenir_inventaris.show']);
        $routes->get('(:num)/ubah', '\App\Modules\Agendaris\Controllers\SouvenirInventaris::edit/$1', ['as' => 'agendaris.souvenir_inventaris.edit']);
        $routes->post('(:num)', '\App\Modules\Agendaris\Controllers\SouvenirInventaris::update/$1', ['as' => 'agendaris.souvenir_inventaris.update']);
        $routes->post('(:num)/hapus', '\App\Modules\Agendaris\Controllers\SouvenirInventaris::destroy/$1', ['as' => 'agendaris.souvenir_inventaris.destroy']);
    });

    $routes->group('atk-kantor', static function (RouteCollection $routes): void {
        $routes->get('/', '\App\Modules\Agendaris\Controllers\AtkKantor::index', ['as' => 'agendaris.atk_kantor']);
        $routes->post('/', '\App\Modules\Agendaris\Controllers\AtkKantor::store', ['as' => 'agendaris.atk_kantor.store']);
        $routes->post('(:num)', '\App\Modules\Agendaris\Controllers\AtkKantor::update/$1', ['as' => 'agendaris.atk_kantor.update']);
        $routes->post('(:num)/hapus', '\App\Modules\Agendaris\Controllers\AtkKantor::destroy/$1', ['as' => 'agendaris.atk_kantor.destroy']);
        $routes->post('pengambilan', '\App\Modules\Agendaris\Controllers\AtkKantor::storePickup', ['as' => 'agendaris.atk_kantor.pickup.store']);
        $routes->get('pengambilan/bukti', '\App\Modules\Agendaris\Controllers\AtkKantor::downloadPickupProof', ['as' => 'agendaris.atk_kantor.pickup.proof']);
        $routes->post('pengambilan/(:num)/hapus', '\App\Modules\Agendaris\Controllers\AtkKantor::destroyPickup/$1', ['as' => 'agendaris.atk_kantor.pickup.destroy']);
    });
});
