<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<section class="page-heading heading-actions">
    <div>
        <p class="eyebrow">AGENDARIS</p>
        <h1>Progres Dokumen</h1>
        <p>Kelola Dokumen Masuk dan tandai sebagai Selesai untuk memindahkannya ke arsip Dokumen Masuk.</p>
    </div>
    <div class="heading-button-group agendaris-heading-actions">
        <form method="post" action="<?= site_url('agendaris/progres-dokumen-masuk/sinkronkan') ?>">
            <?= csrf_field() ?>
            <button type="submit" class="btn btn-secondary btn-agendaris-sync" title="Buat ulang Dokumen Masuk yang hilang dari data Security">↻ Sinkronkan</button>
        </form>
        <button type="button" class="btn btn-primary" data-agendaris-add>＋ Tambah Dokumen Masuk</button>
    </div>
</section>

<?= view('agendaris/progres_tabs', ['activeTab' => 'masuk']) ?>

<section class="panel filter-panel">
    <form method="get" action="<?= site_url('agendaris/progres-dokumen') ?>" class="agendaris-filter-form">
        <div class="form-group search-group"><label for="incoming_progress_q">Cari dokumen</label><div class="input-with-icon"><span>⌕</span><input id="incoming_progress_q" type="search" name="q" value="<?= esc($filters['keyword']) ?>" placeholder="Nomor Agendaris, pengirim, perihal, penerima..."></div></div>
        <div class="form-group"><label for="incoming_progress_jenis">Jenis</label><select id="incoming_progress_jenis" name="jenis"><option value="">Semua jenis</option><?php foreach ($jenisOptions as $option): ?><option value="<?= esc($option) ?>" <?= $filters['jenis'] === $option ? 'selected' : '' ?>><?= esc($option) ?></option><?php endforeach ?></select></div>
        <div class="form-group"><label for="incoming_progress_dari">Dari tanggal diterima</label><input id="incoming_progress_dari" type="date" name="dari" value="<?= esc($filters['from']) ?>"></div>
        <div class="form-group"><label for="incoming_progress_sampai">Sampai tanggal diterima</label><input id="incoming_progress_sampai" type="date" name="sampai" value="<?= esc($filters['to']) ?>"></div>
        <input type="hidden" name="per_page" value="<?= $filters['perPage'] ?>">
        <div class="filter-actions"><button type="submit" class="btn btn-secondary">Terapkan</button><a href="<?= site_url('agendaris/progres-dokumen') ?>" class="btn btn-ghost">Reset</a></div>
    </form>
</section>

<section class="panel register-panel agendaris-table-panel progress-document-panel">
    <div class="table-wrap"><table><thead><tr><th>No.</th><th>Sumber</th><th>Nomor Agendaris</th><th>Tanggal Agendaris</th><th>Tanggal Surat</th><th>Nomor Surat</th><th>Perihal Surat</th><th>Pengirim</th><th>Tanggal Diterima</th><th>Progres</th><th>Aksi</th></tr></thead><tbody>
        <?php if ($agenda === []): ?>
            <tr><td colspan="11"><div class="empty-state"><span>▦</span><strong>Belum ada Dokumen Masuk</strong><p>Tambahkan Dokumen Masuk baru atau sinkronkan dari data Security.</p></div></td></tr>
        <?php else: ?>
            <?php $rowNumber = (($pager->getCurrentPage('progres_dokumen_masuk') - 1) * $filters['perPage']) + 1; ?>
            <?php foreach ($agenda as $row): ?><tr>
                <td><strong><?= $rowNumber++ ?></strong></td>
                <td><span class="agenda-source <?= $row['dokumen_masuk_id'] !== null ? 'security' : 'manual' ?>"><?= $row['dokumen_masuk_id'] !== null ? 'Security' : 'Input Manual' ?></span></td>
                <td><strong><?= $row['nomor_agendaris'] ? esc($row['nomor_agendaris']) : '<span class="agenda-incomplete">Belum dibuat</span>' ?></strong></td>
                <td><?= $row['tanggal_agendaris'] ? date('d-m-Y', strtotime($row['tanggal_agendaris'])) : '<span class="agenda-incomplete">Belum diisi</span>' ?></td>
                <td><?= $row['tanggal_surat'] ? date('d-m-Y', strtotime($row['tanggal_surat'])) : '<span class="agenda-incomplete">Belum diisi</span>' ?></td>
                <td><strong><?= $row['nomor_surat'] ? esc($row['nomor_surat']) : '<span class="agenda-incomplete">Belum diisi</span>' ?></strong></td>
                <td class="cell-wrap"><?= esc($row['perihal_surat']) ?></td>
                <td><?= esc($row['pengirim']) ?></td>
                <td><?= date('d-m-Y', strtotime($row['tanggal_diterima'])) ?></td>
                <?php $completed = ($row['progres'] ?? '') === 'Selesai'; ?>
                <td><span class="pickup-status <?= $completed ? 'completed' : 'pending' ?>"><?= esc($row['progres'] ?? 'Menunggu Penyelesaian') ?></span></td>
                <td><div class="table-actions">
                    <button type="button" class="icon-btn" title="Detail" data-agendaris-view data-agendaris-url="<?= site_url('agendaris/progres-dokumen-masuk/'.$row['id']) ?>">⌕</button>
                    <button type="button" class="icon-btn" title="Ubah" data-agendaris-edit data-agendaris-url="<?= site_url('agendaris/progres-dokumen-masuk/'.$row['id']) ?>">✎</button>
                    <button type="button" class="icon-btn icon-btn-delete" title="Hapus" data-agendaris-delete data-delete-url="<?= site_url('agendaris/progres-dokumen-masuk/'.$row['id'].'/hapus') ?>" data-delete-label="<?= esc($row['nomor_surat'] ?: 'Belum diisi', 'attr') ?>">×</button>
                </div></td>
            </tr><?php endforeach ?>
        <?php endif ?>
    </tbody></table></div>
    <div class="table-list-footer">
        <form method="get" action="<?= site_url('agendaris/progres-dokumen') ?>" class="table-length-form">
            <input type="hidden" name="q" value="<?= esc($filters['keyword']) ?>">
            <input type="hidden" name="jenis" value="<?= esc($filters['jenis']) ?>">
            <input type="hidden" name="dari" value="<?= esc($filters['from']) ?>">
            <input type="hidden" name="sampai" value="<?= esc($filters['to']) ?>">
            <label for="incoming_progress_per_page">Tampilkan</label>
            <select id="incoming_progress_per_page" name="per_page" aria-label="Jumlah baris per halaman" data-table-length><?php foreach ([10, 20, 50, 100] as $size): ?><option value="<?= $size ?>" <?= $filters['perPage'] === $size ? 'selected' : '' ?>><?= $size ?></option><?php endforeach ?></select>
            <span>data</span>
        </form>
        <?php if ($agenda !== []): ?><div class="pagination-wrap"><?= $pager->links('progres_dokumen_masuk', 'default_full') ?></div><?php endif ?>
    </div>
</section>

<?= view('agendaris/form_modal') ?>
<?= view('agendaris/detail_modal', ['readOnly' => false]) ?>
<?= view('agendaris/delete_modal') ?>
<?= $this->endSection() ?>
