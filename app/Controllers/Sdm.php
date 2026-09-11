<?php

namespace App\Controllers;

use App\Libraries\EssAttendanceReportParser;
use App\Libraries\IndonesianHolidayCalendar;
use App\Models\AgendarisModel;
use App\Models\SdmAttendanceCalendarModel;
use App\Models\SdmAttendanceImportModel;
use App\Models\SdmAttendanceRecordModel;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Disposition;
use RuntimeException;

class Sdm extends BaseController
{
    private const LATE_AFTER = '08:00:00';

    public function index(): string
    {
        return view('sdm/index', [
            'title' => 'SDM & Teller',
        ]);
    }

    public function incomingDocuments(): string
    {
        return $this->documentList(false);
    }

    public function incomingDocumentHistory(): string
    {
        return $this->documentList(true);
    }

    public function attendanceDashboard(): string
    {
        return view('sdm/dashboard_kehadiran', [
            'title' => 'Dashboard Kehadiran | SDM & Teller',
        ]);
    }

    public function attendanceCalendar(): ResponseInterface|RedirectResponse
    {
        $year = (int) $this->request->getGet('tahun');
        $month = (int) $this->request->getGet('bulan');
        $year = $year >= 2020 && $year <= 2100 ? $year : 2026;
        $month = $month >= 1 && $month <= 12 ? $month : (int) date('n');

        if ($this->request->isAJAX() || $this->request->getGet('format') === 'json') {
            $calendarData = $this->attendanceCalendarMonthData($year, $month);

            return $this->response->setJSON([
                'success'     => true,
                'year'        => $calendarData['year'],
                'month'       => $calendarData['month'],
                'month_label' => $calendarData['monthLabel'],
                'html'        => view('sdm/_calendar_month', $calendarData),
            ]);
        }

        return redirect()->to(site_url('sdm/sdm-jatim') . '?' . http_build_query([
            'kalender'        => 1,
            'kalender_tahun'  => $year,
            'kalender_bulan'  => $month,
        ]));
    }

    public function updateAttendanceCalendar(): RedirectResponse
    {
        $dateValue = $this->validAttendanceDate((string) $this->request->getPost('calendar_date'));
        $mode = (string) $this->request->getPost('mode');
        $label = trim((string) $this->request->getPost('label'));

        if ($dateValue === '' || ! in_array($mode, ['auto', 'holiday', 'workday'], true)) {
            return redirect()->back()->with('error', 'Pengaturan tanggal belum valid.');
        }

        $date = new \DateTimeImmutable($dateValue);
        $redirectUrl = site_url('sdm/sdm-jatim') . '?' . http_build_query([
            'import_id'       => (int) $this->request->getPost('import_id'),
            'kalender'        => 1,
            'kalender_tahun'  => (int) $date->format('Y'),
            'kalender_bulan'  => (int) $date->format('n'),
        ]);
        $model = new SdmAttendanceCalendarModel();
        $existing = $model->where('calendar_date', $dateValue)->first();

        if ($mode === 'auto') {
            if ($existing !== null && ! $model->delete((int) $existing['id'])) {
                return redirect()->to($redirectUrl)->with('error', 'Pengaturan tanggal belum dapat dikembalikan ke otomatis.');
            }

            return redirect()->to($redirectUrl)->with('success', 'Tanggal kembali mengikuti kalender otomatis.');
        }

        $data = [
            'calendar_date'   => $dateValue,
            'is_holiday'      => $mode === 'holiday' ? 1 : 0,
            'label'           => $label !== '' ? mb_substr($label, 0, 150) : ($mode === 'holiday' ? 'Libur khusus' : 'Hari kerja khusus'),
            'updated_by'      => session()->get('auth_user_id') ?: null,
            'updated_by_name' => trim((string) session()->get('auth_display_name')) ?: null,
            'updated_by_role' => (string) session()->get('auth_role'),
        ];
        $saved = $existing !== null
            ? $model->update((int) $existing['id'], $data)
            : $model->insert($data);

        if ($saved === false) {
            return redirect()->to($redirectUrl)->with('error', 'Pengaturan tanggal belum dapat disimpan.');
        }

        return redirect()->to($redirectUrl)->with('success', 'Kalender kehadiran berhasil diperbarui.');
    }

    public function sdmJatim(): string
    {
        return view('sdm/sdm_jatim', $this->attendancePageData(
            (int) $this->request->getGet('import_id'),
        ));
    }

