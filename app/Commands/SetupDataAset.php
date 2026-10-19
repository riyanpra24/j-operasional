<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class SetupDataAset extends BaseCommand
{
    protected $group = 'Database';
    protected $name = 'aset:setup';
    protected $description = 'Membuat tabel Data Aset saja, tanpa menjalankan migrasi lain.';

    public function run(array $params)
    {
        $db = db_connect();
        $recorded = $db->table('migrations')->where('version', '2026-10-18-140000')->where('namespace', 'App')->countAllResults();
        if ($db->tableExists('data_aset') || $recorded !== 0) {
            CLI::error('Dibatalkan: tabel aset atau riwayat migrasinya sudah ada.');
            return EXIT_ERROR;
        }
        $migration = APPPATH . 'Database/Migrations/2026-10-18-140000_CreateDataAset.php';
        if (! service('migrations')->force($migration, 'App')) {
            CLI::error('Migrasi Data Aset gagal.');
            return EXIT_ERROR;
        }
        CLI::write('Tabel Data Aset berhasil dibuat.');
        return EXIT_SUCCESS;
    }
}
