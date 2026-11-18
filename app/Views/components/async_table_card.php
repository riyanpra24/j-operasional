<?php

/** @var string $cardId */
/** @var string|null $filterFormSelector */
?>
<span hidden data-partial-table-config data-partial-table-target="#<?= esc($cardId, 'attr') ?>"<?= ($filterFormSelector ?? '') !== '' ? ' data-partial-table-filter="' . esc($filterFormSelector, 'attr') . '"' : '' ?>></span>
