<?php

namespace App\Libraries;

use RuntimeException;
use SimpleXMLElement;
use ZipArchive;

/**
 * Reads the approved multi-unit SDM import template without evaluating formulas.
 */
final class SdmEmployeeWorkbookParser
{
    private const MAIN_NS = 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';
    private const REL_NS = 'http://schemas.openxmlformats.org/officeDocument/2006/relationships';

    private const WORK_UNITS = [
        'kanwil surabaya' => 'Kantor Wilayah Surabaya',
        'surabaya' => 'Kantor Cabang Surabaya',
        'madiun' => 'Kantor Cabang Madiun',
        'kediri' => 'Kantor Cabang Kediri',
        'malang' => 'Kantor Cabang Malang',
        'banyuwangi' => 'Kantor Cabang Banyuwangi',
        'kup bojonegoro' => 'Kantor Unit Pelayanan Bojonegoro',
        'kup pamekasan' => 'Kantor Unit Pelayanan Pamekasan',
        'kup jember' => 'Kantor Unit Pelayanan Jember',
    ];

    /** @return list<array<string, string|null>> */
    public function parse(string $path): array
    {
        $zip = new ZipArchive();
        if ($zip->open($path) !== true) {
            throw new RuntimeException('Berkas Excel tidak dapat dibuka.');
        }

        try {
            $this->validateArchive($zip);
            $strings = $this->sharedStrings($zip);
            $records = [];
            $foundSheets = 0;

            foreach ($this->sheetPaths($zip) as $sheetName => $sheetPath) {
                $workUnit = self::WORK_UNITS[$this->key($sheetName)] ?? null;
                if ($workUnit === null) {
                    continue;
                }

                $foundSheets++;
                foreach ($this->sheetRecords($zip, $sheetPath, $sheetName, $workUnit, $strings) as $record) {
                    $records[] = $record;
                }
            }

            if ($foundSheets === 0) {
                throw new RuntimeException('Tidak ditemukan sheet unit kerja dari template SDM.');
            }
            if ($records === []) {
                throw new RuntimeException('Tidak ada data karyawan pada template SDM.');
            }

            return $records;
        } finally {
            $zip->close();
        }
    }

    private function validateArchive(ZipArchive $zip): void
    {
        if ($zip->numFiles > 500) {
            throw new RuntimeException('Struktur Excel terlalu besar.');
        }

        $size = 0;
        for ($index = 0; $index < $zip->numFiles; $index++) {
            $entry = $zip->statIndex($index);
            $size += (int) ($entry['size'] ?? 0);
            $name = (string) ($entry['name'] ?? '');
            if ($size > 20 * 1024 * 1024 || preg_match('~(?:vbaProject|externalLinks/)~i', $name)) {
                throw new RuntimeException('Excel terlalu besar atau menggunakan makro/tautan eksternal yang tidak didukung.');
            }
        }
    }

    /** @return array<string, string> */
    private function sheetPaths(ZipArchive $zip): array
    {
        $workbook = $this->xml($zip, 'xl/workbook.xml');
        $workbook->registerXPathNamespace('s', self::MAIN_NS);
        $workbook->registerXPathNamespace('r', self::REL_NS);
        $relationships = $this->xml($zip, 'xl/_rels/workbook.xml.rels');
        $targets = [];

        foreach ($relationships->children() as $relationship) {
            $targets[(string) $relationship['Id']] = ltrim((string) $relationship['Target'], '/');
        }

        $paths = [];
        foreach ($workbook->xpath('//s:sheets/s:sheet') ?: [] as $sheet) {
            $relationId = (string) $sheet->attributes(self::REL_NS)['id'];
            $target = $targets[$relationId] ?? '';
            $path = str_starts_with($target, 'xl/') ? $target : 'xl/' . $target;
            if ($target !== '' && preg_match('~^xl/worksheets/[^/]+\.xml$~', $path)) {
                $paths[(string) $sheet['name']] = $path;
            }
        }

        return $paths;
    }

