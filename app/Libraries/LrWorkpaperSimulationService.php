<?php

namespace App\Libraries;

use DOMDocument;
use DOMElement;
use DOMXPath;
use RuntimeException;
use Throwable;
use ZipArchive;

/** Fills a copy of the approved workpaper without rewriting its formulas or layout. */
final class LrWorkpaperSimulationService
{
    private const TEMPLATE = APPPATH . 'Resources/Templates/kertas_kerja_realisasi_rka_2026.xlsx';
    private const XML_NS = 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';
    private const REL_NS = 'http://schemas.openxmlformats.org/package/2006/relationships';
    private const OFFICE_REL_NS = 'http://schemas.openxmlformats.org/officeDocument/2006/relationships';
    private const HELPER_COLUMNS = [
        'KUR' => ['W', 'X', 'Y'],
        'NON KUR' => ['AA', 'AB', 'AC'],
        'PEN' => ['AE', 'AF', 'AG'],
    ];

    /** @return array<string,mixed> */
    public function create(string $oraclePath, string $sourceName, int $year, int $month, string $basis, int $userId, string $userName): array
    {
        if ($year < 2000 || $year > 2100 || $month < 1 || $month > 12 || !in_array($basis, LrRealizationService::BASES, true)) {
            throw new RuntimeException('Pilih jenis laporan, bulan, dan tahun yang valid.');
        }
        if ($userId <= 0) throw new RuntimeException('Sesi pengguna tidak valid. Masuk kembali ke aplikasi.');

        $parser = new OracleLrSalaryParser();
        $parsed = [];
        foreach (OracleLrSalaryParser::IMPORT_UNITS as $unit) {
            $result = $parser->parse($oraclePath, $unit, true);
            if ((int) $result['year'] !== $year || (int) $result['month'] !== $month) {
                throw new RuntimeException('Periode sheet ' . $result['sheet'] . ' tidak sama dengan bulan dan tahun yang dipilih.');
            }
            foreach ($result['unmapped'] as $row) {
                if (($row['reason'] ?? '') === 'lob') {
                    throw new RuntimeException('LOB pada sheet ' . $result['sheet'] . ' baris ' . $row['row'] . ' belum dikenal. Perbaiki mapping sebelum simulasi agar tidak ada data yang tertinggal.');
                }
            }
            foreach (self::HELPER_COLUMNS as $lob => $_) {
                if (count($result['helper_rows'][$lob] ?? []) > 242) {
                    throw new RuntimeException('Sheet ' . $result['sheet'] . ' memiliki lebih dari 242 baris ' . $lob . '. Rentang rumus template hanya sampai baris 249; data tidak akan dipotong.');
                }
            }
            $parsed[$unit] = $result;
        }

        $budgetService = new RkaBudgetService();
        $calculator = new RkaCalculator();
        $rka = [];
        $missingRka = [];
        foreach (RkaCalculator::SOURCE_UNITS as $unit) {
            $record = $budgetService->find($unit, $year);
            if ($record === null) $missingRka[] = $unit;
            $rka[$unit] = $record === null
                ? $calculator->calculate($calculator->zeros())
                : json_decode((string) $record['calculated_json'], true, 512, JSON_THROW_ON_ERROR);
        }
        $periods = array_fill_keys(RkaCalculator::SOURCE_UNITS, ['year' => $year, 'month' => $month]);
        $realization = (new LrRealizationCalculator())->calculate($parsed, $rka, $periods);

        $directory = $this->directory();
        $id = bin2hex(random_bytes(16));
        $path = $directory . DIRECTORY_SEPARATOR . $id . '.xlsx';
        if (!copy(self::TEMPLATE, $path)) throw new RuntimeException('Template kertas kerja belum dapat disiapkan.');
        try {
            $this->populate($path, $parsed, $rka, $realization, $year, $basis, $month);
            $totalRows = 0;
            $unmappedAccounts = 0;
            foreach ($parsed as $result) {
                foreach (self::HELPER_COLUMNS as $lob => $_) $totalRows += count($result['helper_rows'][$lob] ?? []);
                foreach ($result['unmapped'] as $row) if (($row['reason'] ?? '') !== 'lob') $unmappedAccounts++;
            }
            $metadata = [
                'id' => $id,
                'source_name' => mb_substr(basename($sourceName), 0, 255),
                'source_hash' => hash_file('sha256', $oraclePath),
                'report_year' => $year,
                'report_month' => $month,
                'report_basis' => $basis,
                'helper_rows' => $totalRows,
                'unmapped_accounts' => $unmappedAccounts,
                'missing_rka' => $missingRka,
                'created_by_id' => $userId,
                'created_by_name' => mb_substr($userName, 0, 150),
                'created_at' => date('Y-m-d H:i:s'),
                'filename' => 'Kertas_Kerja_' . $basis . '_' . self::monthName($month) . '_' . $year . '.xlsx',
            ];
            $metaPath = $directory . DIRECTORY_SEPARATOR . $id . '.json';
            if (file_put_contents($metaPath, json_encode($metadata, JSON_THROW_ON_ERROR), LOCK_EX) === false) {
                throw new RuntimeException('Riwayat hasil simulasi belum berhasil disimpan.');
            }
            return $metadata;
        } catch (Throwable $e) {
            if (is_file($path)) unlink($path);
            throw $e;
        }
    }

