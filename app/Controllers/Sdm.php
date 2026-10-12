<?php

namespace App\Controllers;

use App\Libraries\EssAttendanceReportParser;
use App\Libraries\EssOvertimeReportParser;
use App\Libraries\AttendanceDisciplineExportService;
use App\Libraries\IndonesianHolidayCalendar;
use App\Libraries\SdmEmployeeWorkbookParser;
use App\Libraries\SdmEmployeeWorkbookExporter;
use App\Models\AgendarisModel;
use App\Models\SdmAttendanceCalendarModel;
use App\Models\SdmAttendanceImportModel;
use App\Models\SdmAttendanceRecordModel;
use App\Models\SdmEmployeeProfileModel;
use App\Models\SdmOvertimeImportModel;
use App\Models\SdmOvertimeRecordModel;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Disposition;
use RuntimeException;

class Sdm extends BaseController
{
    private const LATE_FROM = '08:01:00';

    private const COMPOSITION_POSITIONS = [
        'Manajemen Unit Kerja', 'RM CBC', 'RM CAC', 'RM Subrogasi', 'Bagian Bisnis',
        'Bagian Klaim & Subrogasi', 'Bagian Operasional', 'Fungsional', 'Risk Officer (RO)', 'Helpdesk TI',
    ];

    private const EMPLOYMENT_STATUSES = [
        'Karyawan Tetap', 'Calon Karyawan', 'PKWT Umum', 'PKWT ELH', 'Magang', 'Outsourcing',
    ];

    private const OUTSOURCING_CATEGORIES = ['Admin', 'Driver', 'OB/CS', 'Security'];

    public function index(): string
    {
        return view('sdm/index', [
            'title' => 'SDM & Teller',
        ]);
    }

