<?php

namespace App\Libraries;

use App\Models\SdmAttendanceCalendarModel;
use App\Models\SdmAttendanceRecordModel;
use DOMDocument;
use DOMElement;
use DOMXPath;
use RuntimeException;
use ZipArchive;

/** Builds the attendance discipline workbook from the approved recap template. */
final class AttendanceDisciplineExportService
{
    private const TEMPLATE_FILE = 'Resources/Templates/rekap_kedisiplinan.xlsx';
    private const MAIN_NS = 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';
    private const REL_NS = 'http://schemas.openxmlformats.org/package/2006/relationships';
    private const OFFICE_REL_NS = 'http://schemas.openxmlformats.org/officeDocument/2006/relationships';
    private const LEAVE_CODES = ['CT', 'RI', 'CS', 'CBR', 'CK', 'SD'];
    private ?DOMDocument $pendingAttendanceStylesDocument = null;

    /** @param array<string, mixed> $import
     * @return array{path:string,filename:string}
     */
    public function create(array $import): array
    {
        $this->pendingAttendanceStylesDocument = null;
        $template = APPPATH . self::TEMPLATE_FILE;
        if (! is_file($template) || ! is_readable($template)) {
            throw new RuntimeException('Template Rekap Kehadiran belum tersedia.');
        }

        $periodStart = new \DateTimeImmutable((string) $import['period_start']);
        $periodEnd = new \DateTimeImmutable((string) $import['period_end']);
        $records = (new SdmAttendanceRecordModel())
            ->where('import_id', (int) $import['id'])
            ->orderBy('employee_name', 'ASC')
            ->orderBy('attendance_date', 'ASC')
            ->findAll();
        if ($records === []) {
            throw new RuntimeException('Belum ada rincian absensi yang dapat diekspor pada laporan ini.');
        }

        $directory = WRITEPATH . 'cache/attendance_exports';
        if (! is_dir($directory) && ! mkdir($directory, 0770, true) && ! is_dir($directory)) {
            throw new RuntimeException('Penyimpanan sementara file export belum tersedia.');
        }
        $path = $directory . DIRECTORY_SEPARATOR . bin2hex(random_bytes(16)) . '.xlsx';
        if (! copy($template, $path)) {
            throw new RuntimeException('Template Rekap Kehadiran belum dapat disiapkan.');
        }

        try {
            $this->populateWorkbook($path, $import, $periodStart, $periodEnd, $records);
        } catch (\Throwable $exception) {
            if (is_file($path)) {
                unlink($path);
            }
            throw $exception;
        }

        $month = (int) ($import['report_month'] ?? $periodStart->format('n'));
        $year = (int) ($import['report_year'] ?? $periodStart->format('Y'));
        $unit = $this->safeSheetName((string) ($import['work_unit'] ?? 'Unit Kerja'));

        return [
            'path' => $path,
            'filename' => 'Rekap_Kehadiran_' . $this->filenamePart($unit) . '_' . $this->monthName($month) . '_' . $year . '_' . $this->exportTimestamp() . '.xlsx',
        ];
    }

    /**
     * Builds one detail sheet for every available work unit in the chosen period.
     *
     * @param list<array<string, mixed>> $imports
     * @return array{path:string,filename:string}
     */
    public function createForImports(array $imports, int $month, int $year): array
    {
        $this->pendingAttendanceStylesDocument = null;
        if ($imports === []) {
            throw new RuntimeException('Belum ada laporan kehadiran yang dapat diekspor.');
        }
        $template = APPPATH . self::TEMPLATE_FILE;
        if (! is_file($template) || ! is_readable($template)) {
            throw new RuntimeException('Template Rekap Kehadiran belum tersedia.');
        }

        usort($imports, static fn (array $left, array $right): int => strnatcasecmp((string) ($left['work_unit'] ?? ''), (string) ($right['work_unit'] ?? '')));
        $entries = [];
        foreach ($imports as $import) {
            $records = (new SdmAttendanceRecordModel())
                ->where('import_id', (int) $import['id'])
                ->orderBy('employee_name', 'ASC')
                ->orderBy('attendance_date', 'ASC')
                ->findAll();
            if ($records === []) {
                continue;
            }
            $entries[] = [
                'import' => $import,
                'period_start' => new \DateTimeImmutable((string) $import['period_start']),
                'period_end' => new \DateTimeImmutable((string) $import['period_end']),
                'records' => $records,
            ];
        }
        if ($entries === []) {
            throw new RuntimeException('Belum ada rincian absensi yang dapat diekspor pada periode ini.');
        }

        $directory = WRITEPATH . 'cache/attendance_exports';
        if (! is_dir($directory) && ! mkdir($directory, 0770, true) && ! is_dir($directory)) {
            throw new RuntimeException('Penyimpanan sementara file export belum tersedia.');
        }
        $path = $directory . DIRECTORY_SEPARATOR . bin2hex(random_bytes(16)) . '.xlsx';
        if (! copy($template, $path)) {
            throw new RuntimeException('Template Rekap Kehadiran belum dapat disiapkan.');
        }

        try {
            $this->populateMultiUnitWorkbook($path, $entries);
        } catch (\Throwable $exception) {
            if (is_file($path)) {
                unlink($path);
            }
            throw $exception;
        }

        return [
            'path' => $path,
            'filename' => 'Rekap_Kehadiran_Semua_Unit_Kerja_' . $this->monthName($month) . '_' . $year . '_' . $this->exportTimestamp() . '.xlsx',
        ];
    }

