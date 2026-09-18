<?php

namespace App\Libraries;

use RuntimeException;
use SimpleXMLElement;
use ZipArchive;

/** Reads only the audited template; arbitrary Excel formulas are never executed. */
final class RkaWorkbookParser
{
    private const NS = 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';

    public function parse(string $path, ?string $selectedUnit = null): array
    {
        return $this->read($path, false, $selectedUnit);
    }

    public function parseAll(string $path): array
    {
        return $this->read($path, true);
    }

    private function read(string $path, bool $allUnits, ?string $selectedUnit = null): array
    {
        $zip = new ZipArchive();
        if ($zip->open($path) !== true) {
            throw new RuntimeException('Berkas bukan Excel .xlsx yang valid.');
        }
        try {
            $size = 0;
            if ($zip->numFiles > 500) {
                throw new RuntimeException('Struktur berkas Excel terlalu besar.');
            }
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $entry = $zip->statIndex($i);
                $size += $entry['size'];
                $printerSettings = preg_match('~^xl/printerSettings/printerSettings\d+\.bin$~D', $entry['name']);
                if ($size > 25 * 1024 * 1024 || preg_match('~(?:externalLinks/|vbaProject)~i', $entry['name'])
                    || (preg_match('~\.bin$~i', $entry['name']) && !$printerSettings)) {
                    throw new RuntimeException('Excel terlalu besar atau mengandung tautan eksternal/makro yang tidak didukung.');
                }
            }
            $workbook = $this->xml($zip, 'xl/workbook.xml');
            $workbook->registerXPathNamespace('s', self::NS);
            $sheets = $workbook->xpath('//s:sheets/s:sheet');
            if (!$allUnits && (count($sheets) !== 1 || ((string) $sheets[0]['name'] !== RkaCalculator::schema()['sheet']
                && ($selectedUnit === null || !in_array($selectedUnit, RkaCalculator::SOURCE_UNITS, true)
                    || mb_strtolower((string) $sheets[0]['name']) !== mb_strtolower($selectedUnit))))) {
                throw new RuntimeException('Gunakan satu sheet bernama Sheet1 atau nama unit kerja yang dipilih.');
            }
            $unitSheets = [];
            if ($allUnits) {
                $names = array_combine(array_map('mb_strtolower', RkaCalculator::UNITS), RkaCalculator::UNITS);
                $invalid = [];
                foreach ($sheets as $sheetInfo) {
                    $name = (string) $sheetInfo['name'];
                    $unit = $names[mb_strtolower($name)] ?? null;
                    if ($unit === null || isset($unitSheets[$unit])) {
                        $invalid[] = $name;
                    } else {
                        $unitSheets[$unit] = $sheetInfo;
                    }
                }
                $missing = array_diff(RkaCalculator::UNITS, array_keys($unitSheets));
                if ($invalid || $missing) {
                    throw new RuntimeException('Nama sheet tidak sesuai.'
                        . ($invalid ? ' Sheet tidak dikenal atau duplikat: ' . implode(', ', $invalid) . '.' : '')
                        . ($missing ? ' Sheet yang belum ada: ' . implode(', ', $missing) . '.' : '')
                        . ' Gunakan nama sheet: ' . implode(', ', RkaCalculator::UNITS) . '. Tidak ada RKA yang disimpan.');
                }
            } else {
                $unitSheets['Sheet1'] = $sheets[0];
            }
            $results = [];
            $usedPaths = [];
            foreach ($unitSheets as $unit => $sheetInfo) {
            try {
            $relationId = (string) $sheetInfo->attributes('http://schemas.openxmlformats.org/officeDocument/2006/relationships')['id'];
            $relations = $this->xml($zip, 'xl/_rels/workbook.xml.rels');
            $sheetPath = null;
            foreach ($relations->children('http://schemas.openxmlformats.org/package/2006/relationships') as $relation) {
                $attributes = $relation->attributes();
                if ((string) $attributes['TargetMode'] === 'External') {
                    throw new RuntimeException('Tautan eksternal pada Excel tidak didukung.');
                }
                if ((string) $attributes['Id'] === $relationId) {
                    $target = ltrim((string) $attributes['Target'], '/');
                    $target = str_starts_with($target, 'xl/') ? substr($target, 3) : $target;
                    if (! preg_match('~^worksheets/sheet\d+\.xml$~D', $target)) {
                        throw new RuntimeException('Lokasi sheet Excel tidak valid.');
                    }
                    $sheetPath = 'xl/' . $target;
                }
            }
            if ($sheetPath === null) {
                throw new RuntimeException('Sheet Template RKA tidak ditemukan.');
            }
            if (isset($usedPaths[$sheetPath])) {
                throw new RuntimeException('Dua sheet mengarah ke data yang sama. Struktur Excel tidak valid.');
            }
            $usedPaths[$sheetPath] = true;
            $strings = [];
            if ($zip->locateName('xl/sharedStrings.xml') !== false) {
                $shared = $this->xml($zip, 'xl/sharedStrings.xml');
                $shared->registerXPathNamespace('s', self::NS);
                foreach ($shared->xpath('//s:si') as $item) {
                    $item->registerXPathNamespace('s', self::NS);
                    $strings[] = implode('', array_map(static fn ($text) => (string) $text, $item->xpath('.//s:t')));
                }
            }
            $sheet = $this->xml($zip, $sheetPath);
            $sheet->registerXPathNamespace('s', self::NS);
            $dateStyles = $this->dateStyles($zip);
            $cells = [];
            $sharedFormulas = [];
            $sheetCells = $sheet->xpath('//s:sheetData/s:row/s:c');
            if (count($sheetCells) > 5000) {
                throw new RuntimeException('Jumlah sel melebihi kapasitas Template RKA.');
            }
            foreach ($sheetCells as $cell) {
                $address = (string) $cell['r'];
                if (! preg_match('/^[A-Z]{1,3}[1-9]\d{0,5}$/D', $address) || isset($cells[$address])) {
                    throw new RuntimeException('Alamat sel duplikat atau tidak valid pada Excel.');
                }
                $content = $cell->children(self::NS);
                $type = (string) $cell['t'];
                $value = isset($content->v) ? (string) $content->v : '';
                if ($type === 's') {
                    if (! ctype_digit($value) || ! isset($strings[(int) $value])) {
                        throw new RuntimeException('Teks Excel tidak valid pada ' . $address . '.');
                    }
                    $value = $strings[(int) $value];
                } elseif ($type === 'inlineStr') {
                    $cell->registerXPathNamespace('s', self::NS);
                    $value = implode('', array_map(static fn ($text) => (string) $text, $cell->xpath('.//s:is//s:t')));
                }
                $formula = isset($content->f) ? (string) $content->f : null;
                $formulaAttributes = isset($content->f) ? $content->f->attributes() : null;
                $sharedIndex = $formulaAttributes !== null && (string) $formulaAttributes['t'] === 'shared' ? (string) $formulaAttributes['si'] : null;
                if ($sharedIndex !== null && $formula !== '') {
                    $sharedFormulas[$sharedIndex] = ['address' => $address, 'formula' => $formula];
                }
                $cells[$address] = ['value' => $value, 'type' => $type, 'formula' => $formula, 'shared' => $sharedIndex, 'date_style' => isset($dateStyles[(int) $cell['s']])];
            }
            foreach ($cells as $address => &$cell) {
                if ($cell['shared'] !== null && $cell['formula'] === '') {
                    $base = $sharedFormulas[$cell['shared']] ?? null;
                    if ($base === null) {
                        throw new RuntimeException('Rumus bersama Excel tidak lengkap pada ' . $address . '.');
                    }
                    $cell['formula'] = $this->translate($base['formula'], $base['address'], $address);
                }
            }
            unset($cell);
            $schema = RkaCalculator::schema();
            $financialHeaderRow = $this->normalize($cells['F6']['value'] ?? '') === 'kur' ? 6
                : ($this->normalize($cells['F5']['value'] ?? '') === 'kur' ? 5 : null);
            $financialRowOffset = $financialHeaderRow === 5 ? 1 : 0;
            $shiftedLayout = $financialHeaderRow !== null;
            if ($shiftedLayout) {
                // This supplied export repeats Kanwil's final result below the report.
                // Permit only that exact non-formula duplicate, never another input.
                $duplicateAddress = 'K' . (173 - $financialRowOffset);
                $profitAddress = 'K' . (166 - $financialRowOffset);
                if (($unit === 'Kanwil' || (!$allUnits && $selectedUnit === 'Kanwil')) && isset($cells[$duplicateAddress]) && $cells[$duplicateAddress]['value'] !== ''
                    && $cells[$duplicateAddress]['formula'] === null && in_array($cells[$duplicateAddress]['type'], ['', 'n'], true)
                    && !$cells[$duplicateAddress]['date_style']
                    && RkaMoney::decimal($cells[$duplicateAddress]['value']) === RkaMoney::decimal($cells[$profitAddress]['value'] ?? '')) {
                    unset($cells[$duplicateAddress]);
                }
                $cells = $this->mapFinancialLayout($cells, $schema, $allUnits && $unit === 'Korporat Kanwil', $financialRowOffset);
            }
            // The supplied blank template places headers on row 5 and has no formulas.
            // Its inputs use the same audited row addresses; server arithmetic is unchanged.
            $blankLayout = !$shiftedLayout && $this->normalize($cells['C5']['value'] ?? '') === $this->normalize('KUR');
            $headerRow = $shiftedLayout ? $financialHeaderRow : ($blankLayout ? 5 : 6);
            foreach ($schema['columns'] as $column => $label) {
                if ($this->normalize($cells[$column . $headerRow]['value'] ?? '') !== $this->normalize($label)) {
                    throw new RuntimeException('Kolom ' . $column . $headerRow . ' harus ' . $label . '. Gunakan template yang tersedia.');
                }
            }
            foreach ($schema['rows'] as $row => $definition) {
                $label = ($cells['B' . $row]['value'] ?? '') ?: ($cells['A' . $row]['value'] ?? '');
                if ($this->normalize($label) !== $this->normalize($definition['label'])) {
                    throw new RuntimeException('Uraian pada baris ' . $row . ' tidak sesuai Template RKA.');
                }
            }
            $expected = $schema['formulas'];
            foreach (['H25' => 'SUM(C25:G25)', 'H164' => 'SUM(C164:G164)'] as $address => $formula) {
                if (($cells[$address]['formula'] ?? null) !== null) {
                    $expected[$address] = $formula;
                }
            }
            foreach ($expected as $address => $formula) {
                $missingKanwil = $shiftedLayout && ($unit === 'Kanwil' || (!$allUnits && $selectedUnit === 'Kanwil'))
                    && in_array($address, ['H8','H11','H12','H13','H17','H18','H19','H20',
                        'C14','D14','E14','F14','G14','H14','C21','D21','E21','F21','G21','H21',
                        'C23','D23','E23','F23','G23','H23'], true);
                if (($blankLayout || $missingKanwil) && ($cells[$address]['formula'] ?? null) === null) {
                    continue;
                }
                if ($this->normalizeFormula($cells[$address]['formula'] ?? '') !== $this->normalizeFormula($formula)) {
                    throw new RuntimeException('Rumus ' . $address . ' berubah atau hilang. Rumus harus sesuai Template RKA.');
                }
            }
            foreach ($cells as $address => $cell) {
                if ($cell['type'] === 'e' || ($cell['formula'] !== null && ! isset($expected[$address]))) {
                    throw new RuntimeException('Sel bermasalah atau rumus tidak didukung pada ' . $address . '.');
                }
                preg_match('/^([A-Z]+)(\d+)$/D', $address, $position);
                $row = (int) $position[2];
                $groupRows = array_column($schema['groups'], 'row');
                if ($cell['value'] !== '' && ($row > 166 || strlen($position[1]) > 1 || $position[1] > 'H'
                    || ($row >= 8 && ! isset($schema['rows'][$row]) && ! in_array($row, $groupRows, true))
                    || ($row >= 8 && in_array($position[1], ['C', 'D', 'E', 'F', 'G', 'H'], true) && ! isset($schema['rows'][$row])))) {
                    throw new RuntimeException('Ada data di luar struktur Template RKA pada ' . $address . '. Data tidak diabaikan; sesuaikan dengan template.');
                }
            }
            $inputs = [];
            foreach ($schema['input_rows'] as $row) {
                foreach (['C', 'D', 'E', 'F', 'G'] as $column) {
                    $address = $column . $row;
                    $cell = $cells[$address] ?? ['type' => '', 'value' => ''];
                    if (! in_array($cell['type'], ['', 'n'], true) || ($cell['date_style'] ?? false)) {
                        throw new RuntimeException('Nominal ' . $address . ' harus angka Excel, bukan teks atau tanggal.');
                    }
                    $inputs[$address] = RkaMoney::decimal($cell['value'] === '' ? '0' : $cell['value']);
                }
            }
            $heading = $cells['A2']['value'] ?? '';
            if (! preg_match('/\b(20\d{2}|2100)\b/', $heading, $year)) {
                throw new RuntimeException('Tahun RKA pada A2 tidak dapat dibaca.');
            }
            $calculator = new RkaCalculator();
            $results[$unit] = ['year' => (int) $year[1], 'inputs' => $inputs, 'calculated' => $calculator->calculate($inputs)];
            } catch (RuntimeException|\InvalidArgumentException $exception) {
                if (!$allUnits) throw $exception;
                throw new RuntimeException('Sheet "' . $unit . '": ' . $exception->getMessage());
            }
            }
            if ($allUnits) {
                $years = array_unique(array_column($results, 'year'));
                if (count($years) !== 1) throw new RuntimeException('Tahun pada A2 seluruh sheet harus sama. Tidak ada RKA yang disimpan.');
                $sources = [];
                foreach (RkaCalculator::SOURCE_UNITS as $source) $sources[$source] = $results[$source]['inputs'];
                $inputs = (new RkaCalculator())->consolidate($sources);
                $results['Korporat Kanwil']['inputs'] = $inputs;
                $results['Korporat Kanwil']['calculated'] = (new RkaCalculator())->calculate($inputs);
            }
            return $allUnits ? $results : $results['Sheet1'];
        } finally {
            $zip->close();
        }
    }

    private function xml(ZipArchive $zip, string $path): SimpleXMLElement
    {
        $text = $zip->getFromName($path);
        if ($text === false || preg_match('/<!DOCTYPE|<!ENTITY/i', $text)) {
            throw new RuntimeException('Struktur XML Excel tidak valid.');
        }
        $previous = libxml_use_internal_errors(true);
        try {
            $xml = simplexml_load_string($text, SimpleXMLElement::class, LIBXML_NONET);
            if ($xml === false) {
                throw new RuntimeException('XML Excel tidak dapat dibaca.');
            }
            return $xml;
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
    }

    private function translate(string $formula, string $base, string $target): string
    {
        preg_match('/^([A-K])(\d+)$/D', $base, $from);
        preg_match('/^([A-K])(\d+)$/D', $target, $to);
        if (! $from || ! $to) {
            throw new RuntimeException('Referensi rumus bersama berada di luar template.');
        }
        return preg_replace_callback('/(?<![A-Z0-9_])(\$?)([A-K])(\$?)(\d+)/', static fn ($match) => $match[1]
            . ($match[1] === '$' ? $match[2] : chr(ord($match[2]) + ord($to[1]) - ord($from[1])))
            . $match[3] . ($match[3] === '$' ? $match[4] : (int) $match[4] + (int) $to[2] - (int) $from[2]), $formula);
    }

    /** The all-unit Oracle/RKA export uses D/E labels and F:K money. */
    private function mapFinancialLayout(array $cells, array $schema, bool $corporate, int $rowOffset = 0): array
    {
        $mapped = [];
        foreach ($cells as $address => $cell) {
            preg_match('/^([A-Z]+)(\d+)$/D', $address, $position);
            $column = $position[1]; $row = (int) $position[2];
            $targetRow = $this->financialRow($row, $rowOffset);
            if (strlen($column) !== 1 || $column > 'K' || $targetRow > 166) {
                if ($cell['value'] !== '' || $cell['formula'] !== null) {
                    throw new RuntimeException('Ada data di luar struktur RKA pada ' . $address . '.');
                }
                continue;
            }
            if ($column < 'D') {
                if ($cell['formula'] !== null || $cell['type'] === 'e') {
                    throw new RuntimeException('Rumus atau sel bermasalah di kolom referensi ' . $address . '.');
                }
                continue; // A:C are account/reference codes, not amounts.
            }
            $target = chr(ord($column) - 3) . $targetRow;
            if ($corporate && $column >= 'F' && $column <= 'J'
                && in_array($targetRow, $schema['input_rows'], true) && $cell['formula'] !== null) {
                $references = array_map(static fn ($unit) => strtoupper($unit) . '!' . $address, RkaCalculator::SOURCE_UNITS);
                $actual = str_replace("'", '', $this->normalizeFormula($cell['formula']));
                $terms = explode('+', $actual);
                sort($terms); sort($references);
                if ($terms !== $references) {
                    throw new RuntimeException('Rumus konsolidasi ' . $address . ' harus menjumlahkan sel yang sama dari enam unit sumber.');
                }
                $cell['formula'] = null; // Validated source references; calculate from parsed units, not cached Excel results.
            } elseif ($cell['formula'] !== null) {
                $cell['formula'] = preg_replace_callback('/(?<![A-Z0-9_])([F-K])(\d+)/',
                    fn ($match) => chr(ord($match[1]) - 3) . $this->financialRow((int) $match[2], $rowOffset),
                    $this->normalizeFormula($cell['formula']));
            }
            $mapped[$target] = $cell;
        }
        return $mapped;
    }

    private function financialRow(int $row, int $offset): int
    {
        return $offset !== 0 && $row >= 7 ? $row + $offset : $row;
    }

    private function dateStyles(ZipArchive $zip): array
    {
        if ($zip->locateName('xl/styles.xml') === false) {
            return [];
        }
        $styles = $this->xml($zip, 'xl/styles.xml');
        $styles->registerXPathNamespace('s', self::NS);
        $formats = [];
        foreach ($styles->xpath('//s:numFmts/s:numFmt') as $format) {
            $formats[(int) $format['numFmtId']] = (string) $format['formatCode'];
        }
        $dates = [];
        foreach ($styles->xpath('//s:cellXfs/s:xf') as $index => $style) {
            $id = (int) $style['numFmtId'];
            $code = preg_replace('/"[^"\\\\]*(?:\\\\.[^"\\\\]*)*"|\\\\.|\[(?![hms]+\])[^\]]*\]/i', '', $formats[$id] ?? '');
            if (($id >= 14 && $id <= 22) || ($id >= 27 && $id <= 36) || ($id >= 45 && $id <= 47) || ($id >= 50 && $id <= 58) || preg_match('/[ymdhs]/i', $code)) {
                $dates[$index] = true;
            }
        }
        return $dates;
    }

    private function normalize(string $value): string
    {
        return mb_strtolower(trim(preg_replace('/\s+/u', ' ', $value)));
    }

    private function normalizeFormula(string $formula): string
    {
        return strtoupper(preg_replace('/[\s$=]/', '', $formula));
    }
}
