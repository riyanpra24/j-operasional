<?php

namespace App\Libraries;

use DateTimeImmutable;
use DateTimeZone;
use DOMDocument;
use DOMElement;
use DOMXPath;
use RuntimeException;
use ZipArchive;

/**
 * Builds an SDM export by refilling the approved multi-unit import template.
 */
final class SdmEmployeeWorkbookExporter
{
    private const MAIN_NS = 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';
    private const REL_NS = 'http://schemas.openxmlformats.org/officeDocument/2006/relationships';
    private const TITLE = 'UPDATE DATA BASE KARYAWAN UNIT KERJA';

    /** @var array<string, string> */
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

    /**
     * @param list<array<string, mixed>> $employees
     */
    public function export(string $templatePath, array $employees, string $temporaryDirectory): string
    {
        if (! is_file($templatePath)) {
            throw new RuntimeException('Template export SDM tidak ditemukan.');
        }
        if (! is_dir($temporaryDirectory)) {
            throw new RuntimeException('Penyimpanan sementara export SDM tidak tersedia.');
        }

        $temporaryPath = tempnam($temporaryDirectory, 'sdm-export-');
        if ($temporaryPath === false || ! copy($templatePath, $temporaryPath)) {
            throw new RuntimeException('Dokumen export SDM tidak dapat disiapkan.');
        }

        try {
            $zip = new ZipArchive();
            if ($zip->open($temporaryPath) !== true) {
                throw new RuntimeException('Template export SDM tidak dapat dibuka.');
            }

            try {
                $employeesByUnit = $this->employeesByWorkUnit($employees);
                foreach ($this->sheetPaths($zip) as $sheetName => $sheetPath) {
                    $workUnit = self::WORK_UNITS[$this->key($sheetName)] ?? null;
                    if ($workUnit === null) {
                        continue;
                    }

                    $sheetXml = $zip->getFromName($sheetPath);
                    if ($sheetXml === false) {
                        throw new RuntimeException('Struktur sheet template export SDM tidak lengkap.');
                    }

                    if ($zip->addFromString($sheetPath, $this->fillSheet($sheetXml, $employeesByUnit[$workUnit] ?? [])) === false) {
                        throw new RuntimeException('Data SDM gagal ditulis ke template export.');
                    }
                }
            } finally {
                $zip->close();
            }

            $contents = file_get_contents($temporaryPath);
            if ($contents === false) {
                throw new RuntimeException('Dokumen export SDM tidak dapat dibaca.');
            }

            return $contents;
        } finally {
            if (is_file($temporaryPath)) {
                unlink($temporaryPath);
            }
        }
    }

