<?php
/** Checks that result-row formulas are engine-owned and only Oracle source adjustments are exposed. */
ob_start();
define('FCPATH', realpath(__DIR__ . '/../../public') . DIRECTORY_SEPARATOR);
define('ENVIRONMENT', 'development');
require __DIR__ . '/../../app/Config/Paths.php';
$paths = new Config\Paths();
require $paths->systemDirectory . '/Boot.php';
CodeIgniter\Boot::bootConsole($paths);

$db = db_connect(); $checks = 0;
$assert = static function (bool $condition, string $message) use (&$checks): void {
    if (!$condition) throw new RuntimeException($message);
    $checks++;
};

$defaults = App\Libraries\LrFormulaService::defaultRules();
$assert(count($defaults) === 9, 'The calculation engine must retain its nine audited result formulas.');
$assert(end($defaults)['target_label'] === 'LABA SEBELUM PAJAK',
    'The fixed engine order must calculate profit after every upstream subtotal.');
$assert(array_map([App\Libraries\LrFormulaService::class, 'columnScopeKey'], ['KUR', 'PEN', 'NON KUR', 'KBG/SURETYSHIP', 'KONSUMTIF', 'PRODUKTIF'])
    === ['kur', 'pen', 'non-kur', 'kbg-suretyship', 'konsumtif', 'produktif'],
    'Every displayed LOB must remain available for Oracle source adjustment.');

// Even a previously stored custom result formula must no longer change the calculation engine.
$db->transBegin();
try {
    (new App\Libraries\LrFormulaService())->save([
        'unit_scope' => 'surabaya', 'column_scope' => 'kur',
        'target_label' => 'JUMLAH BEBAN KLAIM', 'priority' => '205',
        'formula_lines' => '+ Beban Klaim', 'is_active' => '1',
    ], 'Locked Formula Test');
    $sample = ['matches' => [
        ['row' => 1, 'lob' => 'KUR', 'description' => 'uji beban klaim', 'report_label' => 'Beban Klaim', 'calculation_amount' => '10', 'source_amount' => '10'],
        ['row' => 2, 'lob' => 'KUR', 'description' => 'uji cadangan', 'report_label' => 'Kenaikan (Penurunan) Cadangan Klaim', 'calculation_amount' => '7', 'source_amount' => '7'],
        ['row' => 3, 'lob' => 'KUR', 'description' => 'uji subrogasi', 'report_label' => 'Pendapatan Subrogasi', 'calculation_amount' => '2', 'source_amount' => '2'],
        ['row' => 4, 'lob' => 'KUR', 'description' => 'uji komisi', 'report_label' => 'Beban Komisi Netto', 'calculation_amount' => '1', 'source_amount' => '1'],
    ], 'unmapped' => []];
    $calculated = (new App\Libraries\LrRealizationCalculator())->calculate(['Surabaya' => $sample])['Surabaya'];
    $claimKey = App\Libraries\OracleLrSalaryParser::normalizeLabel('JUMLAH BEBAN KLAIM');
    $assert(($calculated[$claimKey]['KUR'] ?? null) === '14.00',
        'Stored custom result formulas must be ignored; the audited engine formula must calculate the result.');
} finally {
    $db->transRollback();
}

$request = Config\Services::incomingrequest(new Config\App(), false);
Config\Services::injectMock('request', $request);
$controller = new App\Controllers\Akutansi();
$controller->initController($request, Config\Services::response(null, false), Config\Services::logger());
session()->set('auth_role', 'admin');
$html = $controller->formulaSettings();
$assert(is_string($html) && str_contains($html, 'Penyesuaian Sumber Oracle') && str_contains($html, 'Rumus baris hasil dihitung otomatis oleh mesin'),
    'The administrator page must explain that result formulas are automatic.');