    public function recapSdmJatimAttendance(): string|RedirectResponse
    {
        $file = $this->request->getFile('attendance_file');

        if ($file === null || ! $file->isValid()) {
            return view('sdm/sdm_jatim', $this->attendancePageData(
                null,
                'Pilih file Employee Attendance Report ESS yang akan direkap.',
            ));
        }

        if ($file->getSize() > 5 * 1024 * 1024) {
            return view('sdm/sdm_jatim', $this->attendancePageData(
                null,
                'Ukuran file terlalu besar. Maksimal ukuran file adalah 5 MB.',
            ));
        }

        if (! in_array(strtolower($file->getClientExtension()), ['xls', 'html', 'htm'], true)) {
            return view('sdm/sdm_jatim', $this->attendancePageData(
                null,
                'Format file belum didukung. Gunakan file ESS dengan ekstensi .xls.',
            ));
        }

        try {
            $hash = hash_file('sha256', $file->getTempName());
            if ($hash === false) {
                throw new RuntimeException('Identitas file ESS tidak dapat dibaca.');
            }

            $importModel = new SdmAttendanceImportModel();
            $existingImport = $importModel->withDeleted()->where('source_hash', $hash)->first();
            if ($existingImport !== null) {
                if (! empty($existingImport['deleted_at'])) {
                    if (! $this->currentRoleIsAdmin()) {
                        return redirect()->to(site_url('sdm/sdm-jatim'))
                            ->with('error', 'Rekap yang sama berada di Data Terhapus. Hubungi Administrator untuk memulihkannya.');
                    }

                    $restored = db_connect()->table('sdm_attendance_imports')->where('id', (int) $existingImport['id'])->update([
                        'deleted_at'      => null,
                        'deleted_by_role' => null,
                        'deleted_by_name' => null,
                        'updated_at'      => date('Y-m-d H:i:s'),
                    ]);
                    if (! $restored) {
                        throw new RuntimeException('Rekap yang pernah dihapus belum dapat dipulihkan.');
                    }

                    return redirect()->to(site_url('sdm/sdm-jatim?import_id=' . $existingImport['id']))
                        ->with('success', 'Rekap yang pernah dihapus berhasil dipulihkan dan ditampilkan kembali.');
                }

                return redirect()->to(site_url('sdm/sdm-jatim?import_id=' . $existingImport['id']))
                    ->with('success', 'File yang sama sudah pernah disimpan. Rekap tersimpan ditampilkan kembali.');
            }

            $report = (new EssAttendanceReportParser())->parse(
                $file->getTempName(),
                $file->getClientName(),
            );
            $importId = $this->saveAttendanceReport($report, $hash);

            return redirect()->to(site_url('sdm/sdm-jatim?import_id=' . $importId))
                ->with('success', 'Rekap absensi berhasil dibuat dan disimpan otomatis.');
        } catch (\Throwable $exception) {
            log_message('warning', 'Import rekap ESS gagal: {message}', ['message' => $exception->getMessage()]);

            return view('sdm/sdm_jatim', $this->attendancePageData(null, $exception->getMessage()));
        }
    }

    public function updateSdmJatimAnomalies(): RedirectResponse
    {
        $importId = (int) $this->request->getPost('import_id');
        $submittedRecords = $this->request->getPost('records');
        $redirectUrl = site_url('sdm/sdm-jatim?import_id=' . $importId);

        $importModel = new SdmAttendanceImportModel();
        if ($importId <= 0 || $importModel->find($importId) === null || ! is_array($submittedRecords)) {
            return redirect()->to($redirectUrl)->with('error', 'Data anomali belum dapat diperbarui.');
        }

        $recordModel = new SdmAttendanceRecordModel();
        $anomalyRecords = $recordModel
            ->where('import_id', $importId)
            ->whereIn('recap_code', ['A', 'TA', 'TAM', 'TAP'])
            ->findAll();
        $allowedCodes = ['H', 'TLBT', 'I', 'A', 'TA', 'TAM', 'TAP', 'OFF'];
        $db = db_connect();
        $updated = 0;
        $db->transBegin();

        try {
            foreach ($anomalyRecords as $record) {
                $recordId = (int) $record['id'];
                $submitted = $submittedRecords[$recordId] ?? null;
                if (! is_array($submitted)) {
                    continue;
                }

                $code = strtoupper(trim((string) ($submitted['recap_code'] ?? '')));
                if (! in_array($code, $allowedCodes, true)) {
                    throw new RuntimeException('Status koreksi absensi tidak valid.');
                }

                $actualIn = $this->validAttendanceTime((string) ($submitted['actual_in'] ?? ''));
                $actualOut = $this->validAttendanceTime((string) ($submitted['actual_out'] ?? ''));
                if (in_array($code, ['TA', 'TAM', 'TAP'], true)) {
                    if ($actualIn !== null && $actualOut === null) {
                        $code = 'TAP';
                    } elseif ($actualIn === null && $actualOut !== null) {
                        $code = 'TAM';
                    } elseif ($actualIn !== null && $actualOut !== null && in_array($code, ['TAM', 'TAP'], true)) {
                        $code = 'H';
                    } elseif ($actualIn === null && $actualOut === null && in_array($code, ['TAM', 'TAP'], true)) {
                        $code = 'TA';
                    }
                }
                if ($actualIn !== null && $actualOut !== null && in_array($code, ['H', 'TLBT'], true)) {
                    $code = $actualIn > self::LATE_AFTER ? 'TLBT' : 'H';
                }

                $payload = [
                    'recap_code' => $code,
                    'actual_in'  => $actualIn,
                    'actual_out' => $actualOut,
                    'remark'     => trim((string) ($submitted['remark'] ?? '')) ?: null,
                ];

                if ($recordModel->update($recordId, $payload) === false) {
                    throw new RuntimeException('Salah satu data anomali belum dapat diperbarui.');
                }
                $updated++;
            }

            $this->refreshAttendanceImportSummary($importId, $importModel);
            if ($db->transStatus() === false) {
                throw new RuntimeException('Penyimpanan koreksi data anomali belum berhasil.');
            }
            $db->transCommit();
        } catch (\Throwable $exception) {
            $db->transRollback();
            log_message('warning', 'Koreksi anomali absensi gagal: {message}', ['message' => $exception->getMessage()]);

            return redirect()->to($redirectUrl)->with('error', $exception->getMessage());
        }

        return redirect()->to(site_url('sdm/sdm-jatim?import_id=' . $importId))
            ->with('success', $updated . ' data anomali berhasil diperbarui.');
    }

    public function deleteSdmJatimAttendance(): RedirectResponse
    {
        $importId = (int) $this->request->getPost('import_id');
        $importModel = new SdmAttendanceImportModel();
        $import = $importId > 0 ? $importModel->find($importId) : null;

        if ($import === null) {
            return redirect()->to(site_url('sdm/sdm-jatim'))
                ->with('error', 'Rekap absensi tidak ditemukan atau sudah dihapus.');
        }

        if (! $this->deleteRecord($importModel, 'sdm_attendance_imports', $importId)) {
            return redirect()->to(site_url('sdm/sdm-jatim?import_id=' . $importId))
                ->with('error', 'Rekap absensi belum dapat dihapus.');
        }

        return redirect()->to(site_url('sdm/sdm-jatim'))
            ->with('success', $this->currentRoleIsAdmin()
                ? 'Rekap absensi beserta seluruh detailnya berhasil dihapus permanen.'
                : 'Rekap absensi berhasil dipindahkan ke Data Terhapus.');
    }

