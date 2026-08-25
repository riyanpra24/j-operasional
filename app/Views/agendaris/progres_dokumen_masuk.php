<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<section class="page-heading">
    <p class="eyebrow">AGENDARIS</p>
    <h1>Progres Dokumen</h1>
    <p>Pantau proses dokumen masuk dan dokumen keluar dalam satu halaman terhubung.</p>
</section>

<?= view('agendaris/progres_tabs', ['activeTab' => 'masuk']) ?>

<section class="panel filter-panel">
    <form method="get" action="<?= site_url('agendaris/progres-dokumen') ?>" class="agendaris-filter-form">
        <div class="form-group search-group">
            <label for="incoming_progress_q">Cari dokumen</label>
            <div class="input-with-icon"><span>⌕</span><input id="incoming_progress_q" type="search" name="q" value="<?= esc($filters['keyword']) ?>" placeholder="Pengirim, perihal, penerima, jenis..."></div>
        </div>
        <div class="form-group">
            <label for="incoming_progress_filter">Progres</label>
            <select id="incoming_progress_filter" name="progres">
                <option value="">Semua progres</option>
                <option value="menunggu" <?= $filters['progres'] === 'menunggu' ? 'selected' : '' ?>>Menunggu Penyerahan</option>
                <option value="diserahkan" <?= $filters['progres'] === 'diserahkan' ? 'selected' : '' ?>>Sudah Diserahkan</option>
            </select>
        </div>
        <input type="hidden" name="per_page" value="<?= $filters['perPage'] ?>">
        <div class="filter-actions"><button type="submit" class="btn btn-secondary">Terapkan</button><a href="<?= site_url('agendaris/progres-dokumen') ?>" class="btn btn-ghost">Reset</a></div>
    </form>
</section>

<section class="panel register-panel progress-document-panel">
    <div class="table-wrap">
        <table>
            <thead><tr><th>No.</th><th>Pengirim</th><th>Perihal</th><th>Penerima</th><th>Tanggal Diterima</th><th>Jenis</th><th>Penyerahan</th><th>Waktu Penyerahan</th><th>Progres</th><th>Aksi</th></tr></thead>
            <tbody>
            <?php if ($dokumen === []): ?>
                <tr><td colspan="10"><div class="empty-state"><span>⇢</span><strong>Belum ada Progres Dokumen Masuk</strong><p>Ubah filter pencarian atau tunggu data Dokumen Masuk dari Security.</p></div></td></tr>
            <?php else: ?>
                <?php $rowNumber = (($pager->getCurrentPage('progres_dokumen_masuk') - 1) * $filters['perPage']) + 1; ?>
                <?php foreach ($dokumen as $row): ?>
                    <?php $completed = trim((string) ($row['pengambilan'] ?? '')) !== ''; ?>
                    <tr>
                        <td><strong><?= $rowNumber++ ?></strong></td>
                        <td><strong><?= esc($row['pengirim']) ?></strong></td>
                        <td class="cell-wrap"><?= esc($row['perihal'] ?: '-') ?></td>
                        <td><?= esc($row['penerima'] ?: '-') ?></td>
                        <td><?= date('d-m-Y', strtotime($row['tanggal'])) ?></td>
                        <td><?= esc($row['jenis']) ?></td>
                        <td><?= esc($row['pengambilan'] ?: '-') ?></td>
                        <td><?= $row['penyerahan_at'] ? date('d-m-Y H:i', strtotime($row['penyerahan_at'])) . ' WIB' : '-' ?></td>
                        <td><span class="pickup-status <?= $completed ? 'completed' : 'pending' ?>"><?= $completed ? 'Sudah Diserahkan' : 'Menunggu Penyerahan' ?></span></td>
                        <td><button type="button" class="icon-btn" title="Detail" data-incoming-progress-view data-incoming-progress-url="<?= site_url('agendaris/progres-dokumen-masuk/'.$row['id']) ?>">⌕</button></td>
                    </tr>
                <?php endforeach ?>
            <?php endif ?>
            </tbody>
        </table>
    </div>
    <div class="table-list-footer">
        <form method="get" action="<?= site_url('agendaris/progres-dokumen') ?>" class="table-length-form">
            <input type="hidden" name="q" value="<?= esc($filters['keyword']) ?>">
            <input type="hidden" name="progres" value="<?= esc($filters['progres']) ?>">
            <label for="incoming_progress_per_page">Tampilkan</label>
            <select id="incoming_progress_per_page" name="per_page" data-table-length><?php foreach ([10, 20, 50, 100] as $size): ?><option value="<?= $size ?>" <?= $filters['perPage'] === $size ? 'selected' : '' ?>><?= $size ?></option><?php endforeach ?></select>
            <span>data</span>
        </form>
        <?php if ($dokumen !== []): ?><div class="pagination-wrap"><?= $pager->links('progres_dokumen_masuk', 'default_full') ?></div><?php endif ?>
    </div>
</section>

<?= view('agendaris/progres_masuk_detail_modal') ?>
<?= $this->endSection() ?>
