<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<?php $months = [1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember']; ?>

<section class="page-heading export-document-heading simulation-page-heading">
    <div>
        <p class="eyebrow">AKUTANSI</p>
        <h1>Simulasi Hitung</h1>
        <p>Unggah LR Oracle, lalu unduh kertas kerja yang terisi otomatis.</p>
    </div>
    <div class="simulation-heading-actions">
        <button type="button" class="btn btn-primary" data-simulation-upload-open aria-haspopup="dialog" aria-controls="simulationUploadDialog">Isi Kertas Kerja</button>
    </div>
</section>

<section class="panel simulation-history" style="width:100%;max-width:none;margin-inline:0">
    <header class="simulation-history-heading">
        <div>
            <p class="eyebrow">HASIL PENGISIAN</p>
            <h2>Unduh Kertas Kerja</h2>
        </div>
        <span class="simulation-history-count"><?= count($simulations) ?> berkas</span>
    </header>
    <?php if (!$simulations): ?>
        <p class="simulation-empty">Belum ada hasil simulasi. Unggah LR Oracle untuk membuat kertas kerja pertama.</p>
    <?php else: ?>
        <div class="oracle-mapping-table-wrap" role="region" aria-label="Daftar hasil simulasi hitung" tabindex="0">
            <table class="oracle-mapping-table simulation-table">
                <thead>
                    <tr>
                        <th>Dibuat</th>
                        <th>Periode</th>
                        <th>Berkas Oracle</th>
                        <th>Baris bantu</th>
                        <th>Perlu dicek</th>
                        <th>RKA</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($simulations as $item): ?>
                        <?php $needsCheck = (int) ($item['unmapped_accounts'] ?? 0) > 0;
                        $rkaComplete = empty($item['missing_rka']); ?>
                        <tr>
                            <td><time class="simulation-created" datetime="<?= esc(str_replace(' ', 'T', (string) ($item['created_at'] ?? '')), 'attr') ?>"><?= esc($item['created_at'] ?? '') ?></time></td>
                            <td><span class="simulation-period-badge"><strong><?= esc($item['report_basis'] ?? '') ?></strong><span><?= esc(($months[(int) ($item['report_month'] ?? 0)] ?? '') . ' ' . ($item['report_year'] ?? '')) ?></span></span></td>
                            <td><span class="simulation-source-name" title="<?= esc($item['source_name'] ?? '', 'attr') ?>"><?= esc($item['source_name'] ?? '') ?></span></td>
                            <td><span class="simulation-stat simulation-stat-info"><b><?= (int) ($item['helper_rows'] ?? 0) ?></b> baris</span></td>
                            <td><span class="simulation-stat <?= $needsCheck ? 'simulation-stat-warning' : 'simulation-stat-success' ?>"><b><?= (int) ($item['unmapped_accounts'] ?? 0) ?></b> <?= $needsCheck ? 'perlu cek' : 'sesuai' ?></span></td>
                            <td><span class="simulation-stat <?= $rkaComplete ? 'simulation-stat-success' : 'simulation-stat-warning' ?>"><?= $rkaComplete ? '✓ Lengkap' : esc(count($item['missing_rka']) . ' unit belum ada') ?></span></td>
                            <td class="simulation-actions">
                                <a class="btn btn-secondary" href="<?= site_url('akutansi/simulasi-hitung/unduh/' . rawurlencode((string) $item['id'])) ?>">Unduh Excel</a>
                                <form method="post" action="<?= site_url('akutansi/simulasi-hitung/hapus/' . rawurlencode((string) $item['id'])) ?>" data-simulation-delete>
                                    <?= csrf_field() ?><input type="hidden" name="confirm_delete" value="1">
                                    <button type="submit" class="btn btn-danger-outline">Hapus</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach ?>
                </tbody>
            </table>
        </div>
    <?php endif ?>
</section>

<dialog id="simulationUploadDialog" class="lr-settings-dialog simulation-upload-dialog" aria-labelledby="simulationUploadTitle" data-auto-open="<?= $uploadError ? 'true' : 'false' ?>">
    <header class="lr-settings-header">
        <div>
            <p class="eyebrow">SIMULASI HITUNG</p>
            <h2 id="simulationUploadTitle">Pengisian Kertas Kerja</h2>
        </div>
        <button type="button" class="icon-btn" data-simulation-upload-close aria-label="Tutup pengisian kertas kerja">×</button>
    </header>
    <section class="simulation-intro-card simulation-upload-card">
        <span class="export-document-icon" aria-hidden="true">∑</span>
        <div>
            <p class="eyebrow">KERTAS KERJA REALISASI ANGGARAN</p>
            <h2>Isi dari LR Oracle</h2>
            <p>Unggah LR Oracle untuk mengisi template kertas kerja secara otomatis, lalu unduh hasilnya dari daftar di bawah.</p>
        </div>
    </section>
    <?php if ($uploadError): ?><div class="simulation-error" role="alert"><?= esc($uploadError) ?></div><?php endif ?>
    <form method="post" action="<?= site_url('akutansi/simulasi-hitung') ?>" enctype="multipart/form-data" class="export-document-form" data-simulation-upload-form>
        <?= csrf_field() ?>
        <div class="export-document-filters">
            <label><span>Jenis Laporan <b>*</b></span>
                <select name="jenis_laporan" class="lr-upload-select" required>
                    <option value="YTD">YTD — akumulasi tahun berjalan</option>
                    <option value="PTD">PTD — periode terpilih</option>
                </select>
            </label>
            <label><span>Bulan <b>*</b></span>
                <select name="bulan" class="lr-upload-select" required>
                    <?php foreach ($months as $number => $name): ?>
                        <option value="<?= $number ?>" <?= $number === $selectedMonth ? 'selected' : '' ?>><?= esc($name) ?></option>
                    <?php endforeach ?>
                </select>
            </label>
            <label><span>Tahun <b>*</b></span><input type="number" name="tahun" class="lr-upload-select" min="2000" max="2100" step="1" value="2026" required></label>
        </div>
        <label class="simulation-file-label" for="simulationOracleFile">Berkas LR Oracle (.xlsx, maksimal 5 MB) <b>*</b></label>
        <input id="simulationOracleFile" type="file" name="oracle_excel" class="lr-upload-input" accept=".xlsx" required>
        <footer class="export-document-actions"><button type="button" class="btn btn-secondary" data-simulation-upload-close>Batal</button><button type="submit" class="btn btn-primary">Proses Kertas Kerja</button></footer>
    </form>
</dialog>

<?= $this->endSection() ?>
