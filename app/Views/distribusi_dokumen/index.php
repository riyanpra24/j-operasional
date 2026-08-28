<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<section class="page-heading heading-actions distribution-heading">
    <div><p class="eyebrow">ALUR DOKUMEN</p><h1>Distribusi Dokumen</h1><p>Kelola data masuk dan proses distribusi hingga selesai dari halaman ini.</p></div>
    <?php if ($filters['tab'] === 'masuk'): ?><button type="button" class="btn btn-primary" data-open-input-modal>＋ Tambah Dokumen Masuk</button><?php endif ?>
</section>

<?= view('distribusi_dokumen/tabs', ['activeTab' => $filters['tab']]) ?>

<section class="panel filter-panel">
    <form method="get" action="<?= site_url('distribusi-dokumen') ?>" class="distribution-filter-form">
        <div class="form-group search-group"><label for="q">Cari dokumen</label><div class="input-with-icon"><span>⌕</span><input id="q" type="search" name="q" value="<?= esc($filters['keyword']) ?>" placeholder="<?= $filters['tab'] === 'masuk' ? 'Pengirim, perihal, penerima, jenis...' : 'Jenis, pelaksana, UP, Security...' ?>"></div></div>
        <div class="form-group"><label for="dari">Dari <?= $filters['tab'] === 'masuk' ? 'tanggal diterima' : 'tanggal pengiriman' ?></label><input id="dari" type="date" name="dari" value="<?= esc($filters['from']) ?>"></div>
        <div class="form-group"><label for="sampai">Sampai <?= $filters['tab'] === 'masuk' ? 'tanggal diterima' : 'tanggal pengiriman' ?></label><input id="sampai" type="date" name="sampai" value="<?= esc($filters['to']) ?>"></div>
        <input type="hidden" name="tab" value="<?= esc($filters['tab']) ?>">
        <input type="hidden" name="per_page" value="<?= $filters['perPage'] ?>">
        <div class="filter-actions"><button type="submit" class="btn btn-secondary">Terapkan</button><a href="<?= site_url('distribusi-dokumen?tab='.$filters['tab']) ?>" class="btn btn-ghost">Reset</a></div>
    </form>
</section>

<?php if ($filters['tab'] === 'masuk'): ?>
<section class="panel register-panel distribution-table-panel">
    <div class="table-wrap"><table><thead><tr><th>No.</th><th>Pengirim</th><th>Perihal</th><th>Penerima</th><th>Hari / Tanggal Diterima</th><th>Jenis</th><th>Jumlah</th><th>Ekspedisi</th><th>Action</th></tr></thead><tbody>
        <?php if ($dokumen === []): ?><tr><td colspan="9"><div class="empty-state"><span>⇢</span><strong>Belum ada dokumen untuk didistribusikan</strong><p>Gunakan tombol Tambah Dokumen Masuk untuk membuat antrean distribusi baru.</p></div></td></tr>
        <?php else: ?>
            <?php $rowNumber = (($pager->getCurrentPage('distribusi_dokumen') - 1) * $filters['perPage']) + 1; ?>
            <?php foreach ($dokumen as $row): ?><tr>
                <td><strong><?= $rowNumber++ ?></strong></td>
                <td><strong><?= esc($row['pengirim']) ?></strong></td>
                <td class="cell-wrap"><?= esc($row['perihal'] ?: '-') ?></td>
                <td><?= esc($row['penerima'] ?: '-') ?></td>
                <td><div class="date-cell"><strong><?= esc($row['hari']) ?></strong><span><?= date('d-m-Y',strtotime($row['tanggal'])) ?></span></div></td>
                <td><?= esc($row['jenis']) ?></td>
                <td><?= number_format($row['jumlah'], 0, ',', '.') ?><?= ! empty($row['satuan_jumlah']) ? ' ' . esc($row['satuan_jumlah']) : '' ?></td>
                <td><?= esc($row['ekspedisi'] ?: '-') ?></td>
                <td><div class="table-actions">
                    <button type="button" class="icon-btn" title="Detail" data-open-detail-modal data-detail-url="<?= site_url('distribusi-dokumen/dokumen-masuk/'.$row['id']) ?>">⌕</button>
                    <button type="button" class="icon-btn" title="Ubah" data-open-edit-modal data-detail-url="<?= site_url('distribusi-dokumen/dokumen-masuk/'.$row['id']) ?>">✎</button>
                    <button type="button" class="icon-btn icon-btn-delete" title="Hapus" data-open-delete-modal data-delete-url="<?= site_url('distribusi-dokumen/dokumen-masuk/'.$row['id'].'/hapus') ?>" data-delete-label="Dokumen dari <?= esc($row['pengirim'], 'attr') ?>" data-delete-locked="0">×</button>
                    <button class="distribution-action-button" type="button" data-open-distribution-action data-action-url="<?= site_url('distribusi-dokumen/'.$row['id']) ?>">Proses</button>
                </div></td>
            </tr><?php endforeach ?>
        <?php endif ?>
    </tbody></table></div>
    <div class="table-list-footer"><form method="get" action="<?= site_url('distribusi-dokumen') ?>" class="table-length-form"><input type="hidden" name="tab" value="masuk"><input type="hidden" name="q" value="<?= esc($filters['keyword']) ?>"><input type="hidden" name="dari" value="<?= esc($filters['from']) ?>"><input type="hidden" name="sampai" value="<?= esc($filters['to']) ?>"><label for="distribusi_per_page">Tampilkan</label><select id="distribusi_per_page" name="per_page" aria-label="Jumlah baris per halaman" data-table-length><?php foreach ([10, 20, 50, 100] as $size): ?><option value="<?= $size ?>" <?= $filters['perPage'] === $size ? 'selected' : '' ?>><?= $size ?></option><?php endforeach ?></select><span>data</span></form><?php if ($dokumen !== []): ?><div class="pagination-wrap"><?= $pager->links('distribusi_dokumen', 'default_full') ?></div><?php endif ?></div>