    /** @param array<string, mixed> $import
     * @param list<array<string, mixed>> $records
     */
    private function populateWorkbook(string $path, array $import, \DateTimeImmutable $periodStart, \DateTimeImmutable $periodEnd, array $records): void
    {
        $zip = new ZipArchive();
        if ($zip->open($path) !== true) {
            throw new RuntimeException('Template Rekap Kehadiran tidak dapat dibuka.');
        }

        try {
            $workbook = $this->loadXml($zip, 'xl/workbook.xml');
            $relationships = $this->loadXml($zip, 'xl/_rels/workbook.xml.rels');
            [$detailSheet, $summarySheet] = $this->workbookSheets($workbook, $relationships);
            $unitName = $this->safeSheetName((string) ($import['work_unit'] ?? 'Unit Kerja'));
            $month = (int) ($import['report_month'] ?? $periodStart->format('n'));
            $year = (int) ($import['report_year'] ?? $periodStart->format('Y'));
            $summaryName = $this->safeSheetName('Rekap Absensi ' . $this->monthName($month) . ' ' . $year);

            $detailSheet['node']->setAttribute('name', $unitName);
            $summarySheet['node']->setAttribute('name', $summaryName);

            $overrides = $this->calendarOverrides($periodStart, $periodEnd);
            $calendar = new IndonesianHolidayCalendar($overrides);
            $data = $this->attendanceData($records, $periodStart, $periodEnd, $calendar, (string) ($import['work_unit'] ?? 'Unit Kerja'));

            $detailDocument = $this->loadXml($zip, $detailSheet['path']);
            $summaryDocument = $this->loadXml($zip, $summarySheet['path']);
            $this->removeConditionalFormatting($summaryDocument);
            $detailCells = $this->cellMap($detailDocument);
            $attendanceStyles = $this->attendanceStyles($zip, $this->rowStyles($detailCells, 8, 6, 36));
            $attendanceHeaderStyles = $this->attendanceHeaderStyles(
                $zip,
                $this->rowStyles($detailCells, 6, 6, 36),
                $this->rowStyles($detailCells, 7, 6, 36),
            );
            $this->writeDetailSheet($detailDocument, $periodStart, $periodEnd, $calendar, $data['employees'], $unitName, $attendanceStyles, $attendanceHeaderStyles);
            $this->writeSummarySheet($summaryDocument, $periodStart, $periodEnd, $calendar, $data['employees'], $unitName);

            $this->discardStaleCalculationChain($zip, $relationships);
            $this->ensureCalculationOnOpen($workbook);
            $zip->addFromString($detailSheet['path'], $detailDocument->saveXML());
            $zip->addFromString($summarySheet['path'], $summaryDocument->saveXML());
            $zip->addFromString('xl/workbook.xml', $workbook->saveXML());
        } finally {
            $zip->close();
        }
    }

    /**
     * @param list<array{import:array<string,mixed>,period_start:\DateTimeImmutable,period_end:\DateTimeImmutable,records:list<array<string,mixed>>}> $entries
     */
    private function populateMultiUnitWorkbook(string $path, array $entries): void
    {
        $zip = new ZipArchive();
        if ($zip->open($path) !== true) {
            throw new RuntimeException('Template Rekap Kehadiran tidak dapat dibuka.');
        }

        try {
            $workbook = $this->loadXml($zip, 'xl/workbook.xml');
            $relationships = $this->loadXml($zip, 'xl/_rels/workbook.xml.rels');
            [$detailSheet, $summarySheet] = $this->workbookSheets($workbook, $relationships);
            $detailTemplate = $zip->getFromName($detailSheet['path']);
            if (! is_string($detailTemplate)) {
                throw new RuntimeException('Sheet detail pada template Rekap Kehadiran tidak ditemukan.');
            }
            $detailRelationshipsPath = dirname($detailSheet['path']) . '/_rels/' . basename($detailSheet['path']) . '.rels';
            $detailRelationships = $zip->getFromName($detailRelationshipsPath);
            $contentTypes = $this->loadXml($zip, '[Content_Types].xml');
            $detailDocument = $this->loadXml($zip, $detailSheet['path']);
            $detailCells = $this->cellMap($detailDocument);
            $attendanceStyles = $this->attendanceStyles($zip, $this->rowStyles($detailCells, 8, 6, 36));
            $attendanceHeaderStyles = $this->attendanceHeaderStyles(
                $zip,
                $this->rowStyles($detailCells, 6, 6, 36),
                $this->rowStyles($detailCells, 7, 6, 36),
            );

            $this->removeWorkbookSheet($workbook, $relationships, $summarySheet['node']);
            $usedNames = [];
            $nextSheetId = $this->nextSheetId($workbook);
            $nextSheetNumber = 3;

            foreach ($entries as $index => $entry) {
                $import = $entry['import'];
                $unitName = $this->uniqueSheetName((string) ($import['work_unit'] ?? 'Unit Kerja'), $usedNames);
                $usedNames[] = $unitName;
                if ($index === 0) {
                    $sheet = $detailSheet['node'];
                    $sheetPath = $detailSheet['path'];
                    $document = $detailDocument;
                } else {
                    $sheetPath = 'xl/worksheets/sheet' . $nextSheetNumber . '.xml';
                    $nextSheetNumber++;
                    $document = $this->loadXmlString($detailTemplate, $sheetPath);
                    $relationshipId = $this->appendWorksheetRelationship($relationships, $sheetPath);
                    $sheet = $this->appendWorkbookSheet($workbook, $unitName, $nextSheetId, $relationshipId);
                    $nextSheetId++;
                    $this->addWorksheetContentType($contentTypes, $sheetPath);
                    if (is_string($detailRelationships)) {
                        $cloneRelationshipsPath = dirname($sheetPath) . '/_rels/' . basename($sheetPath) . '.rels';
                        $zip->addFromString($cloneRelationshipsPath, $detailRelationships);
                    }
                }

                $sheet->setAttribute('name', $unitName);
                $this->setSheetSelected($document, $index === 0);
                $periodStart = $entry['period_start'];
                $periodEnd = $entry['period_end'];
                $calendar = new IndonesianHolidayCalendar($this->calendarOverrides($periodStart, $periodEnd));
                $data = $this->attendanceData($entry['records'], $periodStart, $periodEnd, $calendar, (string) ($import['work_unit'] ?? 'Unit Kerja'));
                $this->writeDetailSheet($document, $periodStart, $periodEnd, $calendar, $data['employees'], $unitName, $attendanceStyles, $attendanceHeaderStyles);
                $zip->addFromString($sheetPath, $document->saveXML());
            }

            $this->discardStaleCalculationChain($zip, $relationships);
            $this->removeCalculationChainContentType($contentTypes);
            $this->ensureCalculationOnOpen($workbook);
            $zip->addFromString('[Content_Types].xml', $contentTypes->saveXML());
            $zip->addFromString('xl/workbook.xml', $workbook->saveXML());
        } finally {
            $zip->close();
        }
    }

    /** @return array{0:array{node:DOMElement,path:string},1:array{node:DOMElement,path:string}} */
    private function workbookSheets(DOMDocument $workbook, DOMDocument $relationships): array
    {
        $relationshipsXPath = new DOMXPath($relationships);
        $relationshipsXPath->registerNamespace('r', self::REL_NS);
        $targets = [];
        foreach ($relationshipsXPath->query('//r:Relationship') ?: [] as $relationship) {
            if ($relationship instanceof DOMElement) {
                $targets[$relationship->getAttribute('Id')] = $this->zipPath($relationship->getAttribute('Target'));
            }
        }

        $workbookXPath = new DOMXPath($workbook);
        $workbookXPath->registerNamespace('m', self::MAIN_NS);
        $workbookXPath->registerNamespace('r', self::OFFICE_REL_NS);
        $sheets = [];
        foreach ($workbookXPath->query('//m:sheets/m:sheet') ?: [] as $sheet) {
            if (! $sheet instanceof DOMElement) {
                continue;
            }
            $relationshipId = $sheet->getAttributeNS(self::OFFICE_REL_NS, 'id');
            if (! isset($targets[$relationshipId])) {
                throw new RuntimeException('Relasi sheet pada template Rekap Kehadiran tidak lengkap.');
            }
            $sheets[] = ['node' => $sheet, 'path' => $targets[$relationshipId]];
        }
        if (count($sheets) < 2) {
            throw new RuntimeException('Template Rekap Kehadiran harus memiliki sheet detail dan rekap.');
        }

        return [$sheets[0], $sheets[1]];
    }

