<?php

use CodeIgniter\Pager\PagerRenderer;

/** @var PagerRenderer $pager */
$pager->setSurroundCount(2);
?>
<nav aria-label="Navigasi halaman data magang">
    <ul class="pagination">
        <?php if ($pager->hasPreviousPage()): ?>
            <li><a href="<?= $pager->getFirst() ?>" aria-label="Halaman pertama"><span aria-hidden="true">First</span></a></li>
            <li><a href="<?= $pager->getPreviousPage() ?>" aria-label="Halaman sebelumnya"><span aria-hidden="true">Previous</span></a></li>
        <?php endif ?>

        <?php foreach ($pager->links() as $link): ?>
            <li <?= $link['active'] ? 'class="active"' : '' ?>><a href="<?= $link['uri'] ?>"><?= $link['title'] ?></a></li>
        <?php endforeach ?>

        <?php if ($pager->hasNextPage()): ?>
            <li><a href="<?= $pager->getNextPage() ?>" aria-label="Halaman berikutnya"><span aria-hidden="true">Next</span></a></li>
            <li><a href="<?= $pager->getLast() ?>" aria-label="Halaman terakhir"><span aria-hidden="true">Last</span></a></li>
        <?php endif ?>
    </ul>
</nav>