    /** @return list<array<string,mixed>> */
    public function history(int $userId, bool $isAdmin): array
    {
        $records = [];
        foreach (glob($this->directory() . DIRECTORY_SEPARATOR . '*.json') ?: [] as $path) {
            $raw = file_get_contents($path);
            if ($raw === false) continue;
            try { $row = json_decode($raw, true, 512, JSON_THROW_ON_ERROR); }
            catch (Throwable) { continue; }
            if (!is_array($row) || !preg_match('/^[a-f0-9]{32}$/D', (string) ($row['id'] ?? ''))) continue;
            if (!$isAdmin && (int) ($row['created_by_id'] ?? 0) !== $userId) continue;
            if (!is_file($this->directory() . DIRECTORY_SEPARATOR . $row['id'] . '.xlsx')) continue;
            $records[] = $row;
        }
        usort($records, static fn (array $a, array $b): int => strcmp((string) $b['created_at'], (string) $a['created_at']) ?: strcmp((string) $b['id'], (string) $a['id']));
        return $records;
    }

    /** @return array{path:string,filename:string} */
    public function download(string $id, int $userId, bool $isAdmin): array
    {
        if (!preg_match('/^[a-f0-9]{32}$/D', $id)) throw new RuntimeException('Hasil simulasi tidak ditemukan.');
        $base = $this->directory() . DIRECTORY_SEPARATOR . $id;
        if (!is_file($base . '.json') || !is_file($base . '.xlsx')) throw new RuntimeException('Berkas hasil simulasi tidak ditemukan.');
        $row = json_decode((string) file_get_contents($base . '.json'), true, 512, JSON_THROW_ON_ERROR);
        if (!$isAdmin && (int) ($row['created_by_id'] ?? 0) !== $userId) throw new RuntimeException('Anda tidak memiliki akses ke hasil simulasi ini.');
        $this->removeRedHighlightsFromWorkbook($base . '.xlsx');
        return ['path' => $base . '.xlsx', 'filename' => (string) $row['filename']];
    }

    public function delete(string $id, int $userId, bool $isAdmin): void
    {
        if (!preg_match('/^[a-f0-9]{32}$/D', $id)) throw new RuntimeException('Hasil simulasi tidak ditemukan.');
        $base = $this->directory() . DIRECTORY_SEPARATOR . $id;
        $metadataPath = $base . '.json';
        $workbookPath = $base . '.xlsx';
        if (!is_file($metadataPath) || !is_file($workbookPath)) throw new RuntimeException('Hasil simulasi tidak ditemukan.');
        $row = json_decode((string) file_get_contents($metadataPath), true, 512, JSON_THROW_ON_ERROR);
        if (!$isAdmin && (int) ($row['created_by_id'] ?? 0) !== $userId) {
            throw new RuntimeException('Anda tidak memiliki akses untuk menghapus hasil simulasi ini.');
        }
        $pendingWorkbook = $workbookPath . '.deleting';
        $pendingMetadata = $metadataPath . '.deleting';
        if (!rename($workbookPath, $pendingWorkbook)) {
            throw new RuntimeException('Hasil simulasi belum dapat dihapus.');
        }
        if (!rename($metadataPath, $pendingMetadata)) {
            rename($pendingWorkbook, $workbookPath);
            throw new RuntimeException('Hasil simulasi belum dapat dihapus.');
        }
        if (!unlink($pendingWorkbook) || !unlink($pendingMetadata)) {
            throw new RuntimeException('Hasil simulasi belum dapat dihapus.');
        }
    }