    /**
     * @return array<string, mixed>
     */
    private function attendancePageData(?int $selectedImportId = null, ?string $error = null): array
    {
        $filters = [
            'name'         => trim((string) $this->request->getGet('nama')),
            'from'         => $this->validAttendanceDate((string) $this->request->getGet('dari')),
            'to'           => $this->validAttendanceDate((string) $this->request->getGet('sampai')),
            'mode'         => $this->request->getGet('mode') === 'detail' ? 'detail' : 'summary',
            'employee_key' => trim((string) $this->request->getGet('pegawai')),
        ];
        $importModel = new SdmAttendanceImportModel();
        $imports = $importModel
            ->orderBy('period_start', 'DESC')
            ->orderBy('id', 'DESC')
            ->findAll(24);
        foreach ($imports as &$import) {
            $period = new \DateTimeImmutable($import['period_start']);
            $import['period_label'] = $this->attendanceMonthLabel((int) $period->format('n')) . ' ' . $period->format('Y');
        }
        unset($import);

        if (($selectedImportId ?? 0) <= 0 && $imports !== []) {
            $selectedImportId = (int) $imports[0]['id'];
        }

        $selectedImport = null;
        foreach ($imports as $import) {
            if ((int) $import['id'] === $selectedImportId) {
                $selectedImport = $import;
                break;
            }
        }

        if ($selectedImport === null && $imports !== []) {
            $selectedImport = $imports[0];
            $selectedImportId = (int) $selectedImport['id'];
        }

        if ($selectedImport !== null) {
            $periodStart = $selectedImport['period_start'];
            $periodEnd = $selectedImport['period_end'];
            if ($filters['from'] !== '' && ($filters['from'] < $periodStart || $filters['from'] > $periodEnd)) {
                $filters['from'] = '';
            }
            if ($filters['to'] !== '' && ($filters['to'] < $periodStart || $filters['to'] > $periodEnd)) {
                $filters['to'] = '';
            }
            if ($filters['from'] !== '' && $filters['to'] !== '' && $filters['from'] > $filters['to']) {
                [$filters['from'], $filters['to']] = [$filters['to'], $filters['from']];
            }
        }

        return [
            'title'            => 'SDM Jatim | SDM & Teller',
            'report'           => $selectedImport !== null ? $this->storedAttendanceReport($selectedImport, $filters) : null,
            'importError'      => $error,
            'attendanceImports' => $imports,
            'selectedImportId' => $selectedImport !== null ? (int) $selectedImport['id'] : null,
            'attendanceFilters' => $filters,
            'attendanceCalendar' => $this->attendanceCalendarPopupData($selectedImport),
        ];
    }

    /**
     * @param array<string, mixed>|null $selectedImport
     * @return array<string, mixed>
     */
    private function attendanceCalendarPopupData(?array $selectedImport): array
    {
        $defaultPeriod = $selectedImport !== null
            ? new \DateTimeImmutable($selectedImport['period_start'])
            : new \DateTimeImmutable('2026-' . date('m') . '-01');
        $year = (int) $this->request->getGet('kalender_tahun');
        $month = (int) $this->request->getGet('kalender_bulan');
        $year = $year >= 2020 && $year <= 2100 ? $year : (int) $defaultPeriod->format('Y');
        $month = $month >= 1 && $month <= 12 ? $month : (int) $defaultPeriod->format('n');
        $monthStart = new \DateTimeImmutable(sprintf('%04d-%02d-01', $year, $month));
        $calendarData = $this->attendanceCalendarMonthData($year, $month);

        $baseQuery = array_filter([
            'import_id'        => $selectedImport['id'] ?? null,
            'kalender'         => 1,
        ], static fn ($value): bool => $value !== null && $value !== '');

        return $calendarData + [
            'importId'    => (int) ($selectedImport['id'] ?? 0),
            'autoOpen'    => $this->request->getGet('kalender') === '1',
            'previousUrl' => site_url('sdm/sdm-jatim') . '?' . http_build_query($baseQuery + [
                'kalender_tahun' => (int) $monthStart->modify('-1 month')->format('Y'),
                'kalender_bulan' => (int) $monthStart->modify('-1 month')->format('n'),
            ]),
            'nextUrl'     => site_url('sdm/sdm-jatim') . '?' . http_build_query($baseQuery + [
                'kalender_tahun' => (int) $monthStart->modify('+1 month')->format('Y'),
                'kalender_bulan' => (int) $monthStart->modify('+1 month')->format('n'),
            ]),
        ];
    }

    /**
     * @return array{year: int, month: int, monthLabel: string, days: list<array<string, mixed>>, leadingDays: int}
     */
    private function attendanceCalendarMonthData(int $year, int $month): array
    {
        $monthStart = new \DateTimeImmutable(sprintf('%04d-%02d-01', $year, $month));
        $monthEnd = $monthStart->modify('last day of this month');
        $calendar = new IndonesianHolidayCalendar($this->attendanceCalendarOverrides($monthStart, $monthEnd));
        $days = [];

        for ($date = $monthStart; $date <= $monthEnd; $date = $date->modify('+1 day')) {
            $info = $calendar->info($date);
            $days[] = [
                'date'           => $date->format('Y-m-d'),
                'day'            => (int) $date->format('j'),
                'is_non_working' => $info['is_non_working'],
                'label'          => $info['label'],
                'source'         => $info['source'],
                'is_override'    => $info['is_override'],
                'mode'           => $info['is_override'] ? ($info['is_non_working'] ? 'holiday' : 'workday') : 'auto',
            ];
        }

        return [
            'year'        => $year,
            'month'       => $month,
            'monthLabel'  => $this->attendanceMonthLabel($month) . ' ' . $year,
            'days'        => $days,
            'leadingDays' => (int) $monthStart->format('N') - 1,
        ];
    }

