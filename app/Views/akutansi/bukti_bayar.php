<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<?php
/** @var list<array<string,mixed>> $records */
/** @var array<string,string> $filters */

$returnQuery = http_build_query(array_filter($filters, static fn(string $value): bool => $value !== ''));
$money = static function (?string $amount): string {
    if ($amount === null || $amount === '') return '—';
    $negative = str_starts_with($amount, '-');
    $amount = ltrim($amount, '-');
    [$integer, $fraction] = array_pad(explode('.', $amount, 2), 2, '00');
    $integer = preg_replace('/\\B(?=(\\d{3})+(?!\\d))/', '.', $integer) ?? $integer;
    return 'Rp ' . ($negative ? '-' : '') . $integer . ',' . str_pad(substr($fraction, 0, 2), 2, '0');
};
$date = static function (?string $value): string {
    return $value === null || $value === '' ? '—' : date('d-m-Y', strtotime($value));
};
$rowNumber = 1 + (20 * max(0, $pager->getCurrentPage('bukti_bayar') - 1));
$rangeLabel = $filters['tanggal_dari'] !== '' || $filters['tanggal_sampai'] !== ''
    ? ($filters['tanggal_dari'] !== '' ? date('d/m/Y', strtotime($filters['tanggal_dari'])) : '…')
    . ' — ' . ($filters['tanggal_sampai'] !== '' ? date('d/m/Y', strtotime($filters['tanggal_sampai'])) : '…')
    : 'Pilih rentang tanggal';
?>


<?php if (session()->getFlashdata('success')): ?>
    <div class="alert alert-success" role="status"><?= esc((string) session()->getFlashdata('success')) ?></div>
<?php endif ?>
<?php if ($filterError !== null || session()->getFlashdata('bukti_bayar_error')): ?>
    <div class="alert alert-danger" role="alert"><?= esc($filterError ?? (string) session()->getFlashdata('bukti_bayar_error')) ?></div>
<?php endif ?>

