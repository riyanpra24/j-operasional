<?php

require __DIR__ . '/../../app/Libraries/RkaMoney.php';
require __DIR__ . '/../../app/Libraries/RkaCalculator.php';
require __DIR__ . '/../../app/Libraries/LrMoney.php';
require __DIR__ . '/../../app/Libraries/OracleLrSalaryParser.php';
require __DIR__ . '/../../app/Libraries/MonitoringAnggaran.php';

function esc($value, $context = null): string { return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function site_url(string $path): string { return '/' . $path; }
function csrf_field(): string { return '<input type="hidden" name="csrf_test" value="token">'; }

$renderer = new class {
    public function extend($layout): string { return ''; }
    public function section($name): string { return ''; }
    public function endSection(): string { return ''; }
    public function render(string $search, ?array $rka = null, array $realizations = [], int $selectedMonth = 1, array $monitoringNotes = [], string $deviationFilter = 'all'): string
    {
        $monitoringRows = (new App\Libraries\MonitoringAnggaran())->rows($rka, $search, $realizations);
        if ($deviationFilter !== 'all') $monitoringRows = App\Libraries\MonitoringAnggaran::filterDeviationRows($monitoringRows, $selectedMonth, $deviationFilter);
        $months = []; $units = ['Konsolidasi']; $selectedUnit = 'Konsolidasi';
        $selectedYear = 2026; $hasRka = false;
        $selectedDeviationFilter = $deviationFilter;
        ob_start();
        include __DIR__ . '/../../app/Views/akutansi/monitoring_anggaran.php';
        return ob_get_clean();
    }
};

$sampleDeviationRows = [
    ['key' => 'group', 'label' => 'Kelompok', 'group' => true, 'realization_ratio' => '0.70'],
    ['key' => 'normal', 'label' => 'Normal', 'group' => false, 'realization_ratio' => '0.70'],
    ['key' => 'negative', 'label' => 'Minus', 'group' => false, 'realization_ratio' => '0.80'],
];
foreach (['normal' => ['group', 'normal'], 'negative' => ['group', 'negative']] as $filter => $expectedKeys) {
    $keys = array_column(App\Libraries\MonitoringAnggaran::filterDeviationRows($sampleDeviationRows, 9, $filter), 'key');
    if ($keys !== $expectedKeys) throw new RuntimeException("Deviation $filter filter must retain only matching rows and their group context.");
}

foreach (['' => [3, 125], 'gaji' => [1, 1], 'tidak ada uraian ini' => [0, 0]] as $search => [$groupCount, $detailCount]) {
    $document = new DOMDocument();
    @$document->loadHTML('<?xml encoding="UTF-8">' . $renderer->render($search));
    $xpath = new DOMXPath($document);
    $buttons = $xpath->query('//button[@data-monitoring-toggle and not(ancestor::dialog)]');
    $details = $xpath->query('//tr[contains(@class,"monitoring-budget-detail")]');
    if ($buttons->length !== $groupCount || $details->length !== $detailCount) throw new RuntimeException('Unexpected groups or details.');
    $popupButtons = $xpath->query('//dialog//button[@data-monitoring-toggle]');
    $popupDetails = $xpath->query('//dialog//tr[contains(@class,"monitoring-edit-detail")]');
    if ($popupButtons->length !== $groupCount || $popupDetails->length !== $detailCount) throw new RuntimeException('Unexpected popup groups or details.');
    foreach ([$buttons, $popupButtons] as $buttonSet) foreach ($buttonSet as $button) {
        $expectedExpanded = $search === '' ? 'false' : 'true';
        if ($button->getAttribute('aria-expanded') !== $expectedExpanded) throw new RuntimeException('Incorrect initial expansion state.');
        foreach (explode(' ', $button->getAttribute('aria-controls')) as $id) {
            $detail = $document->getElementById($id);
            if (! $detail || $detail->hasAttribute('hidden') !== ($search === '')) throw new RuntimeException('Broken group/detail association.');
        }
    }
    if ($xpath->query('//dialog//textarea[starts-with(@name,"notes[")]')->length !== $groupCount + $detailCount || $xpath->query('//dialog//textarea[starts-with(@name,"original[")]')->length !== $groupCount + $detailCount) throw new RuntimeException('Collapsed rows must retain their editable and original notes.');
}
$document = new DOMDocument();
@$document->loadHTML('<?xml encoding="UTF-8">' . $renderer->render('', null, [], 9, [], 'negative'));
$xpath = new DOMXPath($document);
if ($xpath->query('//select[@name="deviasi"]/option[@value="all"]')->length !== 1
    || $xpath->query('//select[@name="deviasi"]/option[@value="normal"]')->length !== 1
    || $xpath->query('//select[@name="deviasi"]/option[@value="negative" and @selected]')->length !== 1) {
    throw new RuntimeException('The monitoring filter must offer and preserve deviation choices.');
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
if ($xpath->query('//button[@data-monitoring-edit-open]')->length !== 1 || $xpath->query('//dialog[@id="monitoringEditDialog"]')->length !== 1) throw new RuntimeException('Monitoring edit action or dialog missing.');
if ($xpath->query('//table[contains(@class,"monitoring-budget-table") and not(contains(@class,"monitoring-edit-table"))]/colgroup/col[@class="monitoring-note-column"]')->length !== 1) throw new RuntimeException('The monitoring table must reserve a separate explanation column.');
if ($xpath->query('//button[@data-monitoring-export-open and @aria-controls="monitoringExportDialog"]')->length !== 1) throw new RuntimeException('The export menu must open a period dialog.');
if ($xpath->query('//dialog[@id="monitoringExportDialog"]//form[@method="get" and contains(@action,"monitoring-anggaran/export")]')->length !== 1) throw new RuntimeException('The export dialog must submit to the export endpoint.');
if ($xpath->query('//dialog[@id="monitoringExportDialog"]//select[@name="bulan"]/option')->length !== 12 || $xpath->query('//dialog[@id="monitoringExportDialog"]//select[@name="bulan"]/option[@value="9" and @selected]')->length !== 1) throw new RuntimeException('The export month must default to the selected month.');
if ($xpath->query('//dialog[@id="monitoringExportDialog"]//select[@name="dokumen_export"]/option[@value="all"]')->length !== 1 || $xpath->query('//dialog[@id="monitoringExportDialog"]//select[@name="dokumen_export"]/option[@value="negative_deviation"]')->length !== 1) throw new RuntimeException('The export dialog must offer All Monitoring and Deviasi Minus documents.');
if ($xpath->query('//dialog[@id="monitoringExportDialog"]//input[@name="tahun" and @value="2026" and @min="2000" and @max="2100" and @required]')->length !== 1) throw new RuntimeException('The export year must default to the selected year and be validated.');
if ($xpath->query('//dialog[@id="monitoringExportDialog"]//input[@name="unit" and @value="Konsolidasi"]')->length !== 1 || $xpath->query('//dialog[@id="monitoringExportDialog"]//input[@name="uraian" and @value="gaji"]')->length !== 1) throw new RuntimeException('The export form must preserve the current unit and search.');
if (trim($xpath->query('//dialog//p[@class="monitoring-edit-context"]')->item(0)->textContent) !== 'Unit kerja: Konsolidasi · Periode: September 2026') throw new RuntimeException('The editor must display the selected unit and period.');
if ($xpath->query('//dialog//table[contains(@class,"monitoring-edit-table")]/colgroup/col')->length !== 8) throw new RuntimeException('The editor must define a width for every column.');
if ($xpath->query('//dialog[@id="monitoringEditDialog"]//textarea[starts-with(@name,"notes[")]')->length !== 2 || $xpath->query('//dialog[@id="monitoringEditDialog"]//input[not(@type="hidden")]')->length !== 0) throw new RuntimeException('Only explanations must be editable in the dialog.');
if (trim($xpath->query('//tr[contains(@class,"monitoring-budget-detail")]/td[5]')->item(0)->textContent) !== '-18%') throw new RuntimeException('Detail deviation is not rendered correctly.');
if (trim($xpath->query('//tr[contains(@class,"monitoring-budget-detail")]/td[6]')->item(0)->textContent) !== '7') throw new RuntimeException('Detail remaining budget is not rendered correctly.');
if (trim($xpath->query('//tr[@data-monitoring-group]/td[6]')->item(0)->textContent) !== '—') throw new RuntimeException('Incomplete group must not show remaining budget.');
if ($xpath->query('//tr[contains(@class,"monitoring-budget-detail")]/td[5]/span[@class="monitoring-negative-value"]')->length !== 1) throw new RuntimeException('Negative deviation must be marked red.');
if (trim($xpath->query('//tr[@data-monitoring-group]/td[5]')->item(0)->textContent) !== '—') throw new RuntimeException('Incomplete group must not show a deviation.');
$inputs = (new App\Libraries\RkaCalculator())->zeros();
$inputs['C29'] = '100.00';
$rka = (new App\Libraries\RkaCalculator())->calculate($inputs);
$realizations = [];
foreach ((new App\Libraries\MonitoringAnggaran())->rows($rka) as $row) {
    if ($row['group']) continue;
    $label = match ($row['key']) { 'row_57'=>'Imbal jasa profesional - konsultan manajemen', 'row_71'=>'Transportasi dinas dalam negeri', 'row_74'=>'Transportasi dinas luar negeri', default=>$row['label'] };
    $realizations[App\Libraries\OracleLrSalaryParser::normalizeLabel($label)] = ['TOTAL' => '0.00'];
}
$realizations['beban gaji karyawan']['TOTAL'] = '93.00';
$document = new DOMDocument();
$overspentHtml = $renderer->render('gaji', $rka, $realizations, 9);
if (!str_contains($overspentHtml, 'td:has(> .monitoring-negative-value)') || !str_contains($overspentHtml, 'background-color: #fee8e8') || !str_contains($overspentHtml, 'color: #991b1b')) throw new RuntimeException('Negative values must have a readable pastel-red cell style.');
@$document->loadHTML('<?xml encoding="UTF-8">' . $overspentHtml);
$xpath = new DOMXPath($document);
if (trim($xpath->query('//tr[@data-monitoring-group]/td[5]')->item(0)->textContent) !== '-18%') throw new RuntimeException('Group deviation is not based on the full group totals.');
if ($xpath->query('//tr[@data-monitoring-group and not(ancestor::dialog)]/td[5]/span[@class="monitoring-negative-value"]')->length !== 1) throw new RuntimeException('Negative group deviation must be marked red.');
if (trim($xpath->query('//tr[@data-monitoring-group]/td[6]')->item(0)->textContent) !== '7') throw new RuntimeException('Group remaining budget must use full group totals.');
$document = new DOMDocument();
@$document->loadHTML('<?xml encoding="UTF-8">' . $renderer->render('gaji', $rka, $realizations, 9, ['row_29' => '<b>Catatan</b>']));
$xpath = new DOMXPath($document);
if (trim($xpath->query('//tr[contains(@class,"monitoring-budget-detail")]/td[7]')->item(0)->textContent) !== '<b>Catatan</b>' || $xpath->query('//tr[contains(@class,"monitoring-budget-detail")]/td[7]/b')->length !== 0) throw new RuntimeException('Saved notes must display as escaped text.');
if ($xpath->query('//dialog//textarea[@name="notes[row_29]"]')->item(0)->textContent !== '<b>Catatan</b>') throw new RuntimeException('Saved notes must populate the editor.');
$realizations['beban gaji karyawan']['TOTAL'] = '110.00';
$document = new DOMDocument();
@$document->loadHTML('<?xml encoding="UTF-8">' . $renderer->render('gaji', $rka, $realizations, 9));
$xpath = new DOMXPath($document);
foreach (['//tr[@data-monitoring-group]/td[6]', '//tr[contains(@class,"monitoring-budget-detail")]/td[6]'] as $query) {
    $cell = $xpath->query($query)->item(0);
    if (trim($cell->textContent) !== '-10' || $xpath->query('./span[@class="monitoring-negative-value"]', $cell)->length !== 1) throw new RuntimeException('Overspent remaining budget must be marked red.');
}
if ($xpath->query('//dialog//tr[contains(@class,"monitoring-edit-detail")]/td[6]/span[@class="monitoring-negative-value"]')->length !== 1) throw new RuntimeException('The edit dialog must also mark negative values.');
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