    /**
     * @param array<string, mixed> $report
     */
    private function saveAttendanceReport(array $report, string $hash): int
    {
        $db = db_connect();
        $importModel = new SdmAttendanceImportModel();
        $recordModel = new SdmAttendanceRecordModel();
        $db->transBegin();

        try {
            $importId = $importModel->insert([
                'source_name'      => $report['source_name'],
                'source_hash'      => $hash,
                'period_start'     => $report['period_start'],
                'period_end'       => $report['period_end'],
                'employee_count'   => $report['summary']['EMPLOYEES'],
                'row_count'        => $report['summary']['ROWS'],
                'hadir_count'      => $report['summary']['H'] + $report['summary']['TLBT'],
                'izin_count'       => $report['summary']['I'],
                'alpa_count'       => $report['summary']['A'],
                'incomplete_count' => $report['summary']['TA'] + $report['summary']['TAM'] + $report['summary']['TAP'],
                'off_count'        => $report['summary']['OFF'],
                'other_count'      => $report['summary']['OTHER'],
                'warnings_json'    => json_encode($report['warnings'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'imported_by'      => session()->get('auth_user_id') ?: null,
                'imported_by_name' => trim((string) session()->get('auth_display_name')) ?: null,
            ], true);

            if (! is_int($importId) && ! ctype_digit((string) $importId)) {
                throw new RuntimeException('Data rekap belum dapat disimpan.');
            }

            $rows = [];
            foreach ($report['records'] as $record) {
                $rows[] = ['import_id' => (int) $importId] + $record;
            }

            foreach (array_chunk($rows, 200) as $chunk) {
                if ($recordModel->insertBatch($chunk) === false) {
                    throw new RuntimeException('Detail absensi belum dapat disimpan.');
                }
            }

            if ($db->transStatus() === false) {
                throw new RuntimeException('Penyimpanan rekap absensi belum berhasil.');
            }

            $db->transCommit();

            return (int) $importId;
        } catch (\Throwable $exception) {
            $db->transRollback();
            throw $exception;
        }
    }

    /**
     * @param array<string, mixed> $import
     * @param array{name: string, from: string, to: string, mode: string, employee_key: string} $filters
     * @return array<string, mixed>
     */
    private function storedAttendanceReport(array $import, array $filters): array
    {
        $recordModel = (new SdmAttendanceRecordModel())
            ->where('import_id', (int) $import['id']);
        if ($filters['name'] !== '') {
            $recordModel->like('employee_name', $filters['name']);
        }
        if ($filters['from'] !== '') {
            $recordModel->where('attendance_date >=', $filters['from']);
        }
        if ($filters['to'] !== '') {
            $recordModel->where('attendance_date <=', $filters['to']);
        }
        $records = $recordModel
            ->orderBy('employee_name', 'ASC')
            ->orderBy('attendance_date', 'ASC')
            ->findAll();
        $employees = [];
        $anomalies = [];
        $summary = ['H' => 0, 'TLBT' => 0, 'I' => 0, 'A' => 0, 'TA' => 0, 'TAM' => 0, 'TAP' => 0, 'OFF' => 0, 'BL' => 0, 'OTHER' => 0];
        $period = new \DateTimeImmutable($import['period_start']);
        $displayStart = new \DateTimeImmutable($filters['from'] !== '' ? $filters['from'] : $import['period_start']);
        $displayEnd = new \DateTimeImmutable($filters['to'] !== '' ? $filters['to'] : $import['period_end']);
        $holidayCalendar = new IndonesianHolidayCalendar(
            $this->attendanceCalendarOverrides($displayStart, $displayEnd),
        );
        $calendarDays = [];

        for ($date = $displayStart; $date <= $displayEnd; $date = $date->modify('+1 day')) {
            $day = (int) $date->format('j');
            $calendarDays[$day] = $holidayCalendar->info($date);
        }

        foreach ($records as $record) {
            $key = $record['employee_key'];
            if (! isset($employees[$key])) {
                $employees[$key] = [
                    'employee_key'   => $record['employee_key'],
                    'employee_no'    => $record['employee_no'] ?: '-',
                    'employee_name'  => $record['employee_name'],
                    'position'       => $record['position'],
                    'organization'   => $record['organization'],
                    'days'           => [],
                    'day_details'    => [],
                    'totals'         => ['H' => 0, 'TLBT' => 0, 'I' => 0, 'A' => 0, 'TA' => 0, 'TAM' => 0, 'TAP' => 0, 'OFF' => 0, 'BL' => 0],
                    'attendance_rate' => null,
                    'effective_attendance' => 0.0,
                    'punctuality_rate' => null,
                    'discipline_index' => null,
                    'evaluation_status' => '-',
                    'evaluation_status_key' => 'neutral',
                ];
            }

            $recordDate = new \DateTimeImmutable($record['attendance_date']);
            $calendarInfo = $holidayCalendar->info($recordDate);
            $code = $record['recap_code'];
            if ($calendarInfo['is_non_working']) {
                $code = 'OFF';
            } elseif ($calendarInfo['is_override'] && $code === 'OFF') {
                $actualIn = $record['actual_in'];
                $actualOut = $record['actual_out'];
                $code = $actualIn !== null && $actualOut !== null
                    ? ($actualIn > self::LATE_AFTER ? 'TLBT' : 'H')
                    : ($actualIn !== null ? 'TAP' : ($actualOut !== null ? 'TAM' : 'TA'));
            }
            $day = (int) $recordDate->format('j');
            $employees[$key]['days'][$day] = $code;
            $employees[$key]['day_details'][$day] = [
                'recap_code'   => $code,
                'actual_in'    => $record['actual_in'],
                'actual_out'   => $record['actual_out'],
                'holiday_name' => $calendarInfo['is_non_working'] ? $calendarInfo['label'] : null,
            ];
            if (isset($employees[$key]['totals'][$code])) {
                $employees[$key]['totals'][$code]++;
            }
            if (isset($summary[$code])) {
                $summary[$code]++;
            } else {
                $summary['OTHER']++;
            }
            if (in_array($code, ['A', 'TA', 'TAM', 'TAP'], true)) {
                $anomalies[] = [
                    'id'              => (int) $record['id'],
                    'employee_no'     => $record['employee_no'] ?: '-',
                    'employee_name'   => $record['employee_name'],
                    'attendance_date' => $record['attendance_date'],
                    'actual_in'       => $record['actual_in'],
                    'actual_out'      => $record['actual_out'],
                    'raw_status'      => $record['raw_status'],
                    'recap_code'      => $code,
                    'remark'          => $record['remark'],
                ];
            }
        }

        $warnings = json_decode((string) ($import['warnings_json'] ?? '[]'), true);
        if (! is_array($warnings)) {
            $warnings = [];
        }

        foreach ($employees as &$employee) {
            for ($date = $displayStart; $date <= $displayEnd; $date = $date->modify('+1 day')) {
                $day = (int) $date->format('j');
                if (array_key_exists($day, $employee['day_details'])) {
                    continue;
                }

                if ($holidayCalendar->isNonWorkingDay($date)) {
                    $employee['days'][$day] = 'OFF';
                    $employee['day_details'][$day] = [
                        'recap_code'   => 'OFF',
                        'actual_in'    => null,
                        'actual_out'   => null,
                        'holiday_name' => $holidayCalendar->label($date),
                    ];
                    $employee['totals']['OFF']++;
                    $summary['OFF']++;

                    continue;
                }

                $employee['totals']['BL']++;
                $summary['BL']++;
            }
        }
        unset($employee);

        $effectiveWorkDays = count(array_filter(
            $calendarDays,
            static fn (array $day): bool => ! (bool) ($day['is_non_working'] ?? false),
        ));
        $attendanceRates = [];
        $punctualityRates = [];
        $disciplineIndexes = [];
        $evaluationCounts = ['excellent' => 0, 'attention' => 0, 'warning' => 0];
        $effectiveAttendanceTotal = 0.0;

        foreach ($employees as &$employee) {
            $presenceSignals = $employee['totals']['H'] + $employee['totals']['TLBT']
                + $employee['totals']['TA'] + $employee['totals']['TAM'] + $employee['totals']['TAP'];
            $effectiveAttendance = $employee['totals']['H']
                + ($employee['totals']['TLBT'] * 0.75)
                + ($employee['totals']['TAM'] * 0.5)
                + ($employee['totals']['TAP'] * 0.5)
                + ($employee['totals']['TA'] * 0.25);
            $attendanceRate = $effectiveWorkDays > 0
                ? round(($effectiveAttendance / $effectiveWorkDays) * 100, 1)
                : null;
            $punctualityRate = $presenceSignals > 0
                ? round(($employee['totals']['H'] / $presenceSignals) * 100, 1)
                : null;
            $disciplineIndex = $attendanceRate !== null && $punctualityRate !== null
                ? round(($attendanceRate + $punctualityRate) / 2, 1)
                : null;

            if ($employee['totals']['A'] > 0) {
                $evaluationStatus = 'Teguran (Alpa)';
                $evaluationKey = 'warning';
            } elseif (
                $employee['totals']['TLBT'] >= 3
                || ($attendanceRate !== null && $attendanceRate < 95)
                || $employee['totals']['TA'] > 0
            ) {
                $evaluationStatus = 'Perlu Perhatian';
                $evaluationKey = 'attention';
            } else {
                $evaluationStatus = 'Sangat Baik';
                $evaluationKey = 'excellent';
            }

            $employee['effective_work_days'] = $effectiveWorkDays;
            $employee['effective_attendance'] = round($effectiveAttendance, 2);
            $employee['attendance_rate'] = $attendanceRate;
            $employee['punctuality_rate'] = $punctualityRate;
            $employee['discipline_index'] = $disciplineIndex;
            $employee['evaluation_status'] = $evaluationStatus;
            $employee['evaluation_status_key'] = $evaluationKey;
            $effectiveAttendanceTotal += $effectiveAttendance;
            $evaluationCounts[$evaluationKey]++;
            if ($attendanceRate !== null) {
                $attendanceRates[] = $attendanceRate;
            }
            if ($punctualityRate !== null) {
                $punctualityRates[] = $punctualityRate;
            }
            if ($disciplineIndex !== null) {
                $disciplineIndexes[] = $disciplineIndex;
            }
        }
        unset($employee);

        $summary['ROWS'] = count($records);
        $summary['EMPLOYEES'] = count($employees);
        $summary['EFFECTIVE_WORK_DAYS'] = $effectiveWorkDays;
        $summary['EFFECTIVE_ATTENDANCE'] = round($effectiveAttendanceTotal, 2);
        $summary['AVERAGE_ATTENDANCE_RATE'] = $attendanceRates !== []
            ? round(array_sum($attendanceRates) / count($attendanceRates), 1)
            : null;
        $summary['AVERAGE_PUNCTUALITY_RATE'] = $punctualityRates !== []
            ? round(array_sum($punctualityRates) / count($punctualityRates), 1)
            : null;
        $summary['AVERAGE_DISCIPLINE_INDEX'] = $disciplineIndexes !== []
            ? round(array_sum($disciplineIndexes) / count($disciplineIndexes), 1)
            : null;
        $summary['EVALUATIONS'] = $evaluationCounts;

        return [
            'source_name'     => $import['source_name'],
            'period_start'    => $import['period_start'],
            'period_end'      => $import['period_end'],
            'period_label'    => $this->attendanceMonthLabel((int) $period->format('n')) . ' ' . $period->format('Y'),
            'days_in_month'   => (int) $period->format('t'),
            'display_start_day' => (int) $displayStart->format('j'),
            'display_end_day' => (int) $displayEnd->format('j'),
            'calendar_days'    => $calendarDays,
            'employees'       => array_values($employees),
            'anomalies'       => $anomalies,
            'summary'         => $summary,
            'warnings'        => array_values($warnings),
            'imported_at'     => $import['created_at'],
            'imported_by_name' => $import['imported_by_name'] ?: 'Pengguna',
        ];
    }

    private function attendanceMonthLabel(int $month): string
    {
        return [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ][$month];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function attendanceCalendarOverrides(\DateTimeInterface $start, \DateTimeInterface $end): array
    {
        $rows = (new SdmAttendanceCalendarModel())
            ->where('calendar_date >=', $start->format('Y-m-d'))
            ->where('calendar_date <=', $end->format('Y-m-d'))
            ->orderBy('calendar_date', 'ASC')
            ->findAll();
        $overrides = [];

        foreach ($rows as $row) {
            $overrides[$row['calendar_date']] = $row;
        }

        return $overrides;
    }

    private function validAttendanceDate(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }

        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $value);

        return $date !== false && $date->format('Y-m-d') === $value ? $value : '';
    }

    private function validAttendanceTime(string $value): ?string
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        foreach (['!H:i', '!H:i:s'] as $format) {
            $time = \DateTimeImmutable::createFromFormat($format, $value);
            if ($time !== false) {
                return $time->format('H:i:s');
            }
        }

        throw new RuntimeException('Format jam masuk atau jam pulang tidak valid.');
    }

