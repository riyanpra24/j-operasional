<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<section class="page-heading">
    <div>
        <p class="eyebrow">SDM &amp; TELLER</p>
        <h1><?= $historyMode ? 'Riwayat Dokumen Masuk' : 'Dokumen Masuk' ?></h1>
        <p><?= $historyMode ? 'Dokumen yang pernah diterima dan sudah diteruskan oleh' : 'Dokumen Agendaris dengan disposisi terakhir kepada' ?> <strong><?= esc($recipientName !== '' ? $recipientName : 'pengguna aktif') ?></strong>.</p>
    </div>
</section>

<section class="panel filter-panel">
    <form method="get" action="<?= esc($listUrl, 'attr') ?>" class="agendaris-filter-form">
        <div class="form-group search-group">
            <label for="sdm_document_q">Cari dokumen</label>
            <div class="input-with-icon"><span>⌕</span><input id="sdm_document_q" type="search" name="q" value="<?= esc($filters['keyword']) ?>" placeholder="Nomor surat, perihal, pengirim, atau jenis..."></div>
        </div>
        <div class="form-group">
            <label for="sdm_document_status">Status disposisi</label>
            <select id="sdm_document_status" name="status">
                <option value="">Semua status</option>
                <?php foreach ($statusOptions as $option): ?>
                    <option value="<?= esc($option, 'attr') ?>" <?= $filters['status'] === $option ? 'selected' : '' ?>><?= esc($option) ?></option>
                <?php endforeach ?>
            </select>
        </div>
        <input type="hidden" name="per_page" value="<?= $filters['perPage'] ?>">
        <div class="filter-actions"><button type="submit" class="btn btn-secondary">Terapkan</button><a href="<?= esc($listUrl, 'attr') ?>" class="btn btn-ghost">Reset</a></div>
    </form>
</section>

