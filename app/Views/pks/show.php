<?php
$isPopup = $isPopup ?? false;
if (! $isPopup) {
    echo $this->extend('layouts/main');
    echo $this->section('content');
}
$formatDate = static fn (?string $value): string => $value ? date('d-m-Y', strtotime($value)) : '-';
$formatMoney = static fn ($value): string => 'Rp ' . number_format((float) $value, 0, ',', '.');
$formatNumber = static function ($value): string {
    if ($value === null || $value === '') return '-';
    return rtrim(rtrim(number_format((float) $value, 2, ',', '.'), '0'), ',');
};
$baseUrl = site_url('bagian-umum-1/pks-barang-jasa/' . $record['id']);
$isEditMode = $isEditMode ?? false;
?>

<?php if ($isPopup): ?>
<nav class="pks-wizard-steps pks-popup-detail-steps" aria-label="Tahapan detail PKS">
    <button type="button" class="active" data-pks-detail-step-indicator="1"><span>01</span><div><strong>Ringkasan</strong><small>Status dan masa berlaku</small></div></button>
    <i aria-hidden="true"></i>
    <button type="button" data-pks-detail-step-indicator="2"><span>02</span><div><strong>Informasi</strong><small>PKS dan data mitra</small></div></button>
    <i aria-hidden="true"></i>
    <button type="button" data-pks-detail-step-indicator="3"><span>03</span><div><strong>Dokumen</strong><small>Induk dan Addendum</small></div></button>
    <i aria-hidden="true"></i>
    <button type="button" data-pks-detail-step-indicator="4"><span>04</span><div><strong>Barang & Jasa</strong><small>Rincian pekerjaan</small></div></button>
</nav>
<div class="pks-popup-step-panel" data-pks-detail-step-panel="1">
<section class="pks-popup-record-heading">
    <p class="eyebrow">BAGIAN UMUM 1 / DETAIL PKS</p>
    <h2><?= esc($record['nama_kerjasama']) ?></h2>
    <p><?= esc($record['kode_internal']) ?> · <?= esc($record['nama_mitra']) ?></p>
</section>
<?php else: ?>
<section class="page-heading heading-actions pks-detail-heading">
    <div>
        <a href="<?= site_url('bagian-umum-1/pks-barang-jasa') ?>" class="back-link">← Kembali ke daftar</a>
        <p class="eyebrow">BAGIAN UMUM 1 / <?= $isEditMode ? 'KELOLA PKS' : 'DETAIL PKS' ?></p>
        <h1><?= esc($record['nama_kerjasama']) ?></h1>
        <p><?= esc($record['kode_internal']) ?> · <?= esc($record['nama_mitra']) ?></p>
    </div>
    <?php if ($isEditMode): ?>
        <div class="heading-button-group"><a href="<?= $baseUrl ?>" class="btn btn-ghost">⌕ Lihat Detail</a><a href="<?= $baseUrl . '/ubah?data_utama=1' ?>" class="btn btn-secondary">✎ Ubah Data Utama</a><button type="button" class="btn btn-danger-outline" data-pks-delete-open>Hapus PKS</button></div>
    <?php else: ?>
        <div class="heading-button-group"><a href="<?= $baseUrl . '/ubah' ?>" class="btn btn-primary">✎ Edit / Kelola</a></div>
    <?php endif ?>
</section>
<?php endif ?>

<section class="pks-detail-summary">
    <article><small>Status Saat Ini</small><strong><span class="pks-status <?= esc($status['status_class']) ?>"><?= esc($status['status_label']) ?></span></strong><p>Berdasarkan PKS induk atau addendum terakhir</p></article>
    <article><small>Dokumen Terakhir</small><strong><?= esc($latest['nomor_dokumen'] ?? 'Belum ada') ?></strong><p><?= $latest ? ($latest['jenis_dokumen'] === 'PKS' ? 'PKS · Induk' : 'Addendum · Tahap ' . (int) $latest['urutan']) : 'Tambahkan dokumen awal PKS' ?></p></article>
    <article><small>Berlaku Sampai</small><strong><?= $formatDate($latest['periode_selesai'] ?? null) ?></strong><p><?= $latest ? $formatDate($latest['periode_mulai']) . ' s.d. ' . $formatDate($latest['periode_selesai']) : 'Periode belum tersedia' ?></p></article>
    <article><small>Nilai Terakhir</small><strong><?= $latest ? $formatMoney($latest['nilai']) : '-' ?></strong><p>Nilai pada PKS/addendum terakhir</p></article>
