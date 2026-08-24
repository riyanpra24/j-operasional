<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<section class="page-heading heading-actions distribution-heading">
    <div><p class="eyebrow">ALUR DOKUMEN</p><h1>Distribusi Dokumen</h1><p>Dokumen Masuk dan Surat Keluar tersedia otomatis untuk diproses pada halaman ini.</p></div>
    <a href="<?= site_url('dokumen-masuk') ?>" class="btn btn-primary">▤ Buka Dokumen Masuk</a>
</section>

<section class="distribution-sync-card">
    <span class="sync-icon">↻</span>
    <div><strong>Sinkron otomatis</strong><p>Surat Keluar tersimpan pada antrean distribusi terpisah dan terhubung melalui foreign key.</p></div>
    <div class="sync-total"><strong><?= number_format($totalData + $totalKeluar, 0, ',', '.') ?></strong><span>Total distribusi</span></div>
</section>

<section class="panel filter-panel">
    <form method="get" action="<?= site_url('distribusi-dokumen') ?>" class="distribution-filter-form">
        <div class="form-group search-group"><label for="q">Cari dokumen</label><div class="input-with-icon"><span>⌕</span><input id="q" type="search" name="q" value="<?= esc($filters['keyword']) ?>" placeholder="Pengirim, perihal, penerima, jenis..."></div></div>
        <div class="form-group"><label for="dari">Dari tanggal dokumen</label><input id="dari" type="date" name="dari" value="<?= esc($filters['from']) ?>"></div>
        <div class="form-group"><label for="sampai">Sampai tanggal dokumen</label><input id="sampai" type="date" name="sampai" value="<?= esc($filters['to']) ?>"></div>
        <input type="hidden" name="per_page" value="<?= $filters['perPage'] ?>">
        <div class="filter-actions"><button type="submit" class="btn btn-secondary">Terapkan</button><a href="<?= site_url('distribusi-dokumen') ?>" class="btn btn-ghost">Reset</a></div>
    </form>
</section>

<section class="panel register-panel distribution-table-panel">
    <div class="distribution-section-title"><div><span>01</span><h2>Distribusi Dokumen Masuk</h2></div><small>Menunggu pencatatan pengambilan</small></div>
    <div class="table-wrap"><table><thead><tr><th>No.</th><th>Pengirim</th><th>Perihal</th><th>Penerima</th><th>Hari / Tanggal Diterima</th><th>Jenis</th><th>Jumlah</th><th>Ekspedisi</th><th>Action</th></tr></thead><tbody>
        <?php if ($dokumen === []): ?><tr><td colspan="9"><div class="empty-state"><span>⇢</span><strong>Belum ada dokumen untuk didistribusikan</strong><p>Dokumen yang dimasukkan melalui menu Dokumen Masuk akan muncul otomatis.</p></div></td></tr>
        <?php else: ?>
            <?php $rowNumber = (($pager->getCurrentPage('distribusi_dokumen') - 1) * $filters['perPage']) + 1; ?>
            <?php foreach ($dokumen as $row): ?><tr>
                <td><strong><?= $rowNumber++ ?></strong></td>
                <td><strong><?= esc($row['pengirim']) ?></strong></td>
                <td class="cell-wrap"><?= esc($row['perihal'] ?: '-') ?></td>
                <td><?= esc($row['penerima'] ?: '-') ?></td>
                <td><div class="date-cell"><strong><?= esc($row['hari']) ?></strong><span><?= date('d-m-Y',strtotime($row['tanggal'])) ?></span></div></td>
                <td><?= esc($row['jenis']) ?></td>
                <td><?= number_format($row['jumlah'],0,',','.') ?></td>
                <td><?= esc($row['ekspedisi'] ?: '-') ?></td>
                <td><button class="distribution-action-button" type="button" data-open-distribution-action data-action-url="<?= site_url('distribusi-dokumen/'.$row['id']) ?>">Proses</button></td>
            </tr><?php endforeach ?>
        <?php endif ?>
    </tbody></table></div>
    <div class="table-list-footer"><form method="get" action="<?= site_url('distribusi-dokumen') ?>" class="table-length-form"><input type="hidden" name="q" value="<?= esc($filters['keyword']) ?>"><input type="hidden" name="dari" value="<?= esc($filters['from']) ?>"><input type="hidden" name="sampai" value="<?= esc($filters['to']) ?>"><label for="distribusi_per_page">Tampilkan</label><select id="distribusi_per_page" name="per_page" aria-label="Jumlah baris per halaman" data-table-length><?php foreach ([10, 20, 50, 100] as $size): ?><option value="<?= $size ?>" <?= $filters['perPage'] === $size ? 'selected' : '' ?>><?= $size ?></option><?php endforeach ?></select><span>data</span></form><?php if ($dokumen !== []): ?><div class="pagination-wrap"><?= $pager->links('distribusi_dokumen', 'default_full') ?></div><?php endif ?></div>
