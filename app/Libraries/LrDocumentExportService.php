<?php

namespace App\Libraries;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMXPath;
use RuntimeException;
use ZipArchive;

/** Builds a deviation workbook with system values and auditable total formulas. */
final class LrDocumentExportService
{
    private const TEMPLATE_FILE = 'Resources/Templates/laporan_deviasi_anggaran.xlsx';
    private const SHEET_NAMESPACE = 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';
    private const REL_NAMESPACE = 'http://schemas.openxmlformats.org/package/2006/relationships';
    private const OFFICE_REL_NAMESPACE = 'http://schemas.openxmlformats.org/officeDocument/2006/relationships';
    private const CONTENT_TYPE_NAMESPACE = 'http://schemas.openxmlformats.org/package/2006/content-types';

    /** @return array{path:string,filename:string} */
    public function create(array $units, int $year, int $month, string $basis): array
    {
        $units = $this->validateSelection($units, $year, $month, $basis);
        $report = (new LrRealizationService())->view('Korporat Kanwil', $year, $basis, $month);
        $rkaByUnit = [];
        $budgetService = new RkaBudgetService();
        $calculator = new RkaCalculator();
        foreach ($units as $unit) {
            $record = $budgetService->find($unit, $year);
            $rkaByUnit[$unit] = $record === null
                ? $calculator->calculate($calculator->zeros())
                : json_decode((string) $record['calculated_json'], true, 512, JSON_THROW_ON_ERROR);
        }

        return $this->createFromData(
            $units,
            $year,
            $month,
            $basis,
            $rkaByUnit,
            (array) ($report['values_by_unit'] ?? [])
        );
    }

    /**
     * Testable workbook builder. Both data arrays use the same calculated values
     * already shown by the RKA and Laba/Rugi screens.
     *
     * @return array{path:string,filename:string}
     */
    public function createFromData(
        array $units,
        int $year,
        int $month,
        string $basis,
        array $rkaByUnit,
        array $realizationByUnit
    ): array {
        $units = $this->validateSelection($units, $year, $month, $basis);
        $template = APPPATH . self::TEMPLATE_FILE;
        if (!is_file($template) || !is_readable($template)) {
            throw new RuntimeException('Template Export Dokumen belum tersedia.');
        }

        $directory = WRITEPATH . 'cache/lr_exports';
        if (!is_dir($directory) && !mkdir($directory, 0770, true) && !is_dir($directory)) {
            throw new RuntimeException('Penyimpanan sementara Export Dokumen belum tersedia.');
        }
        $path = $directory . DIRECTORY_SEPARATOR . bin2hex(random_bytes(16)) . '.xlsx';
        if (!copy($template, $path)) throw new RuntimeException('Template Export Dokumen belum dapat disiapkan.');

        try {
            $this->populateWorkbook($path, $units, $year, $rkaByUnit, $realizationByUnit);
        } catch (\Throwable $exception) {
            if (is_file($path)) unlink($path);
            throw $exception;
        }

        $scope = count($units) === count(RkaCalculator::UNITS)
            ? 'SEKANWIL'
            : (count($units) === 1 ? $units[0] : count($units) . '_UNIT');
        $scope = preg_replace('/[^A-Za-z0-9]+/', '_', strtoupper($scope)) ?: 'PILIHAN';
        $monthName = strtoupper($this->monthName($month));

        return [
            'path' => $path,
            'filename' => 'Laporan_Deviasi_' . $basis . '_' . $monthName . '_' . $year . '_' . $scope . '.xlsx',
        ];
    }

    /** @return list<string> */
    private function validateSelection(array $units, int $year, int $month, string $basis): array
    {
        if ($year < 2000 || $year > 2100 || $month < 1 || $month > 12 || !in_array($basis, LrRealizationService::BASES, true)) {
            throw new RuntimeException('Pilih jenis laporan, bulan, dan tahun yang valid.');
        }
        $selected = [];
        foreach (RkaCalculator::UNITS as $unit) {
            if (in_array($unit, $units, true)) $selected[] = $unit;
        }
        if ($selected === [] || count($selected) !== count(array_unique($units))) {
            throw new RuntimeException('Pilih minimal satu unit kerja yang valid.');
        }
        return $selected;
    }