<section class="panel filter-panel bukti-bayar-filter-panel">
    <form method="get" action="<?= site_url('akutansi/bukti-bayar') ?>" class="agendaris-filter-form bukti-bayar-filter-form">
        <div class="form-group bukti-date-range-filter">
            <label for="buktiDateRangeToggle">Rentang tanggal</label>
            <div class="bukti-date-range" data-bukti-date-range>
                <button id="buktiDateRangeToggle" class="bukti-date-range-toggle" type="button" data-bukti-range-toggle aria-haspopup="dialog" aria-expanded="false">
                    <span class="bukti-filter-field-icon filter-date-icon" aria-hidden="true"><svg viewBox="0 0 24 24">
                            <rect x="4" y="5" width="16" height="15" rx="2"></rect>
                            <path d="M8 3v4M16 3v4M4 10h16M8 14h2M14 14h2M8 17h2"></path>
                        </svg></span>
                    <strong data-bukti-range-label><?= esc($rangeLabel) ?></strong>
                    <svg aria-hidden="true" viewBox="0 0 24 24">
                        <path d="M7 10l5 5 5-5"></path>
                    </svg>
                </button>
                <input type="hidden" name="tanggal_dari" value="<?= esc($filters['tanggal_dari'], 'attr') ?>" data-bukti-date-from>
                <input type="hidden" name="tanggal_sampai" value="<?= esc($filters['tanggal_sampai'], 'attr') ?>" data-bukti-date-to>
                <section class="bukti-date-range-popover" data-bukti-range-popover hidden role="dialog" aria-label="Pilih rentang tanggal">
                    <header><button type="button" data-bukti-range-prev aria-label="Bulan sebelumnya">‹</button>
                        <div><select data-bukti-range-month aria-label="Bulan"></select><select data-bukti-range-year aria-label="Tahun"></select></div><button type="button" data-bukti-range-next aria-label="Bulan berikutnya">›</button>
                    </header>
                    <div class="bukti-date-range-weekdays" aria-hidden="true"><span>Min</span><span>Sen</span><span>Sel</span><span>Rab</span><span>Kam</span><span>Jum</span><span>Sab</span></div>
                    <div class="bukti-date-range-days" data-bukti-range-days></div>
                    <footer><button type="button" class="btn btn-ghost" data-bukti-range-clear>Hapus</button><button type="button" class="btn btn-secondary" data-bukti-range-apply>Selesai</button></footer>
                </section>
            </div>
        </div>
        <div class="form-group">
            <label for="buktiNomorVoucher">Nomor voucher</label>
            <div class="bukti-filter-input"><span class="bukti-filter-field-icon" aria-hidden="true"><svg viewBox="0 0 24 24">
                        <path d="M6 3h8l4 4v14H6z"></path>
                        <path d="M14 3v5h5M9 13h6M9 17h5"></path>
                    </svg></span><input id="buktiNomorVoucher" type="search" name="nomor_voucher" value="<?= esc($filters['nomor_voucher'], 'attr') ?>" placeholder="Cari nomor voucher" autocomplete="off"></div>
        </div>
        <div class="form-group">
            <label for="buktiNomorMemo">Nomor memo</label>
            <div class="bukti-filter-input"><span class="bukti-filter-field-icon" aria-hidden="true"><svg viewBox="0 0 24 24">
                        <path d="M6 3h8l4 4v14H6z"></path>
                        <path d="M14 3v5h5M9 13h6M9 17h5"></path>
                    </svg></span><input id="buktiNomorMemo" type="search" name="nomor_memo" value="<?= esc($filters['nomor_memo'], 'attr') ?>" placeholder="Cari nomor memo" autocomplete="off"></div>
        </div>
        <div class="form-group">
            <label for="buktiNominal">Nominal</label>
            <div class="bukti-filter-input"><span class="bukti-filter-field-icon bukti-filter-rupiah" aria-hidden="true">Rp</span><input id="buktiNominal" type="text" inputmode="decimal" name="nominal" value="<?= esc($filters['nominal'], 'attr') ?>" placeholder="Contoh: 1.500.000" autocomplete="off"></div>
        </div>
        <div class="filter-actions bukti-bayar-filter-actions">
            <button class="btn btn-secondary" type="submit"><svg aria-hidden="true" viewBox="0 0 24 24">
                    <circle cx="11" cy="11" r="7"></circle>
                    <path d="m16 16 5 5"></path>
                </svg>Filter</button>
            <a class="btn btn-ghost" href="<?= site_url('akutansi/bukti-bayar') ?>"><svg aria-hidden="true" viewBox="0 0 24 24">
                    <path d="M21 12a9 9 0 1 1-2.64-6.36"></path>
                    <path d="M21 3v6h-6"></path>
                </svg>Reset</a>
            <details class="lr-action-menu lr-filter-menu">
                <summary class="lr-filter-menu-trigger" aria-label="Menu tindakan Bukti Bayar" title="Menu tindakan">
                    <svg aria-hidden="true" viewBox="0 0 24 24" focusable="false">
                        <rect x="3" y="3" width="7" height="7" rx="1.4"></rect>
                        <rect x="14" y="3" width="7" height="7" rx="1.4"></rect>
                        <rect x="3" y="14" width="7" height="7" rx="1.4"></rect>
                        <rect x="14" y="14" width="7" height="7" rx="1.4"></rect>
                    </svg>
                </summary>
                <div class="lr-action-menu-popover" role="menu" aria-label="Menu Bukti Bayar">
                    <button type="button" role="menuitem" class="lr-action-menu-item" data-bukti-create-open aria-haspopup="dialog" aria-controls="buktiBayarFormModal">Tambah Bukti Bayar</button>
                </div>
            </details>
        </div>
    </form>
</section>

