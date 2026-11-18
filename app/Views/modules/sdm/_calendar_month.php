<?php
/** @var string $monthLabel */
/** @var list<array<string, mixed>> $days */
/** @var int $leadingDays */

$sourceLabels = [
    'national'         => 'Libur nasional',
    'collective_leave' => 'Cuti bersama',
    'weekend'          => 'Akhir pekan',
    'manual'           => 'Diatur manual',
    'workday'          => 'Hari kerja',
];
$officialDays = array_values(array_filter(
    $days,
    static fn (array $day): bool => in_array($day['source'], ['national', 'collective_leave'], true),
));
$trailingDays = max(0, 42 - $leadingDays - count($days));
?>

<div class="attendance-calendar-official-summary">
    <div class="attendance-calendar-official-heading">
        <strong>Libur Resmi Bulan Ini</strong><span><?= count($officialDays) ?> tanggal</span>
    </div>
    <?php if ($officialDays === []): ?>
        <p>Tidak ada libur nasional atau cuti bersama pada <?= esc($monthLabel) ?>.</p>
    <?php else: ?>
        <div class="attendance-calendar-official-list">
            <?php foreach ($officialDays as $holiday): ?>
                <article>
                    <time datetime="<?= esc($holiday['date'], 'attr') ?>"><?= date('d', strtotime((string) $holiday['date'])) ?></time>
                    <div>
                        <small><?= $holiday['source'] === 'national' ? 'LIBUR NASIONAL' : 'CUTI BERSAMA' ?></small>
                        <strong><?= esc(preg_replace('/^(Libur nasional|Cuti bersama):\s*/', '', (string) $holiday['label'])) ?></strong>
                    </div>
                </article>
            <?php endforeach ?>
        </div>
    <?php endif ?>
</div>

<div class="attendance-calendar-grid" role="grid" aria-label="Tanggal <?= esc($monthLabel, 'attr') ?>">
    <?php foreach (['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'] as $weekday): ?>
        <div class="attendance-calendar-weekday" role="columnheader"><?= esc($weekday) ?></div>
    <?php endforeach ?>
    <?php for ($blank = 0; $blank < $leadingDays; $blank++): ?>
        <div class="attendance-calendar-blank" aria-hidden="true"></div>
    <?php endfor ?>
    <?php foreach ($days as $day): ?>
        <?php
        $source = (string) $day['source'];
        $stateClass = $day['is_override']
            ? 'is-manual'
            : ($source === 'national' || $source === 'collective_leave'
                ? 'is-national'
                : ($source === 'weekend' ? 'is-weekend' : 'is-workday'));
        $dateLabel = date('d-m-Y', strtotime((string) $day['date']));
        ?>
        <button
            type="button"
            class="attendance-calendar-day <?= esc($stateClass, 'attr') ?>"
            role="gridcell"
            data-calendar-edit
            data-date="<?= esc($day['date'], 'attr') ?>"
            data-date-label="<?= esc($dateLabel, 'attr') ?>"
            data-mode="<?= esc($day['mode'], 'attr') ?>"
            data-label="<?= esc($day['label'] ?? '', 'attr') ?>"
            aria-label="<?= esc($dateLabel . ' - ' . ($day['label'] ?? $sourceLabels[$source]), 'attr') ?>"
        >
            <span class="attendance-calendar-day-number"><?= (int) $day['day'] ?></span>
            <span class="attendance-calendar-day-status"><?= $day['is_non_working'] ? 'LIBUR' : 'KERJA' ?></span>
            <small><?= esc($day['label'] ?? $sourceLabels[$source]) ?></small>
            <?php if ($day['is_override']): ?><em>Manual</em><?php endif ?>
        </button>
    <?php endforeach ?>
    <?php for ($blank = 0; $blank < $trailingDays; $blank++): ?>
        <div class="attendance-calendar-blank is-trailing" aria-hidden="true"></div>
    <?php endfor ?>
</div>