    private function refreshAttendanceImportSummary(int $importId, SdmAttendanceImportModel $importModel): void
    {
        $records = (new SdmAttendanceRecordModel())
            ->select('employee_key, recap_code')
            ->where('import_id', $importId)
            ->findAll();
        $employees = [];
        $counts = ['H' => 0, 'TLBT' => 0, 'I' => 0, 'A' => 0, 'TA' => 0, 'TAM' => 0, 'TAP' => 0, 'OFF' => 0, 'OTHER' => 0];

        foreach ($records as $record) {
            $employees[$record['employee_key']] = true;
            $code = $record['recap_code'];
            isset($counts[$code]) ? $counts[$code]++ : $counts['OTHER']++;
        }

        if ($importModel->update($importId, [
            'employee_count'   => count($employees),
            'row_count'        => count($records),
            'hadir_count'      => $counts['H'] + $counts['TLBT'],
            'izin_count'       => $counts['I'],
            'alpa_count'       => $counts['A'],
            'incomplete_count' => $counts['TA'] + $counts['TAM'] + $counts['TAP'],
            'off_count'        => $counts['OFF'],
            'other_count'      => $counts['OTHER'],
        ]) === false) {
            throw new RuntimeException('Ringkasan absensi belum dapat diperbarui.');
        }
    }

