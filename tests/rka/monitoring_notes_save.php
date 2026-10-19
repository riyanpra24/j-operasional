<?php
namespace CodeIgniter\HTTP {
    class RedirectResponse {
        public string $url = ''; public array $flash = [];
        public function to($url) { $this->url = $url; return $this; }
        public function with($key, $value) { $this->flash[$key] = $value; return $this; }
        public function withInput() { return $this; }
    }
}
namespace App\Libraries {
    class RkaCalculator { public const SOURCE_UNITS = ['Kanwil', 'Surabaya']; }
    class MonitoringAnggaran {
        public function rows($values, $search = '') { return [['key' => 'group_29'], ['key' => 'row_29'], ['key' => 'row_30']]; }
    }
}
namespace App\Controllers {
    class BaseController { public $request; public function __construct() { $this->request = new \NoteRequest(); } }
}
namespace {
    class NoteRequest { public function getPost($key) { return $GLOBALS['post'][$key] ?? null; } }
    class NoteDatabase {
        public array $rows = []; public array $snapshot = []; public array $condition = []; public bool $inTransaction = false;
        public function transBegin() { $this->snapshot = $this->rows; $this->inTransaction = true; return true; }
        public function transRollback() { if ($this->inTransaction) $this->rows = $this->snapshot; $this->inTransaction = false; return true; }
        public function transCommit() { $this->inTransaction = false; return true; }
        public function transStatus() { return true; }
        public function query($sql, $params) { $this->queryKey = implode(':', $params); return $this; }
        public function getRowArray() { return $this->rows[$this->queryKey] ?? null; }
        public function table($name) { $this->condition = []; return $this; }
        public function where($field, $value) { $this->condition[$field] = $value; return $this; }
        public function insert($data) { $key = implode(':', [$data['unit_name'], $data['report_year'], $data['report_month'], $data['row_key']]); $this->rows[$key] = $data + ['id' => $key]; return true; }
        public function update($data) { $key = $this->condition['id']; $this->rows[$key] = array_merge($this->rows[$key], $data); return true; }
    }
    class NoteSession { public function get($key) { return 'Penguji'; } }
    function db_connect() { return $GLOBALS['db']; }
    function session() { return new NoteSession(); }
    function redirect() { return new \CodeIgniter\HTTP\RedirectResponse(); }
    function site_url($path) { return '/' . $path; }
    function log_message($level, $message, $context = []) {}
    require __DIR__ . '/../../app/Controllers/Akutansi.php';
    $GLOBALS['db'] = new NoteDatabase();
    $controller = new \App\Controllers\Akutansi();
    $GLOBALS['post'] = ['unit' => 'Surabaya', 'tahun' => '2026', 'bulan' => '9', 'uraian' => '', 'notes' => ['row_29' => 'Catatan September', 'row_30' => ''], 'original' => ['row_29' => '', 'row_30' => '']];
    $response = $controller->saveMonitoringExplanations();
    if (!isset($response->flash['success']) || count($GLOBALS['db']->rows) !== 1 || $GLOBALS['db']->rows['Surabaya:2026:9:row_29']['explanation'] !== 'Catatan September') throw new \RuntimeException('Unable to save a note or unchanged rows were written.');
    $GLOBALS['post']['bulan'] = '10'; $GLOBALS['post']['notes']['row_29'] = 'Catatan Oktober';
    $controller->saveMonitoringExplanations();
    if (count($GLOBALS['db']->rows) !== 2 || $GLOBALS['db']->rows['Surabaya:2026:9:row_29']['explanation'] !== 'Catatan September') throw new \RuntimeException('Notes must be isolated by period.');
    $GLOBALS['post']['bulan'] = '9'; $GLOBALS['post']['notes']['row_29'] = 'Usang';
    $response = $controller->saveMonitoringExplanations();
    if (!isset($response->flash['error']) || $GLOBALS['db']->rows['Surabaya:2026:9:row_29']['explanation'] !== 'Catatan September') throw new \RuntimeException('Stale notes must not overwrite newer notes.');
    $GLOBALS['post']['notes'] = ['row_29' => 'Perubahan', 'invalid' => 'Tidak sah'];
    $GLOBALS['post']['original'] = ['row_29' => 'Catatan September', 'invalid' => ''];
    $response = $controller->saveMonitoringExplanations();
    if (!isset($response->flash['error']) || $GLOBALS['db']->rows['Surabaya:2026:9:row_29']['explanation'] !== 'Catatan September') throw new \RuntimeException('Unknown row must reject the entire request.');
    echo "Monitoring notes save: passed\n";
}
