<?php
$prefix = $prefix ?? 'dokumen';
$current = trim((string) ($current ?? ''));
$groupClass = trim((string) ($groupClass ?? ''));
$options = [
    'J&T Express',
    'JNE',
    'Pos Indonesia',
    'TIKI',
    'GoSend',
    'GrabExpress',
    'Paxel',
    'KAI Logistik',
];
$choice = $current === '' ? '' : (in_array($current, $options, true) ? $current : 'Lainnya');
?>
<div class="form-group <?= esc($groupClass, 'attr') ?>">
    <label for="<?= esc($prefix, 'attr') ?>_ekspedisi_pilihan">Ekspedisi</label>
    <select id="<?= esc($prefix, 'attr') ?>_ekspedisi_pilihan" name="ekspedisi_pilihan" data-ekspedisi-select>
        <option value="">Pilih ekspedisi</option>
        <?php foreach ($options as $option): ?>
            <option value="<?= esc($option, 'attr') ?>" <?= $choice === $option ? 'selected' : '' ?>><?= esc($option) ?></option>
        <?php endforeach ?>
        <option value="Lainnya" <?= $choice === 'Lainnya' ? 'selected' : '' ?>>Lainnya</option>
    </select>
</div>
<div class="form-group <?= esc($groupClass, 'attr') ?>" data-ekspedisi-custom <?= $choice === 'Lainnya' ? '' : 'hidden' ?>>
    <label for="<?= esc($prefix, 'attr') ?>_ekspedisi_lainnya">Ekspedisi lainnya <span class="required">*</span></label>
    <input id="<?= esc($prefix, 'attr') ?>_ekspedisi_lainnya" name="ekspedisi_lainnya" data-ekspedisi-custom-input maxlength="150" value="<?= esc($choice === 'Lainnya' ? $current : '', 'attr') ?>" placeholder="Ketik nama ekspedisi" <?= $choice === 'Lainnya' ? 'required' : 'disabled' ?>>
</div>
