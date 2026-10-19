<?php
function esc($value, $context = null): string { return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function site_url(string $path): string { return '/' . $path; }
function base_url(string $path): string { return '/' . $path; }
function csrf_field(): string { return '<input type="hidden" name="csrf_test" value="token">'; }
function csrf_token(): string { return 'csrf_test'; }
function session(): object { return new class { public function get($key): string { return 'admin'; } }; }
$groups = ['Gedung' => [['id' => 1, 'nama_aset' => '</script><script>alert(1)</script>']]];
$assetGroups = ['Gedung']; $popupState = null;
ob_start(); include __DIR__ . '/../../app/Views/data_aset/modals.php'; $html = ob_get_clean();
$document = new DOMDocument(); @$document->loadHTML('<?xml encoding="UTF-8">' . $html); $xpath = new DOMXPath($document);
if ($xpath->query('//section[@role="dialog" and @aria-modal="true"]')->length !== 2) throw new RuntimeException('Popup dialogs missing.');
if ($xpath->query('//form[@id="assetModalForm"]//*[@name="masa_manfaat" and @readonly]')->length !== 1 || $xpath->query('//form[@id="assetModalForm"]//*[@name="sisa_masa_manfaat" and @readonly]')->length !== 1) throw new RuntimeException('Asset periods must be calculated automatically.');
if ($xpath->query('//form[@id="assetModalForm"]//input[@name="foto_aset" and @type="url"]')->length !== 1) throw new RuntimeException('Asset photo link field missing.');
if ($xpath->query('//div[@id="assetDetailModal"]//*[@data-asset-detail="foto_aset"]')->length !== 1) throw new RuntimeException('Asset photo link missing from details.');
if (str_contains($html, '</script><script>alert(1)</script>')) throw new RuntimeException('Record JSON is not safely escaped.');
echo "Data Aset popup markup: passed\n";