    private function populateWorkbook(
        string $path,
        array $units,
        int $year,
        array $rkaByUnit,
        array $realizationByUnit
    ): void {
        $zip = new ZipArchive();
        if ($zip->open($path) !== true) throw new RuntimeException('Template Export Dokumen tidak dapat dibuka.');
        try {
            $workbook = $this->loadXml($zip, 'xl/workbook.xml');
            $workbookRels = $this->loadXml($zip, 'xl/_rels/workbook.xml.rels');
            $workbookXPath = new DOMXPath($workbook);
            $workbookXPath->registerNamespace('m', self::SHEET_NAMESPACE);
            $workbookXPath->registerNamespace('r', self::OFFICE_REL_NAMESPACE);
            $relsXPath = new DOMXPath($workbookRels);
            $relsXPath->registerNamespace('p', self::REL_NAMESPACE);

            $relationships = [];
            foreach ($relsXPath->query('//p:Relationship') ?: [] as $relationship) {
                if (!$relationship instanceof DOMElement) continue;
                $relationships[$relationship->getAttribute('Id')] = $relationship;
            }

            $selectedSheetNames = array_map('strtoupper', $units);
            $sheetEntries = [];
            foreach ($workbookXPath->query('//m:sheets/m:sheet') ?: [] as $sheetNode) {
                if (!$sheetNode instanceof DOMElement) continue;
                $relationshipId = $sheetNode->getAttributeNS(self::OFFICE_REL_NAMESPACE, 'id');
                $relationship = $relationships[$relationshipId] ?? null;
                if (!$relationship instanceof DOMElement) throw new RuntimeException('Relasi sheet pada template tidak lengkap.');
                $sheetEntries[] = [
                    'name' => $sheetNode->getAttribute('name'),
                    'node' => $sheetNode,
                    'relationship' => $relationship,
                    'path' => $this->zipPath($relationship->getAttribute('Target')),
                ];
            }

            $valuesBySheet = [];
            foreach ($sheetEntries as $entry) {
                $sheetName = strtoupper((string) $entry['name']);
                $unit = $this->unitForSheet($sheetName);
                if ($unit === null || !in_array($sheetName, $selectedSheetNames, true)) continue;
                $valuesBySheet[$sheetName] = $this->valueGrid(
                    (array) ($rkaByUnit[$unit] ?? []),
                    (array) ($realizationByUnit[$unit] ?? [])
                );
            }

            foreach ($sheetEntries as $entry) {
                $sheetName = strtoupper((string) $entry['name']);
                if (!isset($valuesBySheet[$sheetName])) continue;
                $sheetXml = $this->loadXml($zip, (string) $entry['path']);
                $this->populateSheet(
                    $sheetXml,
                    $year,
                    $valuesBySheet[$sheetName],
                    $sheetName,
                    $selectedSheetNames,
                    $valuesBySheet
                );
                $this->removeLegacyDrawing($sheetXml);
                $zip->addFromString((string) $entry['path'], $sheetXml->saveXML());
                $this->stripSheetCommentRelationships($zip, (string) $entry['path']);
            }

            $contentTypes = $this->loadXml($zip, '[Content_Types].xml');
            $contentXPath = new DOMXPath($contentTypes);
            $contentXPath->registerNamespace('ct', self::CONTENT_TYPE_NAMESPACE);
            foreach ($sheetEntries as $entry) {
                if (in_array(strtoupper((string) $entry['name']), $selectedSheetNames, true)) continue;
                $entry['node']->parentNode?->removeChild($entry['node']);
                $entry['relationship']->parentNode?->removeChild($entry['relationship']);
                $zip->deleteName((string) $entry['path']);
                $zip->deleteName($this->sheetRelsPath((string) $entry['path']));
                $partName = '/' . ltrim((string) $entry['path'], '/');
                foreach ($contentXPath->query('//ct:Override[@PartName=' . $this->xpathLiteral($partName) . ']') ?: [] as $node) {
                    $node->parentNode?->removeChild($node);
                }
            }

            foreach ($relsXPath->query('//p:Relationship[contains(@Type,"/person")]') ?: [] as $node) {
                $node->parentNode?->removeChild($node);
            }
            foreach ($contentXPath->query('//ct:Override[contains(@ContentType,"comments") or contains(@ContentType,"person")]') ?: [] as $node) {
                $node->parentNode?->removeChild($node);
            }
            foreach ($workbookXPath->query('//m:workbookView') ?: [] as $view) {
                if ($view instanceof DOMElement) $view->setAttribute('activeTab', '0');
            }
            $calc = $workbookXPath->query('//m:calcPr')?->item(0);
            if (!$calc instanceof DOMElement) {
                $calc = $workbook->createElementNS(self::SHEET_NAMESPACE, 'calcPr');
                $workbook->documentElement?->appendChild($calc);
            }
            $calc->setAttribute('calcMode', 'auto');
            $calc->setAttribute('fullCalcOnLoad', '1');
            $calc->setAttribute('forceFullCalc', '1');

            $this->deleteMatchingParts($zip, '#^xl/comments\d+\.xml$#');
            $this->deleteMatchingParts($zip, '#^xl/drawings/vmldrawing\d*\.vml$#');
            $zip->deleteName('xl/persons/person.xml');
            $zip->addFromString('xl/workbook.xml', $workbook->saveXML());
            $zip->addFromString('xl/_rels/workbook.xml.rels', $workbookRels->saveXML());
            $zip->addFromString('[Content_Types].xml', $contentTypes->saveXML());
        } finally {
            $zip->close();
        }
    }

