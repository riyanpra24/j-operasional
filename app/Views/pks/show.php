<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<?php
$formatDate = static fn (?string $value): string => $value ? date('d-m-Y', strtotime($value)) : '-';
$formatMoney = static fn ($value): string => 'Rp ' . number_format((float) $value, 0, ',', '.');
$formatNumber = static function ($value): string {
    if ($value === null || $value === '') return '-';
    return rtrim(rtrim(number_format((float) $value, 2, ',', '.'), '0'), ',');
};
$baseUrl = site_url('bagian-umum-1/pks-barang-jasa/' . $record['id']);
?>

<section class="page-heading heading-actions pks-detail-heading">
    <div>
        <a href="<?= site_url('bagian-umum-1/pks-barang-jasa') ?>" class="back-link">← Kembali ke daftar</a>
        <p class="eyebrow">BAGIAN UMUM 1 / DETAIL PKS</p>
        <h1><?= esc($record['nama_kerjasama']) ?></h1>
        <p><?= esc($record['kode_internal']) ?> · <?= esc($record['nama_mitra']) ?></p>
    </div>
    <div class="heading-button-group"><a href="<?= $baseUrl . '/ubah' ?>" class="btn btn-secondary">✎ Ubah Data Utama</a><button type="button" class="btn btn-danger-outline" data-pks-delete-open>Hapus PKS</button></div>
</section>

<section class="pks-detail-summary">
    <article><small>Status Saat Ini</small><strong><span class="pks-status <?= esc($status['status_class']) ?>"><?= esc($status['status_label']) ?></span></strong><p>Berdasarkan dokumen dengan urutan terakhir</p></article>
    <article><small>Dokumen Terakhir</small><strong><?= esc($latest['nomor_dokumen'] ?? 'Belum ada') ?></strong><p><?= $latest ? esc($latest['jenis_dokumen']) . ' · tahap ' . (int) $latest['urutan'] : 'Tambahkan dokumen awal PKS' ?></p></article>
    <article><small>Berlaku Sampai</small><strong><?= $formatDate($latest['periode_selesai'] ?? null) ?></strong><p><?= $latest ? $formatDate($latest['periode_mulai']) . ' s.d. ' . $formatDate($latest['periode_selesai']) : 'Periode belum tersedia' ?></p></article>
    <article><small>Nilai Terakhir</small><strong><?= $latest ? $formatMoney($latest['nilai']) : '-' ?></strong><p>Nilai pada PKS/addendum terakhir</p></article>
</section>

<section class="pks-detail-layout">
    <article class="panel pks-overview-panel">
        <div class="panel-header"><div><h2>01 · Informasi Kerja Sama</h2><p>Data utama dan penanggung jawab internal</p></div></div>
        <dl class="pks-detail-list">
            <div><dt>Nomor PKS</dt><dd><?= esc($record['kode_internal']) ?></dd></div>
            <div><dt>Unit Pengelola</dt><dd><?= esc($record['unit_pengelola'] ?: '-') ?></dd></div>
            <div><dt>PIC Internal</dt><dd><?= esc($record['pic_internal'] ?: '-') ?></dd></div>
        </dl>
    </article>
    <article class="panel pks-overview-panel">
        <div class="panel-header"><div><h2>02 · Data Mitra</h2><p>Identitas penyedia dan kontak yang dapat dihubungi</p></div></div>
        <dl class="pks-detail-list">
            <div class="wide"><dt>Nama Mitra</dt><dd><?= esc($record['nama_mitra']) ?></dd></div>
            <div><dt>Nama Kontak</dt><dd><?= esc($record['nama_kontak'] ?: '-') ?></dd></div>
            <div><dt>Jabatan</dt><dd><?= esc($record['jabatan_kontak'] ?: '-') ?></dd></div>
            <div><dt>Telepon</dt><dd><?= esc($record['telepon'] ?: '-') ?></dd></div>
            <div><dt>Email</dt><dd><?= esc($record['email'] ?: '-') ?></dd></div>
            <div class="wide"><dt>Alamat</dt><dd><?= nl2br(esc($record['alamat'] ?: '-')) ?></dd></div>
        </dl>
    </article>
</section>

