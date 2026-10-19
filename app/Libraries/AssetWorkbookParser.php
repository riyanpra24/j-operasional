<?php

namespace App\Libraries;

use RuntimeException;
use SimpleXMLElement;
use ZipArchive;

/** Reads the supplied grouped Data Aset template without importing its totals. */
final class AssetWorkbookParser
{
    private const NS = 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';
    private const REL = 'http://schemas.openxmlformats.org/officeDocument/2006/relationships';
    private const HEADERS = ['Kelompok aset', 'Kodefikasi aset', 'Nomor aset', 'Nama aset', 'Jumlah Asset', 'Status asset', 'Tanggal perolehan', 'Tanggal Habis Masa Manfaat', 'Masa manfaat (dalam bulan)', 'Sisa Masa manfaat (dalam bulan)', 'Lokasi'];

    public function parse(string $path, array $groups): array
    {
        $zip = new ZipArchive();
        if ($zip->open($path) !== true) { throw new RuntimeException('Berkas Excel tidak dapat dibuka.'); }
        try {
            $size = 0;
            if ($zip->numFiles > 300) { throw new RuntimeException('Struktur Excel terlalu besar.'); }
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $entry = $zip->statIndex($i);
                $size += (int) ($entry['size'] ?? 0);
                if ($size > 15 * 1024 * 1024 || preg_match('~(?:vbaProject|externalLinks/)~i', (string) ($entry['name'] ?? ''))) {
                    throw new RuntimeException('Excel terlalu besar atau berisi makro/tautan eksternal.');
                }
            }
            $workbook = $this->xml($zip, 'xl/workbook.xml');
            $workbook->registerXPathNamespace('s', self::NS);
            $sheets = $workbook->xpath('//s:sheets/s:sheet') ?: [];
            if (count($sheets) !== 1) { throw new RuntimeException('Gunakan template dengan satu sheet Data Aset.'); }
            $id = (string) $sheets[0]->attributes(self::REL)['id'];
            $path = null;
            foreach ($this->xml($zip, 'xl/_rels/workbook.xml.rels')->children() as $rel) {
                if ((string) $rel['Id'] === $id && (string) $rel['TargetMode'] !== 'External') {
                    $target = ltrim((string) $rel['Target'], '/');
                    if (preg_match('~^(?:xl/)?worksheets/sheet\d+\.xml$~', $target)) { $path = str_starts_with($target, 'xl/') ? $target : 'xl/' . $target; }
                }
            }
            if ($path === null) { throw new RuntimeException('Sheet Excel tidak valid.'); }
            $props = $workbook->xpath('//s:workbookPr') ?: [];
            $date1904 = isset($props[0]) && in_array((string) $props[0]['date1904'], ['1', 'true'], true);
            $strings = [];
            if ($zip->locateName('xl/sharedStrings.xml') !== false) {
                $shared = $this->xml($zip, 'xl/sharedStrings.xml');
                $shared->registerXPathNamespace('s', self::NS);
                foreach ($shared->xpath('//s:si') ?: [] as $item) {
                    $item->registerXPathNamespace('s', self::NS);
                    $strings[] = implode('', array_map(static fn ($part) => (string) $part, $item->xpath('.//s:t') ?: []));
                }
            }
            $sheet = $this->xml($zip, $path);
            $sheet->registerXPathNamespace('s', self::NS);
            $rows = $sheet->xpath('//s:sheetData/s:row') ?: [];
            if (count($rows) > 10000) { throw new RuntimeException('Maksimal 10.000 baris per file.'); }
            $records = []; $category = null; $headerFound = false; $seen = [];
            foreach ($rows as $row) {
                $number = (int) $row['r'];
                $row->registerXPathNamespace('s', self::NS);
                $cells = []; $formulas = [];
                foreach ($row->xpath('./s:c') ?: [] as $cell) {
                    $col = preg_replace('/\d+/', '', (string) $cell['r']);
                    $raw = (string) $cell->v; $type = (string) $cell['t'];
                    if ($type === 'inlineStr') { $cell->registerXPathNamespace('s', self::NS); $raw = implode('', array_map(static fn ($part) => (string) $part, $cell->xpath('./s:is//s:t') ?: [])); }
                    elseif ($type === 's') { $raw = $strings[(int) $raw] ?? ''; }
                    $cells[$col] = trim($raw);
                    if (isset($cell->f)) { $formulas[$col] = true; }
                }
                if (! $headerFound) {
                    $header = []; foreach (range('A', 'K') as $col) { $header[] = mb_strtolower($cells[$col] ?? ''); }
                    if ($header === array_map('mb_strtolower', self::HEADERS)) { $headerFound = true; }
                    continue;
                }
                $a = $cells['A'] ?? ''; $code = $cells['B'] ?? ''; $assetNo = $cells['C'] ?? '';
                if ($a === '' && $code === '' && $assetNo === '' && implode('', array_intersect_key($cells, array_flip(['D', 'F', 'G', 'H', 'K']))) === '') { continue; }
                if ($code === '' && $assetNo === '' && preg_match('/^jumlah\s+as(?:s)?et\b/i', $a)) { continue; }
                if ($a !== '' && $code === '' && $assetNo === '' && in_array($a, $groups, true)) { $category = $a; continue; }
                if ($category === null) { throw new RuntimeException("Baris $number: kategori aset belum dikenali."); }
                foreach (['A', 'B', 'C', 'D', 'F', 'G', 'H', 'K'] as $col) {
                    if (isset($formulas[$col])) { throw new RuntimeException("Baris $number: rumus tidak didukung pada kolom data aset."); }
                }
                foreach (['A' => 255, 'B' => 100, 'C' => 100, 'D' => 150, 'F' => 100] as $col => $limit) {
                    if (($cells[$col] ?? '') === '' || mb_strlen($cells[$col]) > $limit) { throw new RuntimeException("Baris $number: kolom $col wajib diisi dan tidak boleh melebihi $limit karakter."); }
                }
                if (mb_strlen($cells['K'] ?? '') > 255) { throw new RuntimeException("Baris $number: lokasi terlalu panjang."); }
                $key = mb_strtolower($assetNo);
                if (isset($seen[$key])) { throw new RuntimeException("Baris $number: nomor aset $assetNo berulang dalam file."); }
                $seen[$key] = true;
                $start = $this->date($cells['G'] ?? '', $date1904, $number);
                $end = $this->date($cells['H'] ?? '', $date1904, $number);
                if ($start !== null && $end !== null && $end < $start) { throw new RuntimeException("Baris $number: tanggal habis manfaat sebelum tanggal perolehan."); }
                $records[] = ['kategori' => $category, 'kelompok_aset' => $a, 'kodefikasi_aset' => $code, 'nomor_aset' => $assetNo, 'nama_aset' => $cells['D'], 'status_aset' => $cells['F'], 'tanggal_perolehan' => $start, 'tanggal_habis_manfaat' => $end, 'masa_manfaat' => AssetPeriod::months($start, $end), 'sisa_masa_manfaat' => AssetPeriod::remaining($end), 'lokasi' => ($cells['K'] ?? '') ?: null];
            }
            if (! $headerFound) { throw new RuntimeException('Judul kolom tidak sesuai template Data Aset.'); }
            if ($records === []) { throw new RuntimeException('Tidak ada data aset dalam file.'); }
            return $records;
        } finally { $zip->close(); }
    }

    private function date(string $value, bool $date1904, int $row): ?string
    {
        if ($value === '') { return null; }
        if (is_numeric($value) && (float) $value >= 0 && (float) $value <= 2958465) {
            $days = (int) floor((float) $value);
            if (! $date1904 && $days === 60) { throw new RuntimeException("Baris $row: tanggal Excel tidak valid."); }
            $base = new \DateTimeImmutable($date1904 ? '1904-01-01' : ($days < 60 ? '1899-12-31' : '1899-12-30'));
            $value = $base->modify('+' . $days . ' days')->format('Y-m-d');
        }
        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        if ($date === false || $date->format('Y-m-d') !== $value) { throw new RuntimeException("Baris $row: tanggal harus berupa tanggal Excel yang valid."); }
        return $value;
    }

    private function xml(ZipArchive $zip, string $path): SimpleXMLElement
    {
        $content = $zip->getFromName($path);
        if ($content === false || stripos($content, '<!DOCTYPE') !== false) { throw new RuntimeException('Struktur Excel tidak valid.'); }
        $xml = @simplexml_load_string($content, SimpleXMLElement::class, LIBXML_NONET);
        if (! $xml instanceof SimpleXMLElement) { throw new RuntimeException('Struktur Excel tidak valid.'); }
        return $xml;
    }
}
