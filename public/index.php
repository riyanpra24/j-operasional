<?php

use CodeIgniter\Boot;
use Config\Paths;

/*
 *---------------------------------------------------------------
 * CHECK PHP VERSION
 *---------------------------------------------------------------
 */

$minPhpVersion = '8.2'; // If you update this, don't forget to update `spark`.
if (version_compare(PHP_VERSION, $minPhpVersion, '<')) {
    $message = sprintf(
        'Your PHP version must be %s or higher to run CodeIgniter. Current version: %s',
        $minPhpVersion,
        PHP_VERSION,
    );

    header('HTTP/1.1 503 Service Unavailable.', true, 503);
    echo $message;

    exit(1);
}

/*
 *---------------------------------------------------------------
 * SET THE CURRENT DIRECTORY
 *---------------------------------------------------------------
 */

// Path to the front controller (this file)
define('FCPATH', __DIR__ . DIRECTORY_SEPARATOR);

// Ensure the current directory is pointing to the front controller's directory
if (getcwd() . DIRECTORY_SEPARATOR !== FCPATH) {
    chdir(FCPATH);
}

/*
 *---------------------------------------------------------------
 * BOOTSTRAP THE APPLICATION
 *---------------------------------------------------------------
 * This process sets up the path constants, loads and registers
 * our autoloader, along with Composer's, loads our constants
 * and fires up an environment-specific bootstrapping.
 */

// LOAD OUR PATHS CONFIG FILE
//
// Struktur lokal CodeIgniter:
//   operasional/public/index.php
//   operasional/app/Config/Paths.php
//
// Struktur yang disarankan untuk Hostinger shared hosting:
//   public_html/index.php
//   operasional/app/Config/Paths.php
//
// Kandidat pertama mempertahankan struktur standar CodeIgniter. Kandidat
// kedua memungkinkan isi folder `public` dipindahkan ke `public_html` tanpa
// membuka folder app, system, dan writable ke akses publik.
$pathsConfigCandidates = [
    FCPATH . '../app/Config/Paths.php',
    FCPATH . '../operasional/app/Config/Paths.php',
];

$pathsConfig = null;

foreach ($pathsConfigCandidates as $candidate) {
    if (is_file($candidate)) {
        $pathsConfig = $candidate;
        break;
    }
}

if ($pathsConfig === null) {
    http_response_code(500);
    exit('Konfigurasi CodeIgniter tidak ditemukan. Periksa lokasi folder operasional/app/Config/Paths.php.');
}

require $pathsConfig;

$paths = new Paths();

// LOAD THE FRAMEWORK BOOTSTRAP FILE
require $paths->systemDirectory . '/Boot.php';

exit(Boot::bootWeb($paths));