    /** Public seam for workbook regression tests, without writes to application data. */
    public function populate(string $path, array $parsedByUnit, array $rkaByUnit, array $realizationByUnit, int $year, string $basis = 'YTD', int $month = 1): void
    {
        $zip = new ZipArchive();
        if ($zip->open($path) !== true) throw new RuntimeException('Template kertas kerja tidak dapat dibuka.');
        try {
            $workbook = $this->xml($zip, 'xl/workbook.xml');
            $rels = $this->xml($zip, 'xl/_rels/workbook.xml.rels');
            $w = new DOMXPath($workbook);
            $w->registerNamespace('m', self::XML_NS);
            $relationships = [];
            foreach ($rels->getElementsByTagNameNS(self::REL_NS, 'Relationship') as $relationship) {
                $relationships[$relationship->getAttribute('Id')] = $relationship->getAttribute('Target');
            }
            $redStyles = $this->redStyleIndexes($zip);
            $found = [];
            foreach ($w->query('//m:sheets/m:sheet') ?: [] as $sheet) {
                if (!$sheet instanceof DOMElement) continue;
                $name = strtoupper($sheet->getAttribute('name'));
                $id = $sheet->getAttributeNS(self::OFFICE_REL_NS, 'id');
                $target = (string) ($relationships[$id] ?? '');
                $target = ltrim($target, '/');
                if (str_starts_with($target, 'xl/')) $target = substr($target, 3);
                if (!preg_match('~^worksheets/sheet\d+\.xml$~D', $target)) throw new RuntimeException('Relasi sheet template tidak valid.');
                $unit = $name === 'KORPORAT KANWIL' ? 'Korporat Kanwil' : ucfirst(strtolower($name));
                if (!in_array($unit, RkaCalculator::UNITS, true)) throw new RuntimeException('Sheet template tidak dikenal: ' . $name . '.');
                $found[$unit] = true;
                $entry = 'xl/' . $target;
                $document = $this->xml($zip, $entry);
                $this->populateSheet($document, $unit, $parsedByUnit[$unit] ?? null, (array) ($rkaByUnit[$unit] ?? []), (array) ($realizationByUnit[$unit] ?? []), $year, $basis, $month, $redStyles);
                if (!$zip->addFromString($entry, $document->saveXML())) throw new RuntimeException('Sheet ' . $name . ' belum dapat disimpan.');
            }
            foreach (RkaCalculator::UNITS as $unit) if (!isset($found[$unit])) throw new RuntimeException('Sheet template ' . $unit . ' tidak ditemukan.');
            $calc = $w->query('//m:calcPr')?->item(0);
            if (!$calc instanceof DOMElement) {
                $calc = $workbook->createElementNS(self::XML_NS, 'calcPr');
                $workbook->documentElement?->appendChild($calc);
            }
            $calc->setAttribute('calcMode', 'auto');
            $calc->setAttribute('calcId', '0');
            $calc->setAttribute('fullCalcOnLoad', '1');
            $calc->setAttribute('forceFullCalc', '1');
            if (!$zip->addFromString('xl/workbook.xml', $workbook->saveXML())) throw new RuntimeException('Pengaturan hitung Excel belum dapat disimpan.');
        } finally {
            $zip->close();
        }
    }

