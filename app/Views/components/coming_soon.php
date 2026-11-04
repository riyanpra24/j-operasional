<?php
/**
 * Template global untuk halaman atau fitur yang masih dalam pengembangan.
 *
 * Contoh pemakaian:
 * <?= view('components/coming_soon', ['eyebrow' => 'AKUTANSI', 'title' => 'Laporan Arus Kas', 'description' => 'Halaman ini sedang kami siapkan.']) ?>
 */
$eyebrow = (string) ($eyebrow ?? 'INFORMASI SISTEM');
$heading = (string) ($heading ?? $title ?? 'Halaman Sedang Dalam Progres');
$description = (string) ($description ?? 'Fitur ini sedang kami siapkan dan akan segera tersedia untuk digunakan.');
$status = (string) ($status ?? 'Segera hadir');
$backUrl = $backUrl ?? null;
$backLabel = (string) ($backLabel ?? 'Kembali');
?>

<style>
.coming-soon-page{display:grid;min-height:calc(100vh - 188px);place-items:center;padding:32px 18px;background:radial-gradient(circle at 18% 16%,rgba(31,6,172,.11),transparent 29%),radial-gradient(circle at 86% 84%,rgba(6,182,212,.14),transparent 31%)}
.coming-soon-card{position:relative;width:min(100%,680px);overflow:hidden;padding:48px 38px 42px;border:1px solid #d8e7f5;border-radius:20px;background:rgba(255,255,255,.96);box-shadow:0 18px 42px rgba(24,72,126,.12);text-align:center;animation:comingSoonEnter .65s cubic-bezier(.2,.8,.2,1) both}
.coming-soon-card:before,.coming-soon-card:after{position:absolute;width:175px;height:175px;border-radius:50%;content:'';pointer-events:none}.coming-soon-card:before{top:-102px;left:-82px;background:rgba(31,6,172,.07)}.coming-soon-card:after{right:-100px;bottom:-112px;background:rgba(6,182,212,.09)}
.coming-soon-icon{position:relative;z-index:1;display:grid;width:76px;height:76px;margin:0 auto 22px;place-items:center;border-radius:23px;background:linear-gradient(135deg,#1F06AC,#06B6D4);box-shadow:0 12px 24px rgba(31,6,172,.22);animation:comingSoonFloat 3.4s ease-in-out infinite}.coming-soon-icon img{position:relative;z-index:1;width:59px;height:59px;object-fit:contain;filter:brightness(0) invert(1)}.coming-soon-icon:before{position:absolute;inset:-10px;border:1px dashed rgba(31,6,172,.32);border-radius:31px;content:'';animation:comingSoonOrbit 8s linear infinite}.coming-soon-icon:after{position:absolute;z-index:2;top:-12px;right:4px;width:7px;height:7px;border-radius:50%;background:#08CB00;box-shadow:0 0 0 5px rgba(8,203,0,.12);content:'';animation:comingSoonSpark 1.8s ease-in-out infinite}
.coming-soon-card small{position:relative;z-index:1;display:block;margin:0;color:#197fda;font-size:11px;font-weight:800;letter-spacing:1.25px}.coming-soon-card h1{position:relative;z-index:1;margin:7px 0 0;color:#030164;font-size:28px;line-height:1.2}.coming-soon-card p{position:relative;z-index:1;max-width:500px;margin:13px auto 0;color:#6681a2;font-size:14px;line-height:1.7}
.coming-soon-status{position:relative;z-index:1;display:inline-flex;align-items:center;gap:8px;margin-top:25px;padding:8px 13px;border-radius:999px;background:#edf9ff;color:#1974ba;font-size:12px;font-weight:800}.coming-soon-status i{width:8px;height:8px;border-radius:50%;background:#08CB00;box-shadow:0 0 0 5px rgba(8,203,0,.12);animation:comingSoonPulse 1.8s ease-in-out infinite}
.coming-soon-progress{position:relative;z-index:1;max-width:350px;height:7px;margin:22px auto 0;overflow:hidden;border-radius:999px;background:#e7f0f8}.coming-soon-progress span{display:block;width:38%;height:100%;border-radius:inherit;background:linear-gradient(90deg,#1F06AC,#06B6D4);animation:comingSoonSweep 2.4s ease-in-out infinite}
.coming-soon-card .btn{position:relative;z-index:1;margin-top:26px}@keyframes comingSoonEnter{from{opacity:0;transform:translateY(18px) scale(.98)}to{opacity:1;transform:none}}@keyframes comingSoonFloat{50%{transform:translateY(-7px)}}@keyframes comingSoonOrbit{to{transform:rotate(360deg)}}@keyframes comingSoonSpark{50%{transform:scale(1.35);box-shadow:0 0 0 9px rgba(8,203,0,0)}}@keyframes comingSoonPulse{50%{box-shadow:0 0 0 9px rgba(8,203,0,0)}}@keyframes comingSoonSweep{0%{transform:translateX(-115%)}55%,100%{transform:translateX(280%)}}@media(max-width:600px){.coming-soon-page{min-height:calc(100vh - 145px);padding:22px 14px}.coming-soon-card{padding:38px 24px 34px;border-radius:17px}.coming-soon-card h1{font-size:24px}.coming-soon-card p{font-size:13px}.coming-soon-icon{width:66px;height:66px;margin-bottom:18px;border-radius:20px}.coming-soon-icon img{width:51px;height:51px}}
</style>

<section class="coming-soon-page" aria-labelledby="comingSoonTitle">
    <article class="coming-soon-card">
        <span class="coming-soon-icon"><img src="<?= base_url('assets/images/construction.png') ?>" alt="Ilustrasi konstruksi"></span>
        <small><?= esc($eyebrow) ?></small>
        <h1 id="comingSoonTitle"><?= esc($heading) ?></h1>
        <p><?= esc($description) ?></p>
        <span class="coming-soon-status"><i aria-hidden="true"></i><?= esc($status) ?></span>
        <div class="coming-soon-progress" aria-hidden="true"><span></span></div>
        <?php if ($backUrl): ?><a class="btn btn-secondary" href="<?= esc((string) $backUrl) ?>">← <?= esc($backLabel) ?></a><?php endif ?>
    </article>
</section>