<section class="panel bukti-bayar-panel table-title-flat">
    <header class="table-title-flat__header">
        <div>
            <p>DAFTAR TRANSAKSI</p>
            <h2>Data Bukti Bayar</h2>
        </div>
        <span><?= count($records) ?> data pada halaman ini</span>
    </header>
    <div class="lr-audit-table-wrap table-title-flat__table" role="region" aria-label="Tabel bukti bayar" tabindex="0">
        <table class="lr-audit-table bukti-bayar-table">
            <thead>
                <tr>
                    <th scope="col">No.</th>
                    <th scope="col">Nomor Memo</th>
                    <th scope="col">Nomor Voucher</th>
                    <th scope="col">Tanggal</th>
                    <th scope="col">Nominal</th>
                    <th scope="col">Kategori</th>
                    <th scope="col" class="bukti-bayar-actions-heading">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($records === []): ?>
                    <tr>
                        <td class="bukti-bayar-empty" colspan="7"><strong>Belum ada bukti bayar</strong><span>Tambahkan data baru atau ubah filter pencarian.</span></td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($records as $record): ?>
                        <?php $recordJson = json_encode([
                            'id' => (int) $record['id'],
                            'nomor_memo' => (string) $record['nomor_memo'],
                            'nomor_voucher' => (string) $record['nomor_voucher'],
                            'tanggal' => (string) $record['tanggal'],
                            'nominal' => (string) $record['nominal'],
                            'kategori' => (string) ($record['kategori'] ?? ''),
                        ], JSON_UNESCAPED_UNICODE | JSON_HEX_APOS | JSON_HEX_QUOT); ?>
                        <tr>
                            <td class="bukti-bayar-number"><?= $rowNumber++ ?></td>
                            <td><strong><?= esc((string) $record['nomor_memo']) ?></strong></td>
                            <td><?= esc((string) $record['nomor_voucher']) ?></td>
                            <td><?= esc($date((string) $record['tanggal'])) ?></td>
                            <td class="bukti-bayar-nominal"><?= esc($money((string) $record['nominal'])) ?></td>
                            <td><?= esc((string) ($record['kategori'] ?: '—')) ?></td>
                            <td class="bukti-bayar-actions">
                                <button type="button" class="icon-btn" data-bukti-view data-url="<?= esc(site_url('akutansi/bukti-bayar/' . (int) $record['id']), 'attr') ?>" aria-label="Lihat bukti bayar <?= esc((string) $record['nomor_voucher'], 'attr') ?>" title="Lihat">
                                    <svg aria-hidden="true" viewBox="0 0 24 24">
                                        <path d="M2.5 12s3.4-5.8 9.5-5.8S21.5 12 21.5 12 18.1 17.8 12 17.8 2.5 12 2.5 12Z"></path>
                                        <circle cx="12" cy="12" r="2.7"></circle>
                                    </svg>
                                </button>
                                <button type="button" class="icon-btn" data-bukti-edit data-record="<?= esc((string) $recordJson, 'attr') ?>" aria-label="Edit bukti bayar <?= esc((string) $record['nomor_voucher'], 'attr') ?>" title="Edit">
                                    <svg aria-hidden="true" viewBox="0 0 24 24">
                                        <path d="m4 16.8-.7 3.9 3.9-.7L18.4 8.8l-3.2-3.2L4 16.8Z"></path>
                                        <path d="m13.8 7 3.2 3.2"></path>
                                    </svg>
                                </button>
                                <button type="button" class="icon-btn is-danger" data-bukti-delete data-record="<?= esc((string) $recordJson, 'attr') ?>" aria-label="Hapus bukti bayar <?= esc((string) $record['nomor_voucher'], 'attr') ?>" title="Hapus">
                                    <svg aria-hidden="true" viewBox="0 0 24 24">
                                        <path d="M4.5 7h15"></path>
                                        <path d="M9 4h6l1 3H8l1-3Z"></path>
                                        <path d="m6.5 7 .8 13h9.4l.8-13"></path>
                                        <path d="M10 11v5M14 11v5"></path>
                                    </svg>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach ?>
                <?php endif ?>
            </tbody>
        </table>
    </div>
    <?php if ($pager->getPageCount('bukti_bayar') > 1): ?>
        <footer class="bukti-bayar-pagination"><?= $pager->links('bukti_bayar', 'default_full') ?></footer>
    <?php endif ?>
</section>

