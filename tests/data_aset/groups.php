<?php

function esc($value, $context = null): string { return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function site_url(string $path): string { return '/' . $path; }
function view(string $path, array $data): string { return ''; }

$renderer = new class {
    public function extend($layout): string { return ''; }
    public function section($name): string { return ''; }
    public function endSection(): string { return ''; }
    public function render(string $search, bool $empty = false): string
    {
        $filters = ['q' => $search, 'kelompok' => '', 'status' => '', 'dari' => '', 'sampai' => '', 'perPage' => 10];
        $assetGroups = ['Gedung', 'Kendaraan']; $assetStatuses = []; $pagination = '';
        $record = ['id' => 1, 'kelompok_aset' => 'Gedung Kantor', 'kodefikasi_aset' => 'A', 'nomor_aset' => '1', 'nama_aset' => 'Gedung', 'status_aset' => 'Berfungsi', 'tanggal_perolehan' => '1997-01-01', 'tanggal_habis_manfaat' => '2027-01-01', 'masa_manfaat' => 360, 'sisa_masa_manfaat' => 0, 'lokasi' => null, 'foto_aset' => 'https://example.com/foto.jpg'];
        $groups = $empty ? [] : ['Gedung' => [$record], 'Kendaraan' => [array_replace($record, ['id' => 2, 'tanggal_perolehan' => null, 'tanggal_habis_manfaat' => null, 'sisa_masa_manfaat' => null, 'foto_aset' => 'javascript:alert(1)'])]];
        ob_start(); include __DIR__ . '/../../app/Views/data_aset/index.php'; return ob_get_clean();
    }
};
foreach (['', 'gedung'] as $search) {
    $document = new DOMDocument(); @$document->loadHTML('<?xml encoding="UTF-8">' . $renderer->render($search));
    $xpath = new DOMXPath($document);
    if ($xpath->query('//tr[@class="asset-group"]/th')->length !== 2) throw new RuntimeException('Group headings missing.');
    if ($xpath->query('//tr[@class="asset-detail" and not(@hidden)]')->length !== 2) throw new RuntimeException('Asset details must be visible.');
    if ($xpath->query('//button[@data-asset-toggle]')->length !== 0) throw new RuntimeException('Expand control must be removed.');
    if ($xpath->query('//details[contains(@class,"lr-filter-menu")]//a[contains(@href,"data-aset/tambah")]')->length !== 1) throw new RuntimeException('Add asset action missing from filter menu.');
    if ($xpath->query('//select[@name="per_page" and @data-table-length]/option')->length !== 4 || $xpath->query('//form[contains(@class,"table-length-form")]//input[@name="q"]')->length !== 1 || $xpath->query('//form[contains(@class,"filter-form")]//input[@name="per_page"]')->length !== 1) throw new RuntimeException('Asset page size controls missing.');
    if ($xpath->query('//thead/tr/th')->length !== 8 || $xpath->query('//th[text()="Periode Manfaat"]')->length !== 1 || $xpath->query('//th[text()="Foto Aset"]')->length !== 0 || $xpath->query('//th[contains(text(),"Masa manfaat (dalam bulan)")]')->length !== 0) throw new RuntimeException('Asset columns not configured correctly.');
    if ($xpath->query('//tr[@class="asset-detail"]/td')->length !== 16 || $xpath->query('//tr[@class="asset-group"]/th[@colspan="8"]')->length !== 2) throw new RuntimeException('Table column counts mismatch.');
    if ($xpath->query('//a[@data-asset-create]')->length !== 1 || $xpath->query('//a[@data-asset-view]')->length !== 2 || $xpath->query('//a[@data-asset-edit]')->length !== 2) throw new RuntimeException('Asset popup triggers missing.');
    $periods = $xpath->query('//div[contains(@class,"asset-period")]');
    if (! str_contains($periods[0]->textContent, '01-01-2027') || ! str_contains($periods[0]->textContent, '01-01-1997 s.d.') || ! str_contains($periods[0]->textContent, 'Sisa 0 bulan')) throw new RuntimeException('Incorrect period details.');
    if ($xpath->query('.//span[contains(@class,"pks-expiry-countdown")]', $periods[1])->length !== 0) throw new RuntimeException('Unknown remaining duration must not appear as zero.');
}
if (! str_contains($renderer->render('', true), 'Data aset tidak ditemukan')) throw new RuntimeException('Empty state missing.');
echo "Data Aset group markup: passed\n";