    private function populateSheet(
        DOMDocument $document,
        int $year,
        array $values,
        string $sheetName,
        array $selectedSheetNames,
        array $valuesBySheet
    ): void {
        $xpath = new DOMXPath($document);
        $xpath->registerNamespace('m', self::SHEET_NAMESPACE);
        $cells = [];
        foreach ($xpath->query('//m:sheetData/m:row/m:c') ?: [] as $cell) {
            if ($cell instanceof DOMElement) $cells[$cell->getAttribute('r')] = $cell;
        }
        // The reference workbook contains old formulas, including cells that
        // look blank until Excel recalculates them. Remove every data value
        // before putting calculated system values into the preserved layout.
        foreach ($cells as $reference => $cell) {
            if (preg_match('/^([F-S])(\d{1,3})$/D', $reference, $matches)
                && (int) $matches[2] >= 9 && (int) $matches[2] <= 176) {
                $this->clearCellValue($cell);
                $cell->removeAttribute('t');
            }
        }
        if (isset($cells['R4'])) {
            $this->clearCellValue($cells['R4']);
            $cells['R4']->removeAttribute('t');
        }
        $this->setInlineString($document, $cells, 'D2', 'LABA RUGI RKA TAHUN ' . $year);

        foreach ($values as $reference => $value) {
            $this->setNumber(
                $document, $cells, $reference, $value,
                $this->formulaForCell($sheetName, $reference, $selectedSheetNames, $valuesBySheet)
            );
        }
    }

    private function valueGrid(array $rka, array $realization): array
    {
        $values = [];
        $rkaColumns = ['F' => 'C', 'G' => 'D', 'H' => 'E', 'I' => 'F', 'J' => 'G', 'K' => 'H'];
        $realizationColumns = [
            'L' => 'KUR', 'M' => 'PEN', 'N' => 'KBG/SURETYSHIP', 'O' => 'KONSUMTIF',
            'P' => 'PRODUKTIF', 'Q' => 'NON KUR', 'R' => 'TOTAL', 'S' => '%',
        ];
        foreach (RkaCalculator::schema()['rows'] as $rkaRow => $definition) {
            $excelRow = (int) $rkaRow + 1;
            foreach ($rkaColumns as $excelColumn => $rkaColumn) {
                $values[$excelColumn . $excelRow] = $rka[$rkaColumn . $rkaRow] ?? '0.00';
            }
            $valueKey = $this->canonicalLabel((string) $definition['label']);
            $rowValues = (array) ($realization[$valueKey] ?? []);
            foreach ($realizationColumns as $excelColumn => $sourceColumn) {
                if (array_key_exists($sourceColumn, $rowValues)) {
                    $values[$excelColumn . $excelRow] = $rowValues[$sourceColumn];
                }
            }
        }
        return $values;
    }