<div class="input-modal bukti-bayar-modal" id="buktiBayarFormModal" hidden aria-hidden="true">
    <button type="button" class="modal-backdrop" data-bukti-form-close aria-label="Tutup formulir"></button>
    <section class="modal-dialog bukti-bayar-dialog" role="dialog" aria-modal="true" aria-labelledby="buktiBayarFormTitle">
        <header class="modal-header">
            <div class="modal-title-group"><span class="modal-title-icon" aria-hidden="true">Rp</span>
                <div>
                    <p>AKUNTANSI</p>
                    <h2 id="buktiBayarFormTitle">Tambah Bukti Bayar</h2>
                </div>
            </div>
            <button class="modal-close" type="button" data-bukti-form-close aria-label="Tutup formulir">×</button>
        </header>
        <form method="post" action="<?= site_url('akutansi/bukti-bayar') ?>" data-bukti-form data-store-url="<?= esc(site_url('akutansi/bukti-bayar'), 'attr') ?>" data-update-url="<?= esc(site_url('akutansi/bukti-bayar/__id__'), 'attr') ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="return_query" value="<?= esc($returnQuery, 'attr') ?>">
            <div class="modal-body bukti-bayar-form-grid">
                <label><span class="bukti-bayar-field-title">Nomor Memo <b>*</b></span><input type="text" name="nomor_memo" maxlength="120" placeholder="Masukkan nomor memo" required></label>
                <label><span class="bukti-bayar-field-title">Nomor Voucher <b>*</b></span><input type="text" name="nomor_voucher" maxlength="120" placeholder="Masukkan nomor voucher" required></label>
                <label><span class="bukti-bayar-field-title">Tanggal <b>*</b></span><input type="date" name="tanggal" required></label>
                <label><span class="bukti-bayar-field-title">Nominal <b>*</b></span><input type="text" inputmode="decimal" name="nominal" placeholder="Masukan Nominal" required></label>
                <label><span class="bukti-bayar-field-title">Kategori</span><span class="bukti-category-select" data-bukti-category-select>
                        <select name="kategori" data-bukti-category-input tabindex="-1" aria-hidden="true">
                            <option value="">Pilih kategori</option>
                            <option value="Voucher Bank">Voucher Bank</option>
                            <option value="Voucher Kas">Voucher Kas</option>
                        </select>
                        <button class="bukti-category-trigger is-placeholder" type="button" data-bukti-category-trigger aria-haspopup="listbox" aria-expanded="false" aria-controls="buktiCategoryOptions"><span data-bukti-category-label>Pilih kategori</span><svg aria-hidden="true" viewBox="0 0 24 24"><path d="m6 9 6 6 6-6"></path></svg></button>
                        <span class="bukti-category-options" id="buktiCategoryOptions" data-bukti-category-options role="listbox" hidden>
                            <button type="button" role="option" data-bukti-category-option data-value="">Pilih kategori</button>
                            <button type="button" role="option" data-bukti-category-option data-value="Voucher Bank">Voucher Bank</button>
                            <button type="button" role="option" data-bukti-category-option data-value="Voucher Kas">Voucher Kas</button>
                        </span>
                    </span></label>
                <label class="bukti-bayar-file-field"><span class="bukti-bayar-field-title">Tautan Berkas</span><input type="url" name="berkas_url" maxlength="2048" placeholder="https://Onedrive_2178767"><small>Opsional · masukkan tautan berkas yang dapat dibuka.</small></label>
            </div>
            <footer class="modal-footer"><button class="btn btn-ghost" type="button" data-bukti-form-close>Batal</button><button class="btn btn-primary" type="submit" data-bukti-form-submit>Simpan Bukti Bayar</button></footer>
        </form>
    </section>
</div>

<div class="input-modal bukti-bayar-modal" id="buktiBayarViewModal" hidden aria-hidden="true">
    <button type="button" class="modal-backdrop" data-bukti-view-close aria-label="Tutup detail"></button>
    <section class="modal-dialog bukti-bayar-dialog bukti-bayar-view-dialog" role="dialog" aria-modal="true" aria-labelledby="buktiBayarViewTitle">
        <header class="modal-header">
            <div class="modal-title-group"><span class="modal-title-icon" aria-hidden="true">⌕</span>
                <div>
                    <p>AKUNTANSI</p>
                    <h2 id="buktiBayarViewTitle">Detail Bukti Bayar</h2>
                </div>
            </div>
            <button class="modal-close" type="button" data-bukti-view-close aria-label="Tutup detail">×</button>
        </header>
        <dl class="bukti-bayar-detail-list">
            <div>
                <dt>Nomor Memo</dt>
                <dd data-bukti-detail="nomor_memo">—</dd>
            </div>
            <div>
                <dt>Nomor Voucher</dt>
                <dd data-bukti-detail="nomor_voucher">—</dd>
            </div>
            <div>
                <dt>Tanggal</dt>
                <dd data-bukti-detail="tanggal">—</dd>
            </div>
            <div>
                <dt>Nominal</dt>
                <dd data-bukti-detail="nominal">—</dd>
            </div>
            <div>
                <dt>Kategori</dt>
                <dd data-bukti-detail="kategori">—</dd>
            </div>
            <div class="bukti-bayar-detail-wide">
                <dt>Berkas</dt>
                <dd><a data-bukti-detail-file href="#" target="_blank" rel="noopener" hidden></a><span data-bukti-detail-file-empty>—</span></dd>
            </div>
        </dl>
        <footer class="modal-footer"><button class="btn btn-primary" type="button" data-bukti-view-close>Tutup</button></footer>
    </section>
</div>