</section>
<?php if ($isPopup): ?></div><div class="pks-popup-step-panel" data-pks-detail-step-panel="2" hidden><?php endif ?>

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
<?php if ($isPopup): ?></div><div class="pks-popup-step-panel" data-pks-detail-step-panel="3" hidden><?php endif ?>

<section class="panel pks-work-panel" id="riwayat-dokumen">
    <div class="panel-header pks-section-header"><div><h2>03 · Riwayat PKS dan Addendum</h2><p>Urutan masa berlaku: PKS Induk, Addendum 1, Addendum 2, dan seterusnya.</p></div><?php if ($isEditMode): ?><button type="button" class="btn btn-primary btn-sm" data-pks-toggle="new-document">＋ Tambah Addendum</button><?php endif ?></div>

    <?php if ($isEditMode): ?><div class="pks-inline-form" id="new-document" hidden>
        <form action="<?= $baseUrl . '/dokumen' ?>" method="post" data-pks-document-form>
            <?= csrf_field() ?>
            <div class="pks-inline-heading"><div><strong>Tambah Addendum</strong><small>Nomor tahap dimulai dari Addendum 1 dan dapat disesuaikan selama tidak duplikat.</small></div><button type="button" class="pks-inline-close" data-pks-toggle="new-document" aria-label="Tutup">×</button></div>
            <div class="form-grid pks-child-grid">
                <div class="form-group"><label>Jenis Dokumen</label><input value="Addendum" readonly aria-readonly="true"><input name="jenis_dokumen" type="hidden" value="Addendum"><small>Jenis dokumen dikunci sebagai Addendum.</small></div>
                <div class="form-group"><label>Urutan / Tahap <span class="required">*</span></label><input name="urutan" type="number" min="1" value="<?= $nextSequence ?>" required></div>
                <div class="form-group"><label>Nomor Addendum <span class="required">*</span></label><input name="nomor_dokumen" maxlength="200" placeholder="Contoh: 01/ADD/2026" required></div>
                <div class="form-group"><label>Tanggal Dokumen <span class="required">*</span></label><input name="tanggal_dokumen" type="date" data-pks-document-date required></div>
                <div class="form-group"><label>Nilai Kerja Sama <span class="required">*</span></label><input name="nilai" type="number" min="0" step="0.01" value="0" required></div>
                <div class="form-group"><label>Jangka Waktu <span class="required">*</span></label><div class="pks-duration-input"><input name="jangka_waktu_bulan" type="number" min="1" max="1200" data-pks-duration placeholder="Contoh: 12" required><span>Bulan</span></div><small>Masukkan lama masa berlaku dalam bulan.</small></div>
                <div class="form-group"><label>Periode Mulai <span class="required">*</span></label><input name="periode_mulai" type="date" data-pks-period-start readonly aria-readonly="true" required><small>Terisi otomatis sesuai Tanggal Dokumen.</small></div>
                <div class="form-group"><label>Periode Selesai <span class="required">*</span></label><input name="periode_selesai" type="date" data-pks-period-end readonly aria-readonly="true" required><small>Dihitung otomatis dari Periode Mulai dan Jangka Waktu.</small></div>
                <div class="form-group span-2"><label>Link Berkas</label><input name="link_berkas" type="url" maxlength="2048" placeholder="https://drive.google.com/... atau tautan dokumen lainnya"><small>Opsional. Gunakan link http/https yang dapat diakses sesuai kewenangan.</small></div>
            </div>
            <div class="pks-inline-actions"><button type="button" class="btn btn-ghost" data-pks-toggle="new-document">Batal</button><button class="btn btn-primary" type="submit">Simpan Dokumen</button></div>
        </form>
    </div><?php endif ?>

    <?php if ($documents === []): ?>
        <div class="empty-state compact"><span>▤</span><strong>Dokumen induk belum tersedia</strong><p>Lengkapi dokumen PKS induk untuk mulai memantau masa berlaku.</p></div>
    <?php else: ?>
        <div class="pks-timeline">
            <?php foreach ($documents as $document): ?>
                <?php $isParentDocument = $document['jenis_dokumen'] === 'PKS'; ?>
                <article class="pks-timeline-item">
                    <span class="pks-timeline-number"><?= $isParentDocument ? 'IN' : str_pad((string) $document['urutan'], 2, '0', STR_PAD_LEFT) ?></span>
                    <div class="pks-timeline-card">
                        <header><div><span><?= $isParentDocument ? 'PKS · INDUK' : 'ADDENDUM · TAHAP ' . (int) $document['urutan'] ?></span><h3><?= esc($document['nomor_dokumen']) ?></h3></div><strong><?= $formatMoney($document['nilai']) ?></strong></header>
                        <dl>
                            <div><dt>Tanggal Dokumen</dt><dd><?= $formatDate($document['tanggal_dokumen']) ?></dd></div>
                            <div><dt>Periode Berlaku</dt><dd><?= $formatDate($document['periode_mulai']) ?> s.d. <?= $formatDate($document['periode_selesai']) ?></dd></div>
                            <div><dt>Jangka Waktu</dt><dd><?= (int) ($document['jangka_waktu_bulan'] ?? 1) ?> bulan</dd></div>
                        </dl>
                        <?php if ($document['link_berkas'] || $isEditMode): ?><footer>
                            <?php if ($document['link_berkas']): ?><a href="<?= esc($document['link_berkas'], 'attr') ?>" target="_blank" rel="noopener noreferrer" class="btn btn-secondary btn-sm">↗ Buka Berkas</a><?php endif ?>
                            <?php if ($isEditMode): ?><button type="button" class="btn btn-ghost btn-sm" data-pks-toggle="edit-document-<?= $document['id'] ?>">✎ Ubah</button>
                            <?php if (! $isParentDocument): ?><form action="<?= $baseUrl . '/dokumen/' . $document['id'] . '/hapus' ?>" method="post" class="pks-delete-form" data-confirm="Hapus riwayat <?= esc($document['jenis_dokumen'], 'attr') ?> ini?">
                                <?= csrf_field() ?><button type="submit" class="btn btn-danger-outline btn-sm">Hapus</button>
                            </form><?php endif ?><?php endif ?>
                        </footer><?php endif ?>
                        <?php if ($isEditMode): ?><div class="pks-inline-form compact" id="edit-document-<?= $document['id'] ?>" hidden>
                            <form action="<?= $baseUrl . '/dokumen/' . $document['id'] ?>" method="post" data-pks-document-form>
                                <?= csrf_field() ?>
                                <div class="form-grid pks-child-grid">
                                    <div class="form-group"><label>Jenis Dokumen</label><input value="<?= $isParentDocument ? 'PKS' : 'Addendum' ?>" readonly aria-readonly="true"><input name="jenis_dokumen" type="hidden" value="<?= $isParentDocument ? 'PKS' : 'Addendum' ?>"><small>Jenis dokumen tidak dapat diubah.</small></div>
                                    <?php if ($isParentDocument): ?>
                                        <div class="form-group"><label>Urutan / Tahap</label><input value="Induk" readonly aria-readonly="true"><input name="urutan" type="hidden" value="0"><small>PKS selalu menjadi dokumen induk.</small></div>
                                    <?php else: ?>
                                        <div class="form-group"><label>Urutan / Tahap</label><input name="urutan" type="number" min="1" value="<?= (int) $document['urutan'] ?>" required><small>Tahap Addendum dimulai dari 1.</small></div>
                                    <?php endif ?>
                                    <div class="form-group"><label><?= $isParentDocument ? 'Nomor Dokumen PKS' : 'Nomor Addendum' ?></label><input name="nomor_dokumen" maxlength="200" value="<?= esc($document['nomor_dokumen']) ?>" required></div>
                                    <div class="form-group"><label>Tanggal Dokumen</label><input name="tanggal_dokumen" type="date" value="<?= esc($document['tanggal_dokumen']) ?>" data-pks-document-date required></div>
                                    <div class="form-group"><label>Nilai Kerja Sama</label><input name="nilai" type="number" min="0" step="0.01" value="<?= esc($document['nilai']) ?>" required></div>
                                    <div class="form-group"><label>Jangka Waktu</label><div class="pks-duration-input"><input name="jangka_waktu_bulan" type="number" min="1" max="1200" value="<?= (int) ($document['jangka_waktu_bulan'] ?? 1) ?>" data-pks-duration required><span>Bulan</span></div></div>
                                    <div class="form-group"><label>Periode Mulai</label><input name="periode_mulai" type="date" value="<?= esc($document['tanggal_dokumen']) ?>" data-pks-period-start readonly aria-readonly="true" required><small>Terisi otomatis sesuai Tanggal Dokumen.</small></div>
                                    <div class="form-group"><label>Periode Selesai</label><input name="periode_selesai" type="date" value="<?= esc($document['periode_selesai']) ?>" data-pks-period-end readonly aria-readonly="true" required><small>Dihitung otomatis berdasarkan Jangka Waktu.</small></div>
                                    <div class="form-group span-2"><label>Link Berkas</label><input name="link_berkas" type="url" maxlength="2048" value="<?= esc($document['link_berkas'] ?? '') ?>"></div>
                                </div>
                                <div class="pks-inline-actions"><button type="button" class="btn btn-ghost" data-pks-toggle="edit-document-<?= $document['id'] ?>">Batal</button><button class="btn btn-primary" type="submit">Simpan Perubahan</button></div>
                            </form>
                        </div><?php endif ?>
                    </div>
                </article>
            <?php endforeach ?>
        </div>
    <?php endif ?>