    public function incomingDocuments(): string
    {
        return $this->documentList();
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

        return redirect()->to(site_url('sdm/data-kehadiran') . '?' . http_build_query([
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
        $isOvertimeReturn = $this->request->getPost('return_to') === 'data-lembur';
        $redirectUrl = site_url($isOvertimeReturn ? 'sdm/data-lembur' : 'sdm/data-kehadiran') . '?' . http_build_query($isOvertimeReturn ? [
            'bulan' => (int) $date->format('n'),
            'tahun' => (int) $date->format('Y'),
            'tanggal' => $date->format('Y-m-d'),
        ] : [
            'import_id' => (int) $this->request->getPost('import_id'),
            'kalender' => 1,
            'kalender_tahun' => (int) $date->format('Y'),
            'kalender_bulan' => (int) $date->format('n'),
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

    public function attendanceRecords(): string
    {
        return view('sdm/sdm_jatim', $this->attendancePageData(
            (int) $this->request->getGet('import_id'),
        ));
    }

    public function attendanceOvertime(): string
    {
        $today = new \DateTimeImmutable('now');
        $requestedDate = $this->validAttendanceDate((string) $this->request->getGet('tanggal'));
        $requestedStart = $this->validAttendanceDate((string) $this->request->getGet('dari'));
        $requestedEnd = $this->validAttendanceDate((string) $this->request->getGet('sampai'));

        if ($requestedStart !== '' && $requestedEnd !== '') {
            $periodStart = new \DateTimeImmutable($requestedStart);
            $periodEnd = new \DateTimeImmutable($requestedEnd);
            if ($periodEnd < $periodStart) {
                [$periodStart, $periodEnd] = [$periodEnd, $periodStart];
            }
        } elseif ($requestedDate !== '') {
            // Tanggal yang diklik tetap menjadi sumber periode bila tautan lama
            // belum membawa rentang tanggal.
            $periodStart = new \DateTimeImmutable($requestedDate);
            $periodEnd = $periodStart;
        } else {
            $selectedMonth = (int) $this->request->getGet('bulan');
            $selectedYear = (int) $this->request->getGet('tahun');
            $selectedMonth = $selectedMonth >= 1 && $selectedMonth <= 12 ? $selectedMonth : (int) $today->format('n');
            $selectedYear = $selectedYear >= 2020 && $selectedYear <= 2100 ? $selectedYear : (int) $today->format('Y');
            $periodStart = new \DateTimeImmutable(sprintf('%04d-%02d-01', $selectedYear, $selectedMonth));
            $periodEnd = $periodStart->modify('last day of this month');
        }

        $selectedMonth = (int) $periodStart->format('n');
        $selectedYear = (int) $periodStart->format('Y');
        $rangeStart = $periodStart->format('Y-m-d');
        $rangeEnd = $periodEnd->format('Y-m-d');
        $hasRequestedDate = $requestedDate !== ''
            && $requestedDate >= $rangeStart
            && $requestedDate <= $rangeEnd;
        $selectedDate = $requestedDate;
        if (! $hasRequestedDate) {
            $selectedDate = $periodStart->format('Y-m-d');
        }

        $records = (new SdmOvertimeRecordModel())
            ->where('overtime_date >=', $rangeStart)
            ->where('overtime_date <=', $rangeEnd)
            ->orderBy('overtime_date', 'ASC')
            ->orderBy('employee_name', 'ASC')
            ->findAll();

        $overtimeImports = db_connect()->table('sdm_overtime_imports')
            ->distinct()
            ->select('sdm_overtime_imports.id, sdm_overtime_imports.source_name, sdm_overtime_imports.period_start, sdm_overtime_imports.period_end, sdm_overtime_imports.row_count')
            ->join('sdm_overtime_records', 'sdm_overtime_records.import_id = sdm_overtime_imports.id')
            ->where('sdm_overtime_records.overtime_date >=', $rangeStart)
            ->where('sdm_overtime_records.overtime_date <=', $rangeEnd)
            ->orderBy('sdm_overtime_imports.created_at', 'DESC')
            ->get()
            ->getResultArray();

        $dailyTotals = [];
        $overtimeRows = [];
        foreach ($records as $record) {
            $date = (string) $record['overtime_date'];
            $minutes = (int) $record['overtime_minutes'];
            $dailyTotals[$date] = ($dailyTotals[$date] ?? ['count' => 0, 'minutes' => 0]);
            $dailyTotals[$date]['count']++;
            $dailyTotals[$date]['minutes'] += $minutes;
            $record['overtime_minutes'] = $minutes;
            $overtimeRows[] = $record;
        }

        if (! $hasRequestedDate && $dailyTotals !== []) {
            $selectedDate = (string) array_key_first($dailyTotals);
        }

        $selectedRecords = array_values(array_filter($overtimeRows, static fn (array $record): bool => $record['overtime_date'] === $selectedDate));

        $holidayCalendar = new IndonesianHolidayCalendar($this->attendanceCalendarOverrides($periodStart, $periodEnd));
        $calendarDays = [];
        for ($date = $periodStart; $date <= $periodEnd; $date = $date->modify('+1 day')) {
            $value = $date->format('Y-m-d');
            $summary = $dailyTotals[$value] ?? ['count' => 0, 'minutes' => 0];
            $holiday = $holidayCalendar->info($date);
            $calendarDays[] = [
                'date' => $value,
                'day' => (int) $date->format('j'),
                'is_non_working' => $holiday['is_non_working'],
                'holiday_label' => $holiday['label'],
                'holiday_source' => $holiday['source'],
                'holiday_mode' => $holiday['is_override'] ? ($holiday['is_non_working'] ? 'holiday' : 'workday') : 'auto',
                'count' => $summary['count'],
                'minutes' => $summary['minutes'],
            ];
        }

        $selectedTotalMinutes = array_sum(array_map(static fn (array $record): int => (int) $record['overtime_minutes'], $selectedRecords));

        return view('sdm/data_lembur', [
            'title' => 'Data Lembur | SDM & Teller',
            'selectedMonth' => $selectedMonth,
            'selectedYear' => $selectedYear,
            'rangeStart' => $rangeStart,
            'rangeEnd' => $rangeEnd,
            'monthLabel' => $this->overtimePeriodLabel($rangeStart, $rangeEnd),
            'selectedDate' => $selectedDate,
            'selectedDateLabel' => $this->attendanceDateLabel($selectedDate),
            'showSelectedHolidayNote' => $hasRequestedDate,
            'calendarDays' => $calendarDays,
            'calendarLeadingDays' => (int) $periodStart->format('N') - 1,
            'overtimeRecords' => $selectedRecords,
            'overtimeCount' => count($selectedRecords),
            'overtimeMinutes' => $selectedTotalMinutes,
            'overtimeImports' => $overtimeImports,
            'importError' => null,
        ]);
    }

    public function recapSdmOvertime(): string|RedirectResponse
    {
        $file = $this->request->getFile('overtime_file');
        if ($file === null || ! $file->isValid()) {
            return redirect()->to(site_url('sdm/data-lembur'))->with('error', 'Pilih file Overtime Report ESS dari Workplace.');
        }
        if ($file->getSize() > 5 * 1024 * 1024 || ! in_array(strtolower($file->getClientExtension()), ['xls', 'html', 'htm'], true)) {
            return redirect()->to(site_url('sdm/data-lembur'))->with('error', 'Unggah file Overtime Report ESS berformat .xls dengan ukuran maksimal 5 MB.');
        }

        try {
            $hash = hash_file('sha256', $file->getTempName());
            if ($hash === false) {
                throw new RuntimeException('Identitas file Overtime Report ESS tidak dapat dibaca.');
            }
            $importModel = new SdmOvertimeImportModel();
            $existing = $importModel->where('source_hash', $hash)->first();
            if ($existing !== null) {
                $existingPeriod = new \DateTimeImmutable($existing['period_start']);

                return redirect()->to(site_url('sdm/data-lembur?' . http_build_query([
                    'bulan' => (int) $existingPeriod->format('n'),
                    'tahun' => (int) $existingPeriod->format('Y'),
                ])))
                    ->with('success', 'File Overtime Report ESS tersebut sudah tersimpan. Data yang ada ditampilkan kembali.');
            }

            $report = (new EssOvertimeReportParser())->parse($file->getTempName(), $file->getClientName());
            $this->saveOvertimeReport($report, $hash);
            $firstOvertimeDate = $report['records'][0]['overtime_date'] ?? $report['period_start'];
            $reportPeriod = new \DateTimeImmutable($firstOvertimeDate);

            return redirect()->to(site_url('sdm/data-lembur?' . http_build_query([
                'bulan' => (int) $reportPeriod->format('n'),
                'tahun' => (int) $reportPeriod->format('Y'),
                'tanggal' => $reportPeriod->format('Y-m-d'),
            ])))
                ->with('success', 'Overtime Report ESS berhasil dipetakan ke kalender dan rincian lembur.');
        } catch (\Throwable $exception) {
            log_message('warning', 'Import Overtime Report ESS gagal: {message}', ['message' => $exception->getMessage()]);

            return redirect()->to(site_url('sdm/data-lembur'))->with('error', $exception->getMessage());
        }
    }

    public function deleteSdmOvertime(): RedirectResponse
    {
        $importId = (int) $this->request->getPost('import_id');
        $month = (int) $this->request->getPost('bulan');
        $year = (int) $this->request->getPost('tahun');
        $month = $month >= 1 && $month <= 12 ? $month : (int) date('n');
        $year = $year >= 2020 && $year <= 2100 ? $year : (int) date('Y');
        $returnUrl = site_url('sdm/data-lembur?' . http_build_query(['bulan' => $month, 'tahun' => $year]));

        $importModel = new SdmOvertimeImportModel();
        $import = $importId > 0 ? $importModel->find($importId) : null;
        if ($import === null) {
            return redirect()->to($returnUrl)->with('error', 'Laporan lembur tidak ditemukan atau sudah dihapus.');
        }

        $hasRecords = (new SdmOvertimeRecordModel())
            ->where('import_id', $importId)
            ->where('overtime_date >=', sprintf('%04d-%02d-01', $year, $month))
            ->where('overtime_date <=', (new \DateTimeImmutable(sprintf('%04d-%02d-01', $year, $month)))->modify('last day of this month')->format('Y-m-d'))
            ->first();
        if ($hasRecords === null) {
            return redirect()->to($returnUrl)->with('error', 'Laporan yang dipilih tidak memuat data pada periode ini.');
        }

        if (! $importModel->delete($importId, true)) {
            return redirect()->to($returnUrl)->with('error', 'Laporan lembur belum dapat dihapus.');
        }

        return redirect()->to($returnUrl)->with('success', 'Laporan lembur beserta seluruh rincian yang bersumber darinya berhasil dihapus.');
    }

    public function sdmJatim(): string
    {
        $keyword = trim((string) $this->request->getGet('q'));
        $workUnit = trim((string) $this->request->getGet('unit_kerja'));
        $positionFilter = trim((string) $this->request->getGet('posisi'));
        $perPage = (int) $this->request->getGet('per_page');
        $perPage = in_array($perPage, [10, 20, 50, 100], true) ? $perPage : 10;

        $employees = new SdmEmployeeProfileModel();
        $workUnitOptions = array_values(array_filter(array_unique(array_merge([
            'Kantor Wilayah Surabaya',
            'Kantor Cabang Surabaya',
            'Kantor Cabang Kediri',
            'Kantor Cabang Malang',
            'Kantor Cabang Madiun',
            'Kantor Cabang Banyuwangi',
            'Kantor Unit Pelayanan Bojonegoro',
            'Kantor Unit Pelayanan Pamekasan',
            'Kantor Unit Pelayanan Jember',
        ], array_map(
            static fn (array $employee): string => trim((string) ($employee['work_unit'] ?? '')),
            (new SdmEmployeeProfileModel())->select('work_unit')->findAll(),
        ))), static fn (string $unit): bool => $unit !== ''));
        $workUnitPriority = [
            'Kantor Wilayah Surabaya' => 1,
            'Kantor Cabang Surabaya' => 2,
            'Kantor Cabang Malang' => 3,
            'Kantor Cabang Kediri' => 4,
            'Kantor Cabang Madiun' => 5,
            'Kantor Cabang Banyuwangi' => 6,
        ];
        usort($workUnitOptions, static function (string $left, string $right) use ($workUnitPriority): int {
            if ($left === $right) return 0;
            $leftPriority = $workUnitPriority[$left] ?? PHP_INT_MAX;
            $rightPriority = $workUnitPriority[$right] ?? PHP_INT_MAX;
            if ($leftPriority !== $rightPriority) return $leftPriority <=> $rightPriority;
            return strnatcasecmp($left, $right);
        });
        if ($workUnit !== '' && ! in_array($workUnit, $workUnitOptions, true)) {
            $workUnit = '';
        }
        $positionOptions = array_values(array_filter(array_unique(array_map(
            static fn (array $employee): string => trim((string) ($employee['position'] ?? '')),
            (new SdmEmployeeProfileModel())->select('position')->findAll(),
        )), static fn (string $position): bool => $position !== ''));
        usort($positionOptions, function (string $left, string $right): int {
            $leftWeight = $this->sdmPositionSortWeight($left);
            $rightWeight = $this->sdmPositionSortWeight($right);

            return $leftWeight === $rightWeight
                ? strnatcasecmp($left, $right)
                : $leftWeight <=> $rightWeight;
        });
        if ($positionFilter !== '' && ! in_array($positionFilter, $positionOptions, true)) {
            $positionFilter = '';
        }
        $summaryWorkUnitOptions = array_values(array_filter(
            $workUnitOptions,
            static fn (string $unit): bool => ! in_array($unit, [
                'Kantor Unit Pelayanan Bojonegoro',
                'Kantor Unit Pelayanan Pamekasan',
                'Kantor Unit Pelayanan Jember',
            ], true),
        ));
        $workUnitCounts = array_fill_keys($workUnitOptions, 0);
        foreach ((new SdmEmployeeProfileModel())
            ->select('work_unit, COUNT(*) AS total_employees')
            ->groupBy('work_unit')
            ->findAll() as $summary) {
            $unit = trim((string) ($summary['work_unit'] ?? ''));
            if ($unit !== '') {
                $workUnitCounts[$unit] = (int) ($summary['total_employees'] ?? 0);
            }
        }
        $compositionPositions = self::COMPOSITION_POSITIONS;
        $compositionColumns = array_merge([
            'Karyawan Tetap', 'Calon Karyawan', 'PKWT Umum', 'PKWT ELH', 'Magang',
        ], self::OUTSOURCING_CATEGORIES);
        $compositionStatusCounts = [];
        foreach ($workUnitOptions as $unit) {
            $compositionStatusCounts[$unit] = [];
            foreach ($compositionPositions as $position) {
                $compositionStatusCounts[$unit][$position] = array_fill_keys($compositionColumns, 0);
            }
        }
        foreach ((new SdmEmployeeProfileModel())
            ->select('work_unit, organization, position, employment_status')
            ->findAll() as $employee) {
            $unit = trim((string) ($employee['work_unit'] ?? ''));
            $position = $this->compositionPositionForEmployee($employee);
            $column = $this->compositionColumnForEmployee($employee);
            if ($unit !== '' && $position !== null && $column !== null && isset($compositionStatusCounts[$unit][$position][$column])) {
                $compositionStatusCounts[$unit][$position][$column]++;
            }
        }
        if ($keyword !== '') {
            $employees->groupStart()
                ->like('employee_name', $keyword)
                ->orLike('employee_no', $keyword)
                ->orLike('position', $keyword)
                ->orLike('work_unit', $keyword)
                ->orLike('organization', $keyword)
                ->orLike('corporate_email', $keyword)
                ->groupEnd();
        }
        if ($workUnit !== '') {
            $employees->where('work_unit', $workUnit);
        }
        if ($positionFilter !== '') {
            $employees->where('position', $positionFilter);
        }
        $positionSortColumn = "LOWER(TRIM(COALESCE(position, '')))";
        $employees->orderBy("CASE
            WHEN {$positionSortColumn} IN ('pimpinan wilayah', 'pemimpin cabang', 'kepala kup')
                OR {$positionSortColumn} LIKE 'kepala kantor unit pelayanan%' THEN 1
            WHEN {$positionSortColumn} IN ('wakil pimpinan wilayah', 'wakil pinwil', 'wakil pemimpin cabang') THEN 2
            WHEN {$positionSortColumn} LIKE 'kepala bagian%'
                OR {$positionSortColumn} LIKE 'manager %'
                OR {$positionSortColumn} LIKE 'manajer %' THEN 3
            WHEN {$positionSortColumn} IN ('admin', 'resepsionist', 'receptionist') THEN 5
            WHEN {$positionSortColumn} IN ('ob/cs', 'ob', 'office boy', 'cleaning service') THEN 6
            WHEN {$positionSortColumn} = 'driver' THEN 7
            WHEN {$positionSortColumn} IN ('security', 'satpam') THEN 8
            ELSE 4
        END", 'ASC', false);
        $employees->orderBy('employee_name', 'ASC');

        $employeesNeedingReview = [];
        foreach ((new SdmEmployeeProfileModel())->orderBy('employee_name', 'ASC')->findAll() as $employee) {
            $issues = $this->sdmEmployeeValidationIssues($employee);
            if ($issues !== []) {
                $employee['validation_issues'] = $issues;
                $employeesNeedingReview[] = $employee;
            }
        }

        return view('sdm/sdm_jatim_list', [
            'title' => 'SDM Jatim | SDM & Teller',
            'employees' => $employees->paginate($perPage, 'sdm_jatim_list'),
            'pager' => $employees->pager,
            'filters' => compact('keyword', 'workUnit', 'positionFilter', 'perPage'),
            'workUnitOptions' => $workUnitOptions,
            'positionOptions' => $positionOptions,
            'summaryWorkUnitOptions' => $summaryWorkUnitOptions,
            'workUnitCounts' => $workUnitCounts,
            'compositionPositions' => $compositionPositions,
            'compositionColumns' => $compositionColumns,
            'compositionStatusCounts' => $compositionStatusCounts,
            'employeesNeedingReview' => $employeesNeedingReview,
        ]);
    }

    public function createSdmJatim(): RedirectResponse
    {
        return $this->saveSdmJatim();
    }

    public function downloadSdmJatimTemplate(): ResponseInterface
    {
        $path = ROOTPATH . 'public/assets/templates/template_import_sdm_per_unit_kerja.xlsx';
        if (! is_file($path)) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException('Template import SDM tidak ditemukan.');
        }

        return $this->response
            ->setHeader('Cache-Control', 'private, no-store, max-age=0')
            ->download($path, null)
            ->setFileName('Template_Import_SDM_Per_Unit_Kerja.xlsx');
    }

    public function exportSdmJatimDocument(): ResponseInterface|RedirectResponse
    {
        if (! in_array((string) session()->get('auth_role'), ['admin', 'sdm'], true)) {
            return redirect()->to(site_url('sdm/sdm-jatim'))->with('error', 'Export dokumen SDM hanya tersedia untuk Administrator dan SDM & Teller.');
        }

        $employees = (new SdmEmployeeProfileModel())->findAll();
        if ($employees === []) {
            return redirect()->to(site_url('sdm/sdm-jatim'))->with('error', 'Tidak ada data SDM yang dapat diekspor.');
        }

        usort($employees, function (array $left, array $right): int {
            $workUnit = strcmp((string) ($left['work_unit'] ?? ''), (string) ($right['work_unit'] ?? ''));
            if ($workUnit !== 0) return $workUnit;

            $position = $this->sdmPositionSortWeight((string) ($left['position'] ?? '')) <=> $this->sdmPositionSortWeight((string) ($right['position'] ?? ''));
            if ($position !== 0) return $position;

            return strcasecmp((string) ($left['employee_name'] ?? ''), (string) ($right['employee_name'] ?? ''));
        });

        try {
            $document = (new SdmEmployeeWorkbookExporter())->export(
                ROOTPATH . 'public/assets/templates/template_import_sdm_per_unit_kerja.xlsx',
                $employees,
                WRITEPATH . 'cache',
            );
        } catch (\RuntimeException $exception) {
            log_message('error', 'Export SDM Jatim gagal: {message}', ['message' => $exception->getMessage()]);

            return redirect()->to(site_url('sdm/sdm-jatim'))->with('error', 'Dokumen export SDM tidak dapat dibuat.');
        }

        return $this->response
            ->setHeader('Cache-Control', 'private, no-store, max-age=0')
            ->setHeader('X-Content-Type-Options', 'nosniff')
            ->setHeader('Content-Disposition', 'attachment; filename="Update_Data_Base_Karyawan_Unit_Kerja_' . date('Ymd_His') . '.xlsx"')
            ->setContentType('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
            ->setBody($document);
    }

    public function importSdmJatim(): RedirectResponse
    {
        $returnUrl = $this->sdmJatimReturnUrl();
        if ((string) session()->get('auth_role') !== 'admin') {
            return redirect()->to($returnUrl)->with('error', 'Import data SDM hanya tersedia untuk Administrator.');
        }
        if ((string) $this->request->getPost('confirm_import') !== 'yes') {
            return redirect()->to($returnUrl)->with('error', 'Konfirmasi import data SDM diperlukan.');
        }

        $file = $this->request->getFile('sdm_excel');
        if ($file === null || ! $file->isValid() || $file->hasMoved()
            || strtolower($file->getClientExtension()) !== 'xlsx' || $file->getSize() > 8 * 1024 * 1024) {
            return redirect()->to($returnUrl)->with('error', 'Pilih template SDM berformat .xlsx dengan ukuran maksimal 8 MB.');
        }

        try {
            $rows = (new SdmEmployeeWorkbookParser())->parse($file->getTempName());
            $model = new SdmEmployeeProfileModel();
            $db = db_connect();
            $db->transBegin();
            $added = 0;
            $updated = 0;
            $needsReview = 0;

            foreach ($rows as $row) {
                $data = $this->importedSdmEmployeeData($row);
                if ($this->sdmEmployeeValidationIssues($data) !== []) {
                    $needsReview++;
                }
                $existing = $db->table('sdm_employee_profiles')
                    ->select('id')
                    ->where('employee_no', $data['employee_no'])
                    ->get()
                    ->getRowArray();

                if ($existing === null) {
                    if ($model->insert($data) === false) {
                        throw new RuntimeException('Data pada sheet ' . $row['sheet_name'] . ', baris ' . $row['sheet_row'] . ' tidak dapat disimpan.');
                    }
                    $added++;
                } elseif (! $model->update((int) $existing['id'], $data)) {
                    throw new RuntimeException('Data pada sheet ' . $row['sheet_name'] . ', baris ' . $row['sheet_row'] . ' tidak dapat diperbarui.');
                } else {
                    $updated++;
                }
            }

            if (! $db->transStatus()) {
                throw new RuntimeException('Data SDM tidak dapat disimpan.');
            }
            $db->transCommit();
        } catch (RuntimeException $exception) {
            if (isset($db)) {
                $db->transRollback();
            }

            return redirect()->to($returnUrl)->with('error', $exception->getMessage());
        } catch (\Throwable $exception) {
            if (isset($db)) {
                $db->transRollback();
            }
            log_message('error', 'Import SDM gagal: {message}', ['message' => $exception->getMessage()]);

            return redirect()->to($returnUrl)->with('error', 'Import data SDM belum berhasil.');
        }

        $message = count($rows) . " data SDM berhasil diimport ({$added} baru, {$updated} diperbarui).";
        if ($needsReview > 0) {
            $message .= " {$needsReview} data perlu dilengkapi atau diperbaiki melalui menu Data Perlu Dilengkapi.";
        }

        return redirect()->to($returnUrl)->with('success', $message);
    }

    public function updateSdmJatim(int $id): RedirectResponse
    {
        return $this->saveSdmJatim($id);
    }

    public function deleteSdmJatim(int $id): RedirectResponse
    {
        $model = new SdmEmployeeProfileModel();
        $employee = $model->find($id);
        if (! is_array($employee) || ! $model->delete($id)) {
            return redirect()->to(site_url('sdm/sdm-jatim'))->with('error', 'Data SDM tidak ditemukan atau gagal dihapus.');
        }
        return redirect()->to(site_url('sdm/sdm-jatim'))->with('success', 'Data SDM ' . $employee['employee_name'] . ' berhasil dihapus.');
    }

    public function deleteAllSdmJatim(): RedirectResponse
    {
        if ((string) session()->get('auth_role') !== 'admin') {
            return redirect()->to(site_url('sdm/sdm-jatim'))->with('error', 'Hanya administrator yang dapat menghapus seluruh data SDM.');
        }
        if ((string) $this->request->getPost('confirm_delete') !== 'yes') {
            return redirect()->to(site_url('sdm/sdm-jatim'))->with('error', 'Konfirmasi penghapusan seluruh data SDM diperlukan.');
        }

        $model = new SdmEmployeeProfileModel();
        $total = $model->countAllResults();
        if ($total === 0) {
            return redirect()->to(site_url('sdm/sdm-jatim'))->with('success', 'Tidak ada data SDM yang perlu dihapus.');
        }

        $db = db_connect();
        $db->transBegin();
        try {
            $deleted = $db->table('sdm_employee_profiles')->where('id >', 0)->delete();
            if (! $deleted) {
                throw new \RuntimeException('Penghapusan data SDM gagal dijalankan.');
            }
            $db->transCommit();
        } catch (\Throwable $exception) {
            $db->transRollback();
            log_message('error', 'Gagal menghapus seluruh data SDM: {message}', ['message' => $exception->getMessage()]);
            return redirect()->to(site_url('sdm/sdm-jatim'))->with('error', 'Seluruh data SDM belum dapat dihapus.');
        }

        return redirect()->to(site_url('sdm/sdm-jatim'))->with('success', $total . ' data SDM berhasil dihapus seluruhnya.');
    }

    private function saveSdmJatim(?int $id = null): RedirectResponse
    {
        $returnUrl = $this->sdmJatimReturnUrl();
        $data = [
            'employee_no' => trim((string) $this->request->getPost('employee_no')),
            'employee_name' => trim((string) $this->request->getPost('employee_name')),
            'gender' => $this->normalizeSdmGender((string) $this->request->getPost('gender')),
            'work_unit' => trim((string) $this->request->getPost('work_unit')),
            'organization' => trim((string) $this->request->getPost('organization')),
            'position' => trim((string) $this->request->getPost('position')),
            // Posisi rekap dan kategori outsourcing kini ditentukan dari Bagian + Posisi.
            'composition_position' => null,
            'outsourcing_category' => null,
            'employment_status' => $this->normalizeEmploymentStatus((string) $this->request->getPost('employment_status')),
            'employment_date' => trim((string) $this->request->getPost('employment_date')),
            'phone_number' => trim((string) $this->request->getPost('phone_number')),
            'corporate_email' => trim((string) $this->request->getPost('corporate_email')),
        ];
        foreach (['gender', 'work_unit', 'organization', 'position', 'employment_status', 'employment_date', 'phone_number', 'corporate_email'] as $field) {
            if ($data[$field] === '') $data[$field] = null;
        }
        $errors = [];
        if ($data['employee_no'] === '') $errors[] = 'NPP wajib diisi.';
        if ($data['employee_name'] === '') $errors[] = 'Nama lengkap wajib diisi.';
        if ($data['work_unit'] === null) $errors[] = 'Unit kerja wajib diisi.';
        if ($data['employment_status'] === null) $errors[] = 'Status karyawan wajib diisi.';
        if ($data['gender'] !== null && ! in_array($data['gender'], ['L', 'P'], true)) $errors[] = 'Gender harus L atau P.';
        if ($data['employment_status'] !== null && ! in_array($data['employment_status'], self::EMPLOYMENT_STATUSES, true)) $errors[] = 'Status karyawan tidak valid.';
        if ($data['employment_status'] === 'Outsourcing' && ! $this->isAllowedOutsourcingPosition((string) $data['position'])) $errors[] = 'Pilih posisi outsourcing pada kolom Posisi.';
        if ($data['employment_date'] !== null && ! preg_match('/^\d{4}-\d{2}-\d{2}$/D', $data['employment_date'])) $errors[] = 'Format TMT tidak valid.';
        if ($data['corporate_email'] !== null && ! filter_var($data['corporate_email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Email korporat tidak valid.';

        $model = new SdmEmployeeProfileModel();
        $duplicate = $model->where('employee_no', $data['employee_no'])->first();
        if (is_array($duplicate) && (int) $duplicate['id'] !== $id) $errors[] = 'NPP sudah digunakan oleh data SDM lain.';
        if ($errors !== []) return redirect()->to($returnUrl)->with('error', implode(' ', $errors));

        $success = $id === null ? $model->insert($data) !== false : $model->update($id, $data);
        if (! $success) return redirect()->to($returnUrl)->with('error', 'Data SDM belum dapat disimpan.');
        return redirect()->to($returnUrl)->with('success', 'Data SDM berhasil ' . ($id === null ? 'ditambahkan.' : 'diperbarui.'));
    }

    /**
     * Converts a checked template row into the format used by employee profiles.
     *
     * @param array<string, string|null> $row
     * @return array<string, string|null>
     */
    private function importedSdmEmployeeData(array $row): array
    {
        $status = $this->normalizeEmploymentStatus((string) ($row['employment_status'] ?? ''));
        $gender = $this->normalizeSdmGender((string) ($row['gender'] ?? ''));
        $date = trim((string) ($row['employment_date'] ?? ''));
        $email = trim((string) ($row['corporate_email'] ?? ''));
        $position = $this->normalizeImportedSdmPosition((string) ($row['position'] ?? ''), $status);

        return [
            'employee_no' => $this->importedSdmEmployeeNo($row),
            'employee_name' => trim((string) ($row['employee_name'] ?? '')),
            'gender' => $gender === '' ? null : $gender,
            'work_unit' => trim((string) ($row['work_unit'] ?? '')),
            'organization' => $this->nullableImportedValue($row['organization'] ?? null),
            'position' => $position,
            'composition_position' => null,
            'outsourcing_category' => null,
            'employment_status' => $status,
            'employment_date' => $date === '' ? null : $date,
            'phone_number' => $this->nullableImportedValue($row['phone_number'] ?? null),
            'corporate_email' => $email === '' ? null : $email,
        ];
    }

    /**
     * Returns editable profile issues without blocking an Excel import.
     *
     * @param array<string, mixed> $employee
     * @return list<string>
     */
    private function sdmEmployeeValidationIssues(array $employee): array
    {
        $issues = [];
        $employeeNo = trim((string) ($employee['employee_no'] ?? ''));
        $gender = $this->normalizeSdmGender((string) ($employee['gender'] ?? ''));
        $status = $this->normalizeEmploymentStatus((string) ($employee['employment_status'] ?? ''));
        $position = trim((string) ($employee['position'] ?? ''));
        $date = trim((string) ($employee['employment_date'] ?? ''));
        $email = trim((string) ($employee['corporate_email'] ?? ''));

        if ($employeeNo === '' || str_starts_with($employeeNo, 'IMP-')) $issues[] = 'NPP belum dilengkapi';
        if (trim((string) ($employee['employee_name'] ?? '')) === '') $issues[] = 'Nama lengkap belum diisi';
        if ($gender === '' || ! in_array($gender, ['L', 'P'], true)) $issues[] = 'Gender belum sesuai';
        if (trim((string) ($employee['work_unit'] ?? '')) === '') $issues[] = 'Unit kerja belum diisi';
        if (trim((string) ($employee['organization'] ?? '')) === '') $issues[] = 'Bagian belum diisi';
        if ($position === '') $issues[] = 'Posisi belum diisi';
        if (! in_array($status, self::EMPLOYMENT_STATUSES, true)) $issues[] = 'Status karyawan tidak sesuai';
        if ($status === 'Outsourcing' && ! $this->isAllowedOutsourcingPosition($position)) $issues[] = 'Posisi outsourcing tidak sesuai';
        if ($date !== '' && ! preg_match('/^\d{4}-\d{2}-\d{2}$/D', $date)) $issues[] = 'Format TMT tidak valid';
        if ($email !== '' && ! filter_var($email, FILTER_VALIDATE_EMAIL)) $issues[] = 'Email korporat tidak valid';

        return $issues;
    }

    /** @param array<string, string|null> $row */
    private function importedSdmEmployeeNo(array $row): string
    {
        $employeeNo = trim((string) ($row['employee_no'] ?? ''));
        if ($employeeNo !== '' && $employeeNo !== '-') {
            return mb_substr($employeeNo, 0, 30);
        }

        $unitCode = match ((string) ($row['work_unit'] ?? '')) {
            'Kantor Wilayah Surabaya' => 'KWS',
            'Kantor Cabang Surabaya' => 'KCS',
            'Kantor Cabang Kediri' => 'KDR',
            'Kantor Cabang Malang' => 'KML',
            'Kantor Cabang Madiun' => 'MDN',
            'Kantor Cabang Banyuwangi' => 'BYW',
            'Kantor Unit Pelayanan Bojonegoro' => 'KBO',
            'Kantor Unit Pelayanan Pamekasan' => 'KPM',
            'Kantor Unit Pelayanan Jember' => 'KJB',
            default => 'SDM',
        };

        return 'IMP-' . $unitCode . '-' . (string) ($row['sheet_row'] ?? '0');
    }

    private function nullableImportedValue(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' || $value === '-' ? null : $value;
    }

    private function normalizeImportedSdmPosition(string $position, string $status): ?string
    {
        $position = trim($position);
        $key = $this->compositionText($position);
        if ($key === '' || $key === '-') {
            return null;
        }

        return match ($key) {
            'staf rm cbc', 'staff rm cbc' => 'Staff RM CBC',
            'staf rm cac', 'staff rm cac' => 'Staff RM CAC',
            'staf rm subrogasi', 'staff rm subrogasi' => 'Staff RM Subrogasi',
            'staf bisnis', 'staff bisnis' => 'Staff Bisnis',
            'staf operasional', 'staff operasional' => 'Staff Operasional',
            'staf klaim', 'staff klaim',
            'staf klaim dan subrogasi', 'staff klaim dan subrogasi',
            'staf klaim & subrogasi', 'staff klaim & subrogasi' => 'Staff Klaim',
            'staf fungsional', 'staff fungsional' => 'Staff Fungsional',
            'staf risk officer', 'staff risk officer' => 'Staff Risk Officer',
            'office boy', 'ob' => 'OB/CS',
            'magang mahasiswa', 'magang umum' => 'Magang',
            'rm cbc' => 'Staff RM CBC',
            'rm cac' => 'Staff RM CAC',
            'rm subrogasi' => 'Staff RM Subrogasi',
            'rro' => 'Risk Officer',
            default => $position,
        };
    }

    /**
     * Restores the active list filters after a profile is saved.
     */
    private function sdmJatimReturnUrl(): string
    {
        $query = (string) $this->request->getPost('return_query');
        parse_str(ltrim($query, '?'), $parameters);

        $allowed = [];
        foreach (['q', 'unit_kerja', 'posisi', 'per_page', 'page_sdm_jatim_list', 'review_data'] as $key) {
            if (isset($parameters[$key]) && is_scalar($parameters[$key]) && (string) $parameters[$key] !== '') {
                $allowed[$key] = (string) $parameters[$key];
            }
        }
        if ((string) $this->request->getPost('return_to_validation') === '1') {
            $allowed['review_data'] = '1';
        }

        $returnUrl = site_url('sdm/sdm-jatim');
        $queryString = http_build_query($allowed, '', '&', PHP_QUERY_RFC3986);

        return $queryString === '' ? $returnUrl : $returnUrl . '?' . $queryString;
    }

    /**
     * Normalizes legacy employment-status labels used by ESS and historical SDM files.
     */
    private function normalizeEmploymentStatus(string $status): string
    {
        $status = trim($status);
        $key = mb_strtolower(preg_replace('/\s+/u', ' ', $status) ?? '');

        if (str_contains($key, 'alih daya') || str_contains($key, 'outsour') || $key === 'os') {
            return 'Outsourcing';
        }

        return match ($key) {
            'karyawan tetap', 'pkwtt' => 'Karyawan Tetap',
            'calon karyawan', 'cakar' => 'Calon Karyawan',
            'pkwt', 'pkwt umum', 'kkwt' => 'PKWT Umum',
            'pkwt elh' => 'PKWT ELH',
            'magang', 'magang umum' => 'Magang',
            'outsourcing', 'outsorcing' => 'Outsourcing',
            default => $status,
        };
    }

    /**
     * Stores Excel gender markers consistently while the interface shows full labels.
     */
    private function normalizeSdmGender(string $gender): string
    {
        $gender = trim($gender);
        $key = mb_strtolower(preg_replace('/\s+/u', ' ', $gender) ?? '');

        return match ($key) {
            'l', 'laki-laki', 'laki laki', 'pria' => 'L',
            'p', 'perempuan', 'wanita' => 'P',
            default => strtoupper($gender),
        };
    }

    /**
     * Global display order for leadership, staff, and outsourcing positions.
     */
    private function sdmPositionSortWeight(string $position): int
    {
        $position = $this->compositionText($position);

        if (in_array($position, ['pimpinan wilayah', 'pemimpin cabang', 'kepala kup'], true)
            || str_starts_with($position, 'kepala kantor unit pelayanan')) {
            return 1;
        }
        if (in_array($position, ['wakil pimpinan wilayah', 'wakil pinwil', 'wakil pemimpin cabang'], true)) {
            return 2;
        }
        if (str_starts_with($position, 'kepala bagian')
            || str_starts_with($position, 'manager ')
            || str_starts_with($position, 'manajer ')) {
            return 3;
        }
        if (in_array($position, ['admin', 'resepsionist', 'receptionist'], true)) {
            return 5;
        }
        if (in_array($position, ['ob/cs', 'ob', 'office boy', 'cleaning service'], true)) {
            return 6;
        }
        if ($position === 'driver') {
            return 7;
        }
        if (in_array($position, ['security', 'satpam'], true)) {
            return 8;
        }

        return 4;
    }

    /**
     * Determines rekap row from the selected Bagian and Posisi.
     */
    private function compositionPositionForEmployee(array $employee): ?string
    {
        $position = $this->compositionText((string) ($employee['position'] ?? ''));
        $organization = $this->compositionText((string) ($employee['organization'] ?? ''));
        $workUnit = $this->compositionText((string) ($employee['work_unit'] ?? ''));
        if ($position === '') return null;
        if ($workUnit === 'kantor wilayah surabaya' && (
            $position === 'pimpinan wilayah'
            || in_array($position, ['wakil pimpinan wilayah', 'wakil pinwil'], true)
            || str_starts_with($position, 'kepala bagian ')
        )) return 'Manajemen Unit Kerja';
        if (str_starts_with($workUnit, 'kantor cabang ') && (
            in_array($position, ['pemimpin cabang', 'wakil pemimpin cabang'], true)
            || $position === 'manager'
            || str_starts_with($position, 'manager ')
        )) return 'Manajemen Unit Kerja';
        if ($position === 'staff rm cbc') return 'RM CBC';
        if ($position === 'staff rm cac') return 'RM CAC';
        if ($position === 'staff rm subrogasi') return 'RM Subrogasi';
        if ($position === 'staff risk officer' || $position === 'risk officer') return 'Risk Officer (RO)';
        if ($position === 'it helpdesk' || $position === 'helpdesk ti') return 'Helpdesk TI';
        if ($position === 'staff fungsional' || $position === 'fungsional') return 'Fungsional';
        if ($position === 'staff bisnis') return 'Bagian Bisnis';
        if ($this->outsourcingCategoryFromPosition($position) !== null) {
            if ($this->normalizeEmploymentStatus((string) ($employee['employment_status'] ?? '')) !== 'Outsourcing') return null;
            if (str_contains($organization, 'klaim') || str_contains($organization, 'subrogasi')) return 'Bagian Klaim & Subrogasi';
            if (str_contains($organization, 'bisnis')) return 'Bagian Bisnis';
            if (str_contains($organization, 'operasional')) return 'Bagian Operasional';
            return null;
        }
        if (in_array($position, ['staff klaim dan subrogasi', 'staff klaim'], true)) return 'Bagian Klaim & Subrogasi';
        if ($position === 'staff operasional') return 'Bagian Operasional';

        return null;
    }

    private function compositionColumnForEmployee(array $employee): ?string
    {
        $status = $this->normalizeEmploymentStatus((string) ($employee['employment_status'] ?? ''));
        if ($status === 'Outsourcing') {
            if (in_array($this->compositionText((string) ($employee['position'] ?? '')), [
                'staff rm cbc',
                'staff rm cac',
                'staff bisnis',
                'staff klaim dan subrogasi',
                'staff klaim',
                'staff operasional',
            ], true)) {
                return 'Admin';
            }

            return $this->outsourcingCategoryFromPosition((string) ($employee['position'] ?? ''));
        }

        return in_array($status, self::EMPLOYMENT_STATUSES, true) ? $status : null;
    }

    private function compositionText(string $value): string
    {
        $value = mb_strtolower(trim($value));
        return preg_replace('/\s+/u', ' ', $value) ?? '';
    }

    private function outsourcingCategoryFromPosition(string $position): ?string
    {
        return match ($this->compositionText($position)) {
            'admin' => 'Admin',
            'driver' => 'Driver',
            'ob/cs', 'ob', 'office boy', 'cleaning service' => 'OB/CS',
            'security', 'satpam' => 'Security',
            default => null,
        };
    }

    private function isAllowedOutsourcingPosition(string $position): bool
    {
        if ($this->outsourcingCategoryFromPosition($position) !== null) {
            return true;
        }

        return in_array($this->compositionText($position), [
            'staff rm cbc',
            'staff rm cac',
            'staff bisnis',
            'staff klaim',
            'staff operasional',
        ], true);
    }

    public function recapSdmJatimAttendance(): string|RedirectResponse
    {
        $file = $this->request->getFile('attendance_file');
        $workUnit = trim((string) $this->request->getPost('unit_kerja'));
        $reportMonth = (int) $this->request->getPost('bulan');
        $reportYear = (int) $this->request->getPost('tahun');

        if (! in_array($workUnit, $this->attendanceWorkUnitOptions(), true)
            || $reportMonth < 1 || $reportMonth > 12 || $reportYear < 2020 || $reportYear > 2100) {
            return view('sdm/sdm_jatim', $this->attendancePageData(
                null,
                'Pilih unit kerja, bulan, dan tahun laporan terlebih dahulu.',
            ));
        }

        if ($file === null || ! $file->isValid()) {
            return view('sdm/sdm_jatim', $this->attendancePageData(
                null,
                'Pilih file Employee Attendance Report ESS hasil ekspor Workplace.',
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
                'Format file belum didukung. Unggah Employee Attendance Report ESS dari Workplace berformat .xls.',
            ));
        }

        try {
            $hash = hash_file('sha256', $file->getTempName());
            if ($hash === false) {
                throw new RuntimeException('Identitas file Employee Attendance Report ESS tidak dapat dibaca.');
            }

            $report = (new EssAttendanceReportParser())->parse(
                $file->getTempName(),
                $file->getClientName(),
            );
            $essPeriod = new \DateTimeImmutable((string) ($report['date_period_start'] ?? $report['period_start']));
            if ((int) $essPeriod->format('n') !== $reportMonth || (int) $essPeriod->format('Y') !== $reportYear) {
                $essPeriodLabel = $this->attendanceMonthLabel((int) $essPeriod->format('n')) . ' ' . $essPeriod->format('Y');
                $selectedPeriodLabel = $this->attendanceMonthLabel($reportMonth) . ' ' . $reportYear;

                return redirect()->to(site_url('sdm/data-kehadiran'))->with(
                    'error',
                    'Unggahan dibatalkan. Bulan pada kolom Date file ESS adalah ' . $essPeriodLabel
                    . ', sedangkan periode yang dipilih di sistem adalah ' . $selectedPeriodLabel . '. Pilih periode yang sesuai lalu unggah kembali.'
                );
            }
            $detectedWorkUnit = $report['detected_work_unit'] ?? null;
            if (is_string($detectedWorkUnit) && $detectedWorkUnit !== '' && $detectedWorkUnit !== $workUnit) {
                return redirect()->to(site_url('sdm/data-kehadiran'))->with(
                    'error',
                    'Unggahan dibatalkan. Kolom Organization Unit pada file ESS terdeteksi sebagai ' . $detectedWorkUnit
                    . ', sedangkan unit kerja yang dipilih adalah ' . $workUnit . '. Pilih unit kerja yang sesuai lalu unggah kembali.'
                );
            }

            $importModel = new SdmAttendanceImportModel();
            $existingImport = $importModel->withDeleted()->where('source_hash', $hash)->first();
            if ($existingImport !== null) {
                if (! empty($existingImport['deleted_at'])) {
                    if (! $this->currentRoleIsAdmin()) {
                        return redirect()->to(site_url('sdm/data-kehadiran'))
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

                    return redirect()->to(site_url('sdm/data-kehadiran?import_id=' . $existingImport['id']))
                        ->with('success', 'Rekap yang pernah dihapus berhasil dipulihkan dan ditampilkan kembali.');
                }

                return redirect()->to(site_url('sdm/data-kehadiran?import_id=' . $existingImport['id']))
                    ->with('success', 'File yang sama sudah pernah disimpan. Rekap tersimpan ditampilkan kembali.');
            }

            $importId = $this->saveAttendanceReport($report, $hash, $workUnit, $reportMonth, $reportYear);

            return redirect()->to(site_url('sdm/data-kehadiran?import_id=' . $importId))
                ->with('success', 'Data Employee Attendance Report ESS berhasil dipetakan dan disimpan otomatis.');
        } catch (\Throwable $exception) {
            log_message('warning', 'Import Employee Attendance Report ESS gagal: {message}', ['message' => $exception->getMessage()]);

            return view('sdm/sdm_jatim', $this->attendancePageData(null, $exception->getMessage()));
        }
    }

    public function exportSdmJatimAttendance(): ResponseInterface|RedirectResponse
    {
        $workUnit = trim((string) $this->request->getPost('unit_kerja'));
        $month = (int) $this->request->getPost('bulan');
        $year = (int) $this->request->getPost('tahun');
        $returnUrl = site_url('sdm/data-kehadiran');
        $allWorkUnits = $workUnit === '__all__';
        $imports = [];
        if ($month >= 1 && $month <= 12 && $year >= 2020 && $year <= 2100 && ($allWorkUnits || $workUnit !== '')) {
            $periodStart = sprintf('%04d-%02d-01', $year, $month);
            $periodEnd = (new \DateTimeImmutable($periodStart))->modify('last day of this month')->format('Y-m-d');
            $importModel = new SdmAttendanceImportModel();
            if (! $allWorkUnits) {
                $importModel->where('work_unit', $workUnit);
            }
            $imports = $importModel
                ->where('period_start >=', $periodStart)
                ->where('period_start <=', $periodEnd)
                ->orderBy('work_unit', 'ASC')
                ->orderBy('id', 'DESC')
                ->findAll();
            if ($allWorkUnits) {
                $seenWorkUnits = [];
                $imports = array_values(array_filter($imports, static function (array $import) use (&$seenWorkUnits): bool {
                    $key = mb_strtolower(trim((string) ($import['work_unit'] ?? '')));
                    if ($key === '' || isset($seenWorkUnits[$key])) {
                        return false;
                    }
                    $seenWorkUnits[$key] = true;

                    return true;
                }));
            }
        }
        if (! $allWorkUnits && isset($imports[0]['id'])) {
            $returnUrl .= '?import_id=' . (int) $imports[0]['id'];
        }
        if ($imports === []) {
            return redirect()->to($returnUrl)->with('error', 'Data kehadiran untuk unit kerja dan periode yang dipilih belum tersedia.');
        }

        try {
            $exportService = new AttendanceDisciplineExportService();
            $export = $allWorkUnits
                ? $exportService->createForImports($imports, $month, $year)
                : $exportService->create($imports[0]);
            $contents = file_get_contents($export['path']);
            if ($contents === false) {
                throw new RuntimeException('File Rekap Kehadiran belum dapat dibaca.');
            }
            unlink($export['path']);

            return $this->response
                ->setHeader('Cache-Control', 'private, no-store, max-age=0')
                ->setHeader('X-Content-Type-Options', 'nosniff')
                ->setHeader('Content-Disposition', 'attachment; filename="' . $export['filename'] . '"')
                ->setContentType('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
                ->setBody($contents);
        } catch (\Throwable $exception) {
            log_message('warning', 'Export Rekap Kehadiran gagal: {message}', ['message' => $exception->getMessage()]);

            return redirect()->to($returnUrl)->with(
                'error',
                $exception instanceof RuntimeException ? $exception->getMessage() : 'Rekap Kehadiran belum dapat diekspor. Silakan coba kembali.'
            );
        }
    }

    public function updateSdmJatimAnomalies(): RedirectResponse
    {
        $importId = (int) $this->request->getPost('import_id');
        $submittedRecords = $this->request->getPost('records');
        $redirectUrl = site_url('sdm/data-kehadiran?import_id=' . $importId);

        $importModel = new SdmAttendanceImportModel();
        if ($importId <= 0 || $importModel->find($importId) === null || ! is_array($submittedRecords)) {
            return redirect()->to($redirectUrl)->with('error', 'Data anomali belum dapat diperbarui.');
        }

        $recordModel = new SdmAttendanceRecordModel();
        $anomalyRecords = $recordModel
            ->where('import_id', $importId)
            ->whereIn('recap_code', ['A', 'TA', 'TAM', 'TAP', 'TLTAP', 'TPA'])
            ->findAll();
        $allowedCodes = ['H', 'TLBT', 'TLTAP', 'ODR', 'IZ', 'CT', 'S', 'A', 'TA', 'TAM', 'TAP', 'TPA', 'OFF'];
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
                if (in_array($code, ['TA', 'TAM', 'TAP', 'TLTAP', 'TPA'], true)) {
                    if ($actualIn !== null && $actualOut === null) {
                        $code = $actualIn >= self::LATE_FROM ? 'TLTAP' : 'TAP';
                    } elseif ($actualIn === null && $actualOut !== null) {
                        $code = 'TAM';
                    } elseif ($actualIn !== null && $actualOut !== null && in_array($code, ['TAM', 'TAP', 'TLTAP'], true)) {
                        $code = 'H';
                    } elseif ($actualIn === null && $actualOut === null && in_array($code, ['TAM', 'TAP', 'TLTAP'], true)) {
                        $code = 'TA';
                    }
                }
                if ($actualIn !== null && $actualOut !== null && in_array($code, ['H', 'TLBT'], true)) {
                    $code = $actualIn >= self::LATE_FROM ? 'TLBT' : 'H';
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

        return redirect()->to(site_url('sdm/data-kehadiran?import_id=' . $importId))
            ->with('success', $updated . ' data anomali berhasil diperbarui.');
    }

    public function deleteSdmJatimAttendance(): RedirectResponse
    {
        $importId = (int) $this->request->getPost('import_id');
        $importModel = new SdmAttendanceImportModel();
        $import = $importId > 0 ? $importModel->find($importId) : null;

        if ($import === null) {
            return redirect()->to(site_url('sdm/data-kehadiran'))
                ->with('error', 'Rekap absensi tidak ditemukan atau sudah dihapus.');
        }

        if (! $this->deleteRecord($importModel, 'sdm_attendance_imports', $importId)) {
            return redirect()->to(site_url('sdm/data-kehadiran?import_id=' . $importId))
                ->with('error', 'Rekap absensi belum dapat dihapus.');
        }

        return redirect()->to(site_url('sdm/data-kehadiran'))
            ->with('success', $this->currentRoleIsAdmin()
                ? 'Rekap absensi beserta seluruh detailnya berhasil dihapus permanen.'
                : 'Rekap absensi berhasil dipindahkan ke Data Terhapus.');
    }

    /**
     * @return array<string, mixed>
     */
    private function attendancePageData(?int $selectedImportId = null, ?string $error = null): array
    {
        $perPage = (int) $this->request->getGet('per_page');
        $perPage = in_array($perPage, [10, 20, 50, 100], true) ? $perPage : 10;
        $requestedPage = max(1, (int) $this->request->getGet('halaman'));
        $filters = [
            'name'         => trim((string) $this->request->getGet('nama')),
            'work_unit'    => trim((string) $this->request->getGet('unit_kerja')),
            'from'         => $this->validAttendanceDate((string) $this->request->getGet('dari')),
            'to'           => $this->validAttendanceDate((string) $this->request->getGet('sampai')),
            'mode'         => $this->request->getGet('mode') === 'detail' ? 'detail' : 'summary',
            'employee_key' => trim((string) $this->request->getGet('pegawai')),
        ];
        $importModel = new SdmAttendanceImportModel();
        $imports = $importModel
            ->orderBy('period_start', 'DESC')
            ->orderBy('id', 'DESC')
            ->findAll();
        $workUnits = $this->attendanceWorkUnitOptions($imports);
        if (! in_array($filters['work_unit'], $workUnits, true)) {
            $filters['work_unit'] = '';
        }
        foreach ($imports as &$import) {
            $period = new \DateTimeImmutable($import['period_start']);
            $month = (int) ($import['report_month'] ?? $period->format('n'));
            $year = (int) ($import['report_year'] ?? $period->format('Y'));
            $import['period_label'] = $this->attendanceMonthLabel($month) . ' ' . $year;
        }
        unset($import);

        $filteredImports = $filters['work_unit'] === ''
            ? $imports
            : array_values(array_filter($imports, static fn (array $import): bool => ($import['work_unit'] ?? '') === $filters['work_unit']));

        if (($selectedImportId ?? 0) <= 0 && $filteredImports !== []) {
            $selectedImportId = (int) $filteredImports[0]['id'];
        }

        $selectedImport = null;
        foreach ($filteredImports as $import) {
            if ((int) $import['id'] === $selectedImportId) {
                $selectedImport = $import;
                break;
            }
        }

        if ($selectedImport === null && $filteredImports !== []) {
            $selectedImport = $filteredImports[0];
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

        $report = $selectedImport !== null ? $this->storedAttendanceReport($selectedImport, $filters) : null;
        $attendancePagination = [
            'per_page'     => $perPage,
            'current_page' => 1,
            'last_page'    => 1,
            'total'        => 0,
            'offset'       => 0,
        ];
        if ($report !== null) {
            $total = count($report['employees']);
            $lastPage = max(1, (int) ceil($total / $perPage));
            $currentPage = min($requestedPage, $lastPage);
            $offset = ($currentPage - 1) * $perPage;
            $report['employees'] = array_slice($report['employees'], $offset, $perPage);
            $attendancePagination = [
                'per_page'     => $perPage,
                'current_page' => $currentPage,
                'last_page'    => $lastPage,
                'total'        => $total,
                'offset'       => $offset,
            ];
        }

        return [
            'title'            => 'Data Kehadiran | SDM & Teller',
            'report'           => $report,
            'importError'      => $error,
            'attendanceImports' => $filteredImports,
            'attendanceExportImports' => $imports,
            'attendanceWorkUnits' => $workUnits,
            'selectedImportId' => $selectedImport !== null ? (int) $selectedImport['id'] : null,
            'attendanceFilters' => $filters,
            'attendancePagination' => $attendancePagination,
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
            'previousUrl' => site_url('sdm/data-kehadiran') . '?' . http_build_query($baseQuery + [
                'kalender_tahun' => (int) $monthStart->modify('-1 month')->format('Y'),
                'kalender_bulan' => (int) $monthStart->modify('-1 month')->format('n'),
            ]),
            'nextUrl'     => site_url('sdm/data-kehadiran') . '?' . http_build_query($baseQuery + [
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
    private function saveAttendanceReport(array $report, string $hash, string $workUnit, int $reportMonth, int $reportYear): int
    {
        $db = db_connect();
        $importModel = new SdmAttendanceImportModel();
        $recordModel = new SdmAttendanceRecordModel();
        $db->transBegin();

        try {
            $importId = $importModel->insert([
                'source_name'      => $report['source_name'],
                'source_hash'      => $hash,
                'work_unit'        => $workUnit,
                'period_start'     => $report['period_start'],
                'period_end'       => $report['period_end'],
                'report_month'     => $reportMonth,
                'report_year'      => $reportYear,
                'employee_count'   => $report['summary']['EMPLOYEES'],
                'row_count'        => $report['summary']['ROWS'],
                'hadir_count'      => $report['summary']['H'] + $report['summary']['TLBT'] + $report['summary']['ODR'],
                'izin_count'       => $report['summary']['IZ'] + $report['summary']['CT'] + $report['summary']['S'],
                'alpa_count'       => $report['summary']['A'],
                'incomplete_count' => $report['summary']['TA'] + $report['summary']['TAM'] + $report['summary']['TAP'] + $report['summary']['TLTAP'] + ($report['summary']['TPA'] ?? 0),
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
     * @param array<string, mixed> $report
     */
    private function saveOvertimeReport(array $report, string $hash): int
    {
        $db = db_connect();
        $importModel = new SdmOvertimeImportModel();
        $recordModel = new SdmOvertimeRecordModel();
        $db->transBegin();

        try {
            $importId = $importModel->insert([
                'source_name' => $report['source_name'], 'source_hash' => $hash,
                'period_start' => $report['period_start'], 'period_end' => $report['period_end'],
                'employee_count' => $report['employee_count'], 'row_count' => $report['row_count'],
                'total_minutes' => $report['total_minutes'],
                'imported_by' => session()->get('auth_user_id') ?: null,
                'imported_by_name' => trim((string) session()->get('auth_display_name')) ?: null,
            ], true);
            if (! is_int($importId) && ! ctype_digit((string) $importId)) {
                throw new RuntimeException('Rekap lembur belum dapat disimpan.');
            }

            $rows = array_map(static fn (array $record): array => ['import_id' => (int) $importId] + $record, $report['records']);
            foreach (array_chunk($rows, 200) as $chunk) {
                if ($recordModel->insertBatch($chunk) === false) {
                    throw new RuntimeException('Rincian lembur belum dapat disimpan.');
                }
            }
            if ($db->transStatus() === false) {
                throw new RuntimeException('Penyimpanan rekap lembur belum berhasil.');
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
        $summary = ['H' => 0, 'TLBT' => 0, 'TLTAP' => 0, 'ODR' => 0, 'IZ' => 0, 'CT' => 0, 'S' => 0, 'A' => 0, 'TA' => 0, 'TAM' => 0, 'TAP' => 0, 'TPA' => 0, 'OFF' => 0, 'OTHER' => 0];
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
                    'work_unit'      => (string) ($import['work_unit'] ?? 'Kantor Wilayah Surabaya'),
                    'days'           => [],
                    'day_details'    => [],
                    'totals'         => ['H' => 0, 'TLBT' => 0, 'TLTAP' => 0, 'ODR' => 0, 'IZ' => 0, 'CT' => 0, 'S' => 0, 'A' => 0, 'TA' => 0, 'TAM' => 0, 'TAP' => 0, 'TPA' => 0, 'OFF' => 0],
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
            $rawStatus = strtoupper((string) $record['raw_status']);
            $code = $rawStatus === 'SD'
                ? 'S'
                : $this->normalizeAttendanceLeaveCode((string) $record['recap_code']);
            $actualIn = $record['actual_in'];
            $actualOut = $record['actual_out'];
            if ($code === 'I') {
                $code = $rawStatus === 'IZ' ? 'IZ' : 'CT';
            }
            if ($this->isOnDutyRequestRemark((string) $record['remark'])) {
                $code = 'ODR';
            } elseif ($calendarInfo['is_non_working']) {
                $code = 'OFF';
            } elseif ($calendarInfo['is_override'] && $code === 'OFF') {
                $code = $actualIn !== null && $actualOut !== null
                    ? ($actualIn >= self::LATE_FROM ? 'TLBT' : 'H')
                    : ($actualIn !== null ? ($actualIn >= self::LATE_FROM ? 'TLTAP' : 'TAP') : ($actualOut !== null ? 'TAM' : 'TA'));
            } elseif ($actualIn !== null && $actualOut !== null && in_array($code, ['H', 'TLBT', 'TLTAP'], true)) {
                $code = $actualIn >= self::LATE_FROM ? 'TLBT' : 'H';
            } elseif ($actualIn !== null && $actualOut === null && in_array($code, ['TAP', 'TLTAP'], true)) {
                $code = $actualIn >= self::LATE_FROM ? 'TLTAP' : 'TAP';
            }
            $day = (int) $recordDate->format('j');
            $employees[$key]['days'][$day] = $code;
            $employees[$key]['day_details'][$day] = [
                'recap_code'   => $code,
                'actual_in'    => $record['actual_in'],
                'actual_out'   => $record['actual_out'],
                'holiday_name' => $calendarInfo['is_non_working'] ? $calendarInfo['label'] : null,
            ];
            $isOdrOnNonWorkingDay = $code === 'ODR' && $calendarInfo['is_non_working'];
            if ($isOdrOnNonWorkingDay) {
                $employees[$key]['totals']['OFF']++;
                $summary['OFF']++;
            } elseif (isset($employees[$key]['totals'][$code])) {
                $employees[$key]['totals'][$code]++;
            }
            if ($isOdrOnNonWorkingDay) {
                // ODR tetap ditampilkan pada hari libur, tetapi tidak dihitung sebagai kehadiran.
            } elseif (isset($summary[$code])) {
                $summary[$code]++;
            } else {
                $summary['OTHER']++;
            }
            if (in_array($code, ['A', 'TA', 'TAM', 'TAP', 'TLTAP', 'TPA'], true)) {
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

                $employee['days'][$day] = '-';
                $employee['day_details'][$day] = [
                    'recap_code'   => '-',
                    'actual_in'    => null,
                    'actual_out'   => null,
                    'holiday_name' => null,
                ];
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
            $presenceSignals = $employee['totals']['H'] + $employee['totals']['TLBT'] + $employee['totals']['ODR']
                + $employee['totals']['TA'] + $employee['totals']['TAM'] + $employee['totals']['TAP'] + $employee['totals']['TLTAP'] + $employee['totals']['TPA'];
            $effectiveAttendance = $employee['totals']['H']
                + ($employee['totals']['TLBT'] * 0.75)
                + $employee['totals']['ODR']
                + ($employee['totals']['TAM'] * 0.5)
                + ($employee['totals']['TAP'] * 0.5)
                + ($employee['totals']['TLTAP'] * 0.25)
                + ($employee['totals']['TA'] * 0.25)
                + ($employee['totals']['TPA'] * 0.25);
            $attendanceRate = $effectiveWorkDays > 0
                ? round(($effectiveAttendance / $effectiveWorkDays) * 100, 1)
                : null;
            $punctualityRate = $presenceSignals > 0
                ? round((($employee['totals']['H'] + $employee['totals']['ODR']) / $presenceSignals) * 100, 1)
                : null;
            $disciplineIndex = $attendanceRate !== null && $punctualityRate !== null
                ? round(($attendanceRate + $punctualityRate) / 2, 1)
                : null;

            if ($employee['totals']['A'] > 0) {
                $evaluationStatus = 'Teguran (Alpa)';
                $evaluationKey = 'warning';
            } elseif (
                ($employee['totals']['TLBT'] + $employee['totals']['TLTAP']) >= 3
                || ($attendanceRate !== null && $attendanceRate < 95)
                || $employee['totals']['TA'] > 0
                || $employee['totals']['TPA'] > 0
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
            'period_label'    => $this->attendanceMonthLabel((int) ($import['report_month'] ?? $period->format('n'))) . ' ' . (int) ($import['report_year'] ?? $period->format('Y')),
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
     * @param list<array<string, mixed>> $imports
     * @return list<string>
     */
    private function attendanceWorkUnitOptions(array $imports = []): array
    {
        $units = [
            'Kantor Wilayah Surabaya',
            'Kantor Cabang Surabaya',
            'Kantor Cabang Kediri',
            'Kantor Cabang Malang',
            'Kantor Cabang Madiun',
            'Kantor Cabang Banyuwangi',
        ];

        foreach ($imports as $import) {
            $unit = trim((string) ($import['work_unit'] ?? ''));
            if ($unit !== '') {
                $units[] = $unit;
            }
        }

        $units = array_values(array_unique($units));
        sort($units, SORT_NATURAL | SORT_FLAG_CASE);

        return $units;
    }

    private function attendanceDateLabel(string $date): string
    {
        $value = new \DateTimeImmutable($date);

        return $value->format('j') . ' ' . $this->attendanceMonthLabel((int) $value->format('n')) . ' ' . $value->format('Y');
    }

    private function overtimePeriodLabel(string $start, string $end): string
    {
        $startDate = new \DateTimeImmutable($start);
        $endDate = new \DateTimeImmutable($end);
        if ($startDate->format('Y-m-d') === $endDate->format('Y-m-d')) {
            return $this->attendanceDateLabel($start);
        }
        if ($startDate->format('Y-m') === $endDate->format('Y-m')) {
            return $startDate->format('j') . ' – ' . $endDate->format('j') . ' '
                . $this->attendanceMonthLabel((int) $startDate->format('n')) . ' ' . $startDate->format('Y');
        }
        if ($startDate->format('Y') === $endDate->format('Y')) {
            return $startDate->format('j') . ' ' . $this->attendanceMonthLabel((int) $startDate->format('n'))
                . ' – ' . $endDate->format('j') . ' ' . $this->attendanceMonthLabel((int) $endDate->format('n'))
                . ' ' . $endDate->format('Y');
        }

        return $startDate->format('j') . ' ' . $this->attendanceMonthLabel((int) $startDate->format('n'))
            . ' ' . $startDate->format('Y') . ' – ' . $endDate->format('j') . ' '
            . $this->attendanceMonthLabel((int) $endDate->format('n')) . ' ' . $endDate->format('Y');
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
            ->select('employee_key, recap_code, raw_status, remark, day_type')
            ->where('import_id', $importId)
            ->findAll();
        $employees = [];
        $counts = ['H' => 0, 'TLBT' => 0, 'TLTAP' => 0, 'ODR' => 0, 'IZ' => 0, 'CT' => 0, 'S' => 0, 'A' => 0, 'TA' => 0, 'TAM' => 0, 'TAP' => 0, 'TPA' => 0, 'OFF' => 0, 'OTHER' => 0];

        foreach ($records as $record) {
            $employees[$record['employee_key']] = true;
            $isNonWorkingDay = in_array(strtoupper((string) ($record['day_type'] ?? '')), ['OFF', 'PHOFF'], true);
            $code = $this->isOnDutyRequestRemark((string) ($record['remark'] ?? ''))
                    ? 'ODR'
                    : ($isNonWorkingDay
                        ? 'OFF'
                        : (strtoupper((string) ($record['raw_status'] ?? '')) === 'SD'
                            ? 'S'
                            : ($record['recap_code'] === 'I'
                                ? (strtoupper((string) ($record['raw_status'] ?? '')) === 'IZ' ? 'IZ' : 'CT')
                                : $this->normalizeAttendanceLeaveCode((string) $record['recap_code']))));
            if ($code === 'ODR' && $isNonWorkingDay) {
                $counts['OFF']++;
            } else {
                isset($counts[$code]) ? $counts[$code]++ : $counts['OTHER']++;
            }
        }

        if ($importModel->update($importId, [
            'employee_count'   => count($employees),
            'row_count'        => count($records),
            'hadir_count'      => $counts['H'] + $counts['TLBT'] + $counts['ODR'],
            'izin_count'       => $counts['IZ'] + $counts['CT'] + $counts['S'],
            'alpa_count'       => $counts['A'],
            'incomplete_count' => $counts['TA'] + $counts['TAM'] + $counts['TAP'] + $counts['TLTAP'] + $counts['TPA'],
            'off_count'        => $counts['OFF'],
            'other_count'      => $counts['OTHER'],
        ]) === false) {
            throw new RuntimeException('Ringkasan absensi belum dapat diperbarui.');
        }
    }

    private function isOnDutyRequestRemark(string $remark): bool
    {
        return preg_match('/\b(?:on\s*duty\s*request|odr)\b/i', $remark) === 1;
    }

    private function normalizeAttendanceLeaveCode(string $code): string
    {
        $code = strtoupper(trim($code));

        return in_array($code, ['CT', 'RI', 'CS', 'CBR', 'CK'], true) || preg_match('/^CB(?:\d+)?$/', $code) === 1
            ? 'CT'
            : $code;
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

    private function documentList(): string
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

        $pagerGroup = 'sdm_incoming_documents';
        $direction = $order === 'terlama' ? 'ASC' : 'DESC';

        return view('sdm/dokumen_masuk', [
            'title' => 'Dokumen Masuk | SDM & Teller',
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
            'pagerGroup' => $pagerGroup,
            'listUrl' => site_url('sdm/dokumen-masuk'),
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
            'redirect_url' => $this->incomingDocumentListUrl(),
            'csrf' => ['name' => csrf_token(), 'hash' => csrf_hash()],
        ]);
    }

    /**
     * Keep the active document-list filters after an AJAX disposition update.
     * Only local SDM list paths and their expected filter keys are accepted.
     */
    private function incomingDocumentListUrl(): string
    {
        $fallbackRoute = 'sdm/dokumen-masuk';
        $returnUrl = trim((string) $this->request->getPost('return_url'));

        if ($returnUrl === '') {
            $returnUrl = trim((string) $this->request->getServer('HTTP_REFERER'));
        }

        $parts = $returnUrl !== '' ? parse_url($returnUrl) : false;
        if (! is_array($parts) || ! isset($parts['path'])) {
            return site_url($fallbackRoute);
        }

        $incomingPath = rtrim((string) parse_url(site_url('sdm/dokumen-masuk'), PHP_URL_PATH), '/');
        $requestedPath = rtrim((string) $parts['path'], '/');
        $route = $requestedPath === $incomingPath ? 'sdm/dokumen-masuk' : $fallbackRoute;

        if ($route === $fallbackRoute && $requestedPath !== $incomingPath) {
            return site_url($fallbackRoute);
        }

        $query = [];
        parse_str((string) ($parts['query'] ?? ''), $query);
        $allowedKeys = [
            'q',
            'status',
            'urutan',
            'per_page',
            'page_sdm_incoming_documents',
        ];
        $filters = [];
        foreach ($allowedKeys as $key) {
            if (! isset($query[$key]) || is_array($query[$key])) {
                continue;
            }

            $value = trim((string) $query[$key]);
            if ($value !== '') {
                $filters[$key] = $value;
            }
        }

        return site_url($route) . ($filters !== [] ? '?' . http_build_query($filters) : '');
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
