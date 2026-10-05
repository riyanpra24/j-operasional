<?php

namespace App\Libraries;

use DOMDocument;
use DOMElement;
use DOMXPath;
use RuntimeException;
use ZipArchive;

/** Creates Realisasi Anggaran exports from the approved Excel template. */
final class LrDocumentExportService
{
    private const TEMPLATE_FILE = 'Resources/Templates/realisasi_anggaran_kanwil_surabaya.xlsx';
    private const NS = 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';
    private const REL_NS = 'http://schemas.openxmlformats.org/package/2006/relationships';
    private const OFFICE_REL_NS = 'http://schemas.openxmlformats.org/officeDocument/2006/relationships';

    /** @return array{path:string,filename:string,audit:array{formulas_preserved:int,system_values_checked:int,reconciled_totals:int,broken_template_formulas_removed:int}} */
    public function create(array $units, int $year, int $month, string $basis): array
    {
        $units = $this->validateSelection($units, $year, $month, $basis);
        $report = (new LrRealizationService())->view('Korporat Kanwil', $year, $basis, $month);
        $budgets = new RkaBudgetService();
        $calculator = new RkaCalculator();
        $rka = [];
        foreach ($units as $unit) {
            $record = $budgets->find($unit, $year);
            $rka[$unit] = $record === null ? $calculator->calculate($calculator->zeros())
                : json_decode((string) $record['calculated_json'], true, 512, JSON_THROW_ON_ERROR);
        }
        return $this->createFromData($units, $year, $month, $basis, $rka, (array) ($report['values_by_unit'] ?? []));
    }

    /** @return array{path:string,filename:string,audit:array{formulas_preserved:int,system_values_checked:int,reconciled_totals:int,broken_template_formulas_removed:int}} */
    public function createFromData(array $units, int $year, int $month, string $basis, array $rkaByUnit, array $realizationByUnit): array
    {
        $units = $this->validateSelection($units, $year, $month, $basis);
        $template = APPPATH . self::TEMPLATE_FILE;
        if (!is_file($template) || !is_readable($template)) throw new RuntimeException('Template Realisasi Anggaran belum tersedia.');
        $directory = WRITEPATH . 'cache/lr_exports';
        if (!is_dir($directory) && !mkdir($directory, 0770, true) && !is_dir($directory)) {
            throw new RuntimeException('Penyimpanan sementara Export Dokumen belum tersedia.');
        }
        $path = $directory . DIRECTORY_SEPARATOR . bin2hex(random_bytes(16)) . '.xlsx';
        if (!copy($template, $path)) throw new RuntimeException('Template Realisasi Anggaran belum dapat disiapkan.');
        try {
            $audit = $this->populateWorkbook($path, $year, $rkaByUnit, $realizationByUnit);
            $this->assertReadyForDownload($path);
        } catch (\Throwable $error) {
            if (is_file($path)) unlink($path);
            throw $error;
        }
        return [
            'path' => $path,
            'filename' => 'Realisasi_Anggaran_' . $basis . '_' . strtoupper($this->monthName($month)) . '_' . $year . '_SEKANWIL_' . date('Ymd_His') . '.xlsx',
            'audit' => $audit,
        ];
    }

    /** @return list<string> */
    private function validateSelection(array $units, int $year, int $month, string $basis): array
    {
        if ($year < 2000 || $year > 2100 || $month < 1 || $month > 12 || !in_array($basis, LrRealizationService::BASES, true)) {
            throw new RuntimeException('Pilih jenis laporan, bulan, dan tahun yang valid.');
        }
        $selected = [];
        foreach (RkaCalculator::UNITS as $unit) if (in_array($unit, $units, true)) $selected[] = $unit;
        if (count($selected) !== count(RkaCalculator::UNITS) || count(array_unique($units)) !== count(RkaCalculator::UNITS)) {
            throw new RuntimeException('Template Realisasi Anggaran memuat konsolidasi Korporat Kanwil, sehingga seluruh unit harus diexport bersama.');
        }
        return $selected;
    }