    private function formulaForCell(
        string $sheetName,
        string $reference,
        array $selectedSheetNames,
        array $valuesBySheet
    ): ?string {
        if (!preg_match('/^([F-S])(\\d+)$/D', $reference, $parts)) return null;
        $column = $parts[1];
        $row = $parts[2];

        // Every corporate amount is a total of the six source sheets. Only
        // link sheets that are actually included in this export.
        if ($sheetName === 'KORPORAT KANWIL' && $column !== 'S') {
            $sources = array_map('strtoupper', RkaCalculator::SOURCE_UNITS);
            if (array_diff($sources, $selectedSheetNames) === []) {
                $inputs = array_map(static fn (string $source): array => [$source, $reference], $sources);
                if ($this->sumMatches($sheetName, $reference, $inputs, $valuesBySheet)) {
                    return '=SUM(' . implode(',', array_map(
                        static fn (string $source): string => "'" . $source . "'!" . $reference,
                        $sources
                    )) . ')';
                }
            }
        }

        // The two visible TOTAL columns are the only per-sheet formulas.
        if ($column === 'K') {
            $inputs = array_map(static fn (string $source): array => [$sheetName, $source . $row], ['F','G','H','I','J']);
            if ($this->sumMatches($sheetName, $reference, $inputs, $valuesBySheet)) {
                return '=SUM(F' . $row . ':J' . $row . ')';
            }
        }
        if ($column === 'R') {
            $inputs = array_map(static fn (string $source): array => [$sheetName, $source . $row], ['L','M','Q']);
            if ($this->sumMatches($sheetName, $reference, $inputs, $valuesBySheet)) {
                return '=SUM(L' . $row . ':M' . $row . ',Q' . $row . ')';
            }
        }
        return null;
    }

    /** Keep the cached system value when a proposed total would not reconcile. */
    private function sumMatches(string $sheetName, string $reference, array $inputs, array $valuesBySheet): bool
    {
        if (!isset($valuesBySheet[$sheetName][$reference])) return false;
        $sum = '0.00';
        foreach ($inputs as [$sourceSheet, $sourceReference]) {
            $sum = LrMoney::add($sum, (string) ($valuesBySheet[$sourceSheet][$sourceReference] ?? '0.00'));
        }
        return LrMoney::isZero(LrMoney::subtract(
            $sum,
            (string) $valuesBySheet[$sheetName][$reference]
        ));
    }

    private function setNumber(DOMDocument $document, array $cells, string $reference, mixed $value, ?string $formula = null): void
    {
        $cell = $cells[$reference] ?? null;
        if (!$cell instanceof DOMElement) throw new RuntimeException('Sel template tidak tersedia: ' . $reference . '.');
        $decimal = (string) $value;
        if (!preg_match('/^-?\d+(?:\.\d+)?$/D', $decimal)) throw new RuntimeException('Nominal export tidak valid pada ' . $reference . '.');
        $this->clearCellValue($cell);
        $cell->removeAttribute('t');
        if ($formula !== null) {
            $formulaNode = $document->createElementNS(self::SHEET_NAMESPACE, 'f');
            $formulaNode->appendChild($document->createTextNode(substr($formula, 1)));
            $cell->appendChild($formulaNode);
        }
        $node = $document->createElementNS(self::SHEET_NAMESPACE, 'v');
        $node->appendChild($document->createTextNode($decimal));
        $cell->appendChild($node);
    }

