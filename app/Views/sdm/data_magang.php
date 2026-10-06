<?php

/** @var list<array<string, mixed>> $records */
/** @var list<string> $units */
/** @var list<string> $types */
/** @var array<string, mixed> $filters */
/** @var int $nextContractSequence */
/** @var int $total */
/** @var \CodeIgniter\Pager\PagerInterface $pager */
$baseUrl = site_url('sdm/data-magang');
$query = service('request')->getServer('QUERY_STRING');
$returnUrl = $baseUrl . ($query ? '?' . $query : '');
$isAdmin = (string) session()->get('auth_role') === 'admin';
$modalRecords = [];
foreach ($records as $record) {
    $modalRecords[(string) $record['id']] = [
        'id' => (int) $record['id'],
        'nomor' => $record['nomor'],
        'nama_magang' => $record['nama_magang'],
        'nomor_kontrak_kerja' => $record['nomor_kontrak_kerja'],
        'unit_kerja' => $record['unit_kerja'],
        'jenis_magang' => $record['jenis_magang'],
        'tanggal_mulai' => $record['tanggal_mulai'],
        'tanggal_selesai' => $record['tanggal_selesai'],
        'link_pkk' => $record['link_pkk'],
        'keterangan' => $record['keterangan'],
    ];
}
$formatDate = static fn(?string $value): string => $value ? date('d-m-Y', strtotime($value)) : 'Belum diisi';
?>
<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>


<?php if (session()->getFlashdata('errors')): ?>
    <section class="modal-alert magang-page-error" role="alert"><strong>Data belum dapat disimpan.</strong>
        <ul><?php foreach (session()->getFlashdata('errors') as $error): ?><li><?= esc($error) ?></li><?php endforeach ?></ul>
    </section>
<?php endif ?>

<section class="magang-summary-grid" aria-label="Ringkasan status magang">
    <article class="magang-summary-card is-active">
        <span class="magang-summary-icon" aria-hidden="true">✓</span>
        <div><small>MAGANG AKTIF</small><strong><?= number_format($activeTotal, 0, ',', '.') ?></strong></div>
    </article>
    <article class="magang-summary-card is-completed">
        <span class="magang-summary-icon" aria-hidden="true">×</span>
        <div><small>MAGANG SELESAI</small><strong><?= number_format($completedTotal, 0, ',', '.') ?></strong></div>
    </article>
</section>

<section class="panel filter-panel magang-filter-panel">
    <form action="<?= $baseUrl ?>" method="get" class="magang-filter-form">
        <div class="form-group search-group"><label for="magangSearch">Cari peserta</label>
            <div class="input-with-icon"><span>⌕</span><input id="magangSearch" name="q" type="search" value="<?= esc($filters['keyword']) ?>" placeholder="Nama, nomor kontrak, unit kerja, atau jenis magang"></div>
        </div>
        <div class="form-group"><label for="magangUnit">Unit Kerja</label><select id="magangUnit" name="unit_kerja">
                <option value="">Semua unit kerja</option><?php foreach ($units as $option): ?><option value="<?= esc($option, 'attr') ?>" <?= $filters['unit'] === $option ? 'selected' : '' ?>><?= esc($option) ?></option><?php endforeach ?>
            </select></div>
        <div class="form-group"><label for="magangType">Jenis Magang</label><select id="magangType" name="jenis">
                <option value="">Semua jenis</option><?php foreach ($types as $option): ?><option value="<?= esc($option, 'attr') ?>" <?= $filters['type'] === $option ? 'selected' : '' ?>><?= esc($option) ?></option><?php endforeach ?>
            </select></div>
        <div class="form-group"><label for="magangStatus">Status</label><select id="magangStatus" name="status">
                <option value="">Semua status</option><?php foreach (['Aktif', 'Selesai', 'Belum Mulai', 'Belum Lengkap'] as $option): ?><option value="<?= esc($option, 'attr') ?>" <?= $filters['status'] === $option ? 'selected' : '' ?>><?= esc($option) ?></option><?php endforeach ?>
            </select></div>
        <?= view('components/list_order_filter', ['id' => 'magangOrder', 'value' => $filters['order'], 'label' => 'Urutan nomor kontrak', 'defaultLabel' => 'Nomor terbaru ke terlama', 'newestLabel' => 'Nomor terbaru ke terlama', 'oldestLabel' => 'Nomor terlama ke terbaru']) ?>
        <input type="hidden" name="per_page" value="<?= (int) $filters['perPage'] ?>">
        <div class="filter-actions"><button class="btn btn-secondary" type="submit">Terapkan</button><a class="btn btn-ghost" href="<?= $baseUrl ?>">Reset</a>
            <details class="magang-action-menu">
                <summary aria-label="Menu tindakan data magang" title="Menu tindakan"><svg aria-hidden="true" viewBox="0 0 24 24" focusable="false">
                        <rect x="3" y="3" width="7" height="7" rx="1.4"></rect>
                        <rect x="14" y="3" width="7" height="7" rx="1.4"></rect>
                        <rect x="3" y="14" width="7" height="7" rx="1.4"></rect>
                        <rect x="14" y="14" width="7" height="7" rx="1.4"></rect>
                    </svg></summary>
                <div class="magang-action-menu-popover" role="menu"><button type="button" role="menuitem" data-magang-import>Import Excel</button><button type="button" role="menuitem" data-magang-create>Tambah Peserta</button><?php if ($isAdmin): ?><span aria-hidden="true"></span><button type="button" role="menuitem" class="is-danger" data-magang-delete-all>Hapus Semua Data</button><?php endif ?></div>
            </details>
        </div>
    </form>
