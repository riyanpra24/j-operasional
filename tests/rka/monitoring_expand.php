<?php

require __DIR__ . '/../../app/Libraries/RkaMoney.php';
require __DIR__ . '/../../app/Libraries/RkaCalculator.php';
require __DIR__ . '/../../app/Libraries/LrMoney.php';
require __DIR__ . '/../../app/Libraries/OracleLrSalaryParser.php';
require __DIR__ . '/../../app/Libraries/MonitoringAnggaran.php';

function esc($value, $context = null): string { return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function site_url(string $path): string { return '/' . $path; }

$renderer = new class {
    public function extend($layout): string { return ''; }
    public function section($name): string { return ''; }
    public function endSection(): string { return ''; }
    public function render(string $search, ?array $rka = null, array $realizations = [], int $selectedMonth = 1): string
    {
        $monitoringRows = (new App\Libraries\MonitoringAnggaran())->rows($rka, $search, $realizations);
        $months = []; $units = ['Konsolidasi']; $selectedUnit = 'Konsolidasi';
        $selectedYear = 2026; $hasRka = false;
        ob_start();
        include __DIR__ . '/../../app/Views/akutansi/monitoring_anggaran.php';
        return ob_get_clean();
    }
};

foreach (['' => [3, 58], 'gaji' => [1, 1], 'tidak ada uraian ini' => [0, 0]] as $search => [$groupCount, $detailCount]) {
    $document = new DOMDocument();
    @$document->loadHTML('<?xml encoding="UTF-8">' . $renderer->render($search));
    $xpath = new DOMXPath($document);
    $buttons = $xpath->query('//button[@data-monitoring-toggle]');
    $details = $xpath->query('//tr[contains(@class,"monitoring-budget-detail")]');
    if ($buttons->length !== $groupCount || $details->length !== $detailCount) throw new RuntimeException('Unexpected groups or details.');
    foreach ($buttons as $button) {
        $expectedExpanded = $search === '' ? 'false' : 'true';
        if ($button->getAttribute('aria-expanded') !== $expectedExpanded) throw new RuntimeException('Incorrect initial expansion state.');
        foreach (explode(' ', $button->getAttribute('aria-controls')) as $id) {
            $detail = $document->getElementById($id);
            if (! $detail || $detail->hasAttribute('hidden') !== ($search === '')) throw new RuntimeException('Broken group/detail association.');
        }
    }
}
$document = new DOMDocument();
@$document->loadHTML('<?xml encoding="UTF-8">' . $renderer->render('gaji', ['H29' => '100.00'], ['beban gaji karyawan' => ['TOTAL' => '-93.00']]));
$xpath = new DOMXPath($document);
$percentage = $xpath->query('//tr[contains(@class,"monitoring-budget-detail")]/td[3]')->item(0);
if (! $percentage || trim($percentage->textContent) !== '*93%') throw new RuntimeException('The realization percentage is not rendered in the correct column.');
if ($xpath->query('//tr[contains(@class,"monitoring-budget-detail")]/td[2]/span[@class="monitoring-negative-value"]')->length !== 1 || $xpath->query('//tr[contains(@class,"monitoring-budget-detail")]/td[3]/span[@class="monitoring-negative-value"]')->length !== 1) throw new RuntimeException('Negative realization values and percentages must be marked red.');
if ($xpath->query('//tr[contains(@class,"monitoring-budget-detail")]/td[5]/span[@class="monitoring-negative-value"]')->length !== 0) throw new RuntimeException('Positive deviation must not be marked red.');
$document = new DOMDocument();
@$document->loadHTML('<?xml encoding="UTF-8">' . $renderer->render('gaji', ['H29' => '100.00'], ['beban gaji karyawan' => ['TOTAL' => '93.00']], 9));
$xpath = new DOMXPath($document);
if (trim($xpath->query('//tr[contains(@class,"monitoring-budget-detail")]/td[5]')->item(0)->textContent) !== '*18%') throw new RuntimeException('Detail deviation is not rendered correctly.');
if ($xpath->query('//tr[contains(@class,"monitoring-budget-detail")]/td[5]/span[@class="monitoring-negative-value"]')->length !== 1) throw new RuntimeException('Negative deviation must be marked red.');
if (trim($xpath->query('//tr[@data-monitoring-group]/td[5]')->item(0)->textContent) !== '—') throw new RuntimeException('Incomplete group must not show a deviation.');
$inputs = (new App\Libraries\RkaCalculator())->zeros();
$inputs['C29'] = '100.00';
$rka = (new App\Libraries\RkaCalculator())->calculate($inputs);
$realizations = [];
foreach ((new App\Libraries\MonitoringAnggaran())->rows($rka) as $row) {
    if ($row['group']) continue;
    $label = $row['label'] === 'Tranportasi dinas dalam negeri' ? 'Transportasi dinas dalam negeri' : $row['label'];
    $realizations[App\Libraries\OracleLrSalaryParser::normalizeLabel($label)] = ['TOTAL' => '0.00'];
}
$realizations['beban gaji karyawan']['TOTAL'] = '93.00';
$document = new DOMDocument();
@$document->loadHTML('<?xml encoding="UTF-8">' . $renderer->render('gaji', $rka, $realizations, 9));
$xpath = new DOMXPath($document);
if (trim($xpath->query('//tr[@data-monitoring-group]/td[5]')->item(0)->textContent) !== '*18%') throw new RuntimeException('Group deviation is not based on the full group totals.');
if ($xpath->query('//tr[@data-monitoring-group]/td[5]/span[@class="monitoring-negative-value"]')->length !== 1) throw new RuntimeException('Negative group deviation must be marked red.');
foreach ([1 => '8%', 2 => '17%', 3 => '25%', 4 => '33%', 5 => '42%', 6 => '50%', 7 => '58%', 8 => '67%', 9 => '75%', 10 => '83%', 11 => '92%', 12 => '100%'] as $month => $expected) {
    $document = new DOMDocument();
    @$document->loadHTML('<?xml encoding="UTF-8">' . $renderer->render('gaji', null, [], $month));
    $xpath = new DOMXPath($document);
    foreach (['//tr[@data-monitoring-group]/td[4]', '//tr[contains(@class,"monitoring-budget-detail")]/td[4]'] as $query) {
        $cell = $xpath->query($query)->item(0);
        if (! $cell || trim($cell->textContent) !== $expected) throw new RuntimeException("Incorrect proportional percentage for month $month.");
    }
}
echo "Monitoring expand markup: passed\n";
