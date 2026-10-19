<?php

namespace CodeIgniter\HTTP {
    class RedirectResponse
    {
        public string $message = '';
        public function to($url) { return $this; }
        public function with($key, $message) { $this->message = $message; return $this; }
    }
}
namespace App\Controllers {
    class BaseController
    {
        public $request;
        public function __construct() { $this->request = new \ForbiddenRequest(); }
        protected function currentRoleIsAdmin(): bool { return false; }
    }
}
namespace {
    class ForbiddenRequest { public function getFile($name) { throw new \RuntimeException('Non-admin reached upload processing.'); } }
    function redirect() { return new \CodeIgniter\HTTP\RedirectResponse(); }
    function site_url($path) { return $path; }
    require __DIR__ . '/../../app/Controllers/DataAset.php';
    $response = (new \App\Controllers\DataAset())->import();
    if (! str_contains($response->message, 'administrator')) { throw new \RuntimeException('Non-admin access was not rejected.'); }
    echo "Data Aset Excel administrator access: passed\n";
}