<section class="panel register-panel agendaris-table-panel">
    <div class="table-wrap">
        <table>
            <thead><tr><th>No.</th><th>Nomor Agendaris</th><th>Nomor Surat</th><th>Perihal Surat</th><th>Pengirim</th><th>Tanggal Diterima</th><th>Disposisi Terakhir</th><th>Status</th><th>Catatan / Instruksi</th><th>Berkas</th><th>Aksi</th></tr></thead>
            <tbody>
            <?php if ($documents === []): ?>
                <tr><td colspan="11"><div class="empty-state"><span aria-hidden="true">▤</span><strong><?= $historyMode ? 'Belum ada riwayat dokumen masuk' : 'Belum ada dokumen masuk untuk Anda' ?></strong><p><?= $historyMode ? 'Dokumen yang sudah Anda teruskan akan tersimpan di halaman ini.' : 'Dokumen akan muncul otomatis ketika nama Anda menjadi penerima disposisi terakhir.' ?></p></div></td></tr>
            <?php else: ?>
                <?php $rowNumber = (($pager->getCurrentPage($pagerGroup) - 1) * $filters['perPage']) + 1; ?>
                <?php foreach ($documents as $document): ?>
                    <?php
                    $statusClass = ['Menunggu' => 'pending', 'Diterima' => 'received', 'Diproses' => 'active', 'Diteruskan' => 'forwarded', 'Selesai' => 'completed'][$document['status_disposisi_terakhir']] ?? 'empty';
                    $canEditDisposition = ! $historyMode && mb_strtolower(trim((string) $document['disposisi_terakhir'])) === mb_strtolower(trim($recipientName));
                    $dispositions = [];
                    for ($step = 1; $step <= \Config\Disposition::MAX_STEPS; $step++) {
                        if (trim((string) ($document["disposisi_{$step}"] ?? '')) === '') continue;
                        $dispositions[] = [
                            'step' => $step,
                            'recipient' => $document["disposisi_{$step}"],
                            'status' => $document["disposisi_{$step}_status"] ?: 'Menunggu',
                            'date' => $document["disposisi_{$step}_waktu"] ?? '',
                            'note' => $document["disposisi_{$step}_catatan"] ?? '',
                        ];
                    }
                    $documentModalData = [
                        'id' => (int) $document['id'],
                        'update_url' => site_url('sdm/dokumen-masuk/' . $document['id']),
                        'nomor_agendaris' => $document['nomor_agendaris'] ?: 'Belum dibuat',
                        'nomor_surat' => $document['nomor_surat'] ?: 'Belum diisi',
                        'tanggal_surat' => $document['tanggal_surat'] ?? '',
                        'tanggal_diterima' => $document['tanggal_diterima'] ?? '',
                        'tanggal_agendaris' => $document['tanggal_agendaris'] ?? '',
                        'jenis' => $document['jenis'] ?? '',
                        'perihal_surat' => $document['perihal_surat'],
                        'pengirim' => $document['pengirim'],
                        'penerima' => $document['penerima'] ?? '',
                        'pengambilan' => $document['pengambilan'] ?? '',
                        'penyerahan_at' => $document['sumber_penyerahan_at'] ?? '',
                        'berkas_link' => $document['berkas_link'] ?? '',
                        'sumber_data' => $document['dokumen_masuk_id'] !== null ? 'Security · Dokumen Masuk' : 'Input Manual · Surat Masuk',
                        'progres' => $document['progres'] ?? 'Selesai',
                        'latest_step' => (int) $document['tahap_disposisi_terakhir'],
                        'latest_recipient' => $document['disposisi_terakhir'],
                        'latest_status' => $document['status_disposisi_terakhir'],
                        'latest_date' => $document['waktu_disposisi_terakhir'] ?? '',
                        'latest_note' => $document['catatan_disposisi_terakhir'] ?? '',
                        'can_edit_disposition' => $canEditDisposition,
                        'can_add_disposition' => (int) $document['tahap_disposisi_terakhir'] < \Config\Disposition::MAX_STEPS,
                        'next_step' => min(\Config\Disposition::MAX_STEPS, ((int) $document['tahap_disposisi_terakhir']) + 1),
                        'dispositions' => $dispositions,
                    ];
                    $documentModalJson = esc(json_encode($documentModalData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), 'attr');
                    ?>
                    <tr>
                        <td><strong><?= $rowNumber++ ?></strong></td>
                        <td><strong><?= $document['nomor_agendaris'] ? esc($document['nomor_agendaris']) : '<span class="agenda-incomplete">Belum dibuat</span>' ?></strong></td>
                        <td><strong><?= $document['nomor_surat'] ? esc($document['nomor_surat']) : '<span class="agenda-incomplete">Belum diisi</span>' ?></strong></td>
                        <td class="cell-wrap"><?= esc($document['perihal_surat']) ?></td>
                        <td><?= esc($document['pengirim']) ?></td>
                        <td><?= $document['tanggal_diterima'] ? date('d-m-Y', strtotime($document['tanggal_diterima'])) : '-' ?></td>
                        <td><div class="disposition-table-tracking"><strong><?= esc($document['disposisi_terakhir']) ?></strong><span><?= $document['waktu_disposisi_terakhir'] ? date('d-m-Y H:i', strtotime($document['waktu_disposisi_terakhir'])) . ' WIB' : 'Waktu belum dicatat' ?></span></div></td>
                        <td><em class="disposition-status-badge <?= $statusClass ?>"><?= esc($document['status_disposisi_terakhir']) ?></em></td>
                        <td class="cell-wrap"><?= $document['catatan_disposisi_terakhir'] ? esc($document['catatan_disposisi_terakhir']) : '<span class="agenda-incomplete">Belum ada catatan</span>' ?></td>
                        <td><?php if ($document['berkas_link']): ?><a class="btn btn-outline" href="<?= esc($document['berkas_link'], 'attr') ?>" target="_blank" rel="noopener noreferrer">Buka</a><?php else: ?><span class="agenda-incomplete">Tidak ada</span><?php endif ?></td>
                        <td><div class="table-actions"><button type="button" class="icon-btn" title="Lihat dokumen dan riwayat disposisi" data-sdm-document-view='<?= $documentModalJson ?>'>⌕</button><?php if ($canEditDisposition): ?><button type="button" class="icon-btn" title="Edit disposisi" data-sdm-document-edit='<?= $documentModalJson ?>'>✎</button><?php else: ?><span class="disposition-history-only">Riwayat</span><?php endif ?></div></td>
                    </tr>
                <?php endforeach ?>
            <?php endif ?>
            </tbody>
        </table>
    </div>
    <div class="table-list-footer">
        <form method="get" action="<?= esc($listUrl, 'attr') ?>" class="table-length-form">
            <input type="hidden" name="q" value="<?= esc($filters['keyword']) ?>">
            <input type="hidden" name="status" value="<?= esc($filters['status']) ?>">
            <label for="sdm_document_per_page">Tampilkan</label>
            <select id="sdm_document_per_page" name="per_page" aria-label="Jumlah baris per halaman" data-table-length><?php foreach ([10, 20, 50, 100] as $size): ?><option value="<?= $size ?>" <?= $filters['perPage'] === $size ? 'selected' : '' ?>><?= $size ?></option><?php endforeach ?></select>
            <span>data</span>
        </form>
        <?php if ($documents !== []): ?><div class="pagination-wrap"><?= $pager->links($pagerGroup, 'default_full') ?></div><?php endif ?>
    </div>
</section>

<?= view('sdm/document_modals', [
    'statusOptions' => $statusOptions,
    'recipientOptions' => $recipientOptions,
]) ?>

<?= $this->endSection() ?>
