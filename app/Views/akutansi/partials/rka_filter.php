<section class="panel lr-rka-filter-panel">
    <form method="get" action="<?= esc($filterAction, 'attr') ?>" class="lr-rka-filter">
        <div><label class="lr-upload-label" for="rkaFilterUnit">Unit Kerja</label><select class="lr-upload-select" id="rkaFilterUnit" name="unit_kerja"><?php foreach ($rkaUnits as $unit): ?><option value="<?= esc($unit, 'attr') ?>" <?= $unit === $selectedUnit ? 'selected' : '' ?>><?= esc($unit) ?></option><?php endforeach ?></select></div>
        <div><label class="lr-upload-label" for="rkaFilterYear">Tahun RKA</label><input class="lr-upload-select" id="rkaFilterYear" type="number" name="tahun" min="2000" max="2100" step="1" value="<?= $selectedYear ?>" required></div>
        <button type="submit" class="btn btn-secondary">Terapkan</button>
        <?php if ($record !== null && $selectedUnit !== 'Korporat Kanwil'): ?>
            <button type="button" class="btn btn-secondary lr-rka-delete-button" data-rka-delete-open data-id="<?= (int) $record['id'] ?>" data-unit="<?= esc($selectedUnit, 'attr') ?>" data-year="<?= $selectedYear ?>" data-revision="<?= $revision ?>" aria-haspopup="dialog" aria-controls="rkaDeleteDialog">Hapus RKA</button>
            <button type="button" class="btn btn-secondary" data-rka-edit-open data-id="<?= (int) $record['id'] ?>" data-unit="<?= esc($selectedUnit, 'attr') ?>" data-year="<?= $selectedYear ?>" aria-haspopup="dialog" aria-controls="rkaManualDialog">Edit RKA</button>
        <?php endif ?>
        <?php if ($selectedUnit !== 'Korporat Kanwil'): ?><p class="lr-rka-filter-note"><?php if ($record === null): ?>Belum ada RKA tersimpan untuk unit dan tahun ini.<?php else: ?>Tersimpan dari <?= $record['source_type'] === 'excel' ? 'Excel' : 'isian manual' ?> · <?= esc($record['updated_at']) ?><?php endif ?></p><?php endif ?>
    </form>
</section>