</section>

<section class="panel register-panel magang-register-panel" id="magangTableArea" aria-live="polite">
    <header class="panel-header magang-register-header">
        <div>
            <h2>Database Peserta Magang</h2>
            <p>Status dihitung otomatis dari tanggal awal dan selesai magang.</p>
        </div><span><?= number_format($total, 0, ',', '.') ?> peserta</span>
    </header>
    <div class="table-wrap">
        <table class="magang-table">
            <colgroup>
                <col class="magang-col-participant">
                <col class="magang-col-contract">
                <col class="magang-col-unit">
                <col class="magang-col-type">
                <col class="magang-col-period">
                <col class="magang-col-status">
                <col class="magang-col-note">
                <col class="magang-col-actions">
            </colgroup>
            <thead>
                <tr>
                    <th>Peserta</th>
                    <th>Nomor Kontrak Kerja</th>
                    <th>Unit Kerja</th>
                    <th>Jenis Magang</th>
                    <th>Periode Magang</th>
                    <th>Status</th>
                    <th class="magang-note-header">Keterangan</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($records === []): ?>
                    <tr>
                        <td colspan="8">
                            <div class="empty-state"><span>♙</span><strong>Belum ada data magang</strong>
                                <p>Tambahkan peserta secara manual atau impor Excel Database Magang.</p><button class="btn btn-primary btn-sm" type="button" data-magang-create>Tambah Peserta</button>
                            </div>
                        </td>
                    </tr>
                    <?php else: foreach ($records as $index => $record): ?>
                        <tr data-magang-record="<?= esc(json_encode($modalRecords[(string) $record['id']], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT), 'attr') ?>">
                            <td>
                                <div class="magang-name"><strong><?= esc($record['nama_magang']) ?></strong></div>
                            </td>
                            <td><strong class="magang-contract"><?= esc($record['nomor_kontrak_kerja']) ?></strong></td>
                            <td><?= esc($record['unit_kerja']) ?></td>
                            <td><?= esc($record['jenis_magang']) ?></td>
                            <td>
                                <div class="magang-period"><strong><?= $formatDate($record['tanggal_mulai']) ?></strong><span>s.d. <?= $formatDate($record['tanggal_selesai']) ?></span></div>
                            </td>
                            <td><span class="magang-status <?= esc($record['status_class']) ?>"><?= esc($record['status']) ?></span></td>
                            <td class="magang-note-cell"><?= $record['keterangan'] ? esc($record['keterangan']) : '—' ?></td>
                            <td>
                                <div class="table-actions"><button class="icon-btn" type="button" data-magang-view="<?= (int) $record['id'] ?>" title="Lihat detail peserta" aria-label="Lihat detail peserta">⌕</button><button class="icon-btn" type="button" data-magang-edit="<?= (int) $record['id'] ?>" title="Ubah data peserta">✎</button><button class="icon-btn icon-btn-delete" type="button" data-magang-delete="<?= (int) $record['id'] ?>" title="Hapus data peserta">×</button></div>
                            </td>
                        </tr>
                <?php endforeach;
                endif ?>
            </tbody>
        </table>
    </div>
    <div class="table-list-footer">
        <form method="get" action="<?= $baseUrl ?>" class="table-length-form"><input type="hidden" name="q" value="<?= esc($filters['keyword']) ?>"><input type="hidden" name="unit_kerja" value="<?= esc($filters['unit']) ?>"><input type="hidden" name="jenis" value="<?= esc($filters['type']) ?>"><input type="hidden" name="status" value="<?= esc($filters['status']) ?>"><input type="hidden" name="urutan" value="<?= esc($filters['order']) ?>"><label for="magangPerPage">Tampilkan</label><select id="magangPerPage" name="per_page"><?php foreach ([10, 20, 50, 100] as $size): ?><option value="<?= $size ?>" <?= $filters['perPage'] === $size ? 'selected' : '' ?>><?= $size ?></option><?php endforeach ?></select><span>data</span></form><?php if ($records !== []): ?><div class="pagination-wrap"><?= $pager->links('data_magang', 'magang_full') ?></div><?php endif ?>
    </div>
