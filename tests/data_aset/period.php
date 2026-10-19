<?php
require __DIR__ . '/../../app/Libraries/AssetPeriod.php';

use App\Libraries\AssetPeriod;

$cases = [
    ['1997-01-01', '2027-01-01', 360],
    ['2026-01-20', '2026-03-19', 1],
    ['2026-01-20', '2026-03-20', 2],
    ['2026-01-31', '2026-02-28', 0],
    ['2026-03-01', '2026-02-01', null],
    [null, '2026-02-01', null],
    ['2026-02-30', '2026-03-30', null],
];
foreach ($cases as [$start, $end, $expected]) {
    if (AssetPeriod::months($start, $end) !== $expected) throw new RuntimeException('Incorrect full-month calculation.');
}
if (AssetPeriod::remaining('2027-01-01', '2026-10-19') !== 2) throw new RuntimeException('Incorrect remaining period.');
if (AssetPeriod::remaining('2026-10-18', '2026-10-19') !== 0) throw new RuntimeException('Expired period must be zero.');
if (AssetPeriod::remaining(null, '2026-10-19') !== null) throw new RuntimeException('Missing expiry must remain unknown.');
echo "Data Aset period calculations: passed\n";
