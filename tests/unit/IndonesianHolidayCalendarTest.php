<?php

use App\Libraries\IndonesianHolidayCalendar;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class IndonesianHolidayCalendarTest extends CIUnitTestCase
{
    public function testRecognizesNationalHolidayAndCollectiveLeaveIn2026(): void
    {
        $calendar = new IndonesianHolidayCalendar();

        $this->assertTrue($calendar->isNonWorkingDay(new DateTimeImmutable('2026-08-17')));
        $this->assertSame(
            'Libur nasional: Hari Proklamasi Kemerdekaan',
            $calendar->label(new DateTimeImmutable('2026-08-17')),
        );
        $this->assertTrue($calendar->isNonWorkingDay(new DateTimeImmutable('2026-03-20')));
        $this->assertSame(
            'Cuti bersama: Hari Raya Idul Fitri 1447 H',
            $calendar->label(new DateTimeImmutable('2026-03-20')),
        );
    }

    public function testRecognizesWeekendAndLeavesOrdinaryWorkdayOpen(): void
    {
        $calendar = new IndonesianHolidayCalendar();

        $this->assertTrue($calendar->isNonWorkingDay(new DateTimeImmutable('2026-08-29')));
        $this->assertSame('Akhir pekan', $calendar->label(new DateTimeImmutable('2026-08-29')));
        $this->assertFalse($calendar->isNonWorkingDay(new DateTimeImmutable('2026-08-26')));
        $this->assertNull($calendar->label(new DateTimeImmutable('2026-08-26')));
    }

    public function testManualOverrideCanChangeHolidayAndWorkday(): void
    {
        $calendar = new IndonesianHolidayCalendar([
            '2026-08-25' => ['is_holiday' => 0, 'label' => 'Hari kerja pengganti'],
            '2026-08-26' => ['is_holiday' => 1, 'label' => 'Libur kantor'],
        ]);

        $this->assertFalse($calendar->isNonWorkingDay(new DateTimeImmutable('2026-08-25')));
        $this->assertSame('Hari kerja pengganti', $calendar->label(new DateTimeImmutable('2026-08-25')));
        $this->assertTrue($calendar->isNonWorkingDay(new DateTimeImmutable('2026-08-26')));
        $this->assertSame('Libur kantor', $calendar->label(new DateTimeImmutable('2026-08-26')));
    }
}