    /** @return array<string, array{is_holiday:bool,label:string|null}> */
    private function calendarOverrides(\DateTimeImmutable $periodStart, \DateTimeImmutable $periodEnd): array
    {
        $rows = (new SdmAttendanceCalendarModel())
            ->where('calendar_date >=', $periodStart->format('Y-m-d'))
            ->where('calendar_date <=', $periodEnd->format('Y-m-d'))
            ->findAll();
        $overrides = [];
        foreach ($rows as $row) {
            $overrides[(string) $row['calendar_date']] = [
                'is_holiday' => (bool) $row['is_holiday'],
                'label' => $row['label'] !== null ? (string) $row['label'] : null,
            ];
        }

        return $overrides;
    }

    /** @param list<array<string, mixed>> $records
     * @return array{employees:list<array<string, mixed>>}
     */
    private function attendanceData(array $records, \DateTimeImmutable $periodStart, \DateTimeImmutable $periodEnd, IndonesianHolidayCalendar $calendar, string $workUnit): array
    {
        $employees = [];
        foreach ($records as $record) {
            $key = (string) $record['employee_key'];
            if (! isset($employees[$key])) {
                $employees[$key] = [
                    'employee_no' => (string) ($record['employee_no'] ?: '-'),
                    'employee_name' => (string) $record['employee_name'],
                    'position' => (string) ($record['position'] ?: '-'),
                    'organization' => (string) ($record['organization'] ?: '-'),
                    'work_unit' => $workUnit,
                    'days' => [],
                ];
            }
            $date = new \DateTimeImmutable((string) $record['attendance_date']);
            $employees[$key]['days'][(int) $date->format('j')] = $this->normalizedCode($record, $calendar->info($date));
        }

        foreach ($employees as &$employee) {
            for ($date = $periodStart; $date <= $periodEnd; $date = $date->modify('+1 day')) {
                $day = (int) $date->format('j');
                if (! isset($employee['days'][$day])) {
                    $employee['days'][$day] = $calendar->isNonWorkingDay($date) ? 'OFF' : '';
                }
            }
        }
        unset($employee);

        uasort($employees, static fn (array $left, array $right): int => strnatcasecmp($left['employee_name'], $right['employee_name']));

        return ['employees' => array_values($employees)];
    }

    /** @param array<string, mixed> $record
     * @param array{is_non_working:bool,is_override:bool,label:string|null,source:string} $calendarInfo
     */
    private function normalizedCode(array $record, array $calendarInfo): string
    {
        $remark = (string) ($record['remark'] ?? '');
        if (preg_match('/\b(?:on\s*duty\s*request|odr)\b/i', $remark) === 1) {
            return 'ODR';
        }
        if ($calendarInfo['is_non_working']) {
            return 'OFF';
        }

        $code = $this->normalizeLeaveCode((string) ($record['recap_code'] ?? ''));
        if ($code === 'I') {
            $code = strtoupper((string) ($record['raw_status'] ?? '')) === 'IZ' ? 'IZ' : 'CT';
        }
        $actualIn = $record['actual_in'] ?? null;
        $actualOut = $record['actual_out'] ?? null;
        if ($actualIn !== null && $actualOut !== null && in_array($code, ['H', 'TLBT', 'TLTAP'], true)) {
            $code = (string) $actualIn >= '08:01:00' ? 'TLBT' : 'H';
        } elseif ($actualIn !== null && $actualOut === null && in_array($code, ['TAP', 'TLTAP'], true)) {
            $code = (string) $actualIn >= '08:01:00' ? 'TLTAP' : 'TAP';
        }

        return $this->displayCode($code);
    }

    /**
     * @param array<string, string> $attendanceStyles
     * @param array<int, array<int, array<string, string>>> $attendanceHeaderStyles
     */
    private function writeDetailSheet(DOMDocument $document, \DateTimeImmutable $periodStart, \DateTimeImmutable $periodEnd, IndonesianHolidayCalendar $calendar, array $employees, string $unitName, array $attendanceStyles, array $attendanceHeaderStyles): void
    {
        $cells = $this->cellMap($document);
        $styles = $this->rowStyles($cells, 8, 1, 37);
        $this->removeDataValidations($document);
        $this->removeConditionalFormatting($document);
        $this->setInlineString($document, $cells['A1'] ?? null, 'DETAIL ABSENSI HARIAN KARYAWAN');
        $this->setInlineString($document, $cells['A2'] ?? null, 'Unit Kerja: ' . $unitName . ' - Periode: ' . $this->dateLabel($periodStart) . ' s/d ' . $this->dateLabel($periodEnd));

        $daysInPeriod = (int) $periodEnd->format('j');
        for ($columnIndex = 6; $columnIndex <= 36; $columnIndex++) {
            $column = $this->columnName($columnIndex);
            $day = $columnIndex - 5;
            $this->setInlineString($document, $cells[$column . '6'] ?? null, $day <= $daysInPeriod ? (string) $day : '');
            $status = '';
            if ($day <= $daysInPeriod) {
                $date = $periodStart->modify('+' . ($day - 1) . ' days');
                $status = $calendar->isNonWorkingDay($date) ? 'OFF' : 'KRJ';
                $this->setCellStyle($cells[$column . '6'] ?? null, $attendanceHeaderStyles[6][$columnIndex][$status] ?? null);
                $this->setCellStyle($cells[$column . '7'] ?? null, $attendanceHeaderStyles[7][$columnIndex][$status] ?? null);
            }
            $this->setInlineString($document, $cells[$column . '7'] ?? null, $status);
        }

        $sheetData = $this->sheetData($document);
        $this->removeRowsFrom($sheetData, 8);
        $rowNumber = 8;
        foreach ($employees as $index => $employee) {
            $row = $document->createElementNS(self::MAIN_NS, 'row');
            $row->setAttribute('r', (string) $rowNumber);
            $this->appendCell($document, $row, 'A' . $rowNumber, $styles[1] ?? null, 'number', $index + 1);
            $this->appendCell($document, $row, 'B' . $rowNumber, $styles[2] ?? null, 'text', $employee['employee_no']);
            $this->appendCell($document, $row, 'C' . $rowNumber, $styles[3] ?? null, 'text', $employee['employee_name']);
            $this->appendCell($document, $row, 'D' . $rowNumber, $styles[4] ?? null, 'text', $employee['position']);
            $this->appendCell($document, $row, 'E' . $rowNumber, $styles[5] ?? null, 'text', $employee['organization']);
            for ($columnIndex = 6; $columnIndex <= 36; $columnIndex++) {
                $day = $columnIndex - 5;
                $code = $day <= $daysInPeriod ? (string) ($employee['days'][$day] ?? '') : '';
                $baseStyle = $styles[$columnIndex] ?? '';
                $style = $attendanceStyles[$code] ?? $baseStyle;
                $this->appendCell($document, $row, $this->columnName($columnIndex) . $rowNumber, $style, 'text', $code);
            }
            $attendanceRange = 'F' . $rowNumber . ':AJ' . $rowNumber;
            $workdayRange = '$F$7:$AJ$7';
            $this->appendCell(
                $document,
                $row,
                'AK' . $rowNumber,
                $styles[37] ?? null,
                'formula',
                '=COUNTIF(' . $attendanceRange . ',"H")'
                    . '+COUNTIF(' . $attendanceRange . ',"TL")'
                    . '+COUNTIF(' . $attendanceRange . ',"TL/TAP")'
                    . '+COUNTIF(' . $attendanceRange . ',"TA")'
                    . '+COUNTIF(' . $attendanceRange . ',"TAM")'
                    . '+COUNTIF(' . $attendanceRange . ',"TAP")'
                    . '+COUNTIF(' . $attendanceRange . ',"TPA")'
                    . '+COUNTIFS(' . $workdayRange . ',"KRJ",' . $attendanceRange . ',"ODR")'
            );
            $sheetData->appendChild($row);
            $rowNumber++;
        }
        $this->setDimension($document, 'A1:AK' . max(7, $rowNumber - 1));
    }