<div class="input-modal bukti-bayar-modal" id="buktiBayarDeleteModal" hidden aria-hidden="true">
    <button type="button" class="modal-backdrop" data-bukti-delete-close aria-label="Tutup konfirmasi"></button>
    <section class="modal-dialog delete-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="buktiBayarDeleteTitle">
        <header class="modal-header">
            <div class="modal-title-group"><span class="modal-title-icon" aria-hidden="true">×</span>
                <div>
                    <p>AKUNTANSI</p>
                    <h2 id="buktiBayarDeleteTitle">Hapus Bukti Bayar</h2>
                </div>
            </div>
            <button class="modal-close" type="button" data-bukti-delete-close aria-label="Tutup konfirmasi">×</button>
        </header>
        <form method="post" action="" data-bukti-delete-form data-delete-url="<?= esc(site_url('akutansi/bukti-bayar/__id__/hapus'), 'attr') ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="return_query" value="<?= esc($returnQuery, 'attr') ?>"><input type="hidden" name="confirm_delete" value="1">
            <div class="modal-body">
                <p>Hapus bukti bayar <strong data-bukti-delete-label>ini</strong> dari daftar?</p>
                <p class="bukti-bayar-delete-note">Data akan dipindahkan dari daftar aktif.</p>
            </div>
            <footer class="modal-footer"><button class="btn btn-ghost" type="button" data-bukti-delete-close>Batal</button><button class="btn btn-danger" type="submit">Hapus</button></footer>
        </form>
    </section>
</div>

