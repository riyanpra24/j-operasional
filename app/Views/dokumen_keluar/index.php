<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<section class="page-heading heading-actions">
    <div><p class="eyebrow"><?= ($securityView ?? false) ? 'SECURITY' : 'AGENDARIS' ?></p><h1><?= esc($title) ?></h1><p>Arsip baca-saja untuk dokumen keluar yang telah selesai diproses.</p></div>
    <?php if (! $readOnly): ?><button type="button" class="btn btn-primary" data-dokumen-keluar-add>＋ Tambah Surat Keluar</button><?php endif ?>
</section>

<section class="panel filter-panel">
    <form method="get" action="<?= $indexUrl ?>" class="agendaris-filter-form">
        <div class="form-group search-group"><label for="keluar_q">Cari dokumen</label><div class="input-with-icon"><span>⌕</span><input id="keluar_q" type="search" name="q" value="<?= esc($filters['keyword']) ?>" placeholder="Nomor surat, pemohon, pelaksana, UP, alamat..."></div></div>
        <div class="form-group"><label for="keluar_jenis_filter">Jenis Dokumen</label><select id="keluar_jenis_filter" name="jenis"><option value="">Semua jenis</option><?php foreach ($jenisOptions as $option): ?><option value="<?= esc($option) ?>" <?= $filters['jenis'] === $option ? 'selected' : '' ?>><?= esc($option) ?></option><?php endforeach ?></select></div>
        <div class="form-group"><label for="keluar_dari">Dari tanggal pengiriman</label><input id="keluar_dari" type="date" name="dari" value="<?= esc($filters['from']) ?>"></div>
        <div class="form-group"><label for="keluar_sampai">Sampai tanggal pengiriman</label><input id="keluar_sampai" type="date" name="sampai" value="<?= esc($filters['to']) ?>"></div>
        <input type="hidden" name="per_page" value="<?= $filters['perPage'] ?>">
        <div class="filter-actions"><button type="submit" class="btn btn-secondary">Terapkan</button><a href="<?= $indexUrl ?>" class="btn btn-ghost">Reset</a></div>
    </form>
</section>

<section class="panel register-panel agendaris-table-panel">
    <div class="table-wrap"><table><thead><tr><th>No.</th><th>Nomor Surat</th><th>Jenis Dokumen</th><th>Pemohon</th><th>Pelaksana</th><th>UP</th><th>Tanggal Pengiriman</th><th>Alamat Penerima</th><th>Aksi</th></tr></thead><tbody>
        <?php if ($dokumen === []): ?>
            <tr><td colspan="9"><div class="empty-state"><span>⇢</span><strong>Belum ada Dokumen Keluar selesai</strong><p>Selesaikan dokumen melalui menu Progres Dokumen agar tampil di halaman ini.</p></div></td></tr>
        <?php else: ?>
            <?php $rowNumber = (($pager->getCurrentPage('dokumen_keluar') - 1) * $filters['perPage']) + 1; ?>
            <?php foreach ($dokumen as $row): ?><tr>
                <td><strong><?= $rowNumber++ ?></strong></td>
                <td><strong><?= esc($row['nomor_surat']) ?></strong></td>
                <td><?= esc($row['jenis_surat']) ?></td>
                <td><?= esc($row['pemohon'] ?: '-') ?></td>
                <td><?= esc($row['pelaksana'] ?: '-') ?></td>
                <td><?= esc($row['up'] ?: '-') ?></td>
                <td><?= date('d-m-Y', strtotime($row['tanggal_pengiriman'])) ?></td>
                <td class="cell-wrap"><?= esc($row['alamat_penerima']) ?></td>
                <td><div class="table-actions">
                    <button type="button" class="icon-btn" title="Detail" data-dokumen-keluar-view data-dokumen-keluar-url="<?= site_url($detailUrlPrefix.'/'.$row['id']) ?>">⌕</button>
                    <?php if (! ($securityView ?? false)): ?><button type="button" class="icon-btn" title="Edit dan kembalikan ke progres" data-reopen-progress data-reopen-url="<?= site_url('agendaris/surat-keluar/'.$row['id'].'/kembalikan') ?>" data-reopen-label="Dokumen nomor <?= esc($row['nomor_surat'], 'attr') ?>">✎</button><?php endif ?>
                    <?php if (! $readOnly): ?><button type="button" class="icon-btn" title="Ubah" data-dokumen-keluar-edit data-dokumen-keluar-url="<?= site_url('agendaris/surat-keluar/'.$row['id']) ?>">✎</button>
                    <button type="button" class="icon-btn icon-btn-delete" title="Hapus" data-dokumen-keluar-delete data-delete-url="<?= site_url('agendaris/surat-keluar/'.$row['id'].'/hapus') ?>" data-delete-label="<?= esc($row['nomor_surat'], 'attr') ?>">×</button><?php endif ?>
                </div></td>
            </tr><?php endforeach ?>
        <?php endif ?>
    </tbody></table></div>
    <div class="table-list-footer">
        <form method="get" action="<?= $indexUrl ?>" class="table-length-form">
            <input type="hidden" name="q" value="<?= esc($filters['keyword']) ?>"><input type="hidden" name="jenis" value="<?= esc($filters['jenis']) ?>"><input type="hidden" name="dari" value="<?= esc($filters['from']) ?>"><input type="hidden" name="sampai" value="<?= esc($filters['to']) ?>">
            <label for="keluar_per_page">Tampilkan</label><select id="keluar_per_page" name="per_page" aria-label="Jumlah baris per halaman" data-table-length><?php foreach ([10, 20, 50, 100] as $size): ?><option value="<?= $size ?>" <?= $filters['perPage'] === $size ? 'selected' : '' ?>><?= $size ?></option><?php endforeach ?></select><span>data</span>
        </form>
        <?php if ($dokumen !== []): ?><div class="pagination-wrap"><?= $pager->links('dokumen_keluar', 'default_full') ?></div><?php endif ?>
    </div>
</section>

<?php if (! $readOnly): ?><?= view('dokumen_keluar/form_modal') ?><?php endif ?>
<?= view('dokumen_keluar/detail_modal', ['readOnly' => $readOnly]) ?>
<?php if (! $readOnly): ?><?= view('dokumen_keluar/delete_modal') ?><?php endif ?>
<?php if (! ($securityView ?? false)): ?><?= view('components/reopen_progress_modal') ?><?php endif ?>
<?= $this->endSection() ?>
