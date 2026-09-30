<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<style>
    .accounting-dashboard-status { display:grid; min-height:calc(100vh - 210px); place-items:center; padding:28px; }
    .accounting-dashboard-status p { margin:0; color:#0a3a76; font-size:clamp(20px, 2.5vw, 30px); font-weight:800; text-align:center; }
</style>
<main class="accounting-dashboard-status" aria-label="Status Dashboard Akuntansi">
    <p>Sedang Dalam Pengembangan</p>
</main>
<?= $this->endSection() ?>
