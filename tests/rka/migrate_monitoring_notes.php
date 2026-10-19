<?php
/** Apply only the new monitoring notes migration, never toggle an existing one. */
define('FCPATH', realpath(__DIR__ . '/../../public') . DIRECTORY_SEPARATOR);
define('ENVIRONMENT', 'development');
require __DIR__ . '/../../app/Config/Paths.php';
$paths = new Config\Paths();
require $paths->systemDirectory . '/Boot.php';
CodeIgniter\Boot::bootConsole($paths);
$db = db_connect();
$version = '2026-10-19-120000';
$history = $db->table('migrations')->where('version', $version)->where('namespace', 'App')->get()->getRowArray();
if ($history !== null || $db->tableExists('accounting_monitoring_notes')) throw new RuntimeException('Migrasi atau tabel sudah ada; tidak menjalankan migrasi ulang.');
$runner = service('migrations');
if (! $runner->force(realpath(__DIR__ . '/../../app/Database/Migrations/2026-10-19-120000_CreateMonitoringAnggaranNotes.php'), 'App')) throw new RuntimeException('Migrasi tidak berhasil.');
echo "Monitoring notes migration: applied\n";