</section>
<?php if ($isPopup): ?></div><div class="pks-popup-step-panel" data-pks-detail-step-panel="4" hidden><?php endif ?>

<section class="panel pks-work-panel" id="item-pekerjaan">
    <div class="panel-header pks-section-header"><div><h2>04 · Item Barang dan Jasa</h2><p>Rincian objek pekerjaan yang tercakup dalam kerja sama.</p></div><?php if ($isEditMode): ?><button type="button" class="btn btn-primary btn-sm" data-pks-toggle="new-item">＋ Tambah Item</button><?php endif ?></div>
    <?php if ($isEditMode): ?><div class="pks-inline-form" id="new-item" hidden>
        <form action="<?= $baseUrl . '/item' ?>" method="post">
            <?= csrf_field() ?>
            <div class="pks-inline-heading"><div><strong>Tambah Item Pekerjaan</strong><small>Tuliskan rincian barang, jasa, atau pekerjaan yang tercakup dalam PKS.</small></div><button type="button" class="pks-inline-close" data-pks-toggle="new-item">×</button></div>
            <div class="form-grid pks-item-grid">
                <div class="form-group span-2"><label>Keterangan <span class="required">*</span></label><textarea name="keterangan" maxlength="2000" placeholder="Tuliskan rincian barang, jasa, atau pekerjaan" required></textarea></div>
            </div>
            <div class="pks-inline-actions"><button type="button" class="btn btn-ghost" data-pks-toggle="new-item">Batal</button><button type="submit" class="btn btn-primary">Simpan Item</button></div>
        </form>
    </div><?php endif ?>
    <div class="table-wrap">
        <table class="pks-item-table">
            <thead><tr><th>No.</th><th>Keterangan</th><?php if ($isEditMode): ?><th>Aksi</th><?php endif ?></tr></thead>
            <tbody>
            <?php if ($items === []): ?>
                <tr><td colspan="<?= $isEditMode ? 3 : 2 ?>"><div class="empty-state compact"><span>□</span><strong>Belum ada item pekerjaan</strong><p><?= $isEditMode ? 'Tambahkan keterangan barang atau jasa yang termasuk dalam ruang lingkup PKS.' : 'Keterangan barang atau jasa belum dicatat pada PKS ini.' ?></p></div></td></tr>
            <?php else: ?>
                <?php foreach ($items as $index => $item): ?>
                    <tr><td><strong><?= $index + 1 ?></strong></td><td class="cell-wrap"><?= esc($item['keterangan'] ?: '-') ?></td><?php if ($isEditMode): ?><td><div class="action-buttons"><button type="button" class="icon-btn" data-pks-toggle="edit-item-<?= $item['id'] ?>" title="Ubah">✎</button><form action="<?= $baseUrl . '/item/' . $item['id'] . '/hapus' ?>" method="post" class="pks-delete-form" data-confirm="Hapus item pekerjaan ini?"><?= csrf_field() ?><button type="submit" class="icon-btn icon-btn-delete" title="Hapus">×</button></form></div></td><?php endif ?></tr>
                    <?php if ($isEditMode): ?><tr class="pks-edit-row" id="edit-item-<?= $item['id'] ?>" hidden><td colspan="3"><div class="pks-inline-form compact"><form action="<?= $baseUrl . '/item/' . $item['id'] ?>" method="post"><?= csrf_field() ?><div class="form-grid pks-item-grid"><div class="form-group span-2"><label>Keterangan <span class="required">*</span></label><textarea name="keterangan" maxlength="2000" required><?= esc($item['keterangan'] ?? '') ?></textarea></div></div><div class="pks-inline-actions"><button type="button" class="btn btn-ghost" data-pks-toggle="edit-item-<?= $item['id'] ?>">Batal</button><button type="submit" class="btn btn-primary">Simpan Perubahan</button></div></form></div></td></tr><?php endif ?>
                <?php endforeach ?>
            <?php endif ?>
            </tbody>
        </table>
    </div>
