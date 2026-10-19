<?php

namespace App\Libraries {
    class RkaCalculator { public const SOURCE_UNITS = ['Kanwil', 'Surabaya']; }
    class RkaBudgetService {
        public function find($unit, $year) { $GLOBALS['rkaSelection'] = [$unit, $year]; return null; }
    }
    class LrRealizationService {
        public function view($unit, $year, $basis, $month) {
            $GLOBALS['lrSelection'] = [$unit, $year, $basis, $month];
            return ['values' => ['beban gaji karyawan' => ['TOTAL' => '17.00']]];
        }
    }
    class MonitoringAnggaran {
        public function rows($rka, $search, $actuals) { $GLOBALS['mappedActuals'] = [$rka, $search, $actuals]; return []; }
    }
}
namespace App\Controllers {
    class BaseController {
        public $request;
        public function __construct() { $this->request = new \MonitoringRequest(); }
    }
}
namespace {
    class MonitoringRequest { public function getGet($key) { return $GLOBALS['query'][$key] ?? null; } }
    class MonitoringNotesQuery {
        public function table($name) { return $this; }
        public function select($columns) { return $this; }
        public function where($column, $value) { return $this; }
        public function get() { return $this; }
        public function getResultArray() { return [['row_key' => 'row_29', 'explanation' => 'Catatan']]; }
    }
    function db_connect() { return new MonitoringNotesQuery(); }
    function view($name, $data): string { $GLOBALS['viewData'] = $data; return ''; }
    require __DIR__ . '/../../app/Controllers/Akutansi.php';
    $controller = new \App\Controllers\Akutansi();
    foreach (['Konsolidasi' => 'Korporat Kanwil', 'Surabaya' => 'Surabaya'] as $filterUnit => $reportUnit) {
        $GLOBALS['query'] = ['unit' => $filterUnit, 'tahun' => '2025', 'bulan' => '9', 'uraian' => 'gaji'];
        $controller->monitoringAnggaran();
        if ($GLOBALS['lrSelection'] !== [$reportUnit, 2025, 'YTD', 9] || $GLOBALS['rkaSelection'] !== [$reportUnit, 2025]) throw new \RuntimeException('Incorrect YTD period or unit selection.');
        if ($GLOBALS['mappedActuals'] !== [null, 'gaji', ['beban gaji karyawan' => ['TOTAL' => '17.00']]] || ! $GLOBALS['viewData']['hasRealization']) throw new \RuntimeException('YTD report values did not reach monitoring.');
        if ($GLOBALS['viewData']['monitoringNotes'] !== ['row_29' => 'Catatan']) throw new \RuntimeException('Monitoring notes were not passed to the view.');
    }
    echo "Monitoring anggaran YTD period/unit selection: passed\n";
}