</section>

<div class="input-modal" id="magangViewModal" hidden aria-hidden="true"><button type="button" class="modal-backdrop" data-magang-view-close aria-label="Tutup rincian"></button>
    <section class="modal-dialog magang-view-dialog" role="dialog" aria-modal="true" aria-labelledby="magangViewTitle">
        <header class="modal-header">
            <div class="modal-title-group"><span class="modal-title-icon">⌕</span>
                <div>
                    <p>SDM &amp; TELLER</p>
                    <h2 id="magangViewTitle">Rincian Peserta Magang</h2>
                </div>
            </div><button class="modal-close" type="button" data-magang-view-close aria-label="Tutup rincian">×</button>
        </header>
        <div class="modal-body">
            <dl class="magang-view-list">
                <div>
                    <dt>Nama peserta</dt>
                    <dd data-magang-view-name></dd>
                </div>
                <div>
                    <dt>Nomor kontrak kerja</dt>
                    <dd data-magang-view-contract></dd>
                </div>
                <div>
                    <dt>Unit kerja</dt>
                    <dd data-magang-view-unit></dd>
                </div>
                <div>
                    <dt>Jenis magang</dt>
                    <dd data-magang-view-type></dd>
                </div>
                <div>
                    <dt>Periode magang</dt>
                    <dd data-magang-view-period></dd>
                </div>
                <div>
                    <dt>Status</dt>
                    <dd data-magang-view-status></dd>
                </div>
                <div>
                    <dt>PKK</dt>
                    <dd><a class="magang-pkk-link" data-magang-view-pkk href="#" target="_blank" rel="noopener noreferrer" hidden>Buka dokumen</a><span class="magang-pkk-empty" data-magang-view-no-pkk>Belum ada PKK</span></dd>
                </div>
                <div class="modal-span-2">
                    <dt>Keterangan</dt>
                    <dd data-magang-view-note></dd>
                </div>
            </dl>
        </div>
        <footer class="modal-footer"><button class="btn btn-secondary" type="button" data-magang-view-close>Tutup</button></footer>
    </section>
</div>