<section class="panel pks-work-panel" id="riwayat-dokumen">
    <div class="panel-header pks-section-header"><div><h2>03 · Riwayat PKS dan Addendum</h2><p>Setiap perubahan kontrak disimpan sebagai tahap baru agar mudah ditelusuri.</p></div><button type="button" class="btn btn-primary btn-sm" data-pks-toggle="new-document">＋ Tambah Dokumen</button></div>

    <div class="pks-inline-form" id="new-document" hidden>
        <form action="<?= $baseUrl . '/dokumen' ?>" method="post">
            <?= csrf_field() ?>
            <div class="pks-inline-heading"><div><strong>Tambah Riwayat Dokumen</strong><small>Gunakan urutan 1 untuk PKS awal, kemudian 2, 3, dan seterusnya untuk addendum.</small></div><button type="button" class="pks-inline-close" data-pks-toggle="new-document" aria-label="Tutup">×</button></div>
            <div class="form-grid pks-child-grid">
                <div class="form-group"><label>Jenis Dokumen <span class="required">*</span></label><select name="jenis_dokumen" required><option value="PKS">PKS</option><option value="Addendum" <?= $nextSequence > 1 ? 'selected' : '' ?>>Addendum</option></select></div>
                <div class="form-group"><label>Urutan / Tahap <span class="required">*</span></label><input name="urutan" type="number" min="1" value="<?= $nextSequence ?>" required></div>
                <div class="form-group span-2"><label>Nomor Dokumen <span class="required">*</span></label><input name="nomor_dokumen" maxlength="200" placeholder="Nomor PKS atau addendum" required></div>
                <div class="form-group"><label>Tanggal Dokumen <span class="required">*</span></label><input name="tanggal_dokumen" type="date" required></div>
                <div class="form-group"><label>Nilai Kerja Sama <span class="required">*</span></label><input name="nilai" type="number" min="0" step="0.01" value="0" required></div>
                <div class="form-group"><label>Periode Mulai <span class="required">*</span></label><input name="periode_mulai" type="date" required></div>
                <div class="form-group"><label>Periode Selesai <span class="required">*</span></label><input name="periode_selesai" type="date" required></div>
                <div class="form-group span-2"><label>Link Berkas</label><input name="link_berkas" type="url" maxlength="2048" placeholder="https://drive.google.com/... atau tautan dokumen lainnya"><small>Opsional. Gunakan link http/https yang dapat diakses sesuai kewenangan.</small></div>
                <div class="form-group span-2"><label>Keterangan Perubahan</label><textarea name="keterangan" placeholder="Contoh: perubahan nilai, perpanjangan jangka waktu, atau ruang lingkup"></textarea></div>
            </div>
            <div class="pks-inline-actions"><button type="button" class="btn btn-ghost" data-pks-toggle="new-document">Batal</button><button class="btn btn-primary" type="submit">Simpan Dokumen</button></div>
        </form>
    </div>

    <?php if ($documents === []): ?>
        <div class="empty-state compact"><span>▤</span><strong>Riwayat dokumen belum tersedia</strong><p>Tambahkan PKS awal untuk mulai memantau masa berlaku.</p></div>
    <?php else: ?>
        <div class="pks-timeline">
            <?php foreach ($documents as $document): ?>
                <article class="pks-timeline-item">
                    <span class="pks-timeline-number"><?= str_pad((string) $document['urutan'], 2, '0', STR_PAD_LEFT) ?></span>
                    <div class="pks-timeline-card">
                        <header><div><span><?= esc($document['jenis_dokumen']) ?> · Tahap <?= (int) $document['urutan'] ?></span><h3><?= esc($document['nomor_dokumen']) ?></h3></div><strong><?= $formatMoney($document['nilai']) ?></strong></header>
                        <dl>
                            <div><dt>Tanggal Dokumen</dt><dd><?= $formatDate($document['tanggal_dokumen']) ?></dd></div>
                            <div><dt>Periode Berlaku</dt><dd><?= $formatDate($document['periode_mulai']) ?> s.d. <?= $formatDate($document['periode_selesai']) ?></dd></div>
                            <div class="wide"><dt>Keterangan</dt><dd><?= nl2br(esc($document['keterangan'] ?: '-')) ?></dd></div>
                        </dl>
                        <footer>
                            <?php if ($document['link_berkas']): ?><a href="<?= esc($document['link_berkas'], 'attr') ?>" target="_blank" rel="noopener noreferrer" class="btn btn-secondary btn-sm">↗ Buka Berkas</a><?php endif ?>
                            <button type="button" class="btn btn-ghost btn-sm" data-pks-toggle="edit-document-<?= $document['id'] ?>">✎ Ubah</button>
                            <form action="<?= $baseUrl . '/dokumen/' . $document['id'] . '/hapus' ?>" method="post" class="pks-delete-form" data-confirm="Hapus riwayat <?= esc($document['jenis_dokumen'], 'attr') ?> ini?">
                                <?= csrf_field() ?><button type="submit" class="btn btn-danger-outline btn-sm">Hapus</button>
                            </form>
                        </footer>
                        <div class="pks-inline-form compact" id="edit-document-<?= $document['id'] ?>" hidden>
                            <form action="<?= $baseUrl . '/dokumen/' . $document['id'] ?>" method="post">
                                <?= csrf_field() ?>
                                <div class="form-grid pks-child-grid">
                                    <div class="form-group"><label>Jenis Dokumen</label><select name="jenis_dokumen" required><option value="PKS" <?= $document['jenis_dokumen'] === 'PKS' ? 'selected' : '' ?>>PKS</option><option value="Addendum" <?= $document['jenis_dokumen'] === 'Addendum' ? 'selected' : '' ?>>Addendum</option></select></div>
                                    <div class="form-group"><label>Urutan / Tahap</label><input name="urutan" type="number" min="1" value="<?= (int) $document['urutan'] ?>" required></div>
                                    <div class="form-group span-2"><label>Nomor Dokumen</label><input name="nomor_dokumen" maxlength="200" value="<?= esc($document['nomor_dokumen']) ?>" required></div>
                                    <div class="form-group"><label>Tanggal Dokumen</label><input name="tanggal_dokumen" type="date" value="<?= esc($document['tanggal_dokumen']) ?>" required></div>
                                    <div class="form-group"><label>Nilai Kerja Sama</label><input name="nilai" type="number" min="0" step="0.01" value="<?= esc($document['nilai']) ?>" required></div>
                                    <div class="form-group"><label>Periode Mulai</label><input name="periode_mulai" type="date" value="<?= esc($document['periode_mulai']) ?>" required></div>
                                    <div class="form-group"><label>Periode Selesai</label><input name="periode_selesai" type="date" value="<?= esc($document['periode_selesai']) ?>" required></div>
                                    <div class="form-group span-2"><label>Link Berkas</label><input name="link_berkas" type="url" maxlength="2048" value="<?= esc($document['link_berkas'] ?? '') ?>"></div>
                                    <div class="form-group span-2"><label>Keterangan</label><textarea name="keterangan"><?= esc($document['keterangan'] ?? '') ?></textarea></div>
                                </div>
                                <div class="pks-inline-actions"><button type="button" class="btn btn-ghost" data-pks-toggle="edit-document-<?= $document['id'] ?>">Batal</button><button class="btn btn-primary" type="submit">Simpan Perubahan</button></div>
                            </form>
                        </div>
                    </div>
                </article>
            <?php endforeach ?>
        </div>
    <?php endif ?>