    private function populateSheet(DOMDocument $document, string $unit, ?array $parsed, array $rka, array $realization, int $year, string $basis, int $month, array $redStyles): void
    {
        $xpath = new DOMXPath($document);
        $xpath->registerNamespace('m', self::XML_NS);
        $sheetData = $xpath->query('//m:sheetData')?->item(0);
        if (!$sheetData instanceof DOMElement) throw new RuntimeException('Isi sheet template tidak lengkap.');
        $cells = [];
        $rows = [];
        foreach ($xpath->query('//m:sheetData/m:row') ?: [] as $row) {
            if (!$row instanceof DOMElement) continue;
            $rows[(int) $row->getAttribute('r')] = $row;
            foreach ($row->childNodes as $cell) if ($cell instanceof DOMElement && $cell->localName === 'c') $cells[$cell->getAttribute('r')] = $cell;
        }

        foreach ($cells as $reference => $cell) {
            [$column, $row] = $this->splitReference($reference);
            if ($this->hasFormula($cell)) {
                // Cached values belong to the old period. Excel recalculates unchanged formulas on open.
                $this->clearChildren($cell, ['v']);
                $cell->removeAttribute('t');
                continue;
            }
            if ($unit !== 'Korporat Kanwil' && $row >= 7 && $row <= 163 && in_array($column, ['L', 'M', 'N', 'O', 'P', 'Q'], true)) {
                $value = $this->cellValue($cell);
                if ($value !== null && is_numeric($value) && (float) $value !== 0.0) $this->clearChildren($cell, ['v', 'is']);
            }
            if ($unit !== 'Korporat Kanwil' && $row >= 8 && $row <= 249 && $this->isHelperColumn($column)) {
                $this->clearChildren($cell, ['v', 'is']);
                $cell->removeAttribute('t');
            }
        }
        $this->removeRedHighlights($cells, $redStyles);
        $this->setString($document, $sheetData, $rows, $cells, 'D2', 'LABA RUGI RKA TAHUN ' . $year);
        // A compact provenance marker lets Laba/Rugi verify that this workbook was produced by Simulasi Hitung.
        $this->setString($document, $sheetData, $rows, $cells, 'W1', 'SIMULASI_LR|' . $basis . '|' . $year . '|' . $month);
        if ($unit === 'Korporat Kanwil') return;
        if ($parsed === null) throw new RuntimeException('Data Oracle untuk ' . $unit . ' tidak tersedia.');

        foreach (array_keys(RkaCalculator::schema()['rows']) as $templateRow) {
            $workpaperRow = (int) $templateRow - 1;
            foreach (['F' => 'C', 'G' => 'D', 'H' => 'E', 'I' => 'F', 'J' => 'G'] as $outputColumn => $sourceColumn) {
                $reference = $outputColumn . $workpaperRow;
                if (isset($cells[$reference]) && $this->hasFormula($cells[$reference])) continue;
                $amount = (string) ($rka[$sourceColumn . $templateRow] ?? '0');
                $this->setNumber($document, $sheetData, $rows, $cells, $reference, $amount);
            }
        }

        $otherIncome = $realization[OracleLrSalaryParser::normalizeLabel('PENDAPATAN (BEBAN) LAIN-LAIN BERSIH')] ?? [];
        foreach (['L' => 'KUR', 'M' => 'PEN', 'N' => 'NON KUR'] as $column => $lob) {
            if (isset($otherIncome[$lob])) $this->setNumber($document, $sheetData, $rows, $cells, $column . '163', (string) $otherIncome[$lob]);
        }

        foreach (self::HELPER_COLUMNS as $lob => [$descriptionColumn, $lobColumn, $balanceColumn]) {
            foreach ($parsed['helper_rows'][$lob] ?? [] as $index => $row) {
                $number = $index + 8;
                $this->setString($document, $sheetData, $rows, $cells, $descriptionColumn . $number, (string) $row['description']);
                $this->setString($document, $sheetData, $rows, $cells, $lobColumn . $number, (string) $row['description_lob']);
                $this->setNumber($document, $sheetData, $rows, $cells, $balanceColumn . $number, (string) $row['amount']);
            }
        }
    }

    /** @return array<string,true> */
    private function redStyleIndexes(ZipArchive $zip): array
    {
        $styles = $this->xml($zip, 'xl/styles.xml');
        $xpath = new DOMXPath($styles);
        $xpath->registerNamespace('m', self::XML_NS);
        $redFills = [];
        foreach ($xpath->query('//m:fills/m:fill') ?: [] as $index => $fill) {
            if (!$fill instanceof DOMElement) continue;
            $color = $xpath->query('./m:patternFill/m:fgColor', $fill)?->item(0);
            if (!$color instanceof DOMElement) continue;
            $rgb = strtoupper($color->getAttribute('rgb'));
            if (in_array($rgb, ['FFFF0000', '00FF0000'], true)) $redFills[(string) $index] = true;
        }
        $redStyles = [];
        foreach ($xpath->query('//m:cellXfs/m:xf') ?: [] as $index => $xf) {
            if ($xf instanceof DOMElement && isset($redFills[$xf->getAttribute('fillId')])) $redStyles[(string) $index] = true;
        }
        return $redStyles;
    }

