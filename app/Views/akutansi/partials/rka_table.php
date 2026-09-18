<?php
use App\Libraries\RkaCalculator;
use App\Libraries\RkaMoney;

$editable = $editable ?? false;
$rawInputs = $rawInputs ?? null;
$values = $calculated ?? [];
$idPrefix = $idPrefix ?? 'lr';
$formatAmount = static fn (string $amount): string => RkaMoney::decimal($amount) === '0.00' ? '' : RkaMoney::display($amount);
if ($editable) {
    try {
        $calculator = new RkaCalculator();
        $values = $calculator->calculate($rawInputs !== null ? $calculator->normalizeInputs($rawInputs, true) : $inputs);
    } catch (\InvalidArgumentException $exception) {
        $values = [];
    }
}
$displayRows = [];
foreach ($schema['groups'] as $group) {
    $displayRows[] = $group + ['value_row' => $group['row'], 'label' => $schema['rows'][$group['row']]['label'] ?? '', 'hidden' => false];
    foreach ($group['details'] ?? [] as $row) {
        $displayRows[] = ['row' => $row, 'value_row' => $row, 'label' => $schema['rows'][$row]['label'], 'type' => $schema['rows'][$row]['terms'] === null ? 'detail' : 'detail-total', 'hidden' => true, 'parent' => $group['key']];
    }
}
?>

<section class="panel lr-report-panel" aria-labelledby="<?= esc($idPrefix, 'attr') ?>-report-title">
    <?php if ($editable): ?>
        <?php foreach (['C', 'D', 'E', 'F', 'G'] as $column): ?>
            <?php $cell = $column . '25'; $raw = $rawInputs !== null ? ($rawInputs[$cell] ?? '') : $formatAmount($inputs[$cell]); ?>
            <input type="hidden" name="cells[<?= $cell ?>]" value="<?= esc(is_string($raw) ? $raw : '', 'attr') ?>" data-rka-input="<?= $cell ?>">
        <?php endforeach ?>
    <?php endif ?>
    <header class="lr-report-header">
        <div>
            <p>PT JAMKRINDO KANWIL SURABAYA · <?= esc($selectedUnit) ?> · Dalam Rupiah (Rp)</p>
            <h2 id="<?= esc($idPrefix, 'attr') ?>-report-title">RKA <?= esc($selectedUnit) ?> Tahun <?= $selectedYear ?></h2>
        </div>
        <span class="lr-report-year"><?= $selectedYear ?></span>
    </header>
    <div class="lr-report-scroll" tabindex="0" role="region" aria-label="Tabel RKA <?= esc($selectedUnit, 'attr') ?>, dapat digeser ke samping">
        <table class="lr-report-table lr-rka-table <?= $editable ? 'lr-rka-editable' : '' ?>">
            <caption class="lr-report-caption">RKA <?= esc($selectedUnit) ?> Tahun <?= $selectedYear ?></caption>
            <colgroup><col class="lr-description-column"><?php foreach ($schema['columns'] as $column): ?><col class="lr-rka-number-column"><?php endforeach ?></colgroup>
            <thead><tr><th scope="col">URAIAN</th><?php foreach ($schema['columns'] as $column): ?><th scope="col"><?= esc($column) ?></th><?php endforeach ?></tr></thead>
            <tbody>
                <?php foreach ($displayRows as $row): ?>
                    <?php if ($row['row'] === 25): ?>
                        <tr class="lr-row-section lr-rka-title-row"><th colspan="<?= count($schema['columns']) + 1 ?>" scope="row"><?= esc($row['label']) ?></th></tr>
                        <?php continue; ?>
                    <?php endif ?>
                    <tr class="lr-row-<?= esc($row['type'], 'attr') ?>" <?php if ($row['hidden']): ?>id="<?= esc($idPrefix, 'attr') ?>-<?= esc($row['parent'], 'attr') ?>-<?= $row['row'] ?>" data-lr-detail="<?= esc($row['parent'], 'attr') ?>" hidden<?php elseif (isset($row['details'])): ?>data-lr-expandable<?php endif ?>>
                        <th scope="row">
                            <?php if (isset($row['details'])): ?>
                                <button type="button" class="lr-group-toggle" data-lr-toggle="<?= esc($row['key'], 'attr') ?>" aria-expanded="false" aria-controls="<?= esc(implode(' ', array_map(static fn ($number) => $idPrefix . '-' . $row['key'] . '-' . $number, $row['details'])), 'attr') ?>"><span class="lr-group-chevron" aria-hidden="true">›</span><span><?= esc($row['label']) ?></span></button>
                            <?php else: ?><?= esc($row['label']) ?><?php endif ?>
                        </th>
                        <?php foreach ($schema['columns'] as $column => $columnLabel): ?>
                            <?php $cell = $column . $row['value_row']; $calculatedCell = $column === 'H' || ($schema['rows'][$row['value_row']]['terms'] ?? null) !== null; $inputCell = $editable && !$calculatedCell && in_array($row['row'], $schema['input_rows'], true); $emptyAmount = !isset($values[$cell]) || $formatAmount($values[$cell]) === ''; $displayAmount = $emptyAmount ? '-' : RkaMoney::reportDisplay($values[$cell]); $negativeAmount = !$emptyAmount && str_starts_with($displayAmount, '*'); $formulaTitle = ($editable && $calculatedCell ? 'Dihitung otomatis, tidak dapat diedit. Rumus: ' : '') . ($schema['formulas'][$cell] ?? 'SUM(C' . $row['value_row'] . ':G' . $row['value_row'] . ')'); ?>
                            <td <?= $editable && $calculatedCell ? 'data-rka-calculated="true"' : '' ?>>
                                <?php if ($inputCell): ?>
                                    <?php $raw = $rawInputs !== null ? ($rawInputs[$cell] ?? '') : $formatAmount($inputs[$cell]); ?>
                                    <input type="text" inputmode="decimal" name="cells[<?= esc($cell, 'attr') ?>]" value="<?= esc(is_string($raw) ? $raw : '', 'attr') ?>" placeholder="-" class="lr-rka-input" data-rka-input="<?= esc($cell, 'attr') ?>" maxlength="40" aria-label="<?= esc($row['label'] . ' — ' . $columnLabel . ' dalam rupiah', 'attr') ?>" title="Nominal dalam rupiah (Rp). Template Excel: <?= esc($cell, 'attr') ?>" autocomplete="off">
                                <?php else: ?>
                                    <span data-rka-output="<?= esc($cell, 'attr') ?>" data-rka-empty="<?= $emptyAmount ? 'true' : 'false' ?>" <?= isset($row['details']) ? 'data-rka-group-output' : '' ?> <?= $editable && $calculatedCell ? 'contenteditable="false"' : '' ?> title="<?= esc($formulaTitle, 'attr') ?>"><?php if ($negativeAmount): ?><span class="lr-rka-negative-marker">*</span><?php endif ?><?= esc($negativeAmount ? substr($displayAmount, 1) : $displayAmount) ?></span>
                                <?php endif ?>
                            </td>
                        <?php endforeach ?>
                    </tr>
                <?php endforeach ?>
            </tbody>
        </table>
    </div>
    <footer class="lr-report-footer">Seluruh nominal dalam Rupiah (Rp), bukan ribuan atau jutaan. Tanda * sebelum angka berarti negatif dan tetap dihitung minus. Desimal ,00 disembunyikan; total dihitung otomatis sesuai rumus RKA.</footer>
</section>