    public function synchronizeIncomingDocuments(): RedirectResponse
    {
        $model = new AgendarisModel();
        $documents = $model->findAll();
        $updatedDocuments = 0;
        $normalizedRecipients = 0;
        $db = db_connect();

        $db->transStart();

        foreach ($documents as $document) {
            $updates = [];

            for ($step = 1; $step <= Disposition::MAX_STEPS; $step++) {
                $field = "disposisi_{$step}";
                $currentRecipient = trim((string) ($document[$field] ?? ''));
                if ($currentRecipient === '') {
                    continue;
                }

                $canonicalRecipient = $this->canonicalRecipient($currentRecipient);
                if ($canonicalRecipient !== null && $canonicalRecipient !== $currentRecipient) {
                    $updates[$field] = $canonicalRecipient;
                    $normalizedRecipients++;
                }
            }

            if ($updates !== []) {
                if (! $model->update((int) $document['id'], $updates)) {
                    $db->transRollback();

                    return redirect()->to(site_url('sdm/dokumen-masuk'))
                        ->with('error', 'Sinkronisasi belum berhasil. Silakan coba kembali.');
                }

                $updatedDocuments++;
            }
        }

        $db->transComplete();

        if (! $db->transStatus()) {
            return redirect()->to(site_url('sdm/dokumen-masuk'))
                ->with('error', 'Sinkronisasi belum berhasil. Silakan coba kembali.');
        }

        $message = $updatedDocuments > 0
            ? "{$updatedDocuments} dokumen lama berhasil disinkronkan ({$normalizedRecipients} nama penerima diperbaiki)."
            : 'Seluruh dokumen Agendaris sudah tersinkron dengan SDM & Teller.';

        return redirect()->to(site_url('sdm/dokumen-masuk'))->with('sync_success', $message);
    }