    /** @return list<array<string, string|null>> */
    private function sheetRecords(ZipArchive $zip, string $path, string $sheetName, string $workUnit, array $strings): array
    {
        $sheet = $this->xml($zip, $path);
        $sheet->registerXPathNamespace('s', self::MAIN_NS);
        $headerRow = null;
        $records = [];

        foreach ($sheet->xpath('//s:sheetData/s:row') ?: [] as $row) {
            $rowNumber = (int) $row['r'];
            $cells = $this->rowCells($row, $strings);

            if ($headerRow === null) {
                if ($this->isHeader($cells)) {
                    $headerRow = $rowNumber;
                }
                continue;
            }
            if ($rowNumber <= $headerRow) {
                continue;
            }

            $name = $this->clean($cells['C'] ?? null);
            if ($name === null) {
                continue;
            }

            $status = $this->clean($cells['H'] ?? null);
            if ($status === null) {
                throw new RuntimeException("Status Karyawan wajib diisi pada sheet {$sheetName}, baris {$rowNumber}.");
            }

            $records[] = [
                'sheet_name' => $sheetName,
                'sheet_row' => (string) $rowNumber,
                'employee_no' => $this->clean($cells['D'] ?? null),
                'employee_name' => $name,
                'gender' => $this->clean($cells['E'] ?? null),
                'work_unit' => $workUnit,
                'organization' => $this->clean($cells['F'] ?? null),
                'position' => $this->clean($cells['G'] ?? null),
                'employment_status' => $status,
                'employment_date' => $this->excelDate($cells['I'] ?? null),
                'phone_number' => $this->clean($cells['J'] ?? null),
                'corporate_email' => $this->clean($cells['K'] ?? null),
            ];
        }

        if ($headerRow === null) {
            throw new RuntimeException("Header template tidak ditemukan pada sheet {$sheetName}.");
        }

        return $records;
    }

    /** @param array<string, string|null> $cells */
    private function isHeader(array $cells): bool
    {
        return $this->key((string) ($cells['B'] ?? '')) === 'no'
            && $this->key((string) ($cells['C'] ?? '')) === 'nama lengkap karyawan'
            && str_starts_with($this->key((string) ($cells['D'] ?? '')), 'npp')
            && $this->key((string) ($cells['F'] ?? '')) === 'bagian'
            && $this->key((string) ($cells['G'] ?? '')) === 'posisi'
            && $this->key((string) ($cells['H'] ?? '')) === 'status karyawan';
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

    /** @return array<string, string|null> */
    private function rowCells(SimpleXMLElement $row, array $strings): array
    {
        $row->registerXPathNamespace('s', self::MAIN_NS);
        $values = [];
        foreach ($row->xpath('./s:c') ?: [] as $cell) {
            $reference = (string) $cell['r'];
            $column = preg_replace('/\d+/', '', $reference) ?: '';
            $type = (string) $cell['t'];
            $children = $cell->children(self::MAIN_NS);
            $raw = (string) ($children->v ?? '');
            $values[$column] = match ($type) {
                's' => $strings[(int) $raw] ?? null,
                'inlineStr' => (string) ($children->is->t ?? ''),
                default => $raw !== '' ? $raw : null,
            };
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

    private function clean(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function key(string $value): string
    {
        return mb_strtolower(trim(preg_replace('/\s+/u', ' ', $value) ?? ''));
    }

    private function xml(ZipArchive $zip, string $path): SimpleXMLElement
    {
        $raw = $zip->getFromName($path);
        if ($raw === false) {
            throw new RuntimeException('Struktur Excel tidak lengkap.');
        }

        $previous = libxml_use_internal_errors(true);
        try {
            $xml = simplexml_load_string($raw, SimpleXMLElement::class, LIBXML_NONET | LIBXML_NOCDATA);
            if (! $xml instanceof SimpleXMLElement) {
                throw new RuntimeException('Struktur XML Excel tidak valid.');
            }

            return $xml;
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
    }
}