    /** @param array<string,DOMElement> $cells @param array<string,true> $redStyles */
    private function removeRedHighlights(array $cells, array $redStyles): bool
    {
        $changed = false;
        foreach ($cells as $reference => $cell) {
            if (!isset($redStyles[$cell->getAttribute('s')]) || !preg_match('/^([A-Z]+)([1-9]\d*)$/D', $reference, $parts)) continue;
            $referenceStyle = $cells[$parts[1] . ((int) $parts[2] - 1)] ?? null;
            if (!$referenceStyle instanceof DOMElement || !$referenceStyle->hasAttribute('s')) continue;
            $cell->setAttribute('s', $referenceStyle->getAttribute('s'));
            $changed = true;
        }
        return $changed;
    }

    private function removeRedHighlightsFromWorkbook(string $path): void
    {
        $zip = new ZipArchive();
        if ($zip->open($path) !== true) throw new RuntimeException('Berkas hasil simulasi tidak dapat dibuka.');
        try {
            $redStyles = $this->redStyleIndexes($zip);
            if ($redStyles === []) return;
            $workbook = $this->xml($zip, 'xl/workbook.xml');
            $rels = $this->xml($zip, 'xl/_rels/workbook.xml.rels');
            $xpath = new DOMXPath($workbook);
            $xpath->registerNamespace('m', self::XML_NS);
            $relationships = [];
            foreach ($rels->getElementsByTagNameNS(self::REL_NS, 'Relationship') as $relationship) {
                $relationships[$relationship->getAttribute('Id')] = $relationship->getAttribute('Target');
            }
            foreach ($xpath->query('//m:sheets/m:sheet') ?: [] as $sheet) {
                if (!$sheet instanceof DOMElement) continue;
                $id = $sheet->getAttributeNS(self::OFFICE_REL_NS, 'id');
                $target = ltrim((string) ($relationships[$id] ?? ''), '/');
                if (str_starts_with($target, 'xl/')) $target = substr($target, 3);
                if (!preg_match('~^worksheets/sheet\d+\.xml$~D', $target)) continue;
                $entry = 'xl/' . $target;
                $document = $this->xml($zip, $entry);
                $sheetXpath = new DOMXPath($document);
                $sheetXpath->registerNamespace('m', self::XML_NS);
                $cells = [];
                foreach ($sheetXpath->query('//m:sheetData/m:row/m:c') ?: [] as $cell) {
                    if ($cell instanceof DOMElement) $cells[$cell->getAttribute('r')] = $cell;
                }
                if ($this->removeRedHighlights($cells, $redStyles)
                    && !$zip->addFromString($entry, $document->saveXML())) {
                    throw new RuntimeException('Format hasil simulasi belum dapat diperbarui.');
                }
            }
        } finally {
            $zip->close();
        }
    }

    private function setString(DOMDocument $document, DOMElement $sheetData, array &$rows, array &$cells, string $reference, string $value): void
    {
        $cell = $this->cell($document, $sheetData, $rows, $cells, $reference);
        if ($this->hasFormula($cell)) throw new RuntimeException('Input template ' . $reference . ' berisi rumus yang tidak boleh ditimpa.');
        $this->clearChildren($cell, ['v', 'is']);
        $cell->setAttribute('t', 'inlineStr');
        $inline = $document->createElementNS(self::XML_NS, 'is');
        $inline->appendChild($document->createElementNS(self::XML_NS, 't', $value));
        $cell->appendChild($inline);
    }