</section>

<section class="panel register-panel distribution-table-panel distribution-outgoing-panel">
    <div class="distribution-section-title"><div><span>02</span><h2>Dokumen Surat Keluar</h2></div><small>Data otomatis dari Agendaris → Surat Keluar</small></div>
    <div class="table-wrap"><table><thead><tr><th>No.</th><th>Jenis</th><th>Pelaksana</th><th>UP</th><th>Alamat Penerima</th><th>Progres</th><th>Action</th></tr></thead><tbody>
        <?php if ($dokumenKeluar === []): ?><tr><td colspan="7"><div class="empty-state"><span>⇢</span><strong>Belum ada Surat Keluar</strong><p>Setiap Surat Keluar yang ditambahkan akan muncul otomatis di bagian ini.</p></div></td></tr>
        <?php else: ?>
            <?php $outgoingNumber = (($pagerKeluar->getCurrentPage('distribusi_keluar') - 1) * $filters['perPage']) + 1; ?>
            <?php foreach ($dokumenKeluar as $row): ?>
                <?php $progressComplete = $row['progres'] === 'Diambil Ekspedisi'; ?>
                <tr>
                    <td><strong><?= $outgoingNumber++ ?></strong></td>
                    <td><?= esc($row['jenis_surat']) ?></td>
                    <td><?= esc($row['pelaksana'] ?: '-') ?></td>
                    <td><?= esc($row['up'] ?: '-') ?></td>
                    <td class="cell-wrap"><?= esc($row['alamat_penerima']) ?></td>
                    <td><span class="pickup-status <?= $progressComplete ? 'completed' : 'pending' ?>"><?= esc($row['progres'] ?: 'Menunggu Ekspedisi') ?></span></td>
                    <td><button class="distribution-action-button" type="button" data-open-outgoing-distribution data-action-url="<?= site_url('distribusi-dokumen/surat-keluar/'.$row['id']) ?>">Kelola</button></td>
                </tr>
            <?php endforeach ?>
        <?php endif ?>
    </tbody></table></div>
    <div class="table-list-footer"><form method="get" action="<?= site_url('distribusi-dokumen') ?>" class="table-length-form"><input type="hidden" name="q" value="<?= esc($filters['keyword']) ?>"><input type="hidden" name="dari" value="<?= esc($filters['from']) ?>"><input type="hidden" name="sampai" value="<?= esc($filters['to']) ?>"><label for="distribusi_keluar_per_page">Tampilkan</label><select id="distribusi_keluar_per_page" name="per_page" aria-label="Jumlah Surat Keluar per halaman" data-table-length><?php foreach ([10, 20, 50, 100] as $size): ?><option value="<?= $size ?>" <?= $filters['perPage'] === $size ? 'selected' : '' ?>><?= $size ?></option><?php endforeach ?></select><span>data</span></form><?php if ($dokumenKeluar !== []): ?><div class="pagination-wrap"><?= $pagerKeluar->links('distribusi_keluar', 'default_full') ?></div><?php endif ?></div>
</section>
<?= view('components/distribution_action_modal') ?>
<?= view('components/distribution_outgoing_modal') ?>
<?= $this->endSection() ?>