    private function documentList(bool $historyMode): string
    {
        $recipientName = trim((string) session()->get('auth_display_name'));
        $currentRole = (string) session()->get('auth_role');
        $keyword = trim((string) $this->request->getGet('q'));
        $status = trim((string) $this->request->getGet('status'));
        $perPage = (int) $this->request->getGet('per_page');
        $order = $this->requestedListOrder();

        if (! in_array($perPage, [10, 20, 50, 100], true)) {
            $perPage = 10;
        }

        $allowedStatuses = Disposition::STATUSES;
        if (! in_array($status, $allowedStatuses, true)) {
            $status = '';
        }

        $latestRecipientParts = [];
        $latestStatusParts = [];
        $latestTimeParts = [];
        $latestNoteParts = [];
        $latestStepParts = [];
        for ($step = Disposition::MAX_STEPS; $step >= 1; $step--) {
            $filled = "TRIM(COALESCE(agendaris.disposisi_{$step}, '')) <> ''";
            $latestRecipientParts[] = "WHEN {$filled} THEN agendaris.disposisi_{$step}";
            $latestStatusParts[] = "WHEN {$filled} THEN COALESCE(NULLIF(TRIM(agendaris.disposisi_{$step}_status), ''), 'Menunggu')";
            $latestTimeParts[] = "WHEN {$filled} THEN agendaris.disposisi_{$step}_waktu";
            $latestNoteParts[] = "WHEN {$filled} THEN agendaris.disposisi_{$step}_catatan";
            $latestStepParts[] = "WHEN {$filled} THEN {$step}";
        }
        $latestRecipientSql = 'CASE ' . implode(' ', $latestRecipientParts) . " ELSE '' END";
        $latestStatusSql = 'CASE ' . implode(' ', $latestStatusParts) . " ELSE 'Menunggu' END";
        $latestTimeSql = 'CASE ' . implode(' ', $latestTimeParts) . ' ELSE NULL END';
        $latestNoteSql = 'CASE ' . implode(' ', $latestNoteParts) . ' ELSE NULL END';
        $latestStepSql = 'CASE ' . implode(' ', $latestStepParts) . ' ELSE 0 END';

        $model = new AgendarisModel();
        $model->select(
            "agendaris.*, {$latestRecipientSql} AS disposisi_terakhir, "
            . "{$latestStatusSql} AS status_disposisi_terakhir, "
            . "{$latestTimeSql} AS waktu_disposisi_terakhir, "
            . "{$latestNoteSql} AS catatan_disposisi_terakhir, "
            . "{$latestStepSql} AS tahap_disposisi_terakhir, "
            . 'dokumen_masuk.penyerahan_at AS sumber_penyerahan_at',
            false,
        );
        $model->join('dokumen_masuk', 'dokumen_masuk.id = agendaris.dokumen_masuk_id', 'left');

        $scopedRecipients = $currentRole === 'admin'
            ? $this->activeSdmRecipientNames()
            : ($recipientName !== '' ? [$recipientName] : []);
        $recipientSqlList = implode(', ', array_map(
            static fn (string $name): string => db_connect()->escape(mb_strtolower(trim($name))),
            $scopedRecipients,
        ));

        if ($scopedRecipients === []) {
            $model->where('1 = 0', null, false);
        } elseif ($historyMode) {
            $model->groupStart();
            for ($step = 1; $step <= Disposition::MAX_STEPS; $step++) {
                $condition = "LOWER(TRIM(agendaris.disposisi_{$step})) IN ({$recipientSqlList})";
                if ($step === 1) {
                    $model->where($condition, null, false);
                } else {
                    $model->orWhere($condition, null, false);
                }
            }
            $model->groupEnd();
            $model->where(
                'LOWER(TRIM(' . $latestRecipientSql . ")) NOT IN ({$recipientSqlList})",
                null,
                false,
            );
        } else {
            $model->groupStart()
                ->where('agendaris.progres', 'Selesai')
                ->orWhere('agendaris.sdm_processed_at IS NOT NULL', null, false)
                ->groupEnd();
            $model->where(
                'LOWER(TRIM(' . $latestRecipientSql . ")) IN ({$recipientSqlList})",
                null,
                false,
            );
        }

        if ($keyword !== '') {
            $model->groupStart()
                ->like('agendaris.nomor_agendaris', $keyword)
                ->orLike('agendaris.nomor_surat', $keyword)
                ->orLike('agendaris.perihal_surat', $keyword)
                ->orLike('agendaris.pengirim', $keyword)
                ->orLike('agendaris.jenis', $keyword)
                ->groupEnd();
        }

        if ($status !== '') {
            $model->where($latestStatusSql . ' = ' . db_connect()->escape($status), null, false);
        }

        $pagerGroup = $historyMode ? 'sdm_incoming_history' : 'sdm_incoming_documents';
        $direction = $order === 'terlama' ? 'ASC' : 'DESC';

        return view('sdm/dokumen_masuk', [
            'title' => ($historyMode ? 'Riwayat Dokumen Masuk' : 'Dokumen Masuk') . ' | SDM & Teller',
            'documents' => $model
                ->orderBy('waktu_disposisi_terakhir', $direction)
                ->orderBy('agendaris.id', $direction)
                ->paginate($perPage, $pagerGroup),
            'pager' => $model->pager,
            'recipientName' => $recipientName,
            'recipientScopeLabel' => $currentRole === 'admin' ? 'seluruh akun SDM & Teller' : $recipientName,
            'isAdminView' => $currentRole === 'admin',
            'statusOptions' => $allowedStatuses,
            'recipientOptions' => Disposition::RECIPIENTS,
            'filters' => compact('keyword', 'status', 'perPage', 'order'),
            'historyMode' => $historyMode,
            'pagerGroup' => $pagerGroup,
            'listUrl' => site_url($historyMode ? 'sdm/riwayat' : 'sdm/dokumen-masuk'),
        ]);
    }

