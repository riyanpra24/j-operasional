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
    function view($name, $data): string { $GLOBALS['viewData'] = $data; return ''; }
    require __DIR__ . '/../../app/Controllers/Akutansi.php';
    $controller = new \App\Controllers\Akutansi();
    foreach (['Konsolidasi' => 'Korporat Kanwil', 'Surabaya' => 'Surabaya'] as $filterUnit => $reportUnit) {
        $GLOBALS['query'] = ['unit' => $filterUnit, 'tahun' => '2025', 'bulan' => '9', 'uraian' => 'gaji'];
        $controller->monitoringAnggaran();
        if ($GLOBALS['lrSelection'] !== [$reportUnit, 2025, 'YTD', 9] || $GLOBALS['rkaSelection'] !== [$reportUnit, 2025]) throw new \RuntimeException('Incorrect YTD period or unit selection.');
        if ($GLOBALS['mappedActuals'] !== [null, 'gaji', ['beban gaji karyawan' => ['TOTAL' => '17.00']]] || ! $GLOBALS['viewData']['hasRealization']) throw new \RuntimeException('YTD report values did not reach monitoring.');
    }
    echo "Monitoring anggaran YTD period/unit selection: passed\n";
}