    /** @param list<array<string, mixed>> $employees @return array<string, list<array<string, mixed>>> */
    private function employeesByWorkUnit(array $employees): array
    {
        $grouped = [];
        foreach ($employees as $employee) {
            $workUnit = trim((string) ($employee['work_unit'] ?? ''));
            if ($workUnit !== '') {
                $grouped[$workUnit][] = $employee;
            }
        }

        return $grouped;
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

    /** @param list<array<string, mixed>> $employees */
    private function fillSheet(string $sheetXml, array $employees): string
    {
        $document = new DOMDocument('1.0', 'UTF-8');
        $document->preserveWhiteSpace = true;
        if (! $document->loadXML($sheetXml, LIBXML_NONET | LIBXML_NOCDATA)) {
            throw new RuntimeException('Struktur sheet template export SDM tidak valid.');
        }

        $xpath = new DOMXPath($document);
        $xpath->registerNamespace('s', self::MAIN_NS);
        $title = $xpath->query('//s:c[@r="B2"]/s:v')->item(0);
        if ($title === null) {
            throw new RuntimeException('Judul pada template export SDM tidak ditemukan.');
        }
        $title->nodeValue = self::TITLE;

        $sheetData = $xpath->query('//s:sheetData')->item(0);
        if (! $sheetData instanceof DOMElement) {
            throw new RuntimeException('Tabel pada template export SDM tidak ditemukan.');
        }

        foreach (iterator_to_array($sheetData->childNodes) as $row) {
            if ($row instanceof DOMElement && $row->localName === 'row' && (int) $row->getAttribute('r') >= 5) {
                $sheetData->removeChild($row);
            }
        }

        foreach ($employees as $index => $employee) {
            $this->appendEmployeeRow($document, $sheetData, $index + 5, $index + 1, $employee);
        }

        $xml = $document->saveXML();
        if ($xml === false) {
            throw new RuntimeException('Sheet export SDM gagal dibuat.');
        }

        return $xml;
    }

    /** @param array<string, mixed> $employee */
    private function appendEmployeeRow(DOMDocument $document, DOMElement $sheetData, int $rowNumber, int $number, array $employee): void
    {
        $row = $document->createElementNS(self::MAIN_NS, 'x:row');
        $row->setAttribute('r', (string) $rowNumber);
        $row->setAttribute('ht', '18');
        $row->setAttribute('customHeight', '1');

        $values = [
            ['B', '12', (string) $number, false],
            ['C', '11', trim((string) ($employee['employee_name'] ?? '')), false],
            ['D', '13', trim((string) ($employee['employee_no'] ?? '')), false],
            ['E', '12', $this->genderCode((string) ($employee['gender'] ?? '')), false],
            ['F', '11', trim((string) ($employee['organization'] ?? '')), false],
            ['G', '11', trim((string) ($employee['position'] ?? '')), false],
            ['H', '12', trim((string) ($employee['employment_status'] ?? '')), false],
            ['I', '14', $this->excelDate((string) ($employee['employment_date'] ?? '')), true],
            ['J', '13', trim((string) ($employee['phone_number'] ?? '')), false],
            ['K', '13', trim((string) ($employee['corporate_email'] ?? '')), false],
        ];

        foreach ($values as [$column, $style, $value, $isDate]) {
            $cell = $document->createElementNS(self::MAIN_NS, 'x:c');
            $cell->setAttribute('r', $column . $rowNumber);
            $cell->setAttribute('s', $style);
            if ($value !== '') {
                if (! $isDate) {
                    $cell->setAttribute('t', 'str');
                }
                $valueNode = $document->createElementNS(self::MAIN_NS, 'x:v');
                $valueNode->appendChild($document->createTextNode($value));
                $cell->appendChild($valueNode);
            }
            $row->appendChild($cell);
        }

        $sheetData->appendChild($row);
    }

    private function genderCode(string $gender): string
    {
        $gender = $this->key($gender);

        return match ($gender) {
            'l', 'laki laki', 'pria' => 'L',
            'p', 'perempuan', 'wanita' => 'P',
            default => '',
        };
    }

    private function excelDate(string $value): string
    {
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', trim($value), new DateTimeZone('UTC'));
        $errors = DateTimeImmutable::getLastErrors();
        if ($date === false || ($errors !== false && ($errors['warning_count'] > 0 || $errors['error_count'] > 0))) {
            return '';
        }

        $base = new DateTimeImmutable('1899-12-30', new DateTimeZone('UTC'));

        return (string) $base->diff($date)->format('%r%a');
    }

    private function key(string $value): string
    {
        return mb_strtolower(trim(preg_replace('/\s+/u', ' ', $value) ?? ''));
    }

    private function xml(ZipArchive $zip, string $path): \SimpleXMLElement
    {
        $raw = $zip->getFromName($path);
        if ($raw === false) {
            throw new RuntimeException('Struktur template export SDM tidak lengkap.');
        }

        $previous = libxml_use_internal_errors(true);
        try {
            $xml = simplexml_load_string($raw, \SimpleXMLElement::class, LIBXML_NONET | LIBXML_NOCDATA);
            if (! $xml instanceof \SimpleXMLElement) {
                throw new RuntimeException('Struktur template export SDM tidak valid.');
            }

            return $xml;
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
    }
}
