<?php

namespace App\Libraries;

use DateTimeImmutable;
use DOMDocument;
use DOMElement;
use RuntimeException;

final class EssAttendanceReportParser
{
    private const COLUMN_COUNT = 24;

    /**
     * Mengubah laporan ESS berbentuk HTML-XLS menjadi rekap absensi bulanan.
     *
     * @return array<string, mixed>
     */
    public function parse(string $path, string $sourceName = ''): array
    {
        if (! is_file($path) || ! is_readable($path)) {
            throw new RuntimeException('File laporan ESS tidak dapat dibaca.');
        }

        $html = file_get_contents($path);
        if ($html === false || trim($html) === '') {
            throw new RuntimeException('File laporan ESS kosong.');
        }

        if (stripos($html, 'Employee Attendance Report ESS') === false) {
            throw new RuntimeException('File tidak dikenali sebagai Employee Attendance Report ESS.');
        }

        $document = new DOMDocument();
        $previousErrors = libxml_use_internal_errors(true);
        $loaded = $document->loadHTML($html, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);
        libxml_clear_errors();
        libxml_use_internal_errors($previousErrors);

        if (! $loaded) {
            throw new RuntimeException('Struktur file laporan ESS tidak dapat dibaca.');
        }

        [$periodStart, $periodEnd] = $this->readPeriod($document->textContent);
        $table = $this->findAttendanceTable($document);
        $records = $this->readRecords($table);

        if ($records === []) {
            throw new RuntimeException('File ESS tidak berisi data absensi yang dapat direkap.');
        }

        if ($periodStart === null || $periodEnd === null) {
            $dates = array_column($records, 'date');
            sort($dates);
            $periodStart = new DateTimeImmutable($dates[0]);
            $periodEnd = new DateTimeImmutable($dates[array_key_last($dates)]);
        }

        if ($periodStart->format('Y-m') !== $periodEnd->format('Y-m')) {
            throw new RuntimeException('Rekap hanya mendukung satu periode bulan dalam satu file ESS.');
        }

        return $this->buildRecap($records, $periodStart, $periodEnd, $sourceName);
    }

    /**
     * @return array{0: DateTimeImmutable|null, 1: DateTimeImmutable|null}
     */
    private function readPeriod(string $text): array
    {
        $text = preg_replace('/\s+/u', ' ', $text) ?? $text;
        if (! preg_match('/Period\s*:\s*(\d{2}\/\d{2}\/\d{4})\s*-\s*(\d{2}\/\d{2}\/\d{4})/i', $text, $matches)) {
            return [null, null];
        }

        $start = DateTimeImmutable::createFromFormat('!d/m/Y', $matches[1]);
        $end = DateTimeImmutable::createFromFormat('!d/m/Y', $matches[2]);

        return [$start ?: null, $end ?: null];
    }

    private function findAttendanceTable(DOMDocument $document): DOMElement
    {
        foreach ($document->getElementsByTagName('table') as $table) {
            $heading = $this->normalizeText($table->textContent);
            if (str_contains($heading, 'Employee Name') && str_contains($heading, 'Organization Unit')) {
                return $table;
            }
        }

        throw new RuntimeException('Tabel absensi tidak ditemukan pada file ESS.');
    }

    /**
     * @return list<array<string, string>>
     */
    private function readRecords(DOMElement $table): array
    {
        $records = [];

        foreach ($table->getElementsByTagName('tr') as $row) {
            $cells = [];
            foreach ($row->getElementsByTagName('td') as $cell) {
                $cells[] = $this->normalizeText($cell->textContent);
            }

            if (count($cells) < self::COLUMN_COUNT || ! ctype_digit($cells[0]) || $cells[1] === '') {
                continue;
            }

            $date = $this->excelSerialToDate((int) $cells[0]);
            $records[] = [
                'date'            => $date->format('Y-m-d'),
                'employee_name'   => $cells[1],
                'employee_no'     => $cells[2],
                'position'        => $cells[3],
                'organization'    => $cells[4],
                'shift'           => $cells[5],
                'actual_in'       => $cells[9],
                'actual_out'      => $cells[11],
                'day_type'        => strtoupper($cells[15]),
                'status'          => strtoupper($cells[21]),
                'other_status'    => strtoupper($cells[22]),
                'remark'          => $cells[23],
            ];
        }

        return $records;
    }

