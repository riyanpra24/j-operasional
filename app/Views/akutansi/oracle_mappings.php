<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<section class="page-heading lr-page-heading">
    <div>
        <p class="eyebrow">AKUTANSI / ADMINISTRATOR</p>
        <h1>Pengaturan Mapping Oracle</h1>
        <p>Periksa aturan kelompok LOB dan atur pasangan Description COA untuk upload Laporan Laba / Rugi berikutnya.</p>
    </div>
    <a class="btn btn-secondary" href="<?= site_url('akutansi/laba-rugi') ?>">Kembali ke Laporan</a>
</section>

<?php if ($mappingError !== null): ?>
    <div class="alert alert-danger" role="alert"><strong>Mapping belum disimpan</strong><span><?= esc($mappingError) ?></span></div>
<?php endif ?>

<section class="panel oracle-mapping-guide">
    <div><strong>1. Kelompok LOB</strong><span>KUR, NON KUR, dan PEN mengikuti blok Excel serta dikunci agar tidak terpecah.</span></div>
    <div><strong>2. Mapping Description COA</strong><span>Menentukan baris uraian Laba / Rugi dan perlakuan tanda nominal.</span></div>
    <div><strong>3. Pemeriksaan upload</strong><span>Baris tanpa pasangan ditampilkan sebagai “Belum terpetakan” dan tidak ikut perhitungan.</span></div>
</section>

<section class="panel oracle-mapping-panel" id="mapping-lob">
    <header class="oracle-mapping-heading">
        <div><p class="eyebrow">ATURAN SISTEM</p><h2>Kelompok LOB mengikuti Excel</h2><p>Aturan ini hanya membaca kolom B dan tidak memakai Description LOB untuk memecah NON KUR.</p></div>
    </header>
    <div class="oracle-mapping-table-wrap" role="region" aria-label="Daftar mapping LOB" tabindex="0">
        <table class="oracle-mapping-table">
            <thead><tr><th>Status</th><th>LOB sumber pada kolom B</th><th>Kelompok tujuan</th><th>Keterangan</th></tr></thead>
            <tbody>
            <?php foreach ($lobMappings as $mapping): ?>
                <tr>
                    <td><span class="oracle-mapping-locked-status">Aktif · Dikunci</span></td>
                    <td><strong><?= esc($mapping['source_lob']??'') ?></strong></td>
                    <td><strong><?= esc($mapping['target_column']??'') ?></strong></td>
                    <td>Seluruh Description LOB tetap berada dalam kelompok <?= esc($mapping['target_column']??'') ?>.</td>
                </tr>
            <?php endforeach ?>
            </tbody>
        </table>
    </div>
</section>

<section class="panel oracle-mapping-panel" id="mapping-coa">
    <header class="oracle-mapping-heading">
        <div><p class="eyebrow">PENGELOMPOKAN URAIAN</p><h2>Mapping Description COA</h2><p>Pencocokan mengabaikan perbedaan huruf besar, spasi ganda, dan spasi awal/akhir.</p></div>
        <label class="oracle-mapping-search"><span>Cari mapping</span><input type="search" data-mapping-search placeholder="Cari COA atau uraian tujuan"></label>
    </header>
    <form method="post" action="<?= site_url('akutansi/pengaturan-mapping-oracle/coa') ?>" class="oracle-mapping-add-form oracle-account-add-form">
        <?= csrf_field() ?>
        <label><span>Cakupan unit</span><select name="unit_scope"><option value="all">Semua unit</option><option value="branch">Cabang</option><option value="kanwil">Kanwil</option></select></label>
        <label class="oracle-mapping-wide"><span>Description COA sumber</span><input name="source_description" maxlength="255" placeholder="Nama persis dari kolom D Oracle" required></label>
        <label class="oracle-mapping-wide"><span>Uraian tujuan</span><select name="report_label" required><?php foreach ($reportLabels as $label): ?><option value="<?= esc($label,'attr') ?>"><?= esc($label) ?></option><?php endforeach ?></select></label>
        <label><span>Perlakuan tanda</span><select name="sign_mode"><option value="keep">Pertahankan</option><option value="invert">Balik di semua unit</option><option value="invert_kanwil">Balik khusus Kanwil</option></select></label>
        <label class="oracle-mapping-check"><input type="checkbox" name="is_active" value="1" checked><span>Aktif</span></label>
        <button class="btn btn-primary" type="submit">Tambah Mapping COA</button>
    </form>
    <div class="oracle-mapping-table-wrap oracle-account-table-wrap" role="region" aria-label="Daftar mapping Description COA" tabindex="0">
        <table class="oracle-mapping-table oracle-account-table">
            <thead><tr><th>Status</th><th>Cakupan</th><th>Description COA sumber</th><th>Uraian tujuan</th><th>Perlakuan tanda</th><th>Aksi</th></tr></thead>
            <tbody data-mapping-search-rows>
            <?php foreach ($accountMappings as $mapping): $formId='coa-map-'.(int)($mapping['id']??0); ?>
                <tr>
                    <td><form id="<?= $formId ?>" method="post" action="<?= site_url('akutansi/pengaturan-mapping-oracle/coa') ?>"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)($mapping['id']??0) ?>"></form><label class="oracle-mapping-switch"><input form="<?= $formId ?>" type="checkbox" name="is_active" value="1" <?= (int)($mapping['is_active']??0)===1?'checked':'' ?>><span><?= (int)($mapping['is_active']??0)===1?'Aktif':'Nonaktif' ?></span></label></td>
                    <td><select form="<?= $formId ?>" name="unit_scope"><option value="all" <?= ($mapping['unit_scope']??'')==='all'?'selected':'' ?>>Semua unit</option><option value="branch" <?= ($mapping['unit_scope']??'')==='branch'?'selected':'' ?>>Cabang</option><option value="kanwil" <?= ($mapping['unit_scope']??'')==='kanwil'?'selected':'' ?>>Kanwil</option></select></td>
                    <td><input form="<?= $formId ?>" name="source_description" maxlength="255" value="<?= esc($mapping['source_description']??'','attr') ?>" required></td>
                    <td><select form="<?= $formId ?>" name="report_label"><?php foreach ($reportLabels as $label): ?><option value="<?= esc($label,'attr') ?>" <?= ($mapping['report_label']??'')===$label?'selected':'' ?>><?= esc($label) ?></option><?php endforeach ?></select></td>
                    <td><select form="<?= $formId ?>" name="sign_mode"><option value="keep" <?= ($mapping['sign_mode']??'')==='keep'?'selected':'' ?>>Pertahankan</option><option value="invert" <?= ($mapping['sign_mode']??'')==='invert'?'selected':'' ?>>Balik semua unit</option><option value="invert_kanwil" <?= ($mapping['sign_mode']??'')==='invert_kanwil'?'selected':'' ?>>Balik khusus Kanwil</option></select></td>
                    <td class="oracle-mapping-actions"><button form="<?= $formId ?>" class="btn btn-secondary" type="submit">Simpan</button><form method="post" action="<?= site_url('akutansi/pengaturan-mapping-oracle/coa/hapus') ?>" data-mapping-delete><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)($mapping['id']??0) ?>"><input type="hidden" name="confirm_delete" value="1"><button class="btn btn-danger-outline" type="submit">Hapus</button></form></td>
                </tr>
            <?php endforeach ?>
            </tbody>
        </table>
    </div>
</section>

<?= $this->endSection() ?>