    private function setNumber(DOMDocument $document, DOMElement $sheetData, array &$rows, array &$cells, string $reference, string $value): void
    {
        if (!preg_match('/^-?(?:0|[1-9]\d*)(?:\.\d+)?$/D', $value)) throw new RuntimeException('Nominal tidak valid untuk ' . $reference . '.');
        $cell = $this->cell($document, $sheetData, $rows, $cells, $reference);
        if ($this->hasFormula($cell)) throw new RuntimeException('Input template ' . $reference . ' berisi rumus yang tidak boleh ditimpa.');
        $this->clearChildren($cell, ['v', 'is']);
        $cell->setAttribute('t', 'n');
        $cell->appendChild($document->createElementNS(self::XML_NS, 'v', $value));
    }

    private function cell(DOMDocument $document, DOMElement $sheetData, array &$rows, array &$cells, string $reference): DOMElement
    {
        if (isset($cells[$reference])) return $cells[$reference];
        [$column, $rowNumber] = $this->splitReference($reference);
        if (!isset($rows[$rowNumber])) {
            $row = $document->createElementNS(self::XML_NS, 'row');
            $row->setAttribute('r', (string) $rowNumber);
            $next = null;
            foreach ($rows as $existingNumber => $existingRow) if ($existingNumber > $rowNumber && ($next === null || $existingNumber < $next->number)) $next = (object) ['number' => $existingNumber, 'node' => $existingRow];
            $sheetData->insertBefore($row, $next->node ?? null);
            $rows[$rowNumber] = $row;
        }
        $row = $rows[$rowNumber];
        $cell = $document->createElementNS(self::XML_NS, 'c');
        $cell->setAttribute('r', $reference);
        $styleReference = $column . '8';
        if (isset($cells[$styleReference]) && $cells[$styleReference]->hasAttribute('s')) $cell->setAttribute('s', $cells[$styleReference]->getAttribute('s'));
        $next = null;
        $targetIndex = $this->columnIndex($column);
        foreach ($row->childNodes as $candidate) {
            if (!$candidate instanceof DOMElement || $candidate->localName !== 'c') continue;
            [$candidateColumn] = $this->splitReference($candidate->getAttribute('r'));
            if ($this->columnIndex($candidateColumn) > $targetIndex) { $next = $candidate; break; }
        }
        $row->insertBefore($cell, $next);
        $cells[$reference] = $cell;
        return $cell;
    }

    private function splitReference(string $reference): array
    {
        if (!preg_match('/^([A-Z]{1,3})([1-9]\d*)$/D', $reference, $parts)) throw new RuntimeException('Alamat sel template tidak valid.');
        return [$parts[1], (int) $parts[2]];
    }

    private function columnIndex(string $letters): int
    {
        $number = 0;
        foreach (str_split($letters) as $letter) $number = $number * 26 + ord($letter) - 64;
        return $number;
    }

    private function isHelperColumn(string $column): bool
    {
        foreach (self::HELPER_COLUMNS as $columns) if (in_array($column, $columns, true)) return true;
        return false;
    }

    private function hasFormula(DOMElement $cell): bool
    {
        foreach ($cell->childNodes as $child) if ($child instanceof DOMElement && $child->localName === 'f') return true;
        return false;
    }

    private function cellValue(DOMElement $cell): ?string
    {
        foreach ($cell->childNodes as $child) if ($child instanceof DOMElement && $child->localName === 'v') return $child->textContent;
        return null;
    }

    private function clearChildren(DOMElement $cell, array $names): void
    {
        foreach (iterator_to_array($cell->childNodes) as $child) {
            if ($child instanceof DOMElement && in_array($child->localName, $names, true)) $cell->removeChild($child);
        }
    }

    private function xml(ZipArchive $zip, string $path): DOMDocument
    {
        $raw = $zip->getFromName($path);
        if ($raw === false || preg_match('/<!DOCTYPE|<!ENTITY/i', $raw)) throw new RuntimeException('Struktur template Excel tidak valid.');
        $document = new DOMDocument();
        if (!$document->loadXML($raw, LIBXML_NONET)) throw new RuntimeException('XML template Excel tidak dapat dibaca.');
        return $document;
    }

    private function directory(): string
    {
        $directory = WRITEPATH . 'uploads/lr_simulations';
        if (!is_dir($directory) && !mkdir($directory, 0770, true) && !is_dir($directory)) throw new RuntimeException('Penyimpanan simulasi belum tersedia.');
        return $directory;
    }

    private static function monthName(int $month): string
    {
        return [1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni',
            7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'][$month];
    }
}