    private function setInlineString(DOMDocument $document, array $cells, string $reference, string $value): void
    {
        $cell = $cells[$reference] ?? null;
        if (!$cell instanceof DOMElement) throw new RuntimeException('Sel template tidak tersedia: ' . $reference . '.');
        $this->clearCellValue($cell);
        $cell->setAttribute('t', 'inlineStr');
        $inline = $document->createElementNS(self::SHEET_NAMESPACE, 'is');
        $text = $document->createElementNS(self::SHEET_NAMESPACE, 't');
        $text->appendChild($document->createTextNode($value));
        $inline->appendChild($text);
        $cell->appendChild($inline);
    }

    private function clearCellValue(DOMElement $cell): void
    {
        foreach (iterator_to_array($cell->childNodes) as $child) {
            if (in_array($child->localName, ['f', 'v', 'is'], true)) $cell->removeChild($child);
        }
    }

    private function removeLegacyDrawing(DOMDocument $document): void
    {
        $xpath = new DOMXPath($document);
        $xpath->registerNamespace('m', self::SHEET_NAMESPACE);
        foreach ($xpath->query('//m:legacyDrawing') ?: [] as $node) $node->parentNode?->removeChild($node);
    }

    private function stripSheetCommentRelationships(ZipArchive $zip, string $sheetPath): void
    {
        $relsPath = $this->sheetRelsPath($sheetPath);
        if ($zip->locateName($relsPath) === false) return;
        $rels = $this->loadXml($zip, $relsPath);
        $xpath = new DOMXPath($rels);
        $xpath->registerNamespace('p', self::REL_NAMESPACE);
        foreach ($xpath->query('//p:Relationship[contains(@Type,"/comments") or contains(@Type,"/vmlDrawing")]') ?: [] as $node) {
            $node->parentNode?->removeChild($node);
        }
        $zip->addFromString($relsPath, $rels->saveXML());
    }

    private function deleteMatchingParts(ZipArchive $zip, string $pattern): void
    {
        $names = [];
        for ($index = 0; $index < $zip->numFiles; $index++) {
            $name = $zip->getNameIndex($index);
            if (is_string($name) && preg_match($pattern, $name)) $names[] = $name;
        }
        foreach ($names as $name) $zip->deleteName($name);
    }

    private function loadXml(ZipArchive $zip, string $path): DOMDocument
    {
        $contents = $zip->getFromName($path);
        if (!is_string($contents)) throw new RuntimeException('Bagian template tidak ditemukan: ' . $path . '.');
        $document = new DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);
        try {
            if (!$document->loadXML($contents, LIBXML_NONET | LIBXML_NOBLANKS)) {
                throw new RuntimeException('Struktur template tidak valid: ' . $path . '.');
            }
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
        return $document;
    }

    private function zipPath(string $target): string
    {
        $target = str_replace('\\', '/', trim($target));
        if (str_starts_with($target, '/')) return ltrim($target, '/');
        return str_starts_with($target, 'xl/') ? $target : 'xl/' . ltrim($target, '/');
    }

    private function sheetRelsPath(string $sheetPath): string
    {
        return dirname($sheetPath) . '/_rels/' . basename($sheetPath) . '.rels';
    }

    private function unitForSheet(string $sheet): ?string
    {
        foreach (RkaCalculator::UNITS as $unit) if (strtoupper($unit) === strtoupper($sheet)) return $unit;
        return null;
    }

    private function canonicalLabel(string $label): string
    {
        $label = OracleLrSalaryParser::normalizeLabel($label);
        $label = str_replace('tranportasi dinas', 'transportasi dinas', $label);
        return str_replace(' - konsultan manajemen (sdm, sop, akuntansi dll)', ' - konsultan manajemen', $label);
    }

    private function xpathLiteral(string $value): string
    {
        if (!str_contains($value, "'")) return "'" . $value . "'";
        if (!str_contains($value, '"')) return '"' . $value . '"';
        $parts = explode("'", $value);
        return 'concat(' . implode(',"\'",', array_map(static fn (string $part): string => "'" . $part . "'", $parts)) . ')';
    }

    private function monthName(int $month): string
    {
        return [1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni',
            7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'][$month];
    }
}
