<?php
/** @var string $icon */
$icon = $icon ?? 'view';
?>
<svg class="table-action-icon" aria-hidden="true" viewBox="0 0 24 24" focusable="false">
    <?php if ($icon === 'edit'): ?>
        <path d="m4 16.8-.7 3.9 3.9-.7L18.4 8.8l-3.2-3.2L4 16.8Z" />
        <path d="m13.8 7 3.2 3.2" />
    <?php elseif ($icon === 'delete'): ?>
        <path d="M4.5 7h15" />
        <path d="M9 4h6l1 3H8l1-3Z" />
        <path d="M8 10v8M12 10v8M16 10v8M6.5 7l.7 14h9.6l.7-14" />
    <?php elseif ($icon === 'lock'): ?>
        <rect x="5" y="10" width="14" height="10" rx="2" />
        <path d="M8 10V7a4 4 0 0 1 8 0v3M12 14v2" />
    <?php elseif ($icon === 'open'): ?>
        <path d="M14 4h6v6" />
        <path d="m20 4-9 9" />
        <path d="M18 13v5a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h5" />
    <?php else: ?>
        <path d="M2.5 12s3.4-5.8 9.5-5.8S21.5 12 21.5 12 18.1 17.8 12 17.8 2.5 12 2.5 12Z" />
        <circle cx="12" cy="12" r="2.7" />
    <?php endif ?>
</svg>
