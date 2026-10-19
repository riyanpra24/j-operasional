<?php

namespace App\Models {
    class DataAsetModel
    {
        private bool $statuses = false;
        public function select($field) { $this->statuses = true; return $this; }
        public function distinct() { return $this; }
        public function orderBy($field, $direction) { return $this; }
        public function findAll(): array
        {
            if ($this->statuses) { return [['status_aset' => 'Sedang Digunakan']]; }
            $records = [];
            for ($id = 1; $id <= 23; $id++) {
                $records[] = ['id' => $id, 'kategori' => $id <= 12 ? 'Gedung' : 'Kendaraan'];
            }
            return $records;
        }
    }
}

namespace App\Controllers {
    class BaseController
    {
        public $request;
        public function __construct() { $this->request = new \TestRequest(); }
        protected function currentRoleIsAdmin(): bool { return false; }
    }
}

namespace {
    class TestRequest
    {
        public function getGet($key) { return $GLOBALS['testQuery'][$key] ?? null; }
    }
    function session() { return new class { public function getFlashdata($key) { return null; } }; }
    function service($name) {
        return new class {
            public function makeLinks($page, $perPage, $total, $template, $segment, $group) {
                return "$page/$perPage/$total";
            }
        };
    }
    function view($name, $data): string { $GLOBALS['renderedData'] = $data; return ''; }

    require __DIR__ . '/../../app/Libraries/AssetPeriod.php';
    require __DIR__ . '/../../app/Controllers/DataAset.php';
    $controller = new \App\Controllers\DataAset();
    foreach ([1 => [1, 10], 2 => [11, 20], 3 => [21, 23], 99 => [21, 23]] as $page => [$first, $last]) {
        $GLOBALS['testQuery'] = ['page_aset' => $page];
        $controller->index();
        $result = $GLOBALS['renderedData'];
        $ids = [];
        foreach ($result['groups'] as $records) {
            foreach ($records as $record) { $ids[] = $record['id']; }
        }
        if ($ids !== range($first, $last)) { throw new \RuntimeException("Incorrect records on page $page"); }
        if ($result['filters']['perPage'] !== 10 || $result['pagination'] !== min($page, 3) . '/10/23') {
            throw new \RuntimeException("Incorrect pagination on page $page");
        }
        if ($page === 2 && array_keys($result['groups']) !== ['Gedung', 'Kendaraan']) {
            throw new \RuntimeException('Category headings must be retained across page boundaries.');
        }
    }
    foreach ([10, 20, 50, 100, 999] as $size) {
        $GLOBALS['testQuery'] = ['per_page' => $size];
        $controller->index();
        $result = $GLOBALS['renderedData'];
        $expected = in_array($size, [10, 20, 50, 100], true) ? $size : 10;
        $count = array_sum(array_map('count', $result['groups']));
        if ($result['filters']['perPage'] !== $expected || $count !== min($expected, 23) || $result['pagination'] !== "1/$expected/23") {
            throw new \RuntimeException("Incorrect page size $size");
        }
    }
    echo "Data Aset pagination: passed\n";
}