    public function updateIncomingDocument(int $id): ResponseInterface
    {
        $model = new AgendarisModel();
        $document = $model->find($id);
        $latestStep = $document !== null ? $this->latestDispositionStep($document) : 0;

        if (
            $document === null
            || (($document['progres'] ?? '') !== 'Selesai' && empty($document['sdm_processed_at']))
            || $latestStep === 0
            || (! $this->currentRoleIsAdmin() && ! $this->belongsToCurrentUser($document, $latestStep))
        ) {
            return $this->response->setStatusCode(404)->setJSON([
                'success' => false,
                'message' => 'Dokumen tidak tersedia atau disposisi sudah diteruskan kepada pengguna lain.',
                'csrf' => ['name' => csrf_token(), 'hash' => csrf_hash()],
            ]);
        }

        $status = trim((string) $this->request->getPost('current_status'));
        $date = trim((string) $this->request->getPost('current_date'));
        $note = trim((string) $this->request->getPost('current_note'));
        $addDisposition = $this->request->getPost('add_disposition') === '1';

        $validationData = [
            'current_status' => $status,
            'current_date' => $date,
            'current_note' => $note,
        ];
        $rules = [
            'current_status' => 'required|in_list[' . implode(',', Disposition::STATUSES) . ']',
            'current_date' => 'permit_empty|valid_date[Y-m-d]',
            'current_note' => 'permit_empty|max_length[1000]',
        ];

        if ($addDisposition) {
            if ($latestStep >= Disposition::MAX_STEPS) {
                return $this->validationError(['Seluruh lima tahap disposisi sudah terisi.']);
            }

            $validationData['next_recipient'] = trim((string) $this->request->getPost('next_recipient'));
            $validationData['next_status'] = trim((string) $this->request->getPost('next_status'));
            $validationData['next_date'] = trim((string) $this->request->getPost('next_date'));
            $validationData['next_note'] = trim((string) $this->request->getPost('next_note'));
            $rules['next_recipient'] = 'required|in_list[' . implode(',', Disposition::RECIPIENTS) . ']';
            $rules['next_status'] = 'required|in_list[' . implode(',', Disposition::STATUSES) . ']';
            $rules['next_date'] = 'required|valid_date[Y-m-d]';
            $rules['next_note'] = 'permit_empty|max_length[1000]';
        }

        $validation = service('validation')->setRules($rules);
        if (! $validation->run($validationData)) {
            return $this->validationError(array_values($validation->getErrors()));
        }

        $updates = [
            "disposisi_{$latestStep}_status" => $addDisposition ? 'Diteruskan' : $status,
            "disposisi_{$latestStep}_waktu" => $this->dateTimeValue($date, $document["disposisi_{$latestStep}_waktu"] ?? null),
            "disposisi_{$latestStep}_catatan" => $note !== '' ? $note : null,
        ];

        if (in_array((string) session()->get('auth_role'), ['sdm', 'admin'], true) && empty($document['sdm_processed_at'])) {
            $updates['sdm_processed_at'] = date('Y-m-d H:i:s');
            $updates['sdm_processed_by'] = trim((string) session()->get('auth_display_name')) ?: 'SDM & Teller';
        }

        if ($addDisposition) {
            $nextStep = $latestStep + 1;
            $updates["disposisi_{$nextStep}"] = $validationData['next_recipient'];
            $updates["disposisi_{$nextStep}_status"] = $validationData['next_status'];
            $updates["disposisi_{$nextStep}_waktu"] = $validationData['next_date'] . ' 00:00:00';
            $updates["disposisi_{$nextStep}_catatan"] = $validationData['next_note'] !== '' ? $validationData['next_note'] : null;
        }

        if (! $model->update($id, $updates)) {
            return $this->validationError($model->errors() ?: ['Perubahan disposisi belum berhasil disimpan.']);
        }

        $message = $addDisposition
            ? 'Disposisi berikutnya berhasil ditambahkan dan dokumen telah diteruskan.'
            : 'Status dan catatan disposisi berhasil diperbarui.';
        session()->setFlashdata('success', $message);

        return $this->response->setJSON([
            'success' => true,
            'message' => $message,
            'csrf' => ['name' => csrf_token(), 'hash' => csrf_hash()],
        ]);
    }

    private function latestDispositionStep(array $document): int
    {
        for ($step = Disposition::MAX_STEPS; $step >= 1; $step--) {
            if (trim((string) ($document["disposisi_{$step}"] ?? '')) !== '') {
                return $step;
            }
        }

        return 0;
    }

    private function belongsToCurrentUser(array $document, int $latestStep): bool
    {
        $recipient = mb_strtolower(trim((string) ($document["disposisi_{$latestStep}"] ?? '')));
        $currentUser = mb_strtolower(trim((string) session()->get('auth_display_name')));

        return $recipient !== '' && $currentUser !== '' && hash_equals($recipient, $currentUser);
    }

    private function canonicalRecipient(string $recipient): ?string
    {
        $recipientParts = $this->recipientNameParts($recipient);
        if ($recipientParts === []) {
            return null;
        }

        $matches = [];
        foreach (Disposition::RECIPIENTS as $canonicalRecipient) {
            $canonicalParts = $this->recipientNameParts($canonicalRecipient);
            if ($recipientParts === $canonicalParts) {
                return $canonicalRecipient;
            }

            // Nama lama minimal dua kata boleh dicocokkan dengan awalan nama
            // lengkap, misalnya "Kiki Ramadhani" ke "Kiki Ramadhani Suyono".
            if (
                count($recipientParts) >= 2
                && count($recipientParts) < count($canonicalParts)
                && array_slice($canonicalParts, 0, count($recipientParts)) === $recipientParts
            ) {
                $matches[] = $canonicalRecipient;
            }
        }

        return count($matches) === 1 ? $matches[0] : null;
    }

    private function activeSdmRecipientNames(): array
    {
        $rows = db_connect()->table('users')
            ->select('display_name')
            ->where('role', 'sdm')
            ->where('deleted_at IS NULL', null, false)
            ->get()
            ->getResultArray();

        return array_values(array_filter(array_map(
            static fn (array $row): string => trim((string) ($row['display_name'] ?? '')),
            $rows,
        )));
    }

    private function recipientNameParts(string $name): array
    {
        $normalized = mb_strtolower(trim($name));
        $normalized = preg_replace('/[^\p{L}\p{N}]+/u', ' ', $normalized) ?? '';

        return array_values(array_filter(preg_split('/\s+/u', trim($normalized)) ?: []));
    }

    private function dateTimeValue(string $date, mixed $fallback): string
    {
        if ($date !== '') {
            return $date . ' 00:00:00';
        }

        return trim((string) $fallback) !== '' ? (string) $fallback : date('Y-m-d H:i:s');
    }

    private function validationError(array $errors): ResponseInterface
    {
        return $this->response->setStatusCode(422)->setJSON([
            'success' => false,
            'message' => $errors[0] ?? 'Data disposisi belum valid.',
            'errors' => $errors,
            'csrf' => ['name' => csrf_token(), 'hash' => csrf_hash()],
        ]);
    }
}
