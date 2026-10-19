<?php

namespace CodeIgniter\HTTP {
    class RedirectResponse {
        public string $type = ''; public string $message = '';
        public function to($url) { return $this; }
        public function with($type, $message) { $this->type = $type; $this->message = $message; return $this; }
    }
}
namespace App\Libraries {
    class AssetWorkbookParser {
        public function parse($file, $groups): array { return [['nomor_aset' => 'lama'], ['nomor_aset' => 'baru']]; }
    }
}
namespace App\Models {
    class DataAsetModel {
        public function insert($row) { if ($GLOBALS['failInsert']) { throw new \RuntimeException('Test failure'); } $GLOBALS['inserted'][] = $row['nomor_aset']; return true; }
    }
}
namespace App\Controllers {
    class BaseController {
        public $request;
        public function __construct() { $this->request = new \ImportRequest(); }
        protected function currentRoleIsAdmin(): bool { return true; }
    }
}
namespace {
    class ImportFile {
        public function isValid() { return true; } public function hasMoved() { return false; }
        public function getClientExtension() { return 'xlsx'; } public function getSize() { return 100; }
        public function getTempName() { return 'fixture'; }
    }
    class ImportRequest { public function getFile($name) { return new ImportFile(); } }
    class ImportDatabase {
        public bool $committed = false; public bool $rolledBack = false; private string $number = '';
        public function transBegin() {} public function table($name) { return $this; }
        public function where($field, $value) { $this->number = $value; return $this; }
        public function countAllResults() { return $this->number === 'lama' ? 1 : 0; }
        public function transStatus() { return true; }
        public function transCommit() { $this->committed = true; return true; }
        public function transRollback() { $this->rolledBack = true; }
    }
    function redirect() { return new \CodeIgniter\HTTP\RedirectResponse(); }
    function site_url($path) { return $path; }
    function db_connect() { return $GLOBALS['db']; }
    function log_message(...$args) {}
    require __DIR__ . '/../../app/Controllers/DataAset.php';
    $GLOBALS['db'] = new ImportDatabase(); $GLOBALS['failInsert'] = false; $GLOBALS['inserted'] = [];
    $response = (new \App\Controllers\DataAset())->import();
    if ($response->type !== 'success' || ! $GLOBALS['db']->committed || $GLOBALS['inserted'] !== ['baru'] || ! str_contains($response->message, '1 nomor aset')) { throw new \RuntimeException('Import did not skip existing asset.'); }
    $GLOBALS['db'] = new ImportDatabase(); $GLOBALS['failInsert'] = true;
    $response = (new \App\Controllers\DataAset())->import();
    if ($response->type !== 'error' || ! $GLOBALS['db']->rolledBack || $GLOBALS['db']->committed) { throw new \RuntimeException('Failed import did not roll back.'); }
    echo "Data Aset Excel import transaction: passed\n";
}
