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
    private const DATA_FIRST_ROW = 5;
    private const RECAP_FIRST_ROW = 8;
    private const RECAP_LAST_ROW = 17;
    private const RECAP_TOTAL_ROW = 18;
    private const RECAP_HEADER_STYLE = '15';
    private const RECAP_TOTAL_STYLE = '16';
    private const CONTENT_TYPES_NS = 'http://schemas.openxmlformats.org/package/2006/content-types';
    private const PACKAGE_REL_NS = 'http://schemas.openxmlformats.org/package/2006/relationships';

    /** @var list<string> */
    private const RECAP_POSITIONS = [
        'Manajemen Unit Kerja', 'RM CBC', 'RM CAC', 'RM Subrogasi', 'Bagian Bisnis',
        'Bagian Klaim & Subrogasi', 'Bagian Operasional', 'Fungsional', 'Risk Officer (RO)', 'Helpdesk TI',
    ];

    /** @var list<string> */
    private const RECAP_STATUS_COLUMNS = [
        'Karyawan Tetap', 'Calon Karyawan', 'PKWT Umum', 'PKWT ELH', 'Magang',
    ];

    /** @var list<string> */
    private const OUTSOURCING_COLUMNS = ['Admin', 'Driver', 'OB/CS', 'Security'];

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

    /** @var list<string> */
    private const JATIM_RECAP_SHEET_ORDER = [
        'Kanwil Surabaya', 'Surabaya', 'Malang', 'Kediri', 'Madiun', 'Banyuwangi',
        'KUP Bojonegoro', 'KUP Pamekasan', 'KUP Jember',
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
                $sourceSheets = [];
                foreach ($this->sheetPaths($zip) as $sheetName => $sheetPath) {
                    $sheetXml = $zip->getFromName($sheetPath);
                    if ($sheetXml === false) {
                        throw new RuntimeException('Struktur sheet template export SDM tidak lengkap.');
                    }
                    $sourceSheets[$sheetName] = [$sheetPath, $sheetXml];
                }
                $jatimRecapPackage = $this->jatimRecapPackageParts($zip);
                $this->enableAutomaticCalculation($zip);
                $this->addRecapStyles($zip);
                $employeesByUnit = $this->employeesByWorkUnit($employees);
                $unitSheets = [];
                foreach ($sourceSheets as $sheetName => [$sheetPath, $sheetXml]) {
                    $workUnit = self::WORK_UNITS[$this->key($sheetName)] ?? null;
                    if ($workUnit === null) {
                        continue;
                    }

                    $unitSheets[$sheetName] = $workUnit;

                    if ($zip->addFromString($sheetPath, $this->fillSheet($sheetXml, $employeesByUnit[$workUnit] ?? [], $workUnit)) === false) {
                        throw new RuntimeException('Data SDM gagal ditulis ke template export.');
                    }
                }
                $this->addJatimRecapSheet(
                    $zip,
                    $unitSheets,
                    $jatimRecapPackage,
                    array_map(static fn (array $source): string => $source[0], $sourceSheets),
                );
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

    /** @return array{workbook: string, relationships: string, contentTypes: string} */
    private function jatimRecapPackageParts(ZipArchive $zip): array
    {
        $parts = [
            'workbook' => $zip->getFromName('xl/workbook.xml'),
            'relationships' => $zip->getFromName('xl/_rels/workbook.xml.rels'),
            'contentTypes' => $zip->getFromName('[Content_Types].xml'),
        ];
        foreach ($parts as $part) {
            if (! is_string($part)) {
                throw new RuntimeException('Struktur workbook export SDM tidak lengkap.');
            }
        }

        return $parts;
    }

    /**
     * Adds a master sheet whose values are linked to every unit recap sheet.
     *
     * @param array<string, string> $unitSheets Sheet name => work unit
     * @param array{workbook: string, relationships: string, contentTypes: string} $package
     * @param list<string> $sourceSheetPaths
     */
    private function addJatimRecapSheet(ZipArchive $zip, array $unitSheets, array $package, array $sourceSheetPaths): void
    {
        if (str_contains($package['workbook'], 'name="Rekap SDM Jatim"')) {
            throw new RuntimeException('Sheet Rekap SDM Jatim sudah tersedia pada template export.');
        }

        $sheetNumber = 1;
        foreach ($sourceSheetPaths as $path) {
            if (preg_match('~/sheet(\d+)\.xml$~', $path, $matches)) {
                $sheetNumber = max($sheetNumber, (int) $matches[1] + 1);
            }
        }
        preg_match_all('/sheetId="(\d+)"/', $package['workbook'], $sheetIdMatches);
        $sheetId = max(array_map('intval', $sheetIdMatches[1] ?: ['0'])) + 1;
        $relationshipId = 'rIdSdmJatim';
        $sheetPath = 'xl/worksheets/sheet' . $sheetNumber . '.xml';

        preg_match('/<([A-Za-z][A-Za-z0-9_-]*):sheets\b/', $package['workbook'], $prefixMatches);
        $spreadsheetPrefix = $prefixMatches[1] ?? '';
        $sheetTag = $spreadsheetPrefix === '' ? 'sheet' : $spreadsheetPrefix . ':sheet';
        $sheetsClosingTag = $spreadsheetPrefix === '' ? '</sheets>' : '</' . $spreadsheetPrefix . ':sheets>';
        $sheet = '<' . $sheetTag . ' name="Rekap SDM Jatim" sheetId="' . $sheetId . '" r:id="' . $relationshipId . '" xmlns:r="' . self::REL_NS . '"/>';
        $workbook = str_replace($sheetsClosingTag, $sheet . $sheetsClosingTag, $package['workbook']);
        $relationship = '<Relationship Id="' . $relationshipId . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet' . $sheetNumber . '.xml"/>';
        $relationships = preg_replace('/<\/Relationships>/', $relationship . '</Relationships>', $package['relationships'], 1);
        $contentType = '<Override PartName="/xl/worksheets/sheet' . $sheetNumber . '.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>';
        $contentTypes = preg_replace('/<\/Types>/', $contentType . '</Types>', $package['contentTypes'], 1);
        if (! is_string($workbook) || ! is_string($relationships) || ! is_string($contentTypes)
            || $zip->addFromString('xl/workbook.xml', $workbook) === false
            || $zip->addFromString('xl/_rels/workbook.xml.rels', $relationships) === false
            || $zip->addFromString('[Content_Types].xml', $contentTypes) === false
            || $zip->addFromString($sheetPath, $this->jatimRecapSheetXml($unitSheets)) === false) {
            throw new RuntimeException('Sheet Rekap SDM Jatim gagal ditambahkan ke export.');
        }
    }

    /** @param array<string, string> $unitSheets */
    private function jatimRecapSheetXml(array $unitSheets): string
    {
        $orderedSheets = [];
        foreach (self::JATIM_RECAP_SHEET_ORDER as $sheetName) {
            if (isset($unitSheets[$sheetName])) {
                $orderedSheets[$sheetName] = $unitSheets[$sheetName];
            }
        }
        foreach ($unitSheets as $sheetName => $workUnit) {
            if (! isset($orderedSheets[$sheetName])) {
                $orderedSheets[$sheetName] = $workUnit;
            }
        }

        $headerLabels = array_merge(['Unit Kerja'], self::RECAP_POSITIONS, ['Jumlah']);
        $rows = [
            '<row r="2" ht="26" customHeight="1">' . $this->jatimTextCell('B2', self::RECAP_HEADER_STYLE, 'REKAP SDM JATIM') . '</row>',
            '<row r="4" ht="28" customHeight="1">' . $this->jatimHeaderCells(4, $headerLabels) . '</row>',
        ];
        $rowNumber = 5;
        foreach ($orderedSheets as $sheetName => $workUnit) {
            $cells = $this->jatimTextCell('B' . $rowNumber, '12', $workUnit);
            foreach (self::RECAP_POSITIONS as $index => $position) {
                $sourceRow = self::RECAP_FIRST_ROW + $index;
                $cells .= $this->jatimFormulaCell(chr(ord('C') + $index) . $rowNumber, '12', "'{$sheetName}'!W{$sourceRow}");
            }
            $cells .= $this->jatimFormulaCell('M' . $rowNumber, '12', '=SUM(C' . $rowNumber . ':L' . $rowNumber . ')');
            $rows[] = '<row r="' . $rowNumber . '" ht="20" customHeight="1">' . $cells . '</row>';
            $rowNumber++;
        }
        $totalRow = $rowNumber;
        $totalCells = $this->jatimTextCell('B' . $totalRow, self::RECAP_TOTAL_STYLE, 'JUMLAH SDM JATIM');
        foreach (range('C', 'L') as $column) {
            $totalCells .= $this->jatimFormulaCell($column . $totalRow, self::RECAP_TOTAL_STYLE, '=SUM(' . $column . '5:' . $column . ($totalRow - 1) . ')');
        }
        $totalCells .= $this->jatimFormulaCell('M' . $totalRow, self::RECAP_TOTAL_STYLE, '=SUM(C' . $totalRow . ':L' . $totalRow . ')');
        $rows[] = '<row r="' . $totalRow . '" ht="22" customHeight="1">' . $totalCells . '</row>';

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<worksheet xmlns="' . self::MAIN_NS . '"><dimension ref="B2:M' . $totalRow . '"/><sheetViews><sheetView workbookViewId="0"/></sheetViews><sheetFormatPr defaultRowHeight="15"/>'
            . '<cols><col min="2" max="2" width="35" customWidth="1"/><col min="3" max="12" width="15" customWidth="1"/><col min="13" max="13" width="12" customWidth="1"/></cols>'
            . '<sheetData>' . implode('', $rows) . '</sheetData><mergeCells count="1"><mergeCell ref="B2:M2"/></mergeCells>'
            . '<pageMargins left="0.25" right="0.25" top="0.5" bottom="0.5" header="0.3" footer="0.3"/></worksheet>';
    }

    /** @param list<string> $labels */
    private function jatimHeaderCells(int $rowNumber, array $labels): string
    {
        $cells = '';
        foreach ($labels as $index => $label) {
            $cells .= $this->jatimTextCell(chr(ord('B') + $index) . $rowNumber, self::RECAP_HEADER_STYLE, $label);
        }

        return $cells;
    }

    private function jatimTextCell(string $reference, string $style, string $value): string
    {
        return '<c r="' . $reference . '" s="' . $style . '" t="inlineStr"><is><t>' . htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</t></is></c>';
    }

    private function jatimFormulaCell(string $reference, string $style, string $formula): string
    {
        return '<c r="' . $reference . '" s="' . $style . '"><f>' . htmlspecialchars(ltrim($formula, '='), ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</f><v>0</v></c>';
    }

    /**
     * Adds two local styles used only by the recap block: orange headers and a yellow total row.
     */
    private function addRecapStyles(ZipArchive $zip): void
    {
        $stylesPath = 'xl/styles.xml';
        $styles = $this->xml($zip, $stylesPath);
        $styles->registerXPathNamespace('s', self::MAIN_NS);
        $fonts = $styles->xpath('//s:fonts')[0] ?? null;
        $fills = $styles->xpath('//s:fills')[0] ?? null;
        $cellXfs = $styles->xpath('//s:cellXfs')[0] ?? null;
        if (! $fonts instanceof \SimpleXMLElement || ! $fills instanceof \SimpleXMLElement || ! $cellXfs instanceof \SimpleXMLElement) {
            throw new RuntimeException('Gaya template export SDM tidak lengkap.');
        }

        $namespace = self::MAIN_NS;
        $orangeFillId = count($fills->children($namespace));
        $yellowFillId = $orangeFillId + 1;
        $boldFontId = count($fonts->children($namespace));
        $headerStyleId = count($cellXfs->children($namespace));
        $totalStyleId = $headerStyleId + 1;
        if ($headerStyleId !== (int) self::RECAP_HEADER_STYLE || $totalStyleId !== (int) self::RECAP_TOTAL_STYLE) {
            throw new RuntimeException('Gaya dasar template export SDM berubah dan perlu disesuaikan.');
        }

        $orangeFill = $fills->addChild('fill', null, $namespace);
        $orangePattern = $orangeFill->addChild('patternFill', null, $namespace);
        $orangePattern->addAttribute('patternType', 'solid');
        $orangePattern->addChild('fgColor', null, $namespace)->addAttribute('rgb', 'FFF28524');
        $orangePattern->addChild('bgColor', null, $namespace)->addAttribute('indexed', '64');

        $yellowFill = $fills->addChild('fill', null, $namespace);
        $yellowPattern = $yellowFill->addChild('patternFill', null, $namespace);
        $yellowPattern->addAttribute('patternType', 'solid');
        $yellowPattern->addChild('fgColor', null, $namespace)->addAttribute('rgb', 'FFFFD966');
        $yellowPattern->addChild('bgColor', null, $namespace)->addAttribute('indexed', '64');
        $fills['count'] = (string) count($fills->children($namespace));

        $boldFont = $fonts->addChild('font', null, $namespace);
        $boldFont->addChild('b', null, $namespace);
        $boldFont->addChild('sz', null, $namespace)->addAttribute('val', '10');
        $boldFont->addChild('color', null, $namespace)->addAttribute('rgb', 'FF000000');
        $boldFont->addChild('name', null, $namespace)->addAttribute('val', 'Arial');
        $fonts['count'] = (string) count($fonts->children($namespace));

        $headerXf = $cellXfs->addChild('xf', null, $namespace);
        $headerXf->addAttribute('numFmtId', '0');
        $headerXf->addAttribute('fontId', (string) $boldFontId);
        $headerXf->addAttribute('fillId', (string) $orangeFillId);
        $headerXf->addAttribute('borderId', '1');
        $headerXf->addAttribute('xfId', '0');
        $headerXf->addAttribute('applyAlignment', '1');
        $headerAlignment = $headerXf->addChild('alignment', null, $namespace);
        $headerAlignment->addAttribute('horizontal', 'center');
        $headerAlignment->addAttribute('vertical', 'center');
        $headerAlignment->addAttribute('wrapText', '1');

        $totalXf = $cellXfs->addChild('xf', null, $namespace);
        $totalXf->addAttribute('numFmtId', '0');
        $totalXf->addAttribute('fontId', (string) $boldFontId);
        $totalXf->addAttribute('fillId', (string) $yellowFillId);
        $totalXf->addAttribute('borderId', '1');
        $totalXf->addAttribute('xfId', '0');
        $totalXf->addAttribute('applyAlignment', '1');
        $totalAlignment = $totalXf->addChild('alignment', null, $namespace);
        $totalAlignment->addAttribute('horizontal', 'center');
        $totalAlignment->addAttribute('vertical', 'center');
        $totalAlignment->addAttribute('wrapText', '1');
        $cellXfs['count'] = (string) count($cellXfs->children($namespace));

        $xml = $styles->asXML();
        if ($xml === false || $zip->addFromString($stylesPath, $xml) === false) {
            throw new RuntimeException('Gaya rekap export SDM tidak dapat disiapkan.');
        }
    }

    private function enableAutomaticCalculation(ZipArchive $zip): void
    {
        $path = 'xl/workbook.xml';
        $workbook = $this->xml($zip, $path);
        $workbook->registerXPathNamespace('s', self::MAIN_NS);
        $calculation = $workbook->xpath('//s:calcPr')[0] ?? null;
        if (! $calculation instanceof \SimpleXMLElement) {
            $calculation = $workbook->addChild('calcPr', null, self::MAIN_NS);
        }
        $calculation['calcMode'] = 'auto';
        $calculation['fullCalcOnLoad'] = '1';
        $calculation['forceFullCalc'] = '1';

        $xml = $workbook->asXML();
        if ($xml === false || $zip->addFromString($path, $xml) === false) {
            throw new RuntimeException('Penghitungan otomatis export SDM tidak dapat diatur.');
        }
    }

    /** @param list<array<string, mixed>> $employees */
    private function fillSheet(string $sheetXml, array $employees, string $workUnit): string
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
            if ($row instanceof DOMElement && $row->localName === 'row' && (int) $row->getAttribute('r') >= self::DATA_FIRST_ROW) {
                $sheetData->removeChild($row);
            }
        }

        $this->addRecapHeader($document, $sheetData);
        $lastDataRow = max(self::RECAP_TOTAL_ROW, count($employees) + self::DATA_FIRST_ROW - 1);
        for ($rowNumber = self::DATA_FIRST_ROW; $rowNumber <= $lastDataRow; $rowNumber++) {
            $row = $document->createElementNS(self::MAIN_NS, 'x:row');
            $row->setAttribute('r', (string) $rowNumber);
            $row->setAttribute('ht', $rowNumber === 6 ? '34' : (in_array($rowNumber, [5, 7], true) ? '26' : '18'));
            $row->setAttribute('customHeight', '1');

            $employee = $employees[$rowNumber - self::DATA_FIRST_ROW] ?? null;
            if (is_array($employee)) {
                $this->appendEmployeeCells($document, $row, $rowNumber, $rowNumber - self::DATA_FIRST_ROW + 1, $employee);
            }
            $this->appendRecapCells($document, $row, $rowNumber, $workUnit);
            $sheetData->appendChild($row);
        }

        $this->addRecapMerges($document, $xpath);
        $this->setRecapColumnWidths($document, $xpath, $sheetData);
        $this->setSheetDimension($xpath, $lastDataRow);

        $xml = $document->saveXML();
        if ($xml === false) {
            throw new RuntimeException('Sheet export SDM gagal dibuat.');
        }

        return $xml;
    }

    /** @param array<string, mixed> $employee */
    private function appendEmployeeCells(DOMDocument $document, DOMElement $row, int $rowNumber, int $number, array $employee): void
    {
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

    }

    private function addRecapHeader(DOMDocument $document, DOMElement $sheetData): void
    {
        $rows = [];
        foreach ($sheetData->childNodes as $node) {
            if ($node instanceof DOMElement && $node->localName === 'row') {
                $rows[(int) $node->getAttribute('r')] = $node;
            }
        }

        if (! isset($rows[4])) {
                throw new RuntimeException('Struktur header template export SDM tidak lengkap.');
        }

        $rows[4]->setAttribute('ht', '26');
        $rows[4]->setAttribute('customHeight', '1');
        $this->appendHeaderRange($document, $rows[4], 'M', 'W', [
            'M' => 'REKAPITULASI SDM UKER',
        ]);
    }

    private function appendRecapCells(DOMDocument $document, DOMElement $row, int $rowNumber, string $workUnit): void
    {
        if ($rowNumber === 5) {
            $this->appendHeaderRange($document, $row, 'M', 'W', [
                'M' => 'Posisi',
                'N' => 'Status Karyawan',
                'W' => 'JUMLAH',
            ]);
            return;
        }
        if ($rowNumber === 6) {
            $this->appendHeaderRange($document, $row, 'M', 'W', [
                'N' => 'Organik Jamkrindo',
                'S' => 'Outsourcing',
            ]);
            return;
        }
        if ($rowNumber === 7) {
            $this->appendHeaderRange($document, $row, 'M', 'W', [
                'N' => 'Karyawan Tetap',
                'O' => 'Calon Karyawan',
                'P' => 'PKWT Umum',
                'Q' => 'PKWT ELH',
                'R' => 'Magang',
                'S' => 'Admin',
                'T' => 'Driver',
                'U' => 'OB/CS',
                'V' => 'Security',
            ]);
            return;
        }
        if ($rowNumber < self::RECAP_FIRST_ROW || $rowNumber > self::RECAP_TOTAL_ROW) {
            return;
        }

        if ($rowNumber === self::RECAP_TOTAL_ROW) {
            $this->appendTextCell($document, $row, 'M' . $rowNumber, self::RECAP_TOTAL_STYLE, 'JUMLAH SDM');
            foreach (range('N', 'V') as $column) {
                $this->appendFormulaCell($document, $row, $column . $rowNumber, self::RECAP_TOTAL_STYLE, '=SUM(' . $column . self::RECAP_FIRST_ROW . ':' . $column . self::RECAP_LAST_ROW . ')');
            }
            $this->appendFormulaCell($document, $row, 'W' . $rowNumber, self::RECAP_TOTAL_STYLE, '=SUM(N' . $rowNumber . ':V' . $rowNumber . ')');
            return;
        }

        $position = self::RECAP_POSITIONS[$rowNumber - self::RECAP_FIRST_ROW];
        $this->appendTextCell($document, $row, 'M' . $rowNumber, '12', $position);
        foreach (self::RECAP_STATUS_COLUMNS as $index => $status) {
            $column = chr(ord('N') + $index);
            $this->appendFormulaCell($document, $row, $column . $rowNumber, '12', $this->recapFormula($position, $status, $workUnit));
        }
        foreach (self::OUTSOURCING_COLUMNS as $index => $category) {
            $column = chr(ord('S') + $index);
            $this->appendFormulaCell($document, $row, $column . $rowNumber, '12', $this->recapFormula($position, $category, $workUnit));
        }
        $this->appendFormulaCell($document, $row, 'W' . $rowNumber, '12', '=SUM(N' . $rowNumber . ':V' . $rowNumber . ')');
    }

    private function recapFormula(string $recapPosition, string $column, string $workUnit): string
    {
        if (in_array($column, self::RECAP_STATUS_COLUMNS, true)) {
            if ($recapPosition === 'Manajemen Unit Kerja') {
                return $this->managementFormula($workUnit, $column);
            }
            if ($recapPosition === 'Risk Officer (RO)') {
                return $this->countPositionAndOrganizationFormula('Staff Risk Officer', 'Staff Risk Officer', $column);
            }
            if ($recapPosition === 'Helpdesk TI') {
                return $this->countPositionAndOrganizationFormula('IT Helpdesk', 'IT Helpdesk', $column);
            }
            $positions = $this->positionsForRecap($recapPosition);
            return $positions === [] ? '=0' : $this->countPositionsFormula($positions, $column);
        }

        if ($recapPosition === 'Manajemen Unit Kerja' || ! in_array($column, self::OUTSOURCING_COLUMNS, true)) {
            return '=0';
        }

        $staffPosition = match ($recapPosition) {
            'RM CBC' => 'Staff RM CBC',
            'RM CAC' => 'Staff RM CAC',
            'RM Subrogasi' => 'Staff RM Subrogasi',
            'Bagian Bisnis' => 'Staff Bisnis',
            'Bagian Klaim & Subrogasi' => 'Staff Klaim',
            'Bagian Operasional' => 'Staff Operasional',
            default => null,
        };

        if ($column === 'Admin') {
            $formulas = [];
            if ($staffPosition !== null) {
                $positions = $recapPosition === 'Bagian Klaim & Subrogasi'
                    ? ['Staff Klaim', 'Staff Klaim dan Subrogasi']
                    : [$staffPosition];
                $formulas[] = $this->countPositionsFormula($positions, 'Outsourcing', false);
            }
            $organizationFormula = $this->outsourcingOrganizationFormula($recapPosition, 'Admin');
            if ($organizationFormula !== null) {
                $formulas[] = $organizationFormula;
            }
            return $formulas === [] ? '=0' : '=SUM(' . implode(',', $formulas) . ')';
        }

        $organizationFormula = $this->outsourcingOrganizationFormula($recapPosition, $column);
        return $organizationFormula === null ? '=0' : '=' . $organizationFormula;
    }

    /** @return list<string> */
    private function positionsForRecap(string $recapPosition): array
    {
        return match ($recapPosition) {
            'RM CBC' => ['Staff RM CBC'],
            'RM CAC' => ['Staff RM CAC'],
            'RM Subrogasi' => ['Staff RM Subrogasi'],
            'Bagian Bisnis' => ['Staff Bisnis'],
            'Bagian Klaim & Subrogasi' => ['Staff Klaim', 'Staff Klaim dan Subrogasi'],
            'Bagian Operasional' => ['Staff Operasional'],
            'Fungsional' => ['Staff Fungsional'],
            default => [],
        };
    }

    private function managementFormula(string $workUnit, string $status): string
    {
        if ($workUnit === 'Kantor Wilayah Surabaya') {
            return $this->countPositionsFormula(['Pimpinan Wilayah', 'Wakil Pimpinan Wilayah', 'Wakil Pinwil', 'Kepala Bagian*'], $status);
        }
        if (str_starts_with($workUnit, 'Kantor Cabang ')) {
            return $this->countPositionsFormula(['Pemimpin Cabang', 'Wakil Pemimpin Cabang', 'Manajer*'], $status);
        }
        if (str_starts_with($workUnit, 'Kantor Unit Pelayanan ')) {
            return $this->countPositionsFormula(['Kepala Kantor Unit*'], $status);
        }

        return '=0';
    }

    /** @param list<string> $positions */
    private function countPositionsFormula(array $positions, string $status, bool $withEquals = true): string
    {
        $formulas = [];
        foreach ($positions as $position) {
            $formulas[] = 'COUNTIFS($G$5:$G$1000,"' . $position . '",$H$5:$H$1000,"' . $status . '")';
        }

        $formula = count($formulas) === 1 ? $formulas[0] : 'SUM(' . implode(',', $formulas) . ')';
        return $withEquals ? '=' . $formula : $formula;
    }

    private function countPositionAndOrganizationFormula(string $organization, string $position, string $status): string
    {
        return '=COUNTIFS($F$5:$F$1000,"' . $organization . '",$G$5:$G$1000,"' . $position . '",$H$5:$H$1000,"' . $status . '")';
    }

    private function outsourcingOrganizationFormula(string $recapPosition, string $outsourcingCategory): ?string
    {
        $positionCriteria = match ($outsourcingCategory) {
            'Admin' => ['Admin'],
            'Driver' => ['Driver'],
            'OB/CS' => ['OB/CS', 'OB', 'Office Boy', 'Cleaning Service'],
            'Security' => ['Security', 'Satpam'],
            default => [],
        };
        $organizationTerms = match ($recapPosition) {
            'Bagian Bisnis' => ['bisnis'],
            'Bagian Klaim & Subrogasi' => ['klaim', 'subrogasi'],
            'Bagian Operasional' => ['operasional'],
            default => [],
        };
        if ($positionCriteria === [] || $organizationTerms === []) {
            return null;
        }

        $formulas = [];
        foreach ($positionCriteria as $position) {
            foreach ($organizationTerms as $index => $term) {
                $criteria = [
                    '$G$5:$G$1000,"' . $position . '"',
                    '$H$5:$H$1000,"Outsourcing"',
                    '$F$5:$F$1000,"*' . ucfirst($term) . '*"',
                ];
                // Bagian "Klaim dan Subrogasi" memenuhi dua kata kunci. Baris Subrogasi
                // dibatasi agar satu pegawai tidak pernah terhitung dua kali.
                if ($recapPosition === 'Bagian Klaim & Subrogasi' && $index > 0) {
                    $criteria[] = '$F$5:$F$1000,"<>*Klaim*"';
                }
                $formulas[] = 'COUNTIFS(' . implode(',', $criteria) . ')';
            }
        }

        return count($formulas) === 1 ? $formulas[0] : 'SUM(' . implode(',', $formulas) . ')';
    }

    private function appendTextCell(DOMDocument $document, DOMElement $row, string $reference, string $style, string $value): void
    {
        $cell = $document->createElementNS(self::MAIN_NS, 'x:c');
        $cell->setAttribute('r', $reference);
        $cell->setAttribute('s', $style);
        $cell->setAttribute('t', 'str');
        $valueNode = $document->createElementNS(self::MAIN_NS, 'x:v');
        $valueNode->appendChild($document->createTextNode($value));
        $cell->appendChild($valueNode);
        $row->appendChild($cell);
    }

    /** @param array<string, string> $labels */
    private function appendHeaderRange(DOMDocument $document, DOMElement $row, string $from, string $to, array $labels): void
    {
        for ($index = ord($from); $index <= ord($to); $index++) {
            $column = chr($index);
            if (isset($labels[$column])) {
                $this->appendTextCell($document, $row, $column . $row->getAttribute('r'), self::RECAP_HEADER_STYLE, $labels[$column]);
                continue;
            }

            $cell = $document->createElementNS(self::MAIN_NS, 'x:c');
            $cell->setAttribute('r', $column . $row->getAttribute('r'));
            $cell->setAttribute('s', self::RECAP_HEADER_STYLE);
            $row->appendChild($cell);
        }
    }

    private function appendFormulaCell(DOMDocument $document, DOMElement $row, string $reference, string $style, string $formula): void
    {
        $cell = $document->createElementNS(self::MAIN_NS, 'x:c');
        $cell->setAttribute('r', $reference);
        $cell->setAttribute('s', $style);
        $formulaNode = $document->createElementNS(self::MAIN_NS, 'x:f');
        $formulaNode->appendChild($document->createTextNode(ltrim($formula, '=')));
        $cell->appendChild($formulaNode);
        $cell->appendChild($document->createElementNS(self::MAIN_NS, 'x:v', '0'));
        $row->appendChild($cell);
    }

    private function addRecapMerges(DOMDocument $document, DOMXPath $xpath): void
    {
        $mergeCells = $xpath->query('//s:mergeCells')->item(0);
        if (! $mergeCells instanceof DOMElement) {
            throw new RuntimeException('Struktur gabungan sel template export SDM tidak lengkap.');
        }
        foreach (['M4:W4', 'M5:M7', 'N5:V5', 'W5:W7', 'N6:R6', 'S6:V6'] as $range) {
            $merge = $document->createElementNS(self::MAIN_NS, 'x:mergeCell');
            $merge->setAttribute('ref', $range);
            $mergeCells->appendChild($merge);
        }
        $mergeCells->setAttribute('count', (string) $mergeCells->getElementsByTagNameNS(self::MAIN_NS, 'mergeCell')->length);
    }

    private function setRecapColumnWidths(DOMDocument $document, DOMXPath $xpath, DOMElement $sheetData): void
    {
        $widths = ['M' => 28, 'N' => 18, 'O' => 18, 'P' => 15, 'Q' => 14, 'R' => 12, 'S' => 10, 'T' => 10, 'U' => 10, 'V' => 11, 'W' => 12];
        $columns = $xpath->query('//s:cols')->item(0);
        if (! $columns instanceof DOMElement) {
            $columns = $document->createElementNS(self::MAIN_NS, 'x:cols');
            $sheetData->parentNode?->insertBefore($columns, $sheetData);
        }

        foreach ($widths as $letter => $width) {
            $column = $document->createElementNS(self::MAIN_NS, 'x:col');
            $index = ord($letter) - 64;
            $column->setAttribute('min', (string) $index);
            $column->setAttribute('max', (string) $index);
            $column->setAttribute('width', (string) $width);
            $column->setAttribute('customWidth', '1');
            $columns->appendChild($column);
        }
    }

    private function setSheetDimension(DOMXPath $xpath, int $lastDataRow): void
    {
        $dimension = $xpath->query('//s:dimension')->item(0);
        if ($dimension instanceof DOMElement) {
            $dimension->setAttribute('ref', 'B2:W' . $lastDataRow);
        }
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