<script>
    (() => {
        const formModal = document.querySelector('#buktiBayarFormModal');
        const viewModal = document.querySelector('#buktiBayarViewModal');
        const deleteModal = document.querySelector('#buktiBayarDeleteModal');
        const form = document.querySelector('[data-bukti-form]');
        const deleteForm = document.querySelector('[data-bukti-delete-form]');
        const dateFrom = document.querySelector('[data-bukti-date-from]');
        const dateTo = document.querySelector('[data-bukti-date-to]');
        const range = document.querySelector('[data-bukti-date-range]');
        const rangeToggle = document.querySelector('[data-bukti-range-toggle]');
        const rangePopover = document.querySelector('[data-bukti-range-popover]');
        const rangeLabel = document.querySelector('[data-bukti-range-label]');
        const rangeDays = document.querySelector('[data-bukti-range-days]');
        const rangeMonth = document.querySelector('[data-bukti-range-month]');
        const rangeYear = document.querySelector('[data-bukti-range-year]');
        const categorySelect = document.querySelector('[data-bukti-category-input]');
        const categoryWidget = document.querySelector('[data-bukti-category-select]');
        const categoryTrigger = document.querySelector('[data-bukti-category-trigger]');
        const categoryLabel = document.querySelector('[data-bukti-category-label]');
        const categoryOptions = document.querySelector('[data-bukti-category-options]');
        const formBody = form?.querySelector('.bukti-bayar-form-grid');
        const money = new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            minimumFractionDigits: 2
        });
        const toggle = (modal, open) => {
            if (!modal) return;
            modal.hidden = !open;
            modal.classList.toggle('open', open);
            modal.setAttribute('aria-hidden', open ? 'false' : 'true');
        };
        const parseRecord = (button) => JSON.parse(button.dataset.record || '{}');
        const monthNames = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
        const today = new Date();
        const initialDate = dateFrom?.value ? new Date(`${dateFrom.value}T00:00:00`) : today;
        const calendar = {
            year: initialDate.getFullYear(),
            month: initialDate.getMonth()
        };
        const drag = {
            active: false,
            start: '',
            end: '',
            moved: false,
            continuing: false,
            suppressClick: false
        };
        let hoverDate = '';
        const dateKey = (value) => `${value.getFullYear()}-${String(value.getMonth() + 1).padStart(2, '0')}-${String(value.getDate()).padStart(2, '0')}`;
        const displayDate = (value) => value ? value.split('-').reverse().join('/') : '…';
        const updateRangeLabel = () => {
            rangeLabel.textContent = dateFrom.value || dateTo.value ? `${displayDate(dateFrom.value)} — ${displayDate(dateTo.value)}` : 'Pilih rentang tanggal';
        };
        const renderCalendar = () => {
            if (!rangeDays || !rangeMonth || !rangeYear) return;
            rangeMonth.value = String(calendar.month);
            rangeYear.value = String(calendar.year);
            rangeDays.replaceChildren();
            const firstDay = new Date(calendar.year, calendar.month, 1);
            const start = new Date(calendar.year, calendar.month, 1 - firstDay.getDay());
            const hasHoverPreview = !drag.active && Boolean(dateFrom.value && !dateTo.value && hoverDate);
            const rawStart = drag.active ? drag.start : dateFrom.value;
            const rawEnd = drag.active ? drag.end : (hasHoverPreview ? hoverDate : dateTo.value);
            const rangeStart = rawEnd && rawEnd < rawStart ? rawEnd : rawStart;
            const rangeEnd = rawEnd && rawEnd < rawStart ? rawStart : rawEnd;
            for (let index = 0; index < 42; index += 1) {
                const day = new Date(start.getFullYear(), start.getMonth(), start.getDate() + index);
                const key = dateKey(day);
                const button = document.createElement('button');
                button.type = 'button';
                button.textContent = String(day.getDate());
                button.dataset.date = key;
                button.className = day.getMonth() === calendar.month ? '' : 'is-outside';
                if (key === rangeStart) button.classList.add('is-start');
                if (key === rangeEnd) button.classList.add('is-end');
                if (rangeStart && rangeEnd && key > rangeStart && key < rangeEnd) button.classList.add('is-between');
                if ((drag.active || hasHoverPreview) && rangeStart && key >= rangeStart && key <= rangeEnd) button.classList.add('is-preview');
                if ((drag.active || hasHoverPreview) && key === rawEnd) button.classList.add('is-preview-target');
                button.addEventListener('pointerdown', (event) => {
                    event.preventDefault();
                    drag.continuing = Boolean(dateFrom.value && !dateTo.value);
                    drag.active = true;
                    drag.start = drag.continuing ? dateFrom.value : key;
                    drag.end = key;
                    drag.moved = drag.continuing && key !== drag.start;
                    drag.suppressClick = true;
                    hoverDate = '';
                    renderCalendar();
                });
                button.addEventListener('pointerenter', () => {
                    if (drag.active) {
                        drag.moved = drag.moved || key !== drag.end;
                        drag.end = key;
                        renderCalendar();
                        return;
                    }
                    if (dateFrom.value && !dateTo.value && hoverDate !== key) {
                        hoverDate = key;
                        renderCalendar();
                    }
                });
                button.addEventListener('click', () => {
                    if (drag.suppressClick) {
                        drag.suppressClick = false;
                        return;
                    }
                    if (!dateFrom.value || (dateFrom.value && dateTo.value)) {
                        dateFrom.value = key;
                        dateTo.value = '';
                    } else if (key < dateFrom.value) {
                        dateTo.value = dateFrom.value;
                        dateFrom.value = key;
                    } else {
                        dateTo.value = key;
                    }
                    updateRangeLabel();
                    renderCalendar();
                });
                rangeDays.append(button);
            }
        };
        if (range && rangeToggle && rangePopover && rangeMonth && rangeYear) {
            for (let month = 0; month < 12; month += 1) {
                const option = new Option(monthNames[month], String(month));
                rangeMonth.add(option);
            }
            for (let year = today.getFullYear() - 10; year <= today.getFullYear() + 10; year += 1) rangeYear.add(new Option(String(year), String(year)));
            const closeRange = () => {
                rangePopover.hidden = true;
                rangeToggle.setAttribute('aria-expanded', 'false');
            };
            const animateMonth = (direction) => {
                rangeDays.dataset.direction = direction;
                rangeDays.classList.remove('is-month-switching');
                void rangeDays.offsetWidth;
                rangeDays.classList.add('is-month-switching');
            };
            const moveMonth = (offset) => {
                calendar.month += offset;
                if (calendar.month < 0) {
                    calendar.month = 11;
                    calendar.year -= 1;
                }
                if (calendar.month > 11) {
                    calendar.month = 0;
                    calendar.year += 1;
                }
                drag.active = false;
                drag.suppressClick = false;
                hoverDate = '';
                renderCalendar();
                animateMonth(offset < 0 ? 'previous' : 'next');
            };
            rangeToggle.addEventListener('click', () => {
                const opening = rangePopover.hidden;
                rangePopover.hidden = !opening;
                rangeToggle.setAttribute('aria-expanded', opening ? 'true' : 'false');
                if (opening) renderCalendar();
            });
            document.querySelector('[data-bukti-range-prev]')?.addEventListener('click', () => moveMonth(-1));
            document.querySelector('[data-bukti-range-next]')?.addEventListener('click', () => moveMonth(1));
            rangeMonth.addEventListener('change', () => {
                calendar.month = Number(rangeMonth.value);
                drag.suppressClick = false;
                hoverDate = '';
                renderCalendar();
                animateMonth('next');
            });
            rangeYear.addEventListener('change', () => {
                calendar.year = Number(rangeYear.value);
                drag.suppressClick = false;
                hoverDate = '';
                renderCalendar();
                animateMonth('next');
            });
            rangeDays.addEventListener('pointerleave', () => {
                if (!drag.active && hoverDate !== '') {
                    hoverDate = '';
                    renderCalendar();
                }
            });
            document.querySelector('[data-bukti-range-clear]')?.addEventListener('click', () => {
                dateFrom.value = '';
                dateTo.value = '';
                hoverDate = '';
                updateRangeLabel();
                renderCalendar();
            });
            document.querySelector('[data-bukti-range-apply]')?.addEventListener('click', closeRange);
            document.addEventListener('pointerup', () => {
                if (!drag.active) return;
                const start = drag.start;
                const end = drag.end || start;
                if (drag.continuing || drag.moved) {
                    dateFrom.value = end < start ? end : start;
                    dateTo.value = end < start ? start : end;
                } else {
                    // Satu klik menetapkan tanggal awal. Pilihan tersebut tetap ada
                    // ketika pengguna berpindah bulan untuk menentukan tanggal akhir.
                    dateFrom.value = start;
                    dateTo.value = '';
                }
                drag.active = false;
                hoverDate = '';
                updateRangeLabel();
                renderCalendar();
            });
            document.addEventListener('click', (event) => {
                if (!range.contains(event.target)) closeRange();
            });
            updateRangeLabel();
            renderCalendar();
        }

        const closeCategoryOptions = () => {
            if (!categoryOptions || !categoryTrigger) return;
            categoryOptions.hidden = true;
            categoryTrigger.setAttribute('aria-expanded', 'false');
            categoryWidget?.classList.remove('is-open');
            categoryWidget?.append(categoryOptions);
            categoryOptions.style.removeProperty('position');
            categoryOptions.style.removeProperty('top');
            categoryOptions.style.removeProperty('left');
            categoryOptions.style.removeProperty('right');
            categoryOptions.style.removeProperty('width');
            categoryOptions.style.removeProperty('z-index');
        };
        const positionCategoryOptions = () => {
            if (!categoryOptions || !categoryTrigger || categoryOptions.hidden) return;
            const triggerBounds = categoryTrigger.getBoundingClientRect();
            categoryOptions.style.top = `${Math.round(triggerBounds.bottom + 5)}px`;
            categoryOptions.style.left = `${Math.round(triggerBounds.left)}px`;
            categoryOptions.style.width = `${Math.round(triggerBounds.width)}px`;
        };
        const syncCategory = () => {
            if (!categorySelect || !categoryTrigger || !categoryLabel) return;
            const selected = categorySelect.options[categorySelect.selectedIndex];
            const value = categorySelect.value;
            categoryLabel.textContent = selected?.textContent || 'Pilih kategori';
            categoryTrigger.classList.toggle('is-placeholder', value === '');
            document.querySelectorAll('[data-bukti-category-option]').forEach((option) => {
                option.setAttribute('aria-selected', option.dataset.value === value ? 'true' : 'false');
            });
        };
        if (categoryWidget && categorySelect && categoryTrigger && categoryOptions) {
            categoryTrigger.addEventListener('click', () => {
                const opening = categoryOptions.hidden;
                if (opening) {
                    formModal.append(categoryOptions);
                    categoryOptions.hidden = false;
                    categoryOptions.style.position = 'fixed';
                    categoryOptions.style.zIndex = '150';
                    categoryOptions.style.right = 'auto';
                    positionCategoryOptions();
                } else {
                    closeCategoryOptions();
                }
                categoryTrigger.setAttribute('aria-expanded', opening ? 'true' : 'false');
                categoryWidget.classList.toggle('is-open', opening);
            });
            categoryOptions.addEventListener('click', (event) => {
                const option = event.target.closest('[data-bukti-category-option]');
                if (!option) return;
                categorySelect.value = option.dataset.value || '';
                categorySelect.dispatchEvent(new Event('change', { bubbles: true }));
                closeCategoryOptions();
            });
            categorySelect.addEventListener('change', syncCategory);
            categoryWidget.addEventListener('keydown', (event) => {
                if (event.key === 'Escape') closeCategoryOptions();
            });
            document.addEventListener('click', (event) => {
                if (!categoryWidget.contains(event.target) && !categoryOptions.contains(event.target)) closeCategoryOptions();
            });
            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape') closeCategoryOptions();
            });
            window.addEventListener('resize', positionCategoryOptions);
            document.addEventListener('scroll', positionCategoryOptions, true);
            form?.addEventListener('reset', () => window.setTimeout(syncCategory));
            syncCategory();
        }

        const openCreateForm = (button) => {
            button?.closest('details')?.removeAttribute('open');
            form.reset();
            formBody?.scrollTo({ top: 0 });
            form.action = form.dataset.storeUrl;
            document.querySelector('#buktiBayarFormTitle').textContent = 'Tambah Bukti Bayar';
            document.querySelector('[data-bukti-form-submit]').textContent = 'Simpan Bukti Bayar';
            toggle(formModal, true);
        };
        document.addEventListener('click', (event) => {
            const button = event.target.closest('[data-bukti-create-open]');
            if (!button) return;
            event.preventDefault();
            openCreateForm(button);
        });
        document.querySelectorAll('[data-bukti-form-close]').forEach((button) => button.addEventListener('click', () => {
            closeCategoryOptions();
            toggle(formModal, false);
        }));
        document.querySelectorAll('[data-bukti-view-close]').forEach((button) => button.addEventListener('click', () => toggle(viewModal, false)));
        document.querySelectorAll('[data-bukti-delete-close]').forEach((button) => button.addEventListener('click', () => toggle(deleteModal, false)));

        document.querySelectorAll('[data-bukti-edit]').forEach((button) => button.addEventListener('click', () => {
            const record = parseRecord(button);
            form.action = form.dataset.updateUrl.replace('__id__', record.id);
            form.nomor_memo.value = record.nomor_memo || '';
            form.nomor_voucher.value = record.nomor_voucher || '';
            form.tanggal.value = record.tanggal || '';
            form.nominal.value = record.nominal || '';
            form.kategori.value = record.kategori || '';
            syncCategory();
            form.berkas_url.value = record.berkas_url || '';
            formBody?.scrollTo({ top: 0 });
            document.querySelector('#buktiBayarFormTitle').textContent = 'Edit Bukti Bayar';
            document.querySelector('[data-bukti-form-submit]').textContent = 'Simpan Perubahan';
            toggle(formModal, true);
        }));
        document.querySelectorAll('[data-bukti-delete]').forEach((button) => button.addEventListener('click', () => {
            const record = parseRecord(button);
            deleteForm.action = deleteForm.dataset.deleteUrl.replace('__id__', record.id);
            document.querySelector('[data-bukti-delete-label]').textContent = record.nomor_voucher || record.nomor_memo || 'ini';
            toggle(deleteModal, true);
        }));
        document.querySelectorAll('[data-bukti-view]').forEach((button) => button.addEventListener('click', async () => {
            try {
                const response = await fetch(button.dataset.url, {
                    headers: {
                        Accept: 'application/json'
                    }
                });
                if (!response.ok) throw new Error('Detail tidak tersedia.');
                const {
                    record
                } = await response.json();
                document.querySelector('[data-bukti-detail="nomor_memo"]').textContent = record.nomor_memo || '—';
                document.querySelector('[data-bukti-detail="nomor_voucher"]').textContent = record.nomor_voucher || '—';
                document.querySelector('[data-bukti-detail="tanggal"]').textContent = record.tanggal ? new Date(`${record.tanggal}T00:00:00`).toLocaleDateString('id-ID') : '—';
                document.querySelector('[data-bukti-detail="nominal"]').textContent = money.format(Number(record.nominal || 0));
                document.querySelector('[data-bukti-detail="kategori"]').textContent = record.kategori || '—';
                const fileLink = document.querySelector('[data-bukti-detail-file]');
                const fileEmpty = document.querySelector('[data-bukti-detail-file-empty]');
                if (record.berkas_url) {
                    fileLink.href = record.berkas_url;
                    fileLink.textContent = record.berkas_nama || 'Buka berkas';
                    fileLink.hidden = false;
                    fileEmpty.hidden = true;
                } else {
                    fileLink.hidden = true;
                    fileEmpty.hidden = false;
                }
                toggle(viewModal, true);
            } catch (error) {
                window.alert(error.message || 'Detail bukti bayar belum dapat ditampilkan.');
            }
        }));
    })();
</script>

<?= $this->endSection() ?>
