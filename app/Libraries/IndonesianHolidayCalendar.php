<?php

namespace App\Libraries;

use DateTimeInterface;

/**
 * Kalender hari libur Indonesia yang dipakai oleh rekap kehadiran.
 *
 * Daftar 2026 mengacu pada SKB Menteri Agama, Menteri Ketenagakerjaan,
 * dan Menteri PANRB Nomor 1497/2025, 2/2025, dan 5/2025.
 */
final class IndonesianHolidayCalendar
{
    /** @var array<string, array{is_holiday: bool, label: string|null}> */
    private array $overrides = [];

    /** @var array<string, array{name: string, type: 'national'|'collective_leave'}> */
    private const HOLIDAYS = [
        '2026-01-01' => ['name' => 'Tahun Baru 2026 Masehi', 'type' => 'national'],
        '2026-01-16' => ['name' => 'Isra Mikraj Nabi Muhammad SAW', 'type' => 'national'],
        '2026-02-16' => ['name' => 'Tahun Baru Imlek 2577 Kongzili', 'type' => 'collective_leave'],
        '2026-02-17' => ['name' => 'Tahun Baru Imlek 2577 Kongzili', 'type' => 'national'],
        '2026-03-18' => ['name' => 'Hari Suci Nyepi Tahun Baru Saka 1948', 'type' => 'collective_leave'],
        '2026-03-19' => ['name' => 'Hari Suci Nyepi Tahun Baru Saka 1948', 'type' => 'national'],
        '2026-03-20' => ['name' => 'Hari Raya Idul Fitri 1447 H', 'type' => 'collective_leave'],
        '2026-03-21' => ['name' => 'Hari Raya Idul Fitri 1447 H', 'type' => 'national'],
        '2026-03-22' => ['name' => 'Hari Raya Idul Fitri 1447 H', 'type' => 'national'],
        '2026-03-23' => ['name' => 'Hari Raya Idul Fitri 1447 H', 'type' => 'collective_leave'],
        '2026-03-24' => ['name' => 'Hari Raya Idul Fitri 1447 H', 'type' => 'collective_leave'],
        '2026-04-03' => ['name' => 'Wafat Yesus Kristus', 'type' => 'national'],
        '2026-04-05' => ['name' => 'Hari Kebangkitan Yesus Kristus (Paskah)', 'type' => 'national'],
        '2026-05-01' => ['name' => 'Hari Buruh Internasional', 'type' => 'national'],
        '2026-05-14' => ['name' => 'Kenaikan Yesus Kristus', 'type' => 'national'],
        '2026-05-15' => ['name' => 'Kenaikan Yesus Kristus', 'type' => 'collective_leave'],
        '2026-05-27' => ['name' => 'Hari Raya Idul Adha 1447 H', 'type' => 'national'],
        '2026-05-28' => ['name' => 'Hari Raya Idul Adha 1447 H', 'type' => 'collective_leave'],
        '2026-05-31' => ['name' => 'Hari Raya Waisak 2570 BE', 'type' => 'national'],
        '2026-06-01' => ['name' => 'Hari Lahir Pancasila', 'type' => 'national'],
        '2026-06-16' => ['name' => '1 Muharam 1448 H', 'type' => 'national'],
        '2026-08-17' => ['name' => 'Hari Proklamasi Kemerdekaan', 'type' => 'national'],
        '2026-08-25' => ['name' => 'Maulid Nabi Muhammad SAW', 'type' => 'national'],
        '2026-12-24' => ['name' => 'Kelahiran Yesus Kristus', 'type' => 'collective_leave'],
        '2026-12-25' => ['name' => 'Kelahiran Yesus Kristus', 'type' => 'national'],
    ];

    /**
     * @param array<string, array<string, mixed>> $overrides
     */
    public function __construct(array $overrides = [])
    {
        foreach ($overrides as $date => $override) {
            $this->overrides[$date] = [
                'is_holiday' => (bool) ($override['is_holiday'] ?? false),
                'label'      => isset($override['label']) && trim((string) $override['label']) !== ''
                    ? trim((string) $override['label'])
                    : null,
            ];
        }
    }

    public function isNonWorkingDay(DateTimeInterface $date): bool
    {
        return $this->info($date)['is_non_working'];
    }

    public function isWeekend(DateTimeInterface $date): bool
    {
        return (int) $date->format('N') >= 6;
    }

    public function label(DateTimeInterface $date): ?string
    {
        return $this->info($date)['label'];
    }

    /**
     * @return array{is_non_working: bool, label: string|null, source: 'manual'|'national'|'collective_leave'|'weekend'|'workday', is_override: bool}
     */
    public function info(DateTimeInterface $date): array
    {
        $dateKey = $date->format('Y-m-d');
        if (isset($this->overrides[$dateKey])) {
            $override = $this->overrides[$dateKey];

            return [
                'is_non_working' => $override['is_holiday'],
                'label'          => $override['label'] ?? ($override['is_holiday'] ? 'Libur khusus' : 'Hari kerja khusus'),
                'source'         => 'manual',
                'is_override'    => true,
            ];
        }

        $holiday = self::HOLIDAYS[$dateKey] ?? null;
        if ($holiday !== null) {
            $prefix = $holiday['type'] === 'collective_leave' ? 'Cuti bersama' : 'Libur nasional';

            return [
                'is_non_working' => true,
                'label'          => $prefix . ': ' . $holiday['name'],
                'source'         => $holiday['type'],
                'is_override'    => false,
            ];
        }

        if ($this->isWeekend($date)) {
            return [
                'is_non_working' => true,
                'label'          => 'Akhir pekan',
                'source'         => 'weekend',
                'is_override'    => false,
            ];
        }

        return [
            'is_non_working' => false,
            'label'          => null,
            'source'         => 'workday',
            'is_override'    => false,
        ];
    }
}
