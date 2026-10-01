<?php

namespace App\Libraries;

use DateTimeImmutable;
use DOMDocument;
use DOMElement;
use RuntimeException;

final class EssOvertimeReportParser
{
    /**
     * @return array<string, mixed>
     */
    public function parse(string $path, string $sourceName = ''): array
    {
        if (! is_file($path) || ! is_readable($path)) {
            throw new RuntimeException('File laporan lembur ESS tidak dapat dibaca.');
        }

        $html = file_get_contents($path);
        if ($html === false || trim($html) === '') {
            throw new RuntimeException('File laporan lembur ESS kosong.');
        }
        if (stripos($html, 'Overtime Report ESS') === false) {
            throw new RuntimeException('File bukan Overtime Report ESS dari Workplace.');
        }

        $document = new DOMDocument();
        $previousErrors = libxml_use_internal_errors(true);
        $loaded = $document->loadHTML($html, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);
        libxml_clear_errors();
        libxml_use_internal_errors($previousErrors);
        if (! $loaded) {
            throw new RuntimeException('Struktur file Overtime Report ESS tidak dapat dibaca.');
        }

        [$periodStart, $periodEnd] = $this->readPeriod($document);
        $rows = $this->readRows($this->findOvertimeTable($document));
        if ($rows === []) {
            throw new RuntimeException('Tidak ada data lembur dengan total jam lebih dari nol pada file ESS.');
        }

        if ($periodStart === null || $periodEnd === null) {
            $dates = array_column($rows, 'overtime_date');
            sort($dates);
            $periodStart = new DateTimeImmutable($dates[0]);
            $periodEnd = new DateTimeImmutable($dates[array_key_last($dates)]);
        }

        return $this->buildReport($rows, $periodStart, $periodEnd, $sourceName);
    }

    /** @return array{0: DateTimeImmutable|null, 1: DateTimeImmutable|null} */
    private function readPeriod(DOMDocument $document): array
    {
        foreach ($document->getElementsByTagName('table') as $table) {
            $cells = $table->getElementsByTagName('td');
            if ($cells->length < 5 || ! str_contains($this->normalizeText($cells->item(0)?->textContent ?? ''), 'Date')) {
                continue;
            }

            $start = $this->dateFromCell($cells->item(2));
            $end = $this->dateFromCell($cells->item(4));
            if ($start !== null && $end !== null) {
                return [$start, $end];
            }
        }

        return [null, null];
    }

    private function findOvertimeTable(DOMDocument $document): DOMElement
    {
        foreach ($document->getElementsByTagName('table') as $table) {
            $heading = $this->normalizeText($table->textContent);
            if (str_contains($heading, 'Employee No') && str_contains($heading, 'Overtime Request No') && str_contains($heading, 'Total Overtime')) {
                return $table;
            }
        }

        throw new RuntimeException('Tabel data lembur tidak ditemukan pada file ESS.');
    }

    /** @return list<array<string, mixed>> */
    private function readRows(DOMElement $table): array
    {
        $rows = [];
        foreach ($table->getElementsByTagName('tr') as $row) {
            $cells = [];
            foreach ($row->getElementsByTagName('td') as $cell) {
                $cells[] = [
                    'text' => $this->normalizeText($cell->textContent),
                    'num' => trim($cell->getAttribute('x:num')),
                ];
            }

            if (count($cells) < 35 || ! ctype_digit($cells[0]['text']) || $cells[1]['text'] === '') {
                continue;
            }

            $date = $this->dateFromValue($cells[3]['num'] !== '' ? $cells[3]['num'] : $cells[3]['text']);
            $totalMinutes = $this->wholeNumber($cells[13]['num'] !== '' ? $cells[13]['num'] : $cells[13]['text']);
            if ($date === null || $totalMinutes <= 0) {
                continue;
            }

            $rows[] = [
                'employee_key' => $cells[0]['text'],
                'employee_no' => $cells[0]['text'],
                'employee_name' => $cells[1]['text'],
                'organization' => $cells[2]['text'],
                'overtime_date' => $date->format('Y-m-d'),
                'shift' => $this->nullable($cells[4]['text']),
                'actual_in' => $this->timeValue($cells[5]['text']),
                'actual_out' => $this->timeValue($cells[6]['text']),
                'overtime_start' => $this->timeValue($cells[7]['text']),
                'overtime_end' => $this->timeValue($cells[8]['text']),
                'overtime_minutes' => $totalMinutes,
                'overtime_hours' => $this->decimalNumber($cells[14]['num'] !== '' ? $cells[14]['num'] : $cells[14]['text']),
                'request_no' => $this->nullable($cells[25]['text']),
                'request_date' => $this->dateFromValue($cells[26]['num'] !== '' ? $cells[26]['num'] : $cells[26]['text'])?->format('Y-m-d'),
                'overtime_type' => $this->nullable($cells[27]['text']),
                'reason' => $this->nullable($cells[29]['text']),
                'remark' => $this->nullable($cells[30]['text']),
                'hour_type' => $this->nullable($cells[31]['text']),
                'request_start' => $this->dateTimeFromValue($cells[32]['num'] !== '' ? $cells[32]['num'] : $cells[32]['text']),
                'request_end' => $this->dateTimeFromValue($cells[33]['num'] !== '' ? $cells[33]['num'] : $cells[33]['text']),
                'request_status' => $this->nullable($cells[34]['text']),
            ];
        }

        return $rows;
    }

