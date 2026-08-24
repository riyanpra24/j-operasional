<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<section class="page-heading heading-actions">
    <div><p class="eyebrow">AGENDARIS</p><h1>Progres Dokumen</h1><p>Seluruh data tabel Dokumen Keluar beserta progres distribusinya.</p></div>
    <button type="button" class="btn btn-primary" data-progress-add>＋ Tambah Dokumen</button>
</section>

<section class="panel filter-panel">
    <form method="get" action="<?= site_url('agendaris/progres-dokumen') ?>" class="agendaris-filter-form">
        <div class="form-group search-group"><label for="progress_q">Cari dokumen</label><div class="input-with-icon"><span>⌕</span><input id="progress_q" type="search" name="q" value="<?= esc($filters['keyword']) ?>" placeholder="Nomor surat, jenis, pelaksana, Security..."></div></div>
        <div class="form-group"><label for="progress_filter">Progres</label><select id="progress_filter" name="progres"><option value="">Semua progres</option><option value="Menunggu Ekspedisi" <?= $filters['progres'] === 'Menunggu Ekspedisi' ? 'selected' : '' ?>>Menunggu Ekspedisi</option><option value="Diambil Ekspedisi" <?= $filters['progres'] === 'Diambil Ekspedisi' ? 'selected' : '' ?>>Diambil Ekspedisi</option></select></div>
        <input type="hidden" name="per_page" value="<?= $filters['perPage'] ?>">
        <div class="filter-actions"><button type="submit" class="btn btn-secondary">Terapkan</button><a href="<?= site_url('agendaris/progres-dokumen') ?>" class="btn btn-ghost">Reset</a></div>
    </form>
</section>

<section class="panel register-panel progress-document-panel">
    <div class="table-wrap"><table><thead><tr><th>No.</th><th>Nomor Surat</th><th>Jenis Surat</th><th>Pemohon</th><th>Pelaksana</th><th>UP</th><th>Tanggal Pengiriman</th><th>Nomor Resi</th><th>Tanggal Diterima</th><th>Penerima</th><th>Alamat Penerima</th><th>Security</th><th>Tanggal Diterima Security</th><th>Progres</th><th>Aksi</th></tr></thead><tbody>
        <?php if ($dokumen === []): ?><tr><td colspan="15"><div class="empty-state"><span>⇢</span><strong>Belum ada Progres Dokumen</strong><p>Tambahkan Dokumen Keluar atau ubah filter pencarian.</p></div></td></tr>
        <?php else: ?>
            <?php $rowNumber = (($pager->getCurrentPage('progres_dokumen') - 1) * $filters['perPage']) + 1; ?>
            <?php foreach ($dokumen as $row): ?><?php $locked = $row['progres'] === 'Diambil Ekspedisi'; ?><tr>
                <td><strong><?= $rowNumber++ ?></strong></td><td><strong><?= esc($row['nomor_surat']) ?></strong></td><td><?= esc($row['jenis_surat']) ?></td><td><?= esc($row['pemohon'] ?: '-') ?></td><td><?= esc($row['pelaksana'] ?: '-') ?></td><td class="cell-wrap"><?= esc($row['up'] ?: '-') ?></td><td><?= date('d-m-Y', strtotime($row['tanggal_pengiriman'])) ?></td><td><?= esc($row['nomor_resi'] ?: '-') ?></td><td><?= $row['tanggal_diterima'] ? date('d-m-Y', strtotime($row['tanggal_diterima'])) : '-' ?></td><td><?= esc($row['penerima'] ?: '-') ?></td><td class="cell-wrap"><?= esc($row['alamat_penerima']) ?></td><td><?= esc($row['security'] ?: '-') ?></td><td><?= $row['tanggal_security'] ? date('d-m-Y', strtotime($row['tanggal_security'])) : '-' ?></td><td><span class="pickup-status <?= $locked ? 'completed' : 'pending' ?>"><?= esc($row['progres'] ?: 'Menunggu Ekspedisi') ?></span></td>
                <td><div class="table-actions"><button type="button" class="icon-btn" title="Detail" data-progress-view data-progress-url="<?= site_url('agendaris/progres-dokumen/'.$row['id']) ?>">⌕</button><button type="button" class="icon-btn" title="Ubah" data-progress-edit data-progress-url="<?= site_url('agendaris/progres-dokumen/'.$row['id']) ?>">✎</button><?php if (! $locked): ?><button type="button" class="icon-btn icon-btn-delete" title="Hapus" data-progress-delete data-delete-url="<?= site_url('agendaris/progres-dokumen/'.$row['id'].'/hapus') ?>" data-delete-label="<?= esc($row['nomor_surat'], 'attr') ?>">×</button><?php endif ?></div></td>
            </tr><?php endforeach ?>
        <?php endif ?>
    </tbody></table></div>
    <div class="table-list-footer"><form method="get" action="<?= site_url('agendaris/progres-dokumen') ?>" class="table-length-form"><input type="hidden" name="q" value="<?= esc($filters['keyword']) ?>"><input type="hidden" name="progres" value="<?= esc($filters['progres']) ?>"><label for="progress_per_page">Tampilkan</label><select id="progress_per_page" name="per_page" data-table-length><?php foreach ([10,20,50,100] as $size): ?><option value="<?= $size ?>" <?= $filters['perPage'] === $size ? 'selected' : '' ?>><?= $size ?></option><?php endforeach ?></select><span>data</span></form><?php if ($dokumen !== []): ?><div class="pagination-wrap"><?= $pager->links('progres_dokumen', 'default_full') ?></div><?php endif ?></div>
</section>

<?= view('agendaris/progres_form_modal') ?>
<?= view('agendaris/progres_detail_modal') ?>
<?= view('agendaris/progres_delete_modal') ?>
<?= $this->endSection() ?>