</section>
<?php else: ?>
<section class="panel register-panel distribution-table-panel distribution-outgoing-panel">
    <div class="table-wrap"><table><thead><tr><th>No.</th><th>Jenis</th><th>Jumlah Dokumen</th><th>Nama Ekspedisi</th><th>Pelaksana</th><th>UP</th><th>Alamat Penerima</th><th>Progres</th><th>Action</th></tr></thead><tbody>
        <?php if ($dokumenKeluar === []): ?><tr><td colspan="9"><div class="empty-state"><span>⇢</span><strong>Belum ada Dokumen Keluar untuk diproses</strong><p>Dokumen dari Progres Dokumen Agendaris akan muncul otomatis di bagian ini.</p></div></td></tr>
        <?php else: ?>
            <?php $outgoingNumber = (($pagerKeluar->getCurrentPage('distribusi_keluar') - 1) * $filters['perPage']) + 1; ?>
            <?php foreach ($dokumenKeluar as $row): ?>
                <?php $progressComplete = $row['progres'] === 'Diambil Ekspedisi'; ?>
                <tr>
                    <td><strong><?= $outgoingNumber++ ?></strong></td>
                    <td><?= esc($row['jenis_surat']) ?></td>
                    <td><?= esc(($row['jumlah_dokumen'] ?? null) ?: '-') ?></td>
                    <td><?= esc(($row['nama_ekspedisi'] ?? null) ?: '-') ?></td>
                    <td><?= esc($row['pelaksana'] ?: '-') ?></td>
                    <td><?= esc($row['up'] ?: '-') ?></td>
                    <td class="cell-wrap"><?= esc($row['alamat_penerima']) ?></td>
                    <td><span class="pickup-status <?= $progressComplete ? 'completed' : 'pending' ?>"><?= esc($row['progres'] ?: 'Menunggu Ekspedisi') ?></span></td>
                    <td><button class="distribution-action-button" type="button" data-open-outgoing-distribution data-action-url="<?= site_url('distribusi-dokumen/surat-keluar/'.$row['id']) ?>">Proses</button></td>
                </tr>
            <?php endforeach ?>
        <?php endif ?>
    </tbody></table></div>
    <div class="table-list-footer"><form method="get" action="<?= site_url('distribusi-dokumen') ?>" class="table-length-form"><input type="hidden" name="tab" value="keluar"><input type="hidden" name="q" value="<?= esc($filters['keyword']) ?>"><input type="hidden" name="dari" value="<?= esc($filters['from']) ?>"><input type="hidden" name="sampai" value="<?= esc($filters['to']) ?>"><label for="distribusi_keluar_per_page">Tampilkan</label><select id="distribusi_keluar_per_page" name="per_page" aria-label="Jumlah Surat Keluar per halaman" data-table-length><?php foreach ([10, 20, 50, 100] as $size): ?><option value="<?= $size ?>" <?= $filters['perPage'] === $size ? 'selected' : '' ?>><?= $size ?></option><?php endforeach ?></select><span>data</span></form><?php if ($dokumenKeluar !== []): ?><div class="pagination-wrap"><?= $pagerKeluar->links('distribusi_keluar', 'default_full') ?></div><?php endif ?></div>
</section>
<?php endif ?>
<?= view('components/distribution_action_modal') ?>
<?= view('components/distribution_outgoing_modal') ?>
<?= $this->endSection() ?>