    /** @return array{formulas_preserved:int,system_values_checked:int,reconciled_totals:int,broken_template_formulas_removed:int} */
    private function populateWorkbook(string $path, int $year, array $rkaByUnit, array $realizationByUnit): array
    {
        $zip = new ZipArchive();
        if ($zip->open($path) !== true) throw new RuntimeException('Template Realisasi Anggaran tidak dapat dibuka.');
        try {
            $workbook = $this->loadXml($zip, 'xl/workbook.xml');
            $rels = $this->loadXml($zip, 'xl/_rels/workbook.xml.rels');
            $workbookXPath = new DOMXPath($workbook);
            $workbookXPath->registerNamespace('m', self::NS);
            $workbookXPath->registerNamespace('r', self::OFFICE_REL_NS);
            $relsXPath = new DOMXPath($rels);
            $relsXPath->registerNamespace('r', self::REL_NS);
            $targets = [];
            foreach ($relsXPath->query('//r:Relationship') ?: [] as $relationship) {
                if ($relationship instanceof DOMElement) $targets[$relationship->getAttribute('Id')] = $relationship->getAttribute('Target');
            }
            $sheets = [];
            foreach ($workbookXPath->query('//m:sheets/m:sheet') ?: [] as $sheet) {
                if (!$sheet instanceof DOMElement) continue;
                $relId = $sheet->getAttributeNS(self::OFFICE_REL_NS, 'id');
                if (!isset($targets[$relId])) throw new RuntimeException('Relasi sheet pada template tidak lengkap.');
                $sheets[strtoupper($sheet->getAttribute('name'))] = $this->zipPath($targets[$relId]);
            }
            foreach (RkaCalculator::UNITS as $unit) if (!isset($sheets[strtoupper($unit)])) {
                throw new RuntimeException('Sheet ' . $unit . ' tidak ditemukan pada template Realisasi Anggaran.');
            }
            $values = [];
            foreach (RkaCalculator::UNITS as $unit) {
                $values[strtoupper($unit)] = $this->valueGrid((array) ($rkaByUnit[$unit] ?? []), (array) ($realizationByUnit[$unit] ?? []));
            }
            $audit = [
                'formulas_preserved' => 0,
                'system_values_checked' => 0,
                'reconciled_totals' => $this->auditValueGrids($values),
                'broken_template_formulas_removed' => 0,
            ];
            foreach (RkaCalculator::UNITS as $unit) {
                $key = strtoupper($unit);
                $sheet = $this->loadXml($zip, $sheets[$key]);
                $sheetAudit = $this->populateSheet($sheet, $year, $values[$key]);
                foreach ($sheetAudit as $name => $count) $audit[$name] += $count;
                $zip->addFromString($sheets[$key], $sheet->saveXML());
            }
            $calc = $workbookXPath->query('//m:calcPr')?->item(0);
            if (!$calc instanceof DOMElement) {
                $calc = $workbook->createElementNS(self::NS, 'calcPr');
                $workbook->documentElement?->appendChild($calc);
            }
            $calc->setAttribute('calcMode', 'auto');
            $calc->setAttribute('fullCalcOnLoad', '1');
            $calc->setAttribute('forceFullCalc', '1');
            $this->discardStaleCalculationChain($zip, $rels);
            $zip->addFromString('xl/workbook.xml', $workbook->saveXML());
            return $audit;
        } finally {
            $zip->close();
        }
    }

    /** @return array{formulas_preserved:int,system_values_checked:int,broken_template_formulas_removed:int} */
    private function populateSheet(DOMDocument $document, int $year, array $values): array
    {
        $xpath = new DOMXPath($document);
        $xpath->registerNamespace('m', self::NS);
        $cells = [];
        foreach ($xpath->query('//m:sheetData/m:row/m:c') ?: [] as $cell) if ($cell instanceof DOMElement) $cells[$cell->getAttribute('r')] = $cell;
        $formulas = [];
        $removed = 0;
        foreach ($cells as $reference => $cell) {
            $formula = $this->formulaText($cell);
            if ($formula === null) continue;
            if (str_contains($formula, '#REF!')) {
                $this->clearCellValue($cell);
                $cell->removeAttribute('t');
                $removed++;
                continue;
            }
            $formulas[$reference] = $formula;
            $this->clearCachedValue($cell);
        }
        $this->setInlineString($document, $cells, 'D2', 'LABA RUGI RKA TAHUN ' . $year);
        $checked = 0;
        foreach ($values as $reference => $value) {
            $cell = $cells[$reference] ?? null;
            if (!$cell instanceof DOMElement) throw new RuntimeException('Sel template tidak tersedia: ' . $reference . '.');
            if ($this->formulaText($cell) === null) $this->setNumber($document, $cells, $reference, $value);
            else $this->setCachedNumber($document, $cell, $reference, $value);
            $checked++;
        }
        foreach ($formulas as $reference => $formula) {
            if (($cells[$reference] ?? null) instanceof DOMElement && $this->formulaText($cells[$reference]) !== $formula) {
                throw new RuntimeException('Rumus template berubah pada ' . $reference . '. Export dibatalkan.');
            }
        }
        return ['formulas_preserved' => count($formulas), 'system_values_checked' => $checked, 'broken_template_formulas_removed' => $removed];
    }

