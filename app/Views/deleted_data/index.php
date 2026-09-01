<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<section class="heading-actions">
    <div>
        <span class="eyebrow">ADMINISTRATOR</span>
        <h1>Data Terhapus</h1>
        <p>Data yang dihapus oleh role lain dapat dipulihkan atau dihapus permanen oleh Administrator.</p>
    </div>
</section>

<section class="account-stat-grid" aria-label="Ringkasan data terhapus">
    <article><span>Total Data Terhapus</span><strong><?= number_format($total, 0, ',', '.') ?></strong></article>
    <article><span>Modul Terdampak</span><strong><?= number_format(count(array_filter($counts)), 0, ',', '.') ?></strong></article>
</section>

<section class="panel filter-panel account-filter-panel">
    <form action="<?= site_url('data-terhapus') ?>" method="get" class="account-filter-form">
        <div class="form-group">
            <label for="deletedDataType">Jenis data</label>
            <select id="deletedDataType" name="jenis">
                <option value="">Semua jenis data</option>
                <?php foreach ($resources as $type => $resource): ?>
                    <option value="<?= esc($type) ?>" <?= $selectedType === $type ? 'selected' : '' ?>><?= esc($resource['label']) ?> (<?= number_format($counts[$type] ?? 0, 0, ',', '.') ?>)</option>
                <?php endforeach ?>
            </select>
        </div>
        <div class="filter-actions">
            <button class="btn btn-outline" type="submit">Terapkan</button>
            <a class="btn btn-ghost" href="<?= site_url('data-terhapus') ?>">Reset</a>
        </div>
    </form>
</section>

<section class="panel account-table-panel">
    <div class="table-wrap">
        <table>
            <thead><tr><th>No.</th><th>Jenis Data</th><th>Data</th><th>Dihapus Oleh</th><th>Waktu Dihapus</th><th>Aksi</th></tr></thead>
            <tbody>
            <?php if ($records === []): ?>
                <tr><td colspan="6"><div class="empty-state compact"><span>↶</span><strong>Tidak ada data terhapus</strong><p>Data yang dihapus role lain akan tersedia di halaman ini.</p></div></td></tr>
            <?php else: ?>
                <?php foreach ($records as $index => $record): ?>
                    <tr>
                        <td><strong><?= $index + 1 ?></strong></td>
                        <td><span class="account-role admin"><?= esc($record['module']) ?></span></td>
                        <td><strong><?= esc($record['label']) ?></strong></td>
                        <td>
                            <span class="deleted-by-marker"><?= esc(\Config\UserRoles::label($record['deleted_by_role']) ?: 'Role tidak tercatat') ?></span>
                            <small class="deleted-by-name"><?= esc($record['deleted_by_name'] !== '' ? $record['deleted_by_name'] : 'Nama pengguna tidak tercatat') ?></small>
                        </td>
                        <td><?= $record['deleted_at'] !== '' ? date('d-m-Y H:i', strtotime($record['deleted_at'])) . ' WIB' : '-' ?></td>
                        <td>
                            <div class="deleted-data-actions">
                                <form method="post" action="<?= site_url('data-terhapus/' . $record['type'] . '/' . $record['id'] . '/pulihkan') ?>" onsubmit="return confirm('Pulihkan data ini?')">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="btn btn-outline">↶ Pulihkan</button>
                                </form>
                                <form method="post" action="<?= site_url('data-terhapus/' . $record['type'] . '/' . $record['id'] . '/hapus-permanen') ?>" onsubmit="return confirm('Hapus permanen data ini? Data tidak dapat dipulihkan kembali.')">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="btn btn-delete">Hapus permanen</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach ?>
            <?php endif ?>
            </tbody>
        </table>
    </div>
</section>

<?= $this->endSection() ?>
