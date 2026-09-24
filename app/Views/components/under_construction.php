<?php
/** @var string $module */
/** @var string $message */
?>

<section class="panel construction-panel" aria-label="Status pengembangan <?= esc($module, 'attr') ?>">
    <div class="construction-visual" aria-hidden="true">
        <span class="construction-orbit construction-orbit-one"></span>
        <span class="construction-orbit construction-orbit-two"></span>
        <span class="construction-gear">⚙</span>
        <span class="construction-hammer">⚒</span>
        <span class="construction-spark construction-spark-one">✦</span>
        <span class="construction-spark construction-spark-two">✦</span>
    </div>
    <div class="construction-copy">
        <p class="construction-label"><span></span>SEDANG DIKEMBANGKAN</p>
        <h2><?= esc($module) ?></h2>
        <p><?= esc($message) ?></p>
        <div class="construction-progress" aria-hidden="true"><i></i></div>
        <small>Fitur ini akan segera tersedia.</small>
    </div>
</section>