    private function writeSummarySheet(DOMDocument $document, \DateTimeImmutable $periodStart, \DateTimeImmutable $periodEnd, IndonesianHolidayCalendar $calendar, array $employees, string $detailSheetName): void
    {
        $cells = $this->cellMap($document);
        $styles = $this->rowStyles($cells, 9, 1, 17);
        $totalStyles = $this->rowStyles($cells, 25, 1, 17);
        $this->setInlineString($document, $cells['A1'] ?? null, 'REKAPITULASI PRESENSI KARYAWAN');

        $totalDays = (int) $periodEnd->format('j');
        $weekendDays = 0;
        $nonWeekendHolidays = 0;
        $effectiveDays = 0;
        for ($date = $periodStart; $date <= $periodEnd; $date = $date->modify('+1 day')) {
            $info = $calendar->info($date);
            if ($calendar->isWeekend($date)) {
                $weekendDays++;
            } elseif ($info['is_non_working']) {
                $nonWeekendHolidays++;
            }
            if (! $info['is_non_working']) {
                $effectiveDays++;
            }
        }
        $this->setInlineString($document, $cells['A5'] ?? null, $totalDays . ' Hari');
        $this->setInlineString($document, $cells['C5'] ?? null, $weekendDays . ' Hari');
        $this->setInlineString($document, $cells['E5'] ?? null, $nonWeekendHolidays . ' Hari');
        $this->setInlineString($document, $cells['G5'] ?? null, $effectiveDays . ' Hari');
        $this->setInlineString($document, $cells['I8'] ?? null, 'Izin / Cuti');

        $sheetData = $this->sheetData($document);
        $this->removeRowsFrom($sheetData, 9);
        $summaryRow = 9;
        foreach ($employees as $index => $employee) {
            $detailRow = $index + 8;
            $range = $this->quotedSheetName($detailSheetName) . '!$F$' . $detailRow . ':$AJ$' . $detailRow;
            $row = $document->createElementNS(self::MAIN_NS, 'row');
            $row->setAttribute('r', (string) $summaryRow);
            $this->appendCell($document, $row, 'A' . $summaryRow, $styles[1] ?? null, 'number', $index + 1);
            $this->appendCell($document, $row, 'B' . $summaryRow, $styles[2] ?? null, 'text', $employee['employee_no']);
            $this->appendCell($document, $row, 'C' . $summaryRow, $styles[3] ?? null, 'text', $employee['employee_name']);
            $this->appendCell($document, $row, 'D' . $summaryRow, $styles[4] ?? null, 'text', $employee['position']);
            $this->appendCell($document, $row, 'E' . $summaryRow, $styles[5] ?? null, 'text', $employee['organization']);
            $this->appendCell($document, $row, 'F' . $summaryRow, $styles[6] ?? null, 'number', $effectiveDays);
            $this->appendCell($document, $row, 'G' . $summaryRow, $styles[7] ?? null, 'formula', '=COUNTIF(' . $range . ',"H")+COUNTIF(' . $range . ',"ODR")');
            $this->appendCell($document, $row, 'H' . $summaryRow, $styles[8] ?? null, 'formula', '=COUNTIF(' . $range . ',"TL")+COUNTIF(' . $range . ',"TL/TAP")');
            $this->appendCell($document, $row, 'I' . $summaryRow, $styles[9] ?? null, 'formula', '=COUNTIF(' . $range . ',"IZ")+COUNTIF(' . $range . ',"CT")');
            $this->appendCell($document, $row, 'J' . $summaryRow, $styles[10] ?? null, 'formula', '=COUNTIF(' . $range . ',"A")');
            $this->appendCell($document, $row, 'K' . $summaryRow, $styles[11] ?? null, 'formula', '=COUNTIF(' . $range . ',"TAM")');
            $this->appendCell($document, $row, 'L' . $summaryRow, $styles[12] ?? null, 'formula', '=COUNTIF(' . $range . ',"TAP")+COUNTIF(' . $range . ',"TL/TAP")');
            $this->appendCell($document, $row, 'M' . $summaryRow, $styles[13] ?? null, 'formula', '=COUNTIF(' . $range . ',"TPA")');
            $this->appendCell($document, $row, 'N' . $summaryRow, $styles[14] ?? null, 'formula', '=G' . $summaryRow . '+(COUNTIF(' . $range . ',"TL")*0.75)+(COUNTIF(' . $range . ',"TL/TAP")*0.25)+(K' . $summaryRow . '*0.5)+(COUNTIF(' . $range . ',"TAP")*0.5)+(M' . $summaryRow . '*0.25)+(COUNTIF(' . $range . ',"TA")*0.25)');
            $this->appendCell($document, $row, 'O' . $summaryRow, $styles[15] ?? null, 'formula', '=IF(F' . $summaryRow . '>0,N' . $summaryRow . '/F' . $summaryRow . ',0)');
            $presence = 'G' . $summaryRow . '+H' . $summaryRow . '+K' . $summaryRow . '+L' . $summaryRow . '+M' . $summaryRow . '+COUNTIF(' . $range . ',"TA")-COUNTIF(' . $range . ',"TL/TAP")';
            $this->appendCell($document, $row, 'P' . $summaryRow, $styles[16] ?? null, 'formula', '=IF((' . $presence . ')>0,G' . $summaryRow . '/(' . $presence . '),0)');
            $this->appendCell($document, $row, 'Q' . $summaryRow, $styles[17] ?? null, 'formula', '=AVERAGE(O' . $summaryRow . ',P' . $summaryRow . ')');
            $sheetData->appendChild($row);
            $summaryRow++;
        }

        $totalRow = $summaryRow;
        $row = $document->createElementNS(self::MAIN_NS, 'row');
        $row->setAttribute('r', (string) $totalRow);
        $this->appendCell($document, $row, 'C' . $totalRow, $totalStyles[3] ?? null, 'text', 'Total / Rata-rata');
        $this->appendCell($document, $row, 'F' . $totalRow, $totalStyles[6] ?? null, 'number', $effectiveDays);
        foreach (['G', 'H', 'I', 'J', 'K', 'L', 'M', 'N'] as $column) {
            $this->appendCell($document, $row, $column . $totalRow, $totalStyles[$this->columnIndex($column)] ?? null, 'formula', '=SUM(' . $column . '9:' . $column . ($totalRow - 1) . ')');
        }
        foreach (['O', 'P', 'Q'] as $column) {
            $this->appendCell($document, $row, $column . $totalRow, $totalStyles[$this->columnIndex($column)] ?? null, 'formula', '=AVERAGE(' . $column . '9:' . $column . ($totalRow - 1) . ')');
        }
        $sheetData->appendChild($row);
        $this->setDimension($document, 'A1:Q' . $totalRow);
    }

