<?php
$columns = ['KUR', 'PEN', 'NON KUR', 'KBG/SURETYSHIP', 'KONSUMTIF', 'PRODUKTIF', 'TOTAL', '%'];
$reportValues = $reportValues ?? [];
$renderValue = static function (string $label, string $column) use ($reportValues): string {
    $amount=$reportValues[\App\Libraries\OracleLrSalaryParser::normalizeLabel($label)][$column]??null;
    if ($amount===null) return '<span class="lr-empty-value" aria-label="Belum ada nilai">—</span>';
    $display=$column==='%' ? \App\Libraries\LrMoney::percentageDisplay($amount) : \App\Libraries\LrMoney::reportDisplay($amount);
    $title=$column==='%' ? 'Rasio perhitungan: '.\App\Libraries\LrMoney::exactFormat($amount) : 'Nilai asli: Rp '.\App\Libraries\LrMoney::exactFormat($amount);
    $attributes=' data-lr-exact="'.esc($amount,'attr').'" title="'.esc($title,'attr').'"';
    return str_starts_with($display,'*') ? '<span class="lr-lr-value"'.$attributes.'><span class="lr-rka-negative-marker">*</span>'.esc(substr($display,1)).'</span>' : '<span class="lr-lr-value"'.$attributes.'>'.esc($display).'</span>';
};
$rows = \App\Libraries\LrReportRows::rows();
?>

<section class="panel lr-report-panel" aria-labelledby="lr-report-title">
    <header class="lr-report-header">
        <div>
            <p>PT JAMKRINDO KANWIL SURABAYA<?php if (isset($selectedUnit)): ?> · <?= esc($selectedUnit) ?><?php endif ?></p>
            <h2 id="lr-report-title"><?= esc($reportTitle) ?></h2>
        </div>
        <span class="lr-report-year"><?= $selectedYear ?? 2026 ?></span>
    </header>
    <div class="lr-report-scroll" tabindex="0" role="region" aria-label="<?= esc($reportTitle . ', dapat digeser ke samping', 'attr') ?>">
        <table class="lr-report-table lr-profitloss-table">
            <caption class="lr-report-caption"><?= esc($reportTitle) ?> — PT Jamkrindo Kanwil Surabaya</caption>
            <colgroup>
                <col class="lr-description-column">
                <?php foreach ($columns as $column): ?>
                    <col class="<?= $column === '%' ? 'lr-percentage-column' : 'lr-number-column' ?>">
                <?php endforeach ?>
            </colgroup>
            <thead>
                <tr>
                    <th scope="col">URAIAN</th>
                    <?php foreach ($columns as $column): ?>
                        <th scope="col"><?= esc($column) ?></th>
                    <?php endforeach ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $row): ?>
                    <?php $expanded = false; foreach ($row['details'] ?? [] as $detail) { if (isset($reportValues[\App\Libraries\OracleLrSalaryParser::normalizeLabel($detail['label'])])) $expanded = true; } ?>
                    <?php $valueLabel=$row['value_label']??$row['label']; ?>
                    <tr class="lr-row-<?= esc($row['type'], 'attr') ?>" <?= isset($row['details']) ? 'data-lr-expandable' : '' ?>>
                        <th scope="row">
                            <?php if (isset($row['details'])): ?>
                                <button type="button" class="lr-group-toggle" data-lr-toggle="<?= esc($row['key'], 'attr') ?>" aria-expanded="<?= $expanded ? 'true' : 'false' ?>" aria-controls="<?= esc(implode(' ', array_map(static fn ($index) => 'lr-' . $row['key'] . '-' . $index, array_keys($row['details']))), 'attr') ?>">
                                    <span class="lr-group-chevron" aria-hidden="true">›</span>
                                    <span><?= esc($row['label']) ?></span>
                                </button>
                            <?php else: ?>
                                <?= esc($row['label']) ?>
                            <?php endif ?>
                        </th>
                        <?php foreach ($columns as $column): ?>
                            <td <?= $column === 'TOTAL' ? 'data-lr-total' : '' ?>><?php if (isset($row['details'])): ?><span data-lr-group-output data-lr-expanded="<?= $expanded?'true':'false' ?>" aria-hidden="<?= $expanded?'true':'false' ?>"><?= $renderValue($valueLabel,$column) ?></span><?php else: ?><?= $renderValue($valueLabel,$column) ?><?php endif ?></td>
                        <?php endforeach ?>
                    </tr>
                    <?php foreach ($row['details'] ?? [] as $index => $detail): ?>
                        <tr id="lr-<?= esc($row['key'], 'attr') ?>-<?= $index ?>" class="lr-row-<?= esc($detail['type'], 'attr') ?>" data-lr-detail="<?= esc($row['key'], 'attr') ?>" <?= $expanded ? '' : 'hidden' ?>>
                            <th scope="row"><?= esc($detail['label']) ?></th>
                            <?php foreach ($columns as $column): ?>
                                <td <?= $column === 'TOTAL' ? 'data-lr-total' : '' ?>><?= $renderValue($detail['label'],$column) ?></td>
                            <?php endforeach ?>
                        </tr>
                    <?php endforeach ?>
                <?php endforeach ?>
            </tbody>
        </table>
    </div>
    <footer class="lr-report-footer">Nominal dalam Rupiah (Rp), tampilan dibulatkan ke rupiah penuh. Semua desimal sumber tetap dipakai dalam perhitungan. KBG/Suretyship, Konsumtif, Produktif, total, subtotal, laba, dan persentase dihitung otomatis mengikuti kertas kerja. Tanda * merah berarti negatif. <?= $reportValues ? 'Persentase membandingkan total realisasi dengan total RKA unit dan tahun yang sama; RKA nol ditampilkan 0%.' : 'Belum ada hasil upload untuk unit dan tahun yang dipilih.' ?></footer>
</section>