    /** @return array<string,string> */
    private function valueGrid(array $rka, array $realization): array
    {
        $values = [];
        $rkaColumns = ['F' => 'C', 'G' => 'D', 'H' => 'E', 'I' => 'F', 'J' => 'G', 'K' => 'H'];
        $actualColumns = ['L' => 'KUR', 'M' => 'PEN', 'N' => 'NON KUR'];
        foreach (RkaCalculator::schema()['rows'] as $row => $definition) {
            foreach ($rkaColumns as $target => $source) $values[$target . $row] = (string) ($rka[$source . $row] ?? '0.00');
            $actual = (array) ($realization[$this->canonicalLabel((string) $definition['label'])] ?? []);
            foreach ($actualColumns as $target => $source) $values[$target . $row] = (string) ($actual[$source] ?? '0.00');
            $actualTotal = (string) ($actual['TOTAL'] ?? LrMoney::add(
                LrMoney::add($values['L' . $row], $values['M' . $row]), $values['N' . $row]
            ));
            $values['O' . $row] = $actualTotal;
            $values['P' . $row] = LrMoney::isZero($values['K' . $row]) ? '0.00'
                : (LrMoney::divide($actualTotal, $values['K' . $row], 18) ?? '0.00');
        }
        return $values;
    }

    private function auditValueGrids(array $valuesBySheet): int
    {
        $checked = 0;
        foreach ($valuesBySheet as $sheet => $values) {
            foreach (array_keys(RkaCalculator::schema()['rows']) as $row) {
                $budget = '0.00'; foreach (['F','G','H','I','J'] as $column) $budget = LrMoney::add($budget, $values[$column . $row] ?? '0.00');
                $this->assertSameAmount($budget, $values['K' . $row] ?? '0.00', $sheet . '!K' . $row); $checked++;
                $actual = '0.00'; foreach (['L','M','N'] as $column) $actual = LrMoney::add($actual, $values[$column . $row] ?? '0.00');
                $this->assertSameAmount($actual, $values['O' . $row] ?? '0.00', $sheet . '!O' . $row); $checked++;
                $percentage = LrMoney::isZero($values['K' . $row] ?? '0.00') ? '0.00' : (LrMoney::divide($values['O' . $row] ?? '0.00', $values['K' . $row], 18) ?? '0.00');
                $this->assertSameAmount($percentage, $values['P' . $row] ?? '0.00', $sheet . '!P' . $row, '0.0000001'); $checked++;
            }
        }
        foreach ($valuesBySheet['KORPORAT KANWIL'] ?? [] as $reference => $corporate) {
            if (!preg_match('/^[F-O]\d+$/D', $reference)) continue;
            $sum = '0.00';
            foreach (RkaCalculator::SOURCE_UNITS as $source) $sum = LrMoney::add($sum, (string) ($valuesBySheet[strtoupper($source)][$reference] ?? '0.00'));
            $this->assertSameAmount($sum, (string) $corporate, 'KORPORAT KANWIL!' . $reference); $checked++;
        }
        return $checked;
    }

    private function assertSameAmount(string $expected, string $actual, string $reference, string $tolerance = '0.01'): void
    {
        $difference = LrMoney::subtract($expected, $actual);
        if (str_starts_with($difference, '-')) $difference = LrMoney::negate($difference);
        $scale = max(strlen(explode('.', $difference)[1] ?? ''), strlen(explode('.', $tolerance)[1] ?? ''), 2);
        if (bccomp($difference, $tolerance, $scale) === 1) throw new RuntimeException('Audit export menemukan selisih pada ' . $reference . '. Periksa data RKA atau realisasi sebelum export.');
    }

    private function setNumber(DOMDocument $document, array $cells, string $reference, mixed $value): void
    {
        $cell = $cells[$reference] ?? null;
        if (!$cell instanceof DOMElement) throw new RuntimeException('Sel template tidak tersedia: ' . $reference . '.');
        $this->clearCellValue($cell); $cell->removeAttribute('t');
        $node = $document->createElementNS(self::NS, 'v'); $node->appendChild($document->createTextNode($this->decimal($value, $reference))); $cell->appendChild($node);
    }

    private function setCachedNumber(DOMDocument $document, DOMElement $cell, string $reference, mixed $value): void
    {
        $this->clearCachedValue($cell); $cell->removeAttribute('t');
        $node = $document->createElementNS(self::NS, 'v'); $node->appendChild($document->createTextNode($this->decimal($value, $reference))); $cell->appendChild($node);
    }

    private function decimal(mixed $value, string $reference): string
    {
        $decimal = (string) $value;
        if (!preg_match('/^-?\d+(?:\.\d+)?$/D', $decimal)) throw new RuntimeException('Nominal export tidak valid pada ' . $reference . '.');
        return $decimal;
    }