    private function excelSerialToDate(int $serial): DateTimeImmutable
    {
        if ($serial < 1 || $serial > 2958465) {
            throw new RuntimeException('Tanggal pada file ESS tidak valid.');
        }

        return (new DateTimeImmutable('1899-12-30'))->modify("+{$serial} days");
    }

    /**
     * @param list<array<string, string>> $records
     * @return array<string, mixed>
     */
    private function buildRecap(
        array $records,
        DateTimeImmutable $periodStart,
        DateTimeImmutable $periodEnd,
        string $sourceName,
    ): array {
        $employees = [];
        $seenRecords = [];
        $availableDates = [];
        $warnings = [];
        $summary = ['H' => 0, 'I' => 0, 'A' => 0, 'TA' => 0, 'TAM' => 0, 'TAP' => 0, 'OFF' => 0, 'OTHER' => 0];
        $storedRecords = [];

        foreach ($records as $record) {
            $date = new DateTimeImmutable($record['date']);
            if ($date < $periodStart || $date > $periodEnd) {
                continue;
            }

            $employeeKey = $record['employee_no'] !== ''
                ? $record['employee_no']
                : mb_strtolower($record['employee_name']);
            $recordKey = $employeeKey . '|' . $record['date'];

            if (isset($seenRecords[$recordKey])) {
                $warnings['duplicate'] = 'Terdapat data karyawan dan tanggal yang ganda; data pertama digunakan.';
                continue;
            }

            $seenRecords[$recordKey] = true;
            $availableDates[$record['date']] = true;

            if (! isset($employees[$employeeKey])) {
                $employees[$employeeKey] = [
                    'employee_no'   => $record['employee_no'] !== '' ? $record['employee_no'] : '-',
                    'employee_name' => $record['employee_name'],
                    'position'      => $record['position'],
                    'organization'  => $record['organization'],
                    'days'          => [],
                    'totals'        => ['H' => 0, 'I' => 0, 'A' => 0, 'TA' => 0, 'TAM' => 0, 'TAP' => 0, 'OFF' => 0],
                ];
            }

            $code = $this->recapCode($record);
            $day = (int) $date->format('j');
            $employees[$employeeKey]['days'][$day] = $code;
            $storedRecords[] = [
                'employee_key'    => $employeeKey,
                'employee_no'     => $record['employee_no'] !== '' ? $record['employee_no'] : null,
                'employee_name'   => $record['employee_name'],
                'position'        => $record['position'] !== '' ? $record['position'] : null,
                'organization'    => $record['organization'] !== '' ? $record['organization'] : null,
                'attendance_date' => $record['date'],
                'shift'           => $record['shift'] !== '' ? $record['shift'] : null,
                'actual_in'       => $record['actual_in'] !== '' ? $record['actual_in'] : null,
                'actual_out'      => $record['actual_out'] !== '' ? $record['actual_out'] : null,
                'day_type'        => $record['day_type'] !== '' ? $record['day_type'] : null,
                'raw_status'      => $record['status'] !== '' ? $record['status'] : null,
                'other_status'    => $record['other_status'] !== '' ? $record['other_status'] : null,
                'remark'          => $record['remark'] !== '' ? $record['remark'] : null,
                'recap_code'      => $code,
            ];

            if (isset($employees[$employeeKey]['totals'][$code])) {
                $employees[$employeeKey]['totals'][$code]++;
                $summary[$code]++;
            } else {
                $summary['OTHER']++;
            }
        }

        uasort(
            $employees,
            static fn (array $left, array $right): int => strnatcasecmp($left['employee_name'], $right['employee_name']),
        );

        $daysInMonth = (int) $periodStart->format('t');
        $missingDates = [];
        for ($day = 1; $day <= $daysInMonth; $day++) {
            $date = $periodStart->setDate(
                (int) $periodStart->format('Y'),
                (int) $periodStart->format('m'),
                $day,
            );
            if (! isset($availableDates[$date->format('Y-m-d')])) {
                $missingDates[] = $date->format('d/m/Y');
            }
        }

        if ($missingDates !== []) {
            $warnings['missing_dates'] = 'Tanggal yang tidak tersedia di file: ' . implode(', ', $missingDates) . '.';
        }

        $employeeRows = array_values($employees);
        foreach ($employeeRows as &$employee) {
            $workDays = $employee['totals']['H'] + $employee['totals']['I']
                + $employee['totals']['A'] + $employee['totals']['TA']
                + $employee['totals']['TAM'] + $employee['totals']['TAP'];
            $employee['attendance_rate'] = $workDays > 0
                ? round(($employee['totals']['H'] / $workDays) * 100, 1)
                : null;
        }
        unset($employee);

        $summary['ROWS'] = count($seenRecords);
        $summary['EMPLOYEES'] = count($employeeRows);

        return [
            'source_name'   => $sourceName !== '' ? $sourceName : 'Laporan ESS',
            'period_start'  => $periodStart->format('Y-m-d'),
            'period_end'    => $periodEnd->format('Y-m-d'),
            'period_label'  => $this->indonesianMonth((int) $periodStart->format('n')) . ' ' . $periodStart->format('Y'),
            'days_in_month' => $daysInMonth,
            'employees'     => $employeeRows,
            'summary'       => $summary,
            'warnings'      => array_values($warnings),
            'records'       => $storedRecords,
        ];
    }