<div class="input-modal" id="magangFormModal" hidden aria-hidden="true"><button type="button" class="modal-backdrop" data-magang-form-close aria-label="Tutup form"></button>
    <section class="modal-dialog magang-form-dialog" role="dialog" aria-modal="true" aria-labelledby="magangFormTitle">
        <header class="modal-header">
            <div class="modal-title-group"><span class="modal-title-icon" data-magang-form-icon>＋</span>
                <div>
                    <p>SDM &amp; TELLER</p>
                    <h2 id="magangFormTitle" data-magang-form-title>Tambah Peserta Magang</h2>
                </div>
            </div><button class="modal-close" type="button" data-magang-form-close aria-label="Tutup form">×</button>
        </header>
        <form method="post" action="<?= site_url('sdm/data-magang') ?>" data-magang-form><?= csrf_field() ?><input type="hidden" name="return_to" value="<?= esc($returnUrl, 'attr') ?>">
            <div class="modal-body magang-form-body">
                <div class="modal-form-grid">
                    <div class="form-group"><label for="magangName">Nama Peserta <span class="required">*</span></label><input id="magangName" name="nama_magang" maxlength="200" required></div>
                    <div class="form-group"><label for="magangContract">Nomor Kontrak Kerja <span class="required">*</span></label><input id="magangContract" name="nomor_kontrak_kerja" maxlength="200" required readonly placeholder="Pilih Awal Magang untuk membuat nomor kontrak"><small class="magang-contract-hint">Nomor urut dibuat otomatis oleh sistem, lalu bulan dan tahun mengikuti Awal Magang.</small></div>
                    <div class="form-group"><label for="magangUnitInput">Unit Kerja <span class="required">*</span></label><select id="magangUnitInput" name="unit_kerja" required>
                            <option value="">Pilih unit kerja</option><?php foreach ($units as $option): ?><option value="<?= esc($option, 'attr') ?>"><?= esc($option) ?></option><?php endforeach ?>
                        </select></div>
                    <div class="form-group"><label for="magangTypeInput">Jenis Magang <span class="required">*</span></label><select id="magangTypeInput" name="jenis_magang" required>
                            <option value="">Pilih jenis magang</option><?php foreach ($types as $option): ?><option value="<?= esc($option, 'attr') ?>"><?= esc($option) ?></option><?php endforeach ?>
                        </select></div>
                    <div class="form-group"><label for="magangStart">Awal Magang <span class="required">*</span></label><input id="magangStart" name="tanggal_mulai" type="date" required></div>
                    <div class="form-group"><label for="magangEnd">Selesai Magang</label><input id="magangEnd" name="tanggal_selesai" type="date"></div>
                    <div class="form-group modal-span-2"><label for="magangLink">Link PKK</label><input id="magangLink" name="link_pkk" type="url" maxlength="2048" placeholder="https://..."></div>
                    <div class="form-group modal-span-2"><label for="magangNote">Keterangan</label><textarea id="magangNote" name="keterangan" maxlength="500" rows="3" placeholder="Contoh: PKWT"></textarea></div>
                </div>
            </div>
            <footer class="modal-footer"><button class="btn btn-ghost" type="button" data-magang-form-close>Batal</button><button class="btn btn-primary" type="submit">Simpan Data</button></footer>
        </form>
    </section>
</div>

<div class="input-modal" id="magangImportModal" hidden aria-hidden="true"><button type="button" class="modal-backdrop" data-magang-import-close aria-label="Tutup impor"></button>
    <section class="modal-dialog magang-import-dialog" role="dialog" aria-modal="true" aria-labelledby="magangImportTitle">
        <header class="modal-header">
            <div class="modal-title-group"><span class="modal-title-icon">⇧</span>
                <div>
                    <p>SDM &amp; TELLER</p>
                    <h2 id="magangImportTitle">Import Database Magang</h2>
                </div>
            </div><button class="modal-close" type="button" data-magang-import-close aria-label="Tutup impor">×</button>
        </header>
        <form method="post" action="<?= site_url('sdm/data-magang/import') ?>" enctype="multipart/form-data"><?= csrf_field() ?><input type="hidden" name="return_to" value="<?= esc($returnUrl, 'attr') ?>">
            <div class="modal-body">
                <p class="magang-import-copy">Gunakan sheet bernama <strong>Database Magang</strong>. Data dengan nomor kontrak yang sama akan diperbarui; data lain akan ditambahkan.</p>
                <div class="form-group"><label for="magangImportFile">Berkas Excel (.xlsx, maksimal 5 MB) <span class="required">*</span></label><input id="magangImportFile" name="magang_excel" type="file" accept=".xlsx,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" required></div>
            </div>
            <footer class="modal-footer"><button class="btn btn-ghost" type="button" data-magang-import-close>Batal</button><button class="btn btn-primary" type="submit">Import Data</button></footer>
        </form>
    </section>