    private function setInlineString(DOMDocument $document, array $cells, string $reference, string $value): void
    {
        $cell = $cells[$reference] ?? null;
        if (!$cell instanceof DOMElement) throw new RuntimeException('Sel template tidak tersedia: ' . $reference . '.');
        $this->clearCellValue($cell); $cell->setAttribute('t', 'inlineStr');
        $inline = $document->createElementNS(self::NS, 'is'); $text = $document->createElementNS(self::NS, 't');
        $text->appendChild($document->createTextNode($value)); $inline->appendChild($text); $cell->appendChild($inline);
    }

    private function clearCellValue(DOMElement $cell): void
    {
        foreach (iterator_to_array($cell->childNodes) as $child) if (in_array($child->localName, ['f','v','is'], true)) $cell->removeChild($child);
    }

    private function clearCachedValue(DOMElement $cell): void
    {
        foreach (iterator_to_array($cell->childNodes) as $child) if (in_array($child->localName, ['v','is'], true)) $cell->removeChild($child);
    }

    private function formulaText(DOMElement $cell): ?string
    {
        $formula = $cell->getElementsByTagNameNS(self::NS, 'f')->item(0);
        return $formula instanceof DOMElement ? $formula->textContent : null;
    }

    /**
     * The template's calculation chain only describes its original cells. Once
     * values and cached formula results are updated, retaining it makes Excel
     * repair the exported workbook. Excel will generate a new chain on open.
     */
    private function discardStaleCalculationChain(ZipArchive $zip, DOMDocument $relationships): void
    {
        $xpath = new DOMXPath($relationships);
        $xpath->registerNamespace('r', self::REL_NS);
        foreach ($xpath->query('//r:Relationship[contains(@Type, \'/calcChain\')]') ?: [] as $relationship) {
            $relationship->parentNode?->removeChild($relationship);
        }

        $contentTypes = $this->loadXml($zip, '[Content_Types].xml');
        $contentTypesXPath = new DOMXPath($contentTypes);
        foreach ($contentTypesXPath->query('//*[local-name() = \'Override\' and @PartName = \'/xl/calcChain.xml\']') ?: [] as $override) {
            $override->parentNode?->removeChild($override);
        }

        $zip->deleteName('xl/calcChain.xml');
        $zip->addFromString('xl/_rels/workbook.xml.rels', $relationships->saveXML());
        $zip->addFromString('[Content_Types].xml', $contentTypes->saveXML());
    }

    /** Refuse to deliver a workbook that still contains a stale calculation chain. */
    private function assertReadyForDownload(string $path): void
    {
        $zip = new ZipArchive();
        if ($zip->open($path, ZipArchive::CHECKCONS) !== true) {
            throw new RuntimeException('File Excel hasil export tidak valid.');
        }

        try {
            $relationships = $zip->getFromName('xl/_rels/workbook.xml.rels') ?: '';
            $contentTypes = $zip->getFromName('[Content_Types].xml') ?: '';
            if ($zip->locateName('xl/calcChain.xml') !== false
                || str_contains($relationships, '/calcChain')
                || str_contains($contentTypes, '/xl/calcChain.xml')) {
                throw new RuntimeException('File Excel hasil export masih memuat rantai perhitungan lama.');
            }
        } finally {
            $zip->close();
        }
    }

    private function loadXml(ZipArchive $zip, string $path): DOMDocument
    {
        $contents = $zip->getFromName($path);
        if (!is_string($contents)) throw new RuntimeException('Bagian template tidak ditemukan: ' . $path . '.');
        $document = new DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);
        try { if (!$document->loadXML($contents, LIBXML_NONET | LIBXML_NOBLANKS)) throw new RuntimeException('Struktur template tidak valid: ' . $path . '.'); }
        finally { libxml_clear_errors(); libxml_use_internal_errors($previous); }
        return $document;
    }

    private function zipPath(string $target): string
    {
        $target = str_replace('\\', '/', trim($target));
        return str_starts_with($target, '/') ? ltrim($target, '/') : (str_starts_with($target, 'xl/') ? $target : 'xl/' . ltrim($target, '/'));
    }

    private function canonicalLabel(string $label): string
    {
        $label = OracleLrSalaryParser::normalizeLabel($label);
        $label = str_replace('tranportasi dinas', 'transportasi dinas', $label);
        return str_replace(' - konsultan manajemen (sdm, sop, akuntansi dll)', ' - konsultan manajemen', $label);
    }

    private function monthName(int $month): string
    {
        return [1=>'Januari',2=>'Februari',3=>'Maret',4=>'April',5=>'Mei',6=>'Juni',7=>'Juli',8=>'Agustus',9=>'September',10=>'Oktober',11=>'November',12=>'Desember'][$month];
    }
}