$dom = new DOMDocument(); @$dom->loadHTML('<?xml encoding="UTF-8">' . $html); $xpath = new DOMXPath($dom);
$assert($xpath->query('//dialog[@id="formulaEditorDialog"]')->length === 0
    && $xpath->query('//button[@data-formula-edit]')->length === 0
    && $xpath->query('//form[contains(@action,"seting-rumus/simpan")]')->length === 0,
    'The page must not expose create, edit or delete controls for result-row formulas.');
$assert($xpath->query('//dialog[@id="sourceAdjustmentDialog"]')->length === 1
    && $xpath->query('//form[contains(@action,"seting-rumus/sumber/simpan")]')->length === 1
    && $xpath->query('//select[@data-source-adjustment-column]')->length === 1
    && $xpath->query('//input[@role="combobox" and @aria-controls="sourceDescriptionOptions"]')->length === 1
    && $xpath->query('//button[@data-source-description-toggle]')->length === 1
    && $xpath->query('//*[@data-source-description-menu]')->length === 1
    && $xpath->query('//*[@data-source-description-option]')->length > 0
    && $xpath->query('//button[@data-source-adjustment-add-description]')->length === 1
    && $xpath->query('//datalist')->length === 0
    && $xpath->query('//*[@id="pengajuan-sumber"]')->length === 1,
    'The Oracle source editor must expose LOB selection, a styled searchable dropdown, a separate Add Description action and the approval queue.');
session()->set('auth_role', 'akutansi');
$accountingIndex = $controller->index();
$assert(is_string($accountingIndex)
    && str_contains($accountingIndex, 'Laporan Laba / Rugi')
    && str_contains($accountingIndex, 'RKA Kanwil')
    && str_contains($accountingIndex, 'Penyesuaian Sumber Oracle')
    && !str_contains($accountingIndex, 'Menu Akutansi siap dikembangkan'),
    'The Accounting landing page must expose its three working submenus instead of an empty placeholder.');
$accountingIndexDom = new DOMDocument(); @$accountingIndexDom->loadHTML('<?xml encoding="UTF-8">' . $accountingIndex);
$accountingIndexXpath = new DOMXPath($accountingIndexDom);
$assert($accountingIndexXpath->query('//nav[contains(@class,"main-nav")]/a[contains(@class,"nav-link") and normalize-space(.//span[contains(@class,"nav-link-text")])="Dashboard"]')->length === 0,
    'The Accounting sidebar must not display the Dashboard menu.');
$accountingHtml = $controller->formulaSettings();
$assert(is_string($accountingHtml) && str_contains($accountingHtml, 'Penyesuaian Sumber Oracle')
    && str_contains($accountingHtml, 'AKUTANSI / PENGAJUAN PERUBAHAN'),
    'Accounting users must be able to open the Oracle source adjustment request page.');
$accountingDom = new DOMDocument(); @$accountingDom->loadHTML('<?xml encoding="UTF-8">' . $accountingHtml);
$accountingXpath = new DOMXPath($accountingDom);
$assert($accountingXpath->query('//a[contains(normalize-space(.),"Penyesuaian Sumber Oracle")]')->length >= 1
    && $accountingXpath->query('//button[@data-source-adjustment-create]')->length >= 1
    && $accountingXpath->query('//form[@data-source-adjustment-form and @data-approval-required="1"]')->length === 1
    && $accountingXpath->query('//form[@data-source-request-approve or @data-source-request-reject]')->length === 0,
    'Accounting users may submit changes but must not receive administrator approval controls.');
$assert($controller->approveSourceAdjustmentRequest() instanceof CodeIgniter\HTTP\RedirectResponse
    && $controller->rejectSourceAdjustmentRequest() instanceof CodeIgniter\HTTP\RedirectResponse,
    'Accounting users must be denied from approving or rejecting requests server-side.');
session()->set('auth_role', 'sdm');
$denied = $controller->formulaSettings();
$assert($denied instanceof CodeIgniter\HTTP\RedirectResponse, 'Unrelated roles must remain denied server-side.');
session()->remove('auth_role');

echo "LR source-only settings: {$checks} checks OK; accounting can submit changes, approval remains administrator-only, and the Oracle selector is searchable.\n";