</div>

<div class="input-modal" id="magangDeleteModal" hidden aria-hidden="true"><button type="button" class="modal-backdrop" data-magang-delete-close aria-label="Tutup konfirmasi"></button>
    <section class="modal-dialog delete-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="magangDeleteTitle">
        <header class="modal-header">
            <div class="modal-title-group"><span class="modal-title-icon">×</span>
                <div>
                    <p>SDM &amp; TELLER</p>
                    <h2 id="magangDeleteTitle">Hapus Data Magang</h2>
                </div>
            </div><button class="modal-close" type="button" data-magang-delete-close aria-label="Tutup konfirmasi">×</button>
        </header>
        <form method="post" action="" data-magang-delete-form><?= csrf_field() ?><input type="hidden" name="return_to" value="<?= esc($returnUrl, 'attr') ?>">
            <div class="modal-body">
                <p>Hapus data peserta <strong data-magang-delete-name></strong>?</p>
                <p class="magang-import-copy">Data akan disembunyikan dari daftar dan dapat dipulihkan oleh Administrator melalui Data Terhapus.</p>
            </div>
            <footer class="modal-footer"><button class="btn btn-ghost" type="button" data-magang-delete-close>Batal</button><button class="btn btn-danger" type="submit">Hapus Data</button></footer>
        </form>
    </section>
</div>

<?php if ($isAdmin): ?><div class="input-modal" id="magangDeleteAllModal" hidden aria-hidden="true"><button type="button" class="modal-backdrop" data-magang-delete-all-close aria-label="Tutup konfirmasi"></button>
        <section class="modal-dialog delete-modal-dialog" role="alertdialog" aria-modal="true" aria-labelledby="magangDeleteAllTitle">
            <header class="modal-header">
                <div class="modal-title-group"><span class="modal-title-icon">×</span>
                    <div>
                        <p>SDM &amp; TELLER</p>
                        <h2 id="magangDeleteAllTitle">Hapus Semua Data Magang</h2>
                    </div>
                </div><button class="modal-close" type="button" data-magang-delete-all-close aria-label="Tutup konfirmasi">×</button>
            </header>
            <form method="post" action="<?= site_url('sdm/data-magang/hapus-semua') ?>"><?= csrf_field() ?><input type="hidden" name="return_to" value="<?= esc($returnUrl, 'attr') ?>">
                <div class="modal-body">
                    <p>Semua data peserta magang aktif akan dihapus <strong>secara permanen</strong>.</p>
                    <p class="magang-import-copy">Tindakan ini tidak dapat dipulihkan melalui Data Terhapus.</p><label class="magang-delete-all-confirm"><input type="checkbox" name="confirm_delete_all" value="1" required> Saya memahami dan ingin menghapus seluruh data magang aktif.</label>
                </div>
                <footer class="modal-footer"><button class="btn btn-ghost" type="button" data-magang-delete-all-close>Batal</button><button class="btn btn-danger" type="submit">Hapus Semua Data</button></footer>
            </form>
        </section>
    </div><?php endif ?>