    /** @return array<int, string> */
    private function rowStyles(array $cells, int $row, int $startColumn, int $endColumn): array
    {
        $styles = [];
        for ($column = $startColumn; $column <= $endColumn; $column++) {
            $style = $cells[$this->columnName($column) . $row] ?? null;
            $styles[$column] = $style instanceof DOMElement ? $style->getAttribute('s') : '';
        }
        return $styles;
    }

    /**
     * Creates status styles inside the workbook so the exported cells use the
     * same palette as the attendance screen. The source template can keep its
     * own borders, number formats, and alignment while every status receives
     * one canonical style across all work-unit sheets.
     *
     * @param array<int, string> $baseStyles
     * @return array<string, string>
     */
    private function attendanceStyles(ZipArchive $zip, array $baseStyles): array
    {
        $document = $this->loadXml($zip, 'xl/styles.xml');
        $xpath = new DOMXPath($document);
        $xpath->registerNamespace('m', self::MAIN_NS);
        $fonts = $xpath->query('//m:fonts')?->item(0);
        $fills = $xpath->query('//m:fills')?->item(0);
        $cellXfs = $xpath->query('//m:cellXfs')?->item(0);
        if (! $fonts instanceof DOMElement || ! $fills instanceof DOMElement || ! $cellXfs instanceof DOMElement) {
            throw new RuntimeException('Gaya warna template Rekap Kehadiran tidak dapat dibaca.');
        }

        $styleNodes = iterator_to_array($xpath->query('//m:cellXfs/m:xf') ?: []);
        $baseStyles = array_values(array_unique(array_filter($baseStyles, static fn (string $style): bool => ctype_digit($style))));
        $baseStyle = $baseStyles[0] ?? '0';

        $styles = [];
        foreach ($this->attendancePalette() as $code => $palette) {
            $baseXf = $styleNodes[(int) $baseStyle] ?? $styleNodes[0] ?? null;
            if (! $baseXf instanceof DOMElement) {
                throw new RuntimeException('Gaya dasar template Rekap Kehadiran tidak ditemukan.');
            }
            $fontId = $this->appendAttendanceFont($document, $fonts, $baseXf, $palette['font']);
            $fillId = $this->appendAttendanceFill($document, $fills, $palette['fill']);
            $style = $baseXf->cloneNode(true);
            if (! $style instanceof DOMElement) {
                throw new RuntimeException('Gaya status absensi tidak dapat dibuat.');
            }
            $style->setAttribute('fontId', (string) $fontId);
            $style->setAttribute('fillId', (string) $fillId);
            $style->setAttribute('applyFont', '1');
            $style->setAttribute('applyFill', '1');
            $styleId = count($styleNodes);
            $cellXfs->appendChild($style);
            $styleNodes[] = $style;
            $styles[$code] = (string) $styleId;
        }
        $fonts->setAttribute('count', (string) count(iterator_to_array($xpath->query('//m:fonts/m:font') ?: [])));
        $fills->setAttribute('count', (string) count(iterator_to_array($xpath->query('//m:fills/m:fill') ?: [])));
        $cellXfs->setAttribute('count', (string) count($styleNodes));
        $this->pendingAttendanceStylesDocument = $document;

        return $styles;
    }

