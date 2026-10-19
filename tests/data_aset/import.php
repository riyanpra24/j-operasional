<?php

require __DIR__ . '/../../app/Libraries/AssetPeriod.php';
require __DIR__ . '/../../app/Libraries/AssetWorkbookParser.php';

use App\Libraries\AssetWorkbookParser;

function workbook(string $body): string
{
    $path = tempnam(__DIR__, 'asset-xlsx-');
    $zip = new ZipArchive();
    $zip->open($path, ZipArchive::OVERWRITE);
    $zip->addFromString('xl/workbook.xml', '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="Sheet1" sheetId="1" r:id="rId1"/></sheets></workbook>');
    $zip->addFromString('xl/_rels/workbook.xml.rels', '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Target="worksheets/sheet1.xml"/></Relationships>');
    $headers = ['Kelompok aset', 'Kodefikasi aset', 'Nomor aset', 'Nama aset', 'Jumlah Asset', 'Status asset', 'Tanggal perolehan', 'Tanggal Habis Masa Manfaat', 'Masa manfaat (dalam bulan)', 'Sisa Masa manfaat (dalam bulan)', 'Lokasi'];
    $header = ''; foreach ($headers as $i => $value) { $header .= cell(chr(65 + $i), 8, $value); }
    $zip->addFromString('xl/worksheets/sheet1.xml', '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData><row r="8">' . $header . '</row>' . $body . '</sheetData></worksheet>');
    $zip->close();
    return $path;
}
function cell(string $col, int $row, string $value): string
{
    return '<c r="' . $col . $row . '" t="inlineStr"><is><t>' . htmlspecialchars($value, ENT_XML1) . '</t></is></c>';
}
function assetRow(int $row, string $number, string $end = '2027-01-01'): string
{
    $values = ['A' => 'Gedung Kantor', 'B' => 'KODE', 'C' => $number, 'D' => 'Gedung', 'F' => 'Sedang Digunakan', 'G' => '1997-01-01', 'H' => $end];
    $xml = ''; foreach ($values as $col => $value) { $xml .= cell($col, $row, $value); }
    return '<row r="' . $row . '">' . $xml . '</row>';
}
$group = '<row r="9">' . cell('A', 9, 'Gedung') . '</row>';
$total = '<row r="11">' . cell('A', 11, 'Jumlah asset Gedung') . '</row>';
$parser = new AssetWorkbookParser();
$file = workbook($group . assetRow(10, '001') . $total);
try {
    $rows = $parser->parse($file, ['Gedung']);
    if (count($rows) !== 1 || $rows[0]['nomor_aset'] !== '001' || $rows[0]['masa_manfaat'] !== 360 || $rows[0]['kategori'] !== 'Gedung') { throw new RuntimeException('Incorrect import data.'); }
} finally { unlink($file); }
foreach ([$group . assetRow(10, '001') . assetRow(12, '001'), $group . assetRow(10, '001', '1996-01-01')] as $invalid) {
    $file = workbook($invalid); $failed = false;
    try { $parser->parse($file, ['Gedung']); } catch (RuntimeException $e) { $failed = true; } finally { unlink($file); }
    if (! $failed) { throw new RuntimeException('Invalid workbook accepted.'); }
}
echo "Data Aset Excel import parser: passed\n";
