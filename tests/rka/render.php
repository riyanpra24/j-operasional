<?php
ob_start();
define('FCPATH', realpath(__DIR__ . '/../../public') . DIRECTORY_SEPARATOR);
define('ENVIRONMENT', 'development');
require __DIR__ . '/../../app/Config/Paths.php';
$paths = new Config\Paths(); require $paths->systemDirectory . '/Boot.php';
CodeIgniter\Boot::bootConsole($paths);
Config\Services::injectMock('request', Config\Services::incomingrequest(new Config\App(), false));
$calculator = new App\Libraries\RkaCalculator();
$data = [
    'title' => 'RKA Kanwil Surabaya', 'rkaUnits' => App\Libraries\RkaCalculator::UNITS,
    'schema' => App\Libraries\RkaCalculator::schema(), 'selectedUnit' => 'Surabaya', 'selectedYear' => 2026,
    'record' => null, 'inputs' => $calculator->zeros(), 'calculated' => null,
    'revision' => 0, 'revisions' => [], 'uploadError' => null, 'uploadAutoOpen' => false,
    'manualError' => null, 'rawInputs' => null,
];
foreach (['rka_manual', 'rka_kanwil_surabaya'] as $view) {
    $html = view('akutansi/' . $view, $data);
    $dom = new DOMDocument(); @$dom->loadHTML('<?xml encoding="UTF-8">' . $html); $xpath = new DOMXPath($dom);
    $count = $xpath->query('//input[@data-rka-input]')->length;
    if ($count !== 675) { throw new RuntimeException('Wrong editable field count: ' . $count); }
    foreach ($xpath->query('//table[contains(@class,"lr-rka-editable")]//input[@data-rka-input]') as $input) {
        $cell = $input->getAttribute('data-rka-input');
        $row = (int)substr($cell, 1);
        if ($cell[0] === 'H' || $data['schema']['rows'][$row]['terms'] !== null
            || isset($data['schema']['formulas'][$cell])) throw new RuntimeException('Formula result must never be a manual input: '.$cell);
    }
    foreach ($xpath->query('//table[contains(@class,"lr-rka-editable")]//span[@data-rka-output]') as $output) {
        $cell = $output->getAttribute('data-rka-output');
        $row = (int)substr($cell, 1);
        if ($cell[0] === 'H' || ($data['schema']['rows'][$row]['terms'] ?? null) !== null) {
            if ($output->parentNode->getAttribute('data-rka-calculated') !== 'true'
                || $output->getAttribute('contenteditable') !== 'false'
                || !str_contains($output->getAttribute('title'), 'Dihitung otomatis')) throw new RuntimeException('Automatic result must be clearly protected: '.$cell);
        }
    }
    foreach ($xpath->query('//span[@data-rka-output]') as $output) {
        if (trim($output->textContent) !== '-' || $output->getAttribute('data-rka-empty') !== 'true') { throw new RuntimeException('Empty RKA outputs must show a dark original dash.'); }
    }
    if ($xpath->query('//input[@data-rka-input and @type="text" and @placeholder="-"]')->length !== 670) { throw new RuntimeException('Empty manual inputs must have dash placeholders.'); }
    foreach ($xpath->query('//input[@data-rka-input]') as $input) {
        if (in_array($input->getAttribute('value'), ['-', '—'], true)) { throw new RuntimeException('Display dash must never become a monetary input value.'); }
    }
    if ($xpath->query('//input[@data-rka-input and @type="text"]')->length !== 670) { throw new RuntimeException('Investment title must have no editable inputs.'); }
    $tableCount = $view === 'rka_manual' ? 1 : 2;
    if ($xpath->query('//table[contains(@class,"lr-rka-table")]/thead/tr/th')->length !== 7 * $tableCount) { throw new RuntimeException('Wrong RKA columns.'); }
    if ($xpath->query('//tr[@data-lr-expandable]/td/span[@data-rka-group-output]')->length !== 30 * $tableCount) { throw new RuntimeException('Expandable groups need six hideable summary amounts each.'); }
    if ($xpath->query('//tr[contains(@class,"lr-rka-title-row")]/th[@colspan="7"]')->length !== $tableCount) { throw new RuntimeException('Investment header must span all columns.'); }
    if ($xpath->query('//dialog[@id="rkaManualDialog"]//form[@data-rka-manual-form]')->length !== 1) { throw new RuntimeException('Manual form must be inside popup.'); }
    if ($xpath->query('//select[@id="rkaManualUnit"]/option')->length !== count(App\Libraries\RkaCalculator::UNITS)) { throw new RuntimeException('Manual unit choices incomplete.'); }
    if ($xpath->query('//input[@id="rkaManualYear" and @min="2000" and @max="2100"]')->length !== 1) { throw new RuntimeException('Manual year choice missing.'); }
    if ($xpath->query('//div[@data-rka-input-stage and @hidden]')->length !== 1) { throw new RuntimeException('Manual input stage must start hidden.'); }
    if ($xpath->query('//div[@data-rka-existing-alert and @hidden and @role="alert"]')->length !== 1) { throw new RuntimeException('Existing RKA warning missing.'); }
    if ($view === 'rka_kanwil_surabaya') {
        if ($xpath->query('//select[@id="rkaUploadScope"]/option')->length !== 2) { throw new RuntimeException('Upload must offer single-unit and all-unit modes.'); }
        if ($xpath->query('//dialog[@id="rkaUploadDialog"]//a')->length !== 0) { throw new RuntimeException('Upload popup must not offer template downloads.'); }
        if ($xpath->query('//input[@name="rka_all_snapshots" and @type="hidden"]')->length !== 1) { throw new RuntimeException('All-unit revision protection missing.'); }
        if ($xpath->query('//div[@data-rka-upload-file-stage and @hidden]')->length !== 1) { throw new RuntimeException('Excel file selection must start hidden.'); }
        if ($xpath->query('//div[@data-rka-upload-existing-alert and @role="alert" and @hidden]')->length !== 1) { throw new RuntimeException('Excel existing RKA alert missing.'); }
        if ($xpath->query('//dialog[@id="rkaDeleteDialog"]//form[@data-rka-delete-form]//input[@name="confirm_delete" and @required]')->length !== 1) { throw new RuntimeException('RKA delete needs explicit confirmation.'); }
        if ($xpath->query('//button[@data-rka-delete-open]')->length !== 0) { throw new RuntimeException('No-data page must not offer active RKA deletion.'); }
    }
    $ids = [];
    foreach ($xpath->query('//*[@id]') as $element) { $id = $element->getAttribute('id'); if (isset($ids[$id])) { throw new RuntimeException('Duplicate ID: ' . $id); } $ids[$id] = true; }
    foreach ($xpath->query('//button[@data-lr-toggle]') as $button) {
        foreach (explode(' ', $button->getAttribute('aria-controls')) as $id) { if (! isset($ids[$id])) { throw new RuntimeException('Missing detail: ' . $id); } }
    }
    if (in_array($argv[1] ?? '', ['--preview', '--write-preview'], true) && $view === ($argv[2] ?? 'rka_kanwil_surabaya')) {
        // Preview contains only zero-valued test data; disable external form writes.
        $html = preg_replace('/(<form\b[^>]*\baction=")[^"]*(")/i', '$1#$2', $html);
        $html = str_replace(base_url(), '/', $html);
        if ($argv[1] === '--write-preview') {
            $directory = __DIR__ . '/../../tmp/rka_template/ui';
            if (!is_dir($directory)) { mkdir($directory, 0777, true); }
            file_put_contents($directory . '/' . $view . '.html', $html);
            echo 'Zero-data visual preview generated.' . PHP_EOL;
        } else { echo $html; }
        exit;
    }
    echo $view . ': render OK, ' . $count . ' editable fields, 7 template columns, unique IDs.' . PHP_EOL;
}
$activeData = $data;
$activeData['record'] = ['id' => 999, 'source_type' => 'manual', 'updated_at' => '2026-09-15 00:00:00'];
$activeData['revision'] = 2;
$activeHtml = view('akutansi/rka_kanwil_surabaya', $activeData);
$activeDom = new DOMDocument(); @$activeDom->loadHTML($activeHtml); $activeXpath = new DOMXPath($activeDom);
if ($activeXpath->query('//form[contains(@class,"lr-rka-filter")]/button[@type="submit"]/following-sibling::button[1][@data-rka-delete-open and @data-id="999" and @data-revision="2"]')->length !== 1) { throw new RuntimeException('Delete RKA must sit next to Apply in the filter.'); }
if ($activeXpath->query('//section[contains(@class,"lr-rka-heading")]//button[@data-rka-delete-open]')->length !== 0) { throw new RuntimeException('Delete RKA must not remain in the heading.'); }
foreach (['manual', 'excel'] as $sourceType) {
    $activeData['record']['source_type'] = $sourceType;
    $editHtml = view('akutansi/rka_kanwil_surabaya', $activeData);
    $editDom = new DOMDocument(); @$editDom->loadHTML($editHtml); $editXpath = new DOMXPath($editDom);
    if ($editXpath->query('//form[contains(@class,"lr-rka-filter")]/button[@data-rka-edit-open and @data-id="999"]')->length !== 1) { throw new RuntimeException('Edit button missing for ' . $sourceType . ' RKA.'); }
}
echo 'RKA delete placement: filter, beside Apply, exact record identity preserved.' . PHP_EOL;
$activeData['selectedUnit'] = 'Korporat Kanwil';
$activeData['record']['source_type'] = 'consolidated';
$corporateHtml = view('akutansi/rka_kanwil_surabaya', $activeData);
$corporateDom = new DOMDocument(); @$corporateDom->loadHTML($corporateHtml); $corporateXpath = new DOMXPath($corporateDom);
if ($corporateXpath->query('//form[contains(@class,"lr-rka-filter")]/button[@data-rka-edit-open or @data-rka-delete-open]')->length !== 0) throw new RuntimeException('Corporate derived output must not offer direct edits or deletion.');
foreach (['rkaManualUnit','rkaUploadUnit'] as $id) {
    if ($corporateXpath->query('//select[@id="'.$id.'"]/option[@value="Korporat Kanwil" and @disabled]')->length !== 1
        || $corporateXpath->query('//select[@id="'.$id.'"]/option[@value="Kanwil" and @selected]')->length !== 1) throw new RuntimeException('Corporate settings must default to an editable source unit.');
}
echo 'Corporate UI: source-unit settings, automatic aggregate note and protected actions OK.' . PHP_EOL;
$negativeData = $data;
$negativeData['inputs']['C11'] = '-5867662416.00';
$negativeData['calculated'] = $calculator->calculate($negativeData['inputs']);
$negativeHtml = view('akutansi/rka_kanwil_surabaya', $negativeData);
$negativeDom = new DOMDocument(); @$negativeDom->loadHTML($negativeHtml); $negativeXpath = new DOMXPath($negativeDom);
if ($negativeXpath->query('//input[@data-rka-input="C11" and @value="-5.867.662.416"]')->length !== 1
    || trim($negativeXpath->query('//span[@data-rka-output="C11"]')->item(0)->textContent) !== '*5.867.662.416'
    || trim($negativeXpath->query('//span[@data-rka-output="H166"]')->item(0)->textContent) !== '*5.867.662.416'
    || !str_contains($negativeHtml, 'Dalam Rupiah (Rp)')) throw new RuntimeException('Negative report notation changed editable money or missing currency unit.');
echo 'Rupiah notation: negative report markers, unchanged minus inputs and exact calculated values OK.' . PHP_EOL;
if ($negativeXpath->query('//span[@data-rka-output="C11"]/span[@class="lr-rka-negative-marker" and text()="*"]')->length !== 1
    || $negativeXpath->query('//span[@data-rka-output="H166"]/span[@class="lr-rka-negative-marker" and text()="*"]')->length !== 2) throw new RuntimeException('Negative asterisks must have a separate red marker in report and preview.');
