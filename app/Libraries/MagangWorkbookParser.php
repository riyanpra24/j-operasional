<?php

namespace App\Libraries;

use RuntimeException;
use SimpleXMLElement;
use ZipArchive;

final class MagangWorkbookParser
{
    private const MAIN_NS = 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';
    private const REL_NS = 'http://schemas.openxmlformats.org/officeDocument/2006/relationships';
    private const PACKAGE_REL_NS = 'http://schemas.openxmlformats.org/package/2006/relationships';

    /** @return list<array<string, string|int|null>> */
    public function parse(string $path): array
    {
        $zip = new ZipArchive();
        if ($zip->open($path) !== true) {
            throw new RuntimeException('Berkas Excel tidak dapat dibuka.');
        }

        try {
            $this->validateArchive($zip);
            $sheetPath = $this->databaseSheetPath($zip);
            $rows = $this->xml($zip, $sheetPath);
            $rows->registerXPathNamespace('s', self::MAIN_NS);
            $strings = $this->sharedStrings($zip);
            $records = [];

            foreach ($rows->xpath('//s:sheetData/s:row') ?: [] as $row) {
                $number = (int) $row['r'];
                if ($number < 6) {
                    continue;
                }
                $cells = $this->rowCells($row, $strings);
                $name = $this->clean($cells['B']['value'] ?? null);
                if ($name === null) {
                    continue;
                }
                $contract = $this->clean($cells['C']['value'] ?? null);
                $unit = $this->clean($cells['D']['value'] ?? null);
                $type = $this->clean($cells['E']['value'] ?? null);
                if ($contract === null || $unit === null || $type === null) {
                    throw new RuntimeException('Baris ' . $number . ' belum memiliki nomor kontrak, unit kerja, atau jenis magang.');
                }

                $records[] = [
                    'nomor' => $this->numeric($cells['A']['value'] ?? null),
                    'nama_magang' => $name,
                    'nomor_kontrak_kerja' => $contract,
                    'unit_kerja' => $unit,
                    'jenis_magang' => $type,
                    'tanggal_mulai' => $this->excelDate($cells['F']['value'] ?? null),
                    'tanggal_selesai' => $this->excelDate($cells['G']['value'] ?? null),
                    'link_pkk' => $this->hyperlink($cells['I']['formula'] ?? null),
                    'keterangan' => $this->clean($cells['J']['value'] ?? null),
                ];
            }

            if ($records === []) {
                throw new RuntimeException('Sheet Database Magang tidak memiliki data peserta.');
            }

            return $records;
        } finally {
            $zip->close();
        }
    }

    private function validateArchive(ZipArchive $zip): void
    {
        if ($zip->numFiles > 300) {
            throw new RuntimeException('Struktur Excel terlalu besar.');
        }
        $size = 0;
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $entry = $zip->statIndex($i);
            $size += (int) ($entry['size'] ?? 0);
            if ($size > 15 * 1024 * 1024 || preg_match('~(?:vbaProject|externalLinks/)~i', (string) ($entry['name'] ?? ''))) {
                throw new RuntimeException('Excel terlalu besar atau menggunakan makro/tautan eksternal yang tidak didukung.');
            }
        }
    }

    private function databaseSheetPath(ZipArchive $zip): string
    {
        $workbook = $this->xml($zip, 'xl/workbook.xml');
        $workbook->registerXPathNamespace('s', self::MAIN_NS);
        $workbook->registerXPathNamespace('r', self::REL_NS);
        $targetId = null;
        foreach ($workbook->xpath('//s:sheets/s:sheet') ?: [] as $sheet) {
            if (mb_strtolower(trim((string) $sheet['name'])) === 'database magang') {
                $targetId = (string) $sheet->attributes(self::REL_NS)['id'];
                break;
            }
        }
        if ($targetId === null || $targetId === '') {
            throw new RuntimeException('Sheet bernama Database Magang tidak ditemukan.');
        }

        $relationships = $this->xml($zip, 'xl/_rels/workbook.xml.rels');
        foreach ($relationships->children() as $relationship) {
            if ((string) $relationship['Id'] !== $targetId) {
                continue;
            }
            $target = ltrim((string) $relationship['Target'], '/');
            if (! preg_match('~^worksheets/sheet\d+\.xml$~', $target)) {
                break;
            }

            return 'xl/' . $target;
        }

        throw new RuntimeException('Lokasi sheet Database Magang tidak valid.');
    }

    /** @return list<string> */
    private function sharedStrings(ZipArchive $zip): array
    {
        if ($zip->locateName('xl/sharedStrings.xml') === false) {
            return [];
        }
        $xml = $this->xml($zip, 'xl/sharedStrings.xml');
        $xml->registerXPathNamespace('s', self::MAIN_NS);

        $strings = [];
        foreach ($xml->xpath('//s:si') ?: [] as $item) {
            $item->registerXPathNamespace('s', self::MAIN_NS);
            $parts = $item->xpath('.//s:t') ?: [];
            $strings[] = implode('', array_map(static fn (SimpleXMLElement $part): string => (string) $part, $parts));
        }

        return $strings;
    }

    /** @return array<string, array{value: string|null, formula: string|null}> */
    private function rowCells(SimpleXMLElement $row, array $strings): array
    {
        $row->registerXPathNamespace('s', self::MAIN_NS);
        $values = [];
        foreach ($row->xpath('./s:c') ?: [] as $cell) {
            $reference = (string) $cell['r'];
            $column = preg_replace('/\d+/', '', $reference) ?: '';
            $raw = (string) $cell->v;
            $type = (string) $cell['t'];
            $value = match ($type) {
                's' => $strings[(int) $raw] ?? null,
                'inlineStr' => (string) $cell->is->t,
                default => $raw !== '' ? $raw : null,
            };
            $values[$column] = ['value' => $value, 'formula' => isset($cell->f) ? (string) $cell->f : null];
        }

        return $values;
    }

    private function excelDate(?string $value): ?string
    {
        $value = $this->clean($value);
        if ($value === null) {
            return null;
        }
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return $value;
        }
        if (! is_numeric($value)) {
            return null;
        }

        return gmdate('Y-m-d', ((float) $value - 25569) * 86400);
    }

    private function hyperlink(?string $formula): ?string
    {
        if ($formula === null || ! preg_match('/HYPERLINK\("([^"]+)"/i', $formula, $matches)) {
            return null;
        }

        return filter_var($matches[1], FILTER_VALIDATE_URL) ? $matches[1] : null;
    }

    private function numeric(?string $value): ?int
    {
        return $value !== null && is_numeric($value) ? (int) $value : null;
    }

    private function clean(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function xml(ZipArchive $zip, string $path): SimpleXMLElement
    {
        $content = $zip->getFromName($path);
        if ($content === false) {
            throw new RuntimeException('Struktur Excel tidak lengkap.');
        }
        $xml = simplexml_load_string($content);
        if (! $xml instanceof SimpleXMLElement) {
            throw new RuntimeException('Struktur Excel tidak valid.');
        }

        return $xml;
    }
}