    /**
     * Gives the date and work-status headers one consistent colour per day:
     * blue for KRJ and peach for OFF. Their original border, size and alignment
     * remain intact so the report still follows the approved template.
     *
     * @param array<int, string> $dateBaseStyles
     * @param array<int, string> $statusBaseStyles
     * @return array<int, array<int, array<string, string>>>
     */
    private function attendanceHeaderStyles(ZipArchive $zip, array $dateBaseStyles, array $statusBaseStyles): array
    {
        $document = $this->pendingAttendanceStylesDocument ?? $this->loadXml($zip, 'xl/styles.xml');
        $xpath = new DOMXPath($document);
        $xpath->registerNamespace('m', self::MAIN_NS);
        $fonts = $xpath->query('//m:fonts')?->item(0);
        $fills = $xpath->query('//m:fills')?->item(0);
        $cellXfs = $xpath->query('//m:cellXfs')?->item(0);
        if (! $fonts instanceof DOMElement || ! $fills instanceof DOMElement || ! $cellXfs instanceof DOMElement) {
            throw new RuntimeException('Gaya header template Rekap Kehadiran tidak dapat dibaca.');
        }

        $styleNodes = iterator_to_array($xpath->query('//m:cellXfs/m:xf') ?: []);
        $palettes = [
            'KRJ' => ['fill' => 'FF295C8D', 'font' => 'FFFFFFFF'],
            'OFF' => ['fill' => 'FFFFF0E3', 'font' => 'FFF00000'],
        ];
        $result = [];
        foreach ([6 => $dateBaseStyles, 7 => $statusBaseStyles] as $row => $baseStyles) {
            foreach ($baseStyles as $column => $baseStyle) {
                $baseXf = ctype_digit($baseStyle) ? ($styleNodes[(int) $baseStyle] ?? null) : null;
                if (! $baseXf instanceof DOMElement) {
                    throw new RuntimeException('Gaya dasar header template Rekap Kehadiran tidak ditemukan.');
                }
                foreach ($palettes as $code => $palette) {
                    $fontId = $this->appendAttendanceFont($document, $fonts, $baseXf, $palette['font']);
                    $fillId = $this->appendAttendanceFill($document, $fills, $palette['fill']);
                    $style = $baseXf->cloneNode(true);
                    if (! $style instanceof DOMElement) {
                        throw new RuntimeException('Gaya header absensi tidak dapat dibuat.');
                    }
                    $style->setAttribute('fontId', (string) $fontId);
                    $style->setAttribute('fillId', (string) $fillId);
                    $style->setAttribute('applyFont', '1');
                    $style->setAttribute('applyFill', '1');
                    $styleId = count($styleNodes);
                    $cellXfs->appendChild($style);
                    $styleNodes[] = $style;
                    $result[$row][$column][$code] = (string) $styleId;
                }
            }
        }
        $fonts->setAttribute('count', (string) count(iterator_to_array($xpath->query('//m:fonts/m:font') ?: [])));
        $fills->setAttribute('count', (string) count(iterator_to_array($xpath->query('//m:fills/m:fill') ?: [])));
        $cellXfs->setAttribute('count', (string) count($styleNodes));
        $zip->addFromString('xl/styles.xml', $document->saveXML());
        $this->pendingAttendanceStylesDocument = null;

        return $result;
    }

    /** @return array<string, array{fill:string,font:string}> */
    private function attendancePalette(): array
    {
        return [
            'H'      => ['fill' => 'FFFFFFFF', 'font' => 'FF182B43'],
            'TL'     => ['fill' => 'FFFFF0E3', 'font' => 'FFE66F00'],
            'TL/TAP' => ['fill' => 'FF8D5929', 'font' => 'FFFFFFFF'],
            'ODR'    => ['fill' => 'FF0F766E', 'font' => 'FFFFFFFF'],
            'IZ'     => ['fill' => 'FFCFF3D3', 'font' => 'FF147F35'],
            'CT'     => ['fill' => 'FF5F7111', 'font' => 'FFFFFFFF'],
            'A'      => ['fill' => 'FFFFD9D9', 'font' => 'FFD90000'],
            'TA'     => ['fill' => 'FFF7DADA', 'font' => 'FF9E3939'],
            'TAM'    => ['fill' => 'FF9C3D3B', 'font' => 'FFFFFFFF'],
            'TAP'    => ['fill' => 'FF295C8D', 'font' => 'FFFFFFFF'],
            'TPA'    => ['fill' => 'FFF2CACA', 'font' => 'FFA94747'],
            'OFF'    => ['fill' => 'FFFFF0E3', 'font' => 'FFF00000'],
        ];
    }

    private function appendAttendanceFont(DOMDocument $document, DOMElement $fonts, DOMElement $baseXf, string $color): int
    {
        $fontId = (int) $baseXf->getAttribute('fontId');
        $font = $fonts->childNodes->item($fontId)?->cloneNode(true);
        if (! $font instanceof DOMElement) {
            throw new RuntimeException('Huruf template Rekap Kehadiran tidak ditemukan.');
        }
        foreach (iterator_to_array($font->childNodes) as $child) {
            if ($child instanceof DOMElement && $child->localName === 'color') {
                $font->removeChild($child);
            }
        }
        $fontColor = $document->createElementNS(self::MAIN_NS, 'color');
        $fontColor->setAttribute('rgb', $color);
        $font->appendChild($fontColor);
        $index = count(iterator_to_array($fonts->childNodes));
        $fonts->appendChild($font);

        return $index;
    }

    private function appendAttendanceFill(DOMDocument $document, DOMElement $fills, string $color): int
    {
        $fill = $document->createElementNS(self::MAIN_NS, 'fill');
        $pattern = $document->createElementNS(self::MAIN_NS, 'patternFill');
        $pattern->setAttribute('patternType', 'solid');
        $foreground = $document->createElementNS(self::MAIN_NS, 'fgColor');
        $foreground->setAttribute('rgb', $color);
        $background = $document->createElementNS(self::MAIN_NS, 'bgColor');
        $background->setAttribute('indexed', '64');
        $pattern->appendChild($foreground);
        $pattern->appendChild($background);
        $fill->appendChild($pattern);
        $index = count(iterator_to_array($fills->childNodes));
        $fills->appendChild($fill);

        return $index;
    }

    private function displayCode(string $code): string
    {
        return match ($code) {
            'TLBT' => 'TL',
            'TLTAP' => 'TL/TAP',
            default => $code,
        };
    }

    private function normalizeLeaveCode(string $code): string
    {
        $code = strtoupper(trim($code));

        return in_array($code, self::LEAVE_CODES, true) || preg_match('/^CB(?:\d+)?$/', $code) === 1
            ? 'CT'
            : $code;
    }

    private function sheetData(DOMDocument $document): DOMElement
    {
        $xpath = new DOMXPath($document);
        $xpath->registerNamespace('m', self::MAIN_NS);
        $sheetData = $xpath->query('//m:sheetData')?->item(0);
        if (! $sheetData instanceof DOMElement) {
            throw new RuntimeException('Struktur sheet template tidak lengkap.');
        }
        return $sheetData;
    }

    /** @return array<string, DOMElement> */
    private function cellMap(DOMDocument $document): array
    {
        $xpath = new DOMXPath($document);
        $xpath->registerNamespace('m', self::MAIN_NS);
        $cells = [];
        foreach ($xpath->query('//m:sheetData/m:row/m:c') ?: [] as $cell) {
            if ($cell instanceof DOMElement) {
                $cells[$cell->getAttribute('r')] = $cell;
            }
        }
        return $cells;
    }

    private function removeRowsFrom(DOMElement $sheetData, int $fromRow): void
    {
        foreach (iterator_to_array($sheetData->childNodes) as $row) {
            if ($row instanceof DOMElement && (int) $row->getAttribute('r') >= $fromRow) {
                $sheetData->removeChild($row);
            }
        }
    }