</section>
<?php if ($isPopup): ?></div><?php endif ?>

<?php if ($isEditMode): ?><div class="pks-delete-modal" id="pksDeleteModal" hidden aria-hidden="true">
    <button type="button" class="modal-backdrop" data-pks-delete-close aria-label="Batal hapus"></button>
    <section class="modal-dialog delete-modal-dialog" role="alertdialog" aria-modal="true" aria-labelledby="pksDeleteTitle">
        <div class="delete-modal-body"><span class="delete-warning-icon">!</span><h2 id="pksDeleteTitle">Hapus PKS?</h2><p><strong><?= esc($record['kode_internal']) ?></strong> beserta seluruh riwayat dokumen dan item pekerjaan <?= (string) session()->get('auth_role') === 'admin' ? 'akan dihapus permanen dan tidak dapat dipulihkan.' : 'akan dihapus.' ?></p></div>
        <form method="post" action="<?= $baseUrl . '/hapus' ?>" class="delete-modal-actions"><?= csrf_field() ?><button type="button" class="btn btn-ghost" data-pks-delete-close>Batal</button><button type="submit" class="btn btn-delete">Ya, hapus PKS</button></form>
    </section>
</div><?php endif ?>

<?php if (! $isPopup): ?><script>
(() => {
    const addMonthsClamped = (dateValue, monthsValue) => {
        const match = /^(\d{4})-(\d{2})-(\d{2})$/.exec(dateValue || '');
        const months = Number.parseInt(monthsValue, 10);
        if (!match || !Number.isInteger(months) || months < 1) return '';
        const year = Number(match[1]);
        const monthIndex = Number(match[2]) - 1;
        const day = Number(match[3]);
        const targetIndex = (year * 12) + monthIndex + months;
        const targetYear = Math.floor(targetIndex / 12);
        const targetMonthIndex = targetIndex % 12;
        const lastDay = new Date(targetYear, targetMonthIndex + 1, 0).getDate();
        const pad = value => String(value).padStart(2, '0');
        return `${targetYear}-${pad(targetMonthIndex + 1)}-${pad(Math.min(day, lastDay))}`;
    };
    document.querySelectorAll('[data-pks-document-form]').forEach(form => {
        const documentDate = form.querySelector('[data-pks-document-date]');
        const periodStart = form.querySelector('[data-pks-period-start]');
        const duration = form.querySelector('[data-pks-duration]');
        const periodEnd = form.querySelector('[data-pks-period-end]');
        const syncPeriod = () => {
            periodStart.value = documentDate.value;
            periodEnd.value = addMonthsClamped(documentDate.value, duration.value);
        };
        documentDate.addEventListener('input', syncPeriod);
        duration.addEventListener('input', syncPeriod);
        syncPeriod();
    });
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
    if (!modal) return;
    const openModal = () => { modal.hidden = false; modal.setAttribute('aria-hidden', 'false'); requestAnimationFrame(() => modal.classList.add('open')); document.body.classList.add('modal-open'); };
    const closeModal = () => { modal.classList.remove('open'); modal.setAttribute('aria-hidden', 'true'); setTimeout(() => { modal.hidden = true; document.body.classList.remove('modal-open'); }, 180); };
    document.querySelector('[data-pks-delete-open]')?.addEventListener('click', openModal);
    document.querySelectorAll('[data-pks-delete-close]').forEach(button => button.addEventListener('click', closeModal));
})();
</script>
<?= $this->endSection() ?><?php endif ?>
