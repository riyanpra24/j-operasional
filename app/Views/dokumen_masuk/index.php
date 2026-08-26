<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<section class="page-heading"><div><p class="eyebrow">BUKU REGISTER DIGITAL</p><h1>Dokumen Masuk</h1><p>Arsip baca-saja untuk dokumen masuk yang telah selesai diserahkan melalui Distribusi Dokumen.</p></div></section>

<section class="panel filter-panel"><form method="get" action="<?= site_url('dokumen-masuk') ?>" class="filter-form">
    <div class="form-group search-group"><label for="q">Cari dokumen</label><div class="input-with-icon"><span>⌕</span><input id="q" type="search" name="q" value="<?= esc($filters['keyword']) ?>" placeholder="Pengirim, perihal, penerima, jenis..."></div></div>
    <div class="form-group"><label for="jenis">Jenis</label><select id="jenis" name="jenis"><option value="">Semua jenis</option><?php foreach ($jenisOptions as $option): ?><option value="<?= esc($option) ?>" <?= $filters['jenis'] === $option ? 'selected' : '' ?>><?= esc($option) ?></option><?php endforeach ?></select></div>
    <div class="form-group"><label for="dari">Dari tanggal diterima</label><input id="dari" type="date" name="dari" value="<?= esc($filters['from']) ?>"></div>
    <div class="form-group"><label for="sampai">Sampai tanggal diterima</label><input id="sampai" type="date" name="sampai" value="<?= esc($filters['to']) ?>"></div>
    <input type="hidden" name="per_page" value="<?= $filters['perPage'] ?>">
    <div class="filter-actions"><button type="submit" class="btn btn-secondary">Terapkan</button><a href="<?= site_url('dokumen-masuk') ?>" class="btn btn-ghost">Reset</a></div>
</form></section>

<section class="panel register-panel"><div class="table-wrap"><table><thead><tr><th>No.</th><th>Pengirim</th><th>Perihal</th><th>Penerima</th><th>Hari</th><th>Tanggal Diterima</th><th>Penyerahan</th><th>Aksi</th></tr></thead><tbody>
<?php if ($dokumen === []): ?><tr><td colspan="8"><div class="empty-state"><span>⌕</span><strong>Belum ada Dokumen Masuk selesai</strong><p>Selesaikan penyerahan melalui menu Distribusi Dokumen agar data tampil di halaman ini.</p></div></td></tr>
<?php else: ?><?php $rowNumber = (($pager->getCurrentPage('dokumen_masuk') - 1) * $filters['perPage']) + 1; ?><?php foreach ($dokumen as $row): ?><tr><td><strong><?= $rowNumber++ ?></strong></td><td><strong><?= esc($row['pengirim']) ?></strong></td><td class="cell-wrap"><?= esc($row['perihal'] ?: '-') ?></td><td><?= esc($row['penerima'] ?: '-') ?></td><td><?= esc($row['hari']) ?></td><td><?= date('d-m-Y', strtotime($row['tanggal'])) ?></td><td><span class="pickup-status completed" title="<?= esc($row['pengambilan'], 'attr') ?>"><?= esc($row['pengambilan']) ?></span></td><td><div class="table-actions"><a class="icon-btn" href="<?= site_url('dokumen-masuk/' . $row['id']) ?>" title="Detail" data-open-detail-modal data-detail-url="<?= site_url('dokumen-masuk/' . $row['id']) ?>">⌕</a><button type="button" class="icon-btn" title="Edit dan kembalikan ke distribusi" data-reopen-progress data-reopen-url="<?= site_url('dokumen-masuk/'.$row['id'].'/kembalikan') ?>" data-reopen-label="Dokumen dari <?= esc($row['pengirim'], 'attr') ?>">✎</button></div></td></tr><?php endforeach ?><?php endif ?>
</tbody></table></div><div class="table-list-footer"><form method="get" action="<?= site_url('dokumen-masuk') ?>" class="table-length-form"><input type="hidden" name="q" value="<?= esc($filters['keyword']) ?>"><input type="hidden" name="jenis" value="<?= esc($filters['jenis']) ?>"><input type="hidden" name="dari" value="<?= esc($filters['from']) ?>"><input type="hidden" name="sampai" value="<?= esc($filters['to']) ?>"><label for="dokumen_per_page">Tampilkan</label><select id="dokumen_per_page" name="per_page" aria-label="Jumlah baris per halaman" data-table-length><?php foreach ([10, 20, 50, 100] as $size): ?><option value="<?= $size ?>" <?= $filters['perPage'] === $size ? 'selected' : '' ?>><?= $size ?></option><?php endforeach ?></select><span>data</span></form><?php if ($dokumen !== []): ?><div class="pagination-wrap"><?= $pager->links('dokumen_masuk', 'default_full') ?></div><?php endif ?></div></section>
<?= view('components/reopen_progress_modal', [
    'reopenTitle'       => 'Kembalikan ke Distribusi Dokumen?',
    'reopenDescription' => 'akan hilang dari arsip dan kembali ke Distribusi Dokumen agar dapat diedit.',
    'reopenSubmitLabel' => 'Ya, kembalikan ke distribusi',
]) ?>
<?= $this->endSection() ?>