    /** The exported workbook is a read-only recap, so no status dropdowns are retained. */
    private function removeDataValidations(DOMDocument $document): void
    {
        $xpath = new DOMXPath($document);
        $xpath->registerNamespace('m', self::MAIN_NS);
        foreach ($xpath->query('//m:dataValidations') ?: [] as $validations) {
            $validations->parentNode?->removeChild($validations);
        }
    }

    private function appendCell(DOMDocument $document, DOMElement $row, string $reference, string|null $style, string $type, string|int|float $value): void
    {
        $cell = $document->createElementNS(self::MAIN_NS, 'c');
        $cell->setAttribute('r', $reference);
        if ($style !== null && $style !== '') {
            $cell->setAttribute('s', $style);
        }
        if ($type === 'text') {
            $cell->setAttribute('t', 'inlineStr');
            $inline = $document->createElementNS(self::MAIN_NS, 'is');
            $text = $document->createElementNS(self::MAIN_NS, 't');
            $text->appendChild($document->createTextNode((string) $value));
            $inline->appendChild($text);
            $cell->appendChild($inline);
        } elseif ($type === 'formula') {
            $formula = $document->createElementNS(self::MAIN_NS, 'f');
            $formula->appendChild($document->createTextNode(ltrim((string) $value, '=')));
            $cell->appendChild($formula);
        } else {
            $number = $document->createElementNS(self::MAIN_NS, 'v');
            $number->appendChild($document->createTextNode((string) $value));
            $cell->appendChild($number);
        }
        $row->appendChild($cell);
    }

    private function setInlineString(DOMDocument $document, ?DOMElement $cell, string $value): void
    {
        if (! $cell instanceof DOMElement) {
            return;
        }
        foreach (iterator_to_array($cell->childNodes) as $child) {
            if (in_array($child->localName, ['f', 'v', 'is'], true)) {
                $cell->removeChild($child);
            }
        }
        $cell->setAttribute('t', 'inlineStr');
        $inline = $document->createElementNS(self::MAIN_NS, 'is');
        $text = $document->createElementNS(self::MAIN_NS, 't');
        $text->appendChild($document->createTextNode($value));
        $inline->appendChild($text);
        $cell->appendChild($inline);
    }

    private function setCellStyle(?DOMElement $cell, ?string $style): void
    {
        if (! $cell instanceof DOMElement || $style === null || ! ctype_digit($style)) {
            return;
        }
        $cell->setAttribute('s', $style);
    }

    private function setDimension(DOMDocument $document, string $reference): void
    {
        $xpath = new DOMXPath($document);
        $xpath->registerNamespace('m', self::MAIN_NS);
        $dimension = $xpath->query('//m:dimension')?->item(0);
        if ($dimension instanceof DOMElement) {
            $dimension->setAttribute('ref', $reference);
        }
    }

    private function removeConditionalFormatting(DOMDocument $document): void
    {
        $xpath = new DOMXPath($document);
        $xpath->registerNamespace('m', self::MAIN_NS);
        foreach ($xpath->query('//m:conditionalFormatting') ?: [] as $rule) {
            $rule->parentNode?->removeChild($rule);
        }
    }

    private function ensureCalculationOnOpen(DOMDocument $workbook): void
    {
        $xpath = new DOMXPath($workbook);
        $xpath->registerNamespace('m', self::MAIN_NS);
        $calculation = $xpath->query('//m:calcPr')?->item(0);
        if (! $calculation instanceof DOMElement) {
            $calculation = $workbook->createElementNS(self::MAIN_NS, 'calcPr');
            $workbook->documentElement?->appendChild($calculation);
        }
        $calculation->setAttribute('calcMode', 'auto');
        $calculation->setAttribute('fullCalcOnLoad', '1');
        $calculation->setAttribute('forceFullCalc', '1');
    }

    /**
     * The template contains a calculation chain for its original cells. Once the
     * report rows and formulas are rebuilt, that chain no longer matches and
     * Microsoft Excel attempts to repair the workbook. Let Excel build a fresh
     * chain when it opens the exported file instead.
     */
    private function discardStaleCalculationChain(ZipArchive $zip, DOMDocument $relationships): void
    {
        $relationshipsXPath = new DOMXPath($relationships);
        $relationshipsXPath->registerNamespace('r', self::REL_NS);
        foreach ($relationshipsXPath->query('//r:Relationship[contains(@Type, \'/calcChain\')]') ?: [] as $relationship) {
            $relationship->parentNode?->removeChild($relationship);
        }

        $contentTypes = $this->loadXml($zip, '[Content_Types].xml');
        $this->removeCalculationChainContentType($contentTypes);

        $zip->deleteName('xl/calcChain.xml');
        $zip->addFromString('xl/_rels/workbook.xml.rels', $relationships->saveXML());
        $zip->addFromString('[Content_Types].xml', $contentTypes->saveXML());
    }

    private function removeCalculationChainContentType(DOMDocument $contentTypes): void
    {
        $contentTypesXPath = new DOMXPath($contentTypes);
        foreach ($contentTypesXPath->query('//*[local-name() = \'Override\' and @PartName = \'/xl/calcChain.xml\']') ?: [] as $override) {
            $override->parentNode?->removeChild($override);
        }
    }

    private function removeWorkbookSheet(DOMDocument $workbook, DOMDocument $relationships, DOMElement $sheet): void
    {
        $relationshipId = $sheet->getAttributeNS(self::OFFICE_REL_NS, 'id');
        $sheet->parentNode?->removeChild($sheet);
        if ($relationshipId === '') {
            return;
        }

        $xpath = new DOMXPath($relationships);
        $xpath->registerNamespace('r', self::REL_NS);
        foreach ($xpath->query('//r:Relationship[@Id = ' . $this->xpathLiteral($relationshipId) . ']') ?: [] as $relationship) {
            $relationship->parentNode?->removeChild($relationship);
        }
    }

    private function nextSheetId(DOMDocument $workbook): int
    {
        $xpath = new DOMXPath($workbook);
        $xpath->registerNamespace('m', self::MAIN_NS);
        $highest = 0;
        foreach ($xpath->query('//m:sheets/m:sheet') ?: [] as $sheet) {
            if ($sheet instanceof DOMElement) {
                $highest = max($highest, (int) $sheet->getAttribute('sheetId'));
            }
        }

        return $highest + 1;
    }

    /** @param list<string> $usedNames */
    private function uniqueSheetName(string $value, array $usedNames): string
    {
        $base = $this->safeSheetName($value);
        $candidate = $base;
        $suffix = 2;
        while (in_array(mb_strtolower($candidate), array_map('mb_strtolower', $usedNames), true)) {
            $tail = ' (' . $suffix . ')';
            $candidate = mb_substr($base, 0, 31 - mb_strlen($tail)) . $tail;
            $suffix++;
        }

        return $candidate;
    }

