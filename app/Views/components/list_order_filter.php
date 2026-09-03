<?php
$fieldId = $id ?? 'list_order';
$selectedOrder = $value ?? '';
$selectedLabel = [
    'terbaru' => 'Terbaru ke terlama',
    'terlama' => 'Terlama ke terbaru',
][$selectedOrder] ?? 'Atur urutan tanggal';
?>
<div class="form-group list-order-filter <?= $selectedOrder !== '' ? 'is-active' : '' ?>">
    <details class="list-order-menu">
        <summary title="<?= esc($selectedLabel, 'attr') ?>" aria-label="<?= esc($selectedLabel, 'attr') ?>">
            <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                <path d="M8 5v14m0 0-3-3m3 3 3-3M16 19V5m0 0-3 3m3-3 3 3" />
            </svg>
        </summary>
        <div class="list-order-popover">
            <label for="<?= esc($fieldId, 'attr') ?>">Urutan tanggal</label>
            <select id="<?= esc($fieldId, 'attr') ?>" name="urutan">
                <option value="" <?= $selectedOrder === '' ? 'selected' : '' ?>>Urutan awal</option>
                <option value="terbaru" <?= $selectedOrder === 'terbaru' ? 'selected' : '' ?>>Terbaru ke terlama</option>
                <option value="terlama" <?= $selectedOrder === 'terlama' ? 'selected' : '' ?>>Terlama ke terbaru</option>
            </select>
            <small>Klik Terapkan untuk mengurutkan tabel.</small>
        </div>
    </details>
</div>