    /**
     * @param array<string, string> $record
     */
    private function recapCode(array $record): string
    {
        if ($record['status'] === 'ABS') {
            return 'A';
        }

        if (in_array($record['status'], ['CB2', 'RI', 'CT'], true)) {
            return 'I';
        }

        $hasActualIn = $record['actual_in'] !== '';
        $hasActualOut = $record['actual_out'] !== '';
        $hasIncompleteMarker = preg_match('/(^|,)(NSI|NSO)(,|$)/', $record['other_status']) === 1;

        if ($record['status'] === 'PRS') {
            if ($hasActualIn && ! $hasActualOut) {
                return 'TAM';
            }
            if (! $hasActualIn && $hasActualOut) {
                return 'TAP';
            }

            return $hasActualIn && $hasActualOut && ! $hasIncompleteMarker ? 'H' : 'TA';
        }

        if ($record['status'] === 'OFF' || in_array($record['day_type'], ['OFF', 'PHOFF'], true)) {
            return 'OFF';
        }

        // Baris dengan hanya salah satu jam tetap disimpan sebagai presensi
        // tidak lengkap, termasuk ketika kode status dari ESS kosong/tidak dikenal.
        if ($hasActualIn && ! $hasActualOut) {
            return 'TAM';
        }
        if (! $hasActualIn && $hasActualOut) {
            return 'TAP';
        }
        if ($hasIncompleteMarker) {
            return 'TA';
        }

        if (! $hasActualIn && ! $hasActualOut) {
            return 'TA';
        }

        return $record['status'] !== '' ? $record['status'] : '-';
    }

    private function normalizeText(string $value): string
    {
        $value = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return trim(preg_replace('/\s+/u', ' ', $value) ?? $value);
    }

    private function indonesianMonth(int $month): string
    {
        return [
            1 => 'Januari',
            2 => 'Februari',
            3 => 'Maret',
            4 => 'April',
            5 => 'Mei',
            6 => 'Juni',
            7 => 'Juli',
            8 => 'Agustus',
            9 => 'September',
            10 => 'Oktober',
            11 => 'November',
            12 => 'Desember',
        ][$month];
    }
}