    /** @param list<array<string, mixed>> $rows
     * @return array<string, mixed> */
    private function buildReport(array $rows, DateTimeImmutable $periodStart, DateTimeImmutable $periodEnd, string $sourceName): array
    {
        $unique = [];
        $employees = [];
        $totalMinutes = 0;
        foreach ($rows as $row) {
            $key = $row['employee_key'] . '|' . $row['overtime_date'] . '|' . ($row['request_no'] ?: $row['overtime_start']);
            if (isset($unique[$key])) {
                continue;
            }
            $unique[$key] = $row;
            $employees[$row['employee_key']] = true;
            $totalMinutes += (int) $row['overtime_minutes'];
        }

        $records = array_values($unique);
        usort($records, static fn (array $left, array $right): int => [$left['overtime_date'], $left['employee_name']] <=> [$right['overtime_date'], $right['employee_name']]);

        return [
            'source_name' => $sourceName !== '' ? $sourceName : 'Overtime Report ESS',
            'period_start' => $periodStart->format('Y-m-d'),
            'period_end' => $periodEnd->format('Y-m-d'),
            'employee_count' => count($employees),
            'row_count' => count($records),
            'total_minutes' => $totalMinutes,
            'records' => $records,
        ];
    }

    private function dateFromCell(?DOMElement $cell): ?DateTimeImmutable
    {
        if ($cell === null) {
            return null;
        }

        return $this->dateFromValue(trim($cell->getAttribute('x:num')) ?: $this->normalizeText($cell->textContent));
    }

    private function dateFromValue(string $value): ?DateTimeImmutable
    {
        $value = trim($value);
        if ($value === '' || $value === '-') {
            return null;
        }
        if (is_numeric($value)) {
            return (new DateTimeImmutable('1899-12-30'))->modify('+' . (int) floor((float) $value) . ' days');
        }
        foreach (['!d/m/Y', '!Y-m-d'] as $format) {
            $date = DateTimeImmutable::createFromFormat($format, $value);
            if ($date !== false) {
                return $date;
            }
        }

        return null;
    }

    private function dateTimeFromValue(string $value): ?string
    {
        if (! is_numeric(trim($value))) {
            return null;
        }
        $seconds = (int) round(((float) $value - floor((float) $value)) * 86400);
        $date = $this->dateFromValue($value);

        return $date?->setTime(0, 0)->modify('+' . $seconds . ' seconds')->format('Y-m-d H:i:s');
    }

    private function timeValue(string $value): ?string
    {
        $value = trim($value);
        if ($value === '' || $value === '-' || $value === '(-)') {
            return null;
        }
        foreach (['!H:i:s', '!H:i'] as $format) {
            $time = DateTimeImmutable::createFromFormat($format, $value);
            if ($time !== false) {
                return $time->format('H:i:s');
            }
        }

        return null;
    }

    private function wholeNumber(string $value): int
    {
        return is_numeric(trim($value)) ? (int) round((float) $value) : 0;
    }

    private function decimalNumber(string $value): ?float
    {
        return is_numeric(trim($value)) ? (float) $value : null;
    }

    private function nullable(string $value): ?string
    {
        $value = trim($value);

        return $value !== '' && $value !== '-' ? $value : null;
    }

    private function normalizeText(string $value): string
    {
        $value = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return trim(preg_replace('/\s+/u', ' ', $value) ?? $value);
    }
}