</section>

<section class="panel pks-work-panel" id="item-pekerjaan">
    <div class="panel-header pks-section-header"><div><h2>04 · Item Barang dan Jasa</h2><p>Rincian objek pekerjaan yang tercakup dalam kerja sama.</p></div><button type="button" class="btn btn-primary btn-sm" data-pks-toggle="new-item">＋ Tambah Item</button></div>
    <div class="pks-inline-form" id="new-item" hidden>
        <form action="<?= $baseUrl . '/item' ?>" method="post">
            <?= csrf_field() ?>
            <div class="pks-inline-heading"><div><strong>Tambah Item Pekerjaan</strong><small>Jumlah dan satuan boleh dikosongkan jika item bersifat umum.</small></div><button type="button" class="pks-inline-close" data-pks-toggle="new-item">×</button></div>
            <div class="form-grid pks-item-grid">
                <div class="form-group span-2"><label>Nama Barang / Jasa <span class="required">*</span></label><input name="nama_item" maxlength="250" placeholder="Contoh: Jasa kebersihan gedung" required></div>
                <div class="form-group"><label>Jumlah</label><input name="jumlah" type="number" min="0" step="0.01"></div>
                <div class="form-group"><label>Satuan</label><input name="satuan" maxlength="80" placeholder="Bulan, unit, paket, orang"></div>
                <div class="form-group span-2"><label>Keterangan</label><textarea name="keterangan" placeholder="Spesifikasi atau catatan item"></textarea></div>
            </div>
            <div class="pks-inline-actions"><button type="button" class="btn btn-ghost" data-pks-toggle="new-item">Batal</button><button type="submit" class="btn btn-primary">Simpan Item</button></div>
        </form>
    </div>
    <div class="table-wrap">
        <table class="pks-item-table">
            <thead><tr><th>No.</th><th>Nama Barang / Jasa</th><th>Jumlah</th><th>Satuan</th><th>Keterangan</th><th>Aksi</th></tr></thead>
            <tbody>
            <?php if ($items === []): ?>
                <tr><td colspan="6"><div class="empty-state compact"><span>□</span><strong>Belum ada item pekerjaan</strong><p>Tambahkan barang atau jasa yang termasuk dalam ruang lingkup PKS.</p></div></td></tr>
            <?php else: ?>
                <?php foreach ($items as $index => $item): ?>
                    <tr><td><strong><?= $index + 1 ?></strong></td><td><strong><?= esc($item['nama_item']) ?></strong></td><td><?= $formatNumber($item['jumlah']) ?></td><td><?= esc($item['satuan'] ?: '-') ?></td><td class="cell-wrap"><?= esc($item['keterangan'] ?: '-') ?></td><td><div class="action-buttons"><button type="button" class="icon-btn" data-pks-toggle="edit-item-<?= $item['id'] ?>" title="Ubah">✎</button><form action="<?= $baseUrl . '/item/' . $item['id'] . '/hapus' ?>" method="post" class="pks-delete-form" data-confirm="Hapus item <?= esc($item['nama_item'], 'attr') ?>?"><?= csrf_field() ?><button type="submit" class="icon-btn icon-btn-delete" title="Hapus">×</button></form></div></td></tr>
                    <tr class="pks-edit-row" id="edit-item-<?= $item['id'] ?>" hidden><td colspan="6"><div class="pks-inline-form compact"><form action="<?= $baseUrl . '/item/' . $item['id'] ?>" method="post"><?= csrf_field() ?><div class="form-grid pks-item-grid"><div class="form-group span-2"><label>Nama Barang / Jasa</label><input name="nama_item" maxlength="250" value="<?= esc($item['nama_item']) ?>" required></div><div class="form-group"><label>Jumlah</label><input name="jumlah" type="number" min="0" step="0.01" value="<?= esc($item['jumlah'] ?? '') ?>"></div><div class="form-group"><label>Satuan</label><input name="satuan" maxlength="80" value="<?= esc($item['satuan'] ?? '') ?>"></div><div class="form-group span-2"><label>Keterangan</label><textarea name="keterangan"><?= esc($item['keterangan'] ?? '') ?></textarea></div></div><div class="pks-inline-actions"><button type="button" class="btn btn-ghost" data-pks-toggle="edit-item-<?= $item['id'] ?>">Batal</button><button type="submit" class="btn btn-primary">Simpan Perubahan</button></div></form></div></td></tr>
                <?php endforeach ?>
            <?php endif ?>
            </tbody>
        </table>
    </div>
