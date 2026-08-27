<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<?php
$value = static function (string $field, ?array $record): string {
    $oldValue = old($field);
    return $oldValue !== null ? (string) $oldValue : (string) ($record[$field] ?? '');
};
$isEdit = $record !== null;
?>

<section class="page-heading pks-form-heading">
    <a href="<?= $isEdit ? site_url('bagian-umum-1/pks-barang-jasa/' . $record['id']) : site_url('bagian-umum-1/pks-barang-jasa') ?>" class="back-link">← Kembali</a>
    <p class="eyebrow">BAGIAN UMUM 1 / PKS BARANG DAN JASA</p>
    <h1><?= esc($title) ?></h1>
    <p>Isi data utama kerja sama dan mitra. Riwayat PKS/addendum serta item pekerjaan ditambahkan setelah data ini tersimpan.</p>
</section>

<form action="<?= esc($action) ?>" method="post" class="data-form pks-main-form">
    <?= csrf_field() ?>
    <section class="panel form-section">
        <div class="section-heading"><span class="section-number">01</span><div><h2>Identitas Kerja Sama</h2><p>Informasi ringkas untuk mengenali dan mencari PKS.</p></div></div>
        <div class="form-grid">
            <div class="form-group"><label for="kodeInternal">Kode Internal <span class="required">*</span></label><input id="kodeInternal" name="kode_internal" maxlength="80" value="<?= esc($value('kode_internal', $record)) ?>" placeholder="Contoh: PKS/BJ/2026/001" required><small>Kode unik internal, bukan nomor dokumen PKS.</small></div>
            <div class="form-group"><label for="unitPengelola">Unit Pengelola</label><input id="unitPengelola" name="unit_pengelola" maxlength="150" value="<?= esc($value('unit_pengelola', $record)) ?>" placeholder="Contoh: Bagian Umum"></div>
            <div class="form-group span-2"><label for="namaKerjasama">Nama Kerja Sama <span class="required">*</span></label><input id="namaKerjasama" name="nama_kerjasama" maxlength="250" value="<?= esc($value('nama_kerjasama', $record)) ?>" placeholder="Contoh: Pengadaan Jasa Kebersihan Kantor" required></div>
            <div class="form-group"><label for="picInternal">PIC Internal</label><input id="picInternal" name="pic_internal" maxlength="150" value="<?= esc($value('pic_internal', $record)) ?>" placeholder="Nama penanggung jawab internal"></div>
            <div class="form-group"><label for="ruangLingkup">Ruang Lingkup</label><textarea id="ruangLingkup" name="ruang_lingkup" placeholder="Ringkasan pekerjaan/barang atau jasa yang disepakati"><?= esc($value('ruang_lingkup', $record)) ?></textarea></div>
            <div class="form-group span-2"><label for="pksKeterangan">Keterangan</label><textarea id="pksKeterangan" name="keterangan" placeholder="Catatan tambahan bila diperlukan"><?= esc($value('keterangan', $record)) ?></textarea></div>
        </div>
    </section>

    <section class="panel form-section">
        <div class="section-heading"><span class="section-number">02</span><div><h2>Data Mitra</h2><p>Identitas penyedia barang/jasa dan kontak yang dapat dihubungi.</p></div></div>
        <div class="form-grid">
            <div class="form-group span-2"><label for="namaMitra">Nama Mitra / Penyedia <span class="required">*</span></label><input id="namaMitra" name="nama_mitra" maxlength="200" value="<?= esc($value('nama_mitra', $record)) ?>" placeholder="Nama perusahaan atau penyedia" required></div>
            <div class="form-group span-2"><label for="alamatMitra">Alamat</label><textarea id="alamatMitra" name="alamat" placeholder="Alamat lengkap mitra"><?= esc($value('alamat', $record)) ?></textarea></div>
            <div class="form-group"><label for="namaKontak">Nama Kontak</label><input id="namaKontak" name="nama_kontak" maxlength="150" value="<?= esc($value('nama_kontak', $record)) ?>" placeholder="Nama PIC dari pihak mitra"></div>
            <div class="form-group"><label for="jabatanKontak">Jabatan Kontak</label><input id="jabatanKontak" name="jabatan_kontak" maxlength="150" value="<?= esc($value('jabatan_kontak', $record)) ?>" placeholder="Jabatan PIC mitra"></div>
            <div class="form-group"><label for="teleponMitra">Telepon</label><input id="teleponMitra" name="telepon" maxlength="50" value="<?= esc($value('telepon', $record)) ?>" placeholder="Nomor telepon atau WhatsApp"></div>
            <div class="form-group"><label for="emailMitra">Email</label><input id="emailMitra" name="email" type="email" maxlength="150" value="<?= esc($value('email', $record)) ?>" placeholder="email@perusahaan.co.id"></div>
        </div>
    </section>

    <div class="form-actions-sticky"><a href="<?= $isEdit ? site_url('bagian-umum-1/pks-barang-jasa/' . $record['id']) : site_url('bagian-umum-1/pks-barang-jasa') ?>" class="btn btn-ghost">Batal</a><button type="submit" class="btn btn-primary"><?= $isEdit ? 'Simpan Perubahan' : 'Simpan & Lanjutkan' ?></button></div>
</form>
<?= $this->endSection() ?>