<script>
    (() => {
        const records = <?= json_encode($modalRecords, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
        const nextContractSequence = <?= (int) $nextContractSequence ?>;
        const romanMonths = ['', 'I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X', 'XI', 'XII'];
        const formModal = document.getElementById('magangFormModal');
        const form = formModal?.querySelector('[data-magang-form]');
        const importModal = document.getElementById('magangImportModal');
        const viewModal = document.getElementById('magangViewModal');
        const deleteModal = document.getElementById('magangDeleteModal');
        const deleteForm = deleteModal?.querySelector('[data-magang-delete-form]');
        const deleteAllModal = document.getElementById('magangDeleteAllModal');
        const open = (modal) => {
            if (!modal) return;
            modal.hidden = false;
            modal.setAttribute('aria-hidden', 'false');
            requestAnimationFrame(() => modal.classList.add('open'));
        };
        const close = (modal) => {
            if (!modal) return;
            modal.classList.remove('open');
            modal.setAttribute('aria-hidden', 'true');
            window.setTimeout(() => {
                modal.hidden = true;
            }, 180);
        };
        const setField = (name, value) => {
            const field = form?.elements.namedItem(name);
            if (field) field.value = value ?? '';
        };
        const contractField = form?.elements.namedItem('nomor_kontrak_kerja');
        const startField = form?.elements.namedItem('tanggal_mulai');
        const updateGeneratedContract = () => {
            if (!contractField || !startField || form?.dataset.mode !== 'create') return;
            const [year, month] = String(startField.value || '').split('-').map(Number);
            contractField.value = year && month ? `${String(nextContractSequence).padStart(3, '0')}/PKKM/KW.6/${romanMonths[month]}/${year}` : '';
        };
        startField?.addEventListener('change', updateGeneratedContract);
        const recordFor = (button, key) => {
            const stored = records[button.dataset[key]];
            if (stored) return stored;
            try {
                return JSON.parse(button.closest('[data-magang-record]')?.dataset.magangRecord || 'null');
            } catch (error) {
                return null;
            }
        };
        const openCreateForm = () => {
            form.reset();
            form.dataset.mode = 'create';
            form.action = <?= json_encode(site_url('sdm/data-magang')) ?>;
            startField.required = true;
            contractField.readOnly = true;
            contractField.placeholder = 'Pilih Awal Magang untuk membuat nomor kontrak';
            updateGeneratedContract();
            formModal.querySelector('[data-magang-form-title]').textContent = 'Tambah Peserta Magang';
            formModal.querySelector('[data-magang-form-icon]').textContent = '＋';
            open(formModal);
        };
        const openEditForm = (button) => {
            const record = recordFor(button, 'magangEdit');
            if (!record) return;
            form.reset();
            form.dataset.mode = 'edit';
            form.action = <?= json_encode(site_url('sdm/data-magang')) ?> + '/' + record.id;
            Object.entries(record).forEach(([name, value]) => setField(name, value));
            startField.required = false;
            contractField.readOnly = true;
            contractField.placeholder = '';
            formModal.querySelector('[data-magang-form-title]').textContent = 'Ubah Peserta Magang';
            formModal.querySelector('[data-magang-form-icon]').textContent = '✎';
            open(formModal);
        };
        document.querySelectorAll('[data-magang-form-close]').forEach((button) => button.addEventListener('click', () => close(formModal)));
        document.querySelectorAll('[data-magang-import]').forEach((button) => button.addEventListener('click', () => open(importModal)));
        document.querySelectorAll('[data-magang-import-close]').forEach((button) => button.addEventListener('click', () => close(importModal)));
        const openView = (button) => {
            const record = recordFor(button, 'magangView');
            if (!record || !viewModal) return;
            const write = (name, value) => {
                const target = viewModal.querySelector(`[data-magang-view-${name}]`);
                if (target) target.textContent = value || 'Belum diisi';
            };
            write('name', record.nama_magang);
            write('contract', record.nomor_kontrak_kerja);
            write('unit', record.unit_kerja);
            write('type', record.jenis_magang);
            write('period', `${record.tanggal_mulai ? new Date(`${record.tanggal_mulai}T00:00:00`).toLocaleDateString('id-ID') : 'Belum diisi'} s.d. ${record.tanggal_selesai ? new Date(`${record.tanggal_selesai}T00:00:00`).toLocaleDateString('id-ID') : 'Belum diisi'}`);
            write('note', record.keterangan);
            const status = viewModal.querySelector('[data-magang-view-status]');
            if (status) status.textContent = button.closest('tr')?.querySelector('.magang-status')?.textContent?.trim() || 'Belum diisi';
            const pkk = viewModal.querySelector('[data-magang-view-pkk]');
            const noPkk = viewModal.querySelector('[data-magang-view-no-pkk]');
            if (pkk && noPkk) {
                const available = Boolean(record.link_pkk);
                pkk.hidden = !available;
                noPkk.hidden = available;
                if (available) pkk.href = record.link_pkk;
            }
            open(viewModal);
        };
        document.querySelectorAll('[data-magang-view-close]').forEach((button) => button.addEventListener('click', () => close(viewModal)));
        const openDelete = (button) => {
            const record = recordFor(button, 'magangDelete');
            if (!record) return;
            deleteForm.action = <?= json_encode(site_url('sdm/data-magang')) ?> + '/' + record.id + '/hapus';
            deleteModal.querySelector('[data-magang-delete-name]').textContent = record.nama_magang;
            open(deleteModal);
        };
        document.addEventListener('click', (event) => {
            const button = event.target.closest('[data-magang-create], [data-magang-edit], [data-magang-view], [data-magang-delete]');
            if (!button) return;
            if (button.matches('[data-magang-create]')) openCreateForm();
            if (button.matches('[data-magang-edit]')) openEditForm(button);
            if (button.matches('[data-magang-view]')) openView(button);
            if (button.matches('[data-magang-delete]')) openDelete(button);
        });
        document.querySelectorAll('[data-magang-delete-close]').forEach((button) => button.addEventListener('click', () => close(deleteModal)));
        document.querySelectorAll('[data-magang-delete-all]').forEach((button) => button.addEventListener('click', () => open(deleteAllModal)));
        document.querySelectorAll('[data-magang-delete-all-close]').forEach((button) => button.addEventListener('click', () => close(deleteAllModal)));
        const actionMenus = [...document.querySelectorAll('.magang-action-menu')];
        document.addEventListener('click', (event) => actionMenus.forEach((menu) => {
            if (!menu.open || menu.contains(event.target)) return;
            menu.open = false;
        }));
        actionMenus.forEach((menu) => menu.querySelectorAll('[role="menuitem"]').forEach((item) => item.addEventListener('click', () => {
            menu.open = false;
        })));
        let tableIsLoading = false;
        const loadTable = async (url, pushHistory = true) => {
            const currentTable = document.getElementById('magangTableArea');
            if (!currentTable || tableIsLoading) return;
            tableIsLoading = true;
            currentTable.classList.add('is-loading');
            currentTable.setAttribute('aria-busy', 'true');
            try {
                const response = await fetch(url, {
                    credentials: 'same-origin',
                    cache: 'no-store',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                });
                if (!response.ok) throw new Error('Data magang tidak dapat dimuat.');
                const documentResponse = new DOMParser().parseFromString(await response.text(), 'text/html');
                const replacement = documentResponse.getElementById('magangTableArea');
                if (!replacement) throw new Error('Tabel data magang tidak ditemukan.');
                currentTable.replaceWith(replacement);
                if (pushHistory) window.history.pushState({ magangTable: true }, '', url);
            } finally {
                tableIsLoading = false;
                document.getElementById('magangTableArea')?.classList.remove('is-loading');
                document.getElementById('magangTableArea')?.removeAttribute('aria-busy');
            }
        };
        document.addEventListener('click', (event) => {
            const link = event.target.closest('#magangTableArea .pagination a');
            if (!link || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;
            event.preventDefault();
            loadTable(link.href).catch(() => window.location.assign(link.href));
        });
        document.addEventListener('change', (event) => {
            const select = event.target.closest('#magangTableArea .table-length-form select[name="per_page"]');
            if (!select) return;
            const lengthForm = select.form;
            const url = new URL(lengthForm.action, window.location.href);
            new FormData(lengthForm).forEach((value, name) => url.searchParams.set(name, String(value)));
            url.searchParams.delete('page_data_magang');
            loadTable(url.toString()).catch(() => lengthForm.submit());
        });
        window.addEventListener('popstate', () => {
            loadTable(window.location.href, false).catch(() => window.location.reload());
        });
        document.addEventListener('keydown', (event) => {
            if (event.key !== 'Escape') return;
            actionMenus.forEach((menu) => {
                menu.open = false;
            });
            [formModal, importModal, viewModal, deleteModal, deleteAllModal].forEach((modal) => {
                if (modal && !modal.hidden) close(modal);
            });
        });
    })();
</script>
<?= $this->endSection() ?>