</section>

<div class="pks-delete-modal" id="pksDeleteModal" hidden aria-hidden="true">
    <button type="button" class="modal-backdrop" data-pks-delete-close aria-label="Batal hapus"></button>
    <section class="modal-dialog delete-modal-dialog" role="alertdialog" aria-modal="true" aria-labelledby="pksDeleteTitle">
        <div class="delete-modal-body"><span class="delete-warning-icon">!</span><h2 id="pksDeleteTitle">Hapus PKS?</h2><p><strong><?= esc($record['kode_internal']) ?></strong> beserta seluruh riwayat dokumen dan item pekerjaan akan dihapus permanen.</p></div>
        <form method="post" action="<?= $baseUrl . '/hapus' ?>" class="delete-modal-actions"><?= csrf_field() ?><button type="button" class="btn btn-ghost" data-pks-delete-close>Batal</button><button type="submit" class="btn btn-delete">Ya, hapus PKS</button></form>
    </section>
</div>

<script>
(() => {
    document.querySelectorAll('[data-pks-toggle]').forEach(button => button.addEventListener('click', () => {
        const target = document.getElementById(button.dataset.pksToggle);
        if (!target) return;
        target.hidden = !target.hidden;
        if (!target.hidden) target.querySelector('input,select,textarea')?.focus();
    }));
    document.querySelectorAll('.pks-delete-form').forEach(form => form.addEventListener('submit', event => {
        if (!window.confirm(form.dataset.confirm || 'Hapus data ini?')) event.preventDefault();
    }));
    const modal = document.getElementById('pksDeleteModal');
    const openModal = () => { modal.hidden = false; modal.setAttribute('aria-hidden', 'false'); requestAnimationFrame(() => modal.classList.add('open')); document.body.classList.add('modal-open'); };
    const closeModal = () => { modal.classList.remove('open'); modal.setAttribute('aria-hidden', 'true'); setTimeout(() => { modal.hidden = true; document.body.classList.remove('modal-open'); }, 180); };
    document.querySelector('[data-pks-delete-open]')?.addEventListener('click', openModal);
    document.querySelectorAll('[data-pks-delete-close]').forEach(button => button.addEventListener('click', closeModal));
})();
</script>
<?= $this->endSection() ?>
