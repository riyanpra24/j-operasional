<?php

use App\Libraries\EssAttendanceReportParser;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class EssAttendanceReportParserTest extends CIUnitTestCase
{
    private string $fixturePath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->fixturePath = tempnam(sys_get_temp_dir(), 'ess-report-') ?: '';
    }

    protected function tearDown(): void
    {
        if ($this->fixturePath !== '' && is_file($this->fixturePath)) {
            unlink($this->fixturePath);
        }

        parent::tearDown();
    }

    public function testBuildsMonthlyRecapAndMapsEssStatuses(): void
    {
        file_put_contents($this->fixturePath, $this->reportHtml([
            $this->row('46237', 'Kiki Ramadhani Suyono', '90873', 'PRS', 'FPRS,PRS', '07:55:00', '17:10:00'),
            $this->row('46238', 'Kiki Ramadhani Suyono', '90873', 'PRS', 'NSO,PRS', '07:55:00', ''),
            $this->row('46237', 'Angger Wicaksono', '90870', 'ABS', 'ABS', '', ''),
            $this->row('46238', 'Angger Wicaksono', '90870', 'CB2', 'CB2', '', ''),
        ]));

        $report = (new EssAttendanceReportParser())->parse($this->fixturePath, 'attendance.xls');

        $this->assertSame('Agustus 2026', $report['period_label']);
        $this->assertSame(2, $report['summary']['EMPLOYEES']);
        $this->assertSame(4, $report['summary']['ROWS']);
        $this->assertSame(1, $report['summary']['H']);
        $this->assertSame(0, $report['summary']['TLBT']);
        $this->assertSame(0, $report['summary']['TA']);
        $this->assertSame(0, $report['summary']['TAM']);
        $this->assertSame(1, $report['summary']['TAP']);
        $this->assertSame(1, $report['summary']['A']);
        $this->assertSame(1, $report['summary']['I']);
        $this->assertCount(4, $report['records']);
        $this->assertSame('H', $report['records'][0]['recap_code']);
        $this->assertSame('TAP', $report['records'][1]['recap_code']);
        $this->assertSame('07:55:00', $report['records'][1]['actual_in']);
        $this->assertNull($report['records'][1]['actual_out']);

        $employees = array_column($report['employees'], null, 'employee_no');
        $this->assertSame('H', $employees['90873']['days'][3]);
        $this->assertSame('TAP', $employees['90873']['days'][4]);
        $this->assertSame('A', $employees['90870']['days'][3]);
        $this->assertSame('I', $employees['90870']['days'][4]);
    }

    public function testKeepsASingleScanWhenEssStatusIsEmpty(): void
    {
        file_put_contents($this->fixturePath, $this->reportHtml([
            $this->row('46237', 'Kiki Ramadhani Suyono', '90873', '', 'NSO', '07:55:00', ''),
        ]));

        $report = (new EssAttendanceReportParser())->parse($this->fixturePath, 'attendance.xls');

        $this->assertCount(1, $report['records']);
        $this->assertSame('TAP', $report['records'][0]['recap_code']);
        $this->assertSame('07:55:00', $report['records'][0]['actual_in']);
        $this->assertNull($report['records'][0]['actual_out']);
    }

    public function testStoresCheckoutOnlyAsTam(): void
    {
        file_put_contents($this->fixturePath, $this->reportHtml([
            $this->row('46237', 'Kiki Ramadhani Suyono', '90873', 'PRS', 'NSI,PRS', '', '17:10:00'),
        ]));

        $report = (new EssAttendanceReportParser())->parse($this->fixturePath, 'attendance.xls');

        $this->assertCount(1, $report['records']);
        $this->assertSame('TAM', $report['records'][0]['recap_code']);
        $this->assertNull($report['records'][0]['actual_in']);
        $this->assertSame('17:10:00', $report['records'][0]['actual_out']);
    }

    public function testStoresMissingCheckinAndCheckoutAsTa(): void
    {
        file_put_contents($this->fixturePath, $this->reportHtml([
            $this->row('46237', 'Kiki Ramadhani Suyono', '90873', '', '', '', ''),
        ]));

        $report = (new EssAttendanceReportParser())->parse($this->fixturePath, 'attendance.xls');

        $this->assertCount(1, $report['records']);
        $this->assertSame('TA', $report['records'][0]['recap_code']);
        $this->assertNull($report['records'][0]['actual_in']);
        $this->assertNull($report['records'][0]['actual_out']);
    }

    public function testMarksCompleteAttendanceAfterEightAsLate(): void
    {
        file_put_contents($this->fixturePath, $this->reportHtml([
            $this->row('46237', 'Tepat Waktu', '90871', 'PRS', 'FPRS,PRS', '08:00:00', '17:00:00'),
            $this->row('46238', 'Datang Terlambat', '90872', 'PRS', 'FPRS,PRS', '08:01:00', '17:00:00'),
        ]));

        $report = (new EssAttendanceReportParser())->parse($this->fixturePath, 'attendance.xls');
        $records = array_column($report['records'], null, 'employee_no');
        $employees = array_column($report['employees'], null, 'employee_no');

        $this->assertSame('H', $records['90871']['recap_code']);
        $this->assertSame('TLBT', $records['90872']['recap_code']);
        $this->assertSame(1, $report['summary']['H']);
        $this->assertSame(1, $report['summary']['TLBT']);
        $this->assertSame(100.0, $employees['90872']['attendance_rate']);
    }

    public function testRejectsAFileThatIsNotAnEssReport(): void
    {
        file_put_contents($this->fixturePath, '<html><body>Not an ESS report</body></html>');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('File tidak dikenali');

        (new EssAttendanceReportParser())->parse($this->fixturePath);
    }

    /**
     * @param list<string> $rows
     */
    private function reportHtml(array $rows): string
    {
        return '<html><body><b>Employee Attendance Report ESS</b>'
            . '<table><tr><td>Period</td><td>: 01/08/2026 - 31/08/2026</td></tr></table>'
            . '<table class="tabGen"><tr><td>Date</td><td>Employee Name</td><td>Employee No</td>'
            . '<td>Position</td><td>Organization Unit</td></tr>'
            . implode('', $rows) . '</table></body></html>';
    }

    private function row(
        string $serial,
        string $name,
        string $number,
        string $status,
        string $otherStatus,
        string $actualIn,
        string $actualOut,
    ): string {
        $cells = [
            $serial, $name, $number, 'Staf', 'Bagian Operasional', 'NORMAL_OFFICE', '480',
            '08:00:00', '17:00:00', $actualIn, '0', $actualOut, '0', '12:00:00',
            '13:00:00', 'WD', '480', '0', '', '0', '0', $status, $otherStatus, '',
        ];

        return '<tr><td>' . implode('</td><td>', array_map('htmlspecialchars', $cells)) . '</td></tr>';
    }
}