    private function appendWorksheetRelationship(DOMDocument $relationships, string $sheetPath): string
    {
        $xpath = new DOMXPath($relationships);
        $xpath->registerNamespace('r', self::REL_NS);
        $highest = 0;
        foreach ($xpath->query('//r:Relationship') ?: [] as $relationship) {
            if ($relationship instanceof DOMElement && preg_match('/^rId(\d+)$/', $relationship->getAttribute('Id'), $matches) === 1) {
                $highest = max($highest, (int) $matches[1]);
            }
        }
        $relationshipId = 'rId' . ($highest + 1);
        $relationship = $relationships->createElementNS(self::REL_NS, 'Relationship');
        $relationship->setAttribute('Id', $relationshipId);
        $relationship->setAttribute('Type', self::OFFICE_REL_NS . '/worksheet');
        $relationship->setAttribute('Target', ltrim(substr($sheetPath, 3), '/'));
        $relationships->documentElement?->appendChild($relationship);

        return $relationshipId;
    }

    private function appendWorkbookSheet(DOMDocument $workbook, string $name, int $sheetId, string $relationshipId): DOMElement
    {
        $xpath = new DOMXPath($workbook);
        $xpath->registerNamespace('m', self::MAIN_NS);
        $sheets = $xpath->query('//m:sheets')?->item(0);
        if (! $sheets instanceof DOMElement) {
            throw new RuntimeException('Struktur daftar sheet template tidak valid.');
        }
        $sheet = $workbook->createElementNS(self::MAIN_NS, 'sheet');
        $sheet->setAttribute('name', $name);
        $sheet->setAttribute('sheetId', (string) $sheetId);
        $sheet->setAttributeNS(self::OFFICE_REL_NS, 'r:id', $relationshipId);
        $sheets->appendChild($sheet);

        return $sheet;
    }

    private function addWorksheetContentType(DOMDocument $contentTypes, string $sheetPath): void
    {
        $xpath = new DOMXPath($contentTypes);
        $partName = '/' . ltrim($sheetPath, '/');
        if ($xpath->query('//*[local-name() = "Override" and @PartName = ' . $this->xpathLiteral($partName) . ']')?->length > 0) {
            return;
        }
        $namespace = $contentTypes->documentElement?->namespaceURI ?? 'http://schemas.openxmlformats.org/package/2006/content-types';
        $override = $contentTypes->createElementNS($namespace, 'Override');
        $override->setAttribute('PartName', $partName);
        $override->setAttribute('ContentType', 'application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml');
        $contentTypes->documentElement?->appendChild($override);
    }

    private function setSheetSelected(DOMDocument $document, bool $selected): void
    {
        $xpath = new DOMXPath($document);
        $xpath->registerNamespace('m', self::MAIN_NS);
        foreach ($xpath->query('//m:sheetView') ?: [] as $sheetView) {
            if (! $sheetView instanceof DOMElement) {
                continue;
            }
            if ($selected) {
                $sheetView->setAttribute('tabSelected', '1');
            } else {
                $sheetView->removeAttribute('tabSelected');
            }
        }
    }

    private function xpathLiteral(string $value): string
    {
        if (! str_contains($value, "'")) {
            return "'" . $value . "'";
        }
        if (! str_contains($value, '"')) {
            return '"' . $value . '"';
        }

        return "concat('" . str_replace("'", "', \"'\", '", $value) . "')";
    }

    /** @return list<string> */
    private function sharedStrings(ZipArchive $zip): array
    {
        $contents = $zip->getFromName('xl/sharedStrings.xml');
        if (! is_string($contents)) {
            return [];
        }
        $document = new DOMDocument('1.0', 'UTF-8');
        if (! $document->loadXML($contents, LIBXML_NONET | LIBXML_NOBLANKS)) {
            return [];
        }
        $xpath = new DOMXPath($document);
        $xpath->registerNamespace('m', self::MAIN_NS);
        $strings = [];
        foreach ($xpath->query('//m:si') ?: [] as $string) {
            $strings[] = trim($string->textContent);
        }
        return $strings;
    }

    private function cellText(DOMElement $cell, array $sharedStrings): string
    {
        if ($cell->getAttribute('t') === 'inlineStr') {
            return trim($cell->textContent);
        }
        $value = trim($cell->textContent);
        if ($cell->getAttribute('t') === 's' && ctype_digit($value)) {
            return (string) ($sharedStrings[(int) $value] ?? '');
        }
        return $value;
    }

    private function loadXml(ZipArchive $zip, string $path): DOMDocument
    {
        $contents = $zip->getFromName($path);
        if (! is_string($contents)) {
            throw new RuntimeException('Bagian template tidak ditemukan: ' . $path . '.');
        }
        return $this->loadXmlString($contents, $path);
    }

    private function loadXmlString(string $contents, string $path): DOMDocument
    {
        $document = new DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);
        try {
            if (! $document->loadXML($contents, LIBXML_NONET | LIBXML_NOBLANKS)) {
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
        return str_starts_with($target, '/') ? ltrim($target, '/') : (str_starts_with($target, 'xl/') ? $target : 'xl/' . ltrim($target, '/'));
    }

    private function columnName(int $column): string
    {
        $name = '';
        while ($column > 0) {
            $column--;
            $name = chr(65 + ($column % 26)) . $name;
            $column = intdiv($column, 26);
        }
        return $name;
    }

    private function columnIndex(string $column): int
    {
        $index = 0;
        foreach (str_split($column) as $letter) {
            $index = ($index * 26) + (ord($letter) - 64);
        }
        return $index;
    }

    private function quotedSheetName(string $name): string
    {
        return "'" . str_replace("'", "''", $name) . "'";
    }

    private function safeSheetName(string $value): string
    {
        $value = trim(preg_replace('/[\\\\\\/\?\*\[\]:]/', ' ', $value) ?? '');
        $value = preg_replace('/\s+/', ' ', $value) ?? '';
        return mb_substr($value !== '' ? $value : 'Unit Kerja', 0, 31);
    }

    private function filenamePart(string $value): string
    {
        $value = preg_replace('/[^A-Za-z0-9_-]+/u', '_', $value) ?? 'Unit_Kerja';
        return trim($value, '_') ?: 'Unit_Kerja';
    }

    private function exportTimestamp(): string
    {
        return (new \DateTimeImmutable('now', new \DateTimeZone('Asia/Jakarta')))->format('Ymd_His');
    }

    private function dateLabel(\DateTimeImmutable $date): string
    {
        return $date->format('j') . ' ' . $this->monthName((int) $date->format('n')) . ' ' . $date->format('Y');
    }

    private function monthName(int $month): string
    {
        return [1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'][$month] ?? 'Periode';
    }
}
