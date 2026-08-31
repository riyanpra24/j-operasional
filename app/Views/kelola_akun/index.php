<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<?php
$roleLabels = \Config\UserRoles::LABELS;
$modalToOpen = session()->getFlashdata('account_modal');
$formData = session()->getFlashdata('account_form_data') ?? [];
$editId = session()->getFlashdata('account_edit_id');
?>

<section class="heading-actions account-heading">
    <div>
        <span class="eyebrow">ADMINISTRATOR</span>
        <h1>Add Account</h1>
        <p>Tambah, ubah, dan kelola hak akses pengguna J-Operasional.</p>
    </div>
    <button type="button" class="btn btn-primary" data-account-create>＋ Tambah Akun</button>
</section>

<section class="account-stat-grid" aria-label="Ringkasan akun">
    <article><span>Total Akun</span><strong><?= number_format($counts['total'], 0, ',', '.') ?></strong></article>
    <?php foreach ($roleLabels as $roleValue => $roleName): ?>
        <article><span><?= esc($roleName) ?></span><strong><?= number_format($counts[$roleValue] ?? 0, 0, ',', '.') ?></strong></article>
    <?php endforeach ?>
</section>

<section class="panel filter-panel account-filter-panel">
    <form action="<?= site_url('kelola-akun') ?>" method="get" class="account-filter-form">
        <div class="form-group search-group">
            <label for="accountSearch">Cari akun</label>
            <div class="input-with-icon"><span>⌕</span><input id="accountSearch" name="q" value="<?= esc($filters['keyword']) ?>" placeholder="Username atau nama pengguna"></div>
        </div>
        <div class="form-group">
            <label for="accountRoleFilter">Role</label>
            <select id="accountRoleFilter" name="role">
                <option value="">Semua role</option>
                <?php foreach ($roleLabels as $value => $label): ?>
                    <option value="<?= esc($value) ?>" <?= $filters['role'] === $value ? 'selected' : '' ?>><?= esc($label) ?></option>
                <?php endforeach ?>
            </select>
        </div>
        <div class="filter-actions"><button class="btn btn-outline" type="submit">Terapkan</button><a class="btn btn-ghost" href="<?= site_url('kelola-akun') ?>">Reset</a></div>
    </form>
</section>

<section class="panel account-table-panel">
    <div class="table-wrap">
        <table>
            <thead><tr><th>No.</th><th>Nama pengguna</th><th>Username</th><th>Role</th><th>Terakhir diperbarui</th><th>Aksi</th></tr></thead>
            <tbody>
            <?php if ($users === []): ?>
                <tr><td colspan="6"><div class="empty-state compact"><span>♙</span><strong>Akun tidak ditemukan</strong><p>Ubah filter atau tambahkan akun baru.</p></div></td></tr>
            <?php else: ?>
                <?php foreach ($users as $index => $user): ?>
                    <?php
                    $accountData = [
                        'id' => (int) $user['id'],
                        'username' => $user['username'],
                        'display_name' => $user['display_name'],
                        'role' => $user['role'],
                        'has_admin_login_pin' => ! empty($user['admin_login_pin_hash']),
                    ];
                    ?>
                    <tr>
                        <td><strong><?= (($pager->getCurrentPage('users') - 1) * $filters['perPage']) + $index + 1 ?></strong></td>
                        <td><div class="account-name-cell"><span><?= esc(strtoupper(substr($user['display_name'], 0, 1))) ?></span><strong><?= esc($user['display_name']) ?></strong></div></td>
                        <td><?= esc($user['username']) ?></td>
                        <td><span class="account-role <?= esc($user['role']) ?>"><?= esc($roleLabels[$user['role']] ?? ucfirst($user['role'])) ?></span></td>
                        <td><?= $user['updated_at'] ? date('d-m-Y H:i', strtotime($user['updated_at'])) . ' WIB' : '-' ?></td>
                        <td><div class="action-buttons">
                            <button type="button" class="icon-btn" data-account-detail-url="<?= site_url('kelola-akun/' . $user['id']) ?>" title="Detail" aria-label="Lihat detail akun">⌕</button>
                            <button type="button" class="icon-btn" data-account-edit='<?= esc(json_encode($accountData), 'attr') ?>' title="Edit" aria-label="Edit akun">✎</button>
                            <button type="button" class="icon-btn icon-btn-delete" data-account-delete='<?= esc(json_encode($accountData), 'attr') ?>' title="Hapus" aria-label="Hapus akun">×</button>
                        </div></td>
                    </tr>
                <?php endforeach ?>
            <?php endif ?>
            </tbody>
        </table>
    </div>
    <div class="table-list-footer">
        <form method="get" action="<?= site_url('kelola-akun') ?>" class="table-length-form">
            <input type="hidden" name="q" value="<?= esc($filters['keyword']) ?>"><input type="hidden" name="role" value="<?= esc($filters['role']) ?>">
            <label for="accountPerPage">Tampilkan</label><select id="accountPerPage" name="per_page" onchange="this.form.submit()">
                <?php foreach ([10,20,50,100] as $option): ?><option value="<?= $option ?>" <?= $filters['perPage'] === $option ? 'selected' : '' ?>><?= $option ?></option><?php endforeach ?>
            </select><span>data</span>
        </form>
        <div class="pagination-wrap"><?= $pager->links('users') ?></div>
    </div>
</section>

<div class="account-modal" id="accountFormModal" hidden aria-hidden="true">
    <button type="button" class="modal-backdrop" data-account-modal-close aria-label="Tutup form akun"></button>
    <section class="modal-dialog account-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="accountFormTitle">
        <header class="modal-header"><div class="modal-title-group"><span class="modal-title-icon" data-account-form-icon>＋</span><div><p>ADMINISTRATOR</p><h2 id="accountFormTitle" data-account-form-title>Tambah Akun</h2></div></div><button type="button" class="modal-close" data-account-modal-close aria-label="Tutup">×</button></header>
        <form action="<?= site_url('kelola-akun') ?>" method="post" data-account-form>
            <?= csrf_field() ?>
            <div class="modal-body">
                <div class="modal-section-heading"><span>01</span><div><strong>Data akun</strong><small>Lengkapi identitas dan hak akses pengguna</small></div></div>
                <div class="modal-form-grid">
                    <div class="form-group"><label for="accountDisplayName">Nama pengguna <span>*</span></label><input id="accountDisplayName" name="display_name" maxlength="150" required></div>
                    <div class="form-group"><label for="accountUsername">Username <span>*</span></label><input id="accountUsername" name="username" maxlength="100" autocomplete="off" required></div>
                    <div class="form-group"><label for="accountRole">Role <span>*</span></label><select id="accountRole" name="role" required><?php foreach ($roleLabels as $value => $label): ?><option value="<?= esc($value) ?>"><?= esc($label) ?></option><?php endforeach ?></select></div>
                    <div class="form-group"><label for="accountPassword">Password <span data-account-password-required>*</span></label><input id="accountPassword" name="password" type="password" minlength="8" maxlength="255" autocomplete="new-password"><small data-account-password-help>Minimal 8 karakter</small></div>
                    <div class="form-group modal-span-2" data-admin-login-pin-group hidden><label for="accountAdminLoginPin">PIN login admin <span data-admin-pin-required>*</span></label><input id="accountAdminLoginPin" name="admin_login_pin" type="password" inputmode="numeric" pattern="[0-9]{6}" minlength="6" maxlength="6" autocomplete="new-password" placeholder="Masukkan 6 angka"><small data-admin-pin-help>PIN digunakan untuk mengeluarkan sesi admin dari perangkat lain.</small></div>
                    <div class="modal-span-2 admin-credential-verification" data-admin-credential-verification hidden>
                        <div class="modal-section-heading"><span>02</span><div><strong>Verifikasi perubahan</strong><small>Wajib diisi ketika password atau PIN admin akan diganti</small></div></div>
                        <div class="modal-form-grid">
                            <div class="form-group"><label for="accountCurrentPassword">Password lama <span data-current-password-required>*</span></label><input id="accountCurrentPassword" name="current_password" type="password" maxlength="255" autocomplete="current-password" placeholder="Masukkan password lama"><small>Verifikasi pemilik akun admin.</small></div>
                            <div class="form-group" data-current-admin-pin-group><label for="accountCurrentAdminPin">PIN lama <span data-current-admin-pin-required>*</span></label><input id="accountCurrentAdminPin" name="current_admin_login_pin" type="password" inputmode="numeric" pattern="[0-9]{6}" minlength="6" maxlength="6" autocomplete="one-time-code" placeholder="Masukkan PIN lama"><small>PIN login admin yang sedang digunakan.</small></div>
                        </div>
                    </div>
                </div>
            </div>
            <footer class="modal-footer"><button type="button" class="btn btn-ghost" data-account-modal-close>Batal</button><button type="submit" class="btn btn-primary" data-account-submit>Simpan akun</button></footer>
        </form>
    </section>
</div>

<div class="account-modal" id="accountDetailModal" hidden aria-hidden="true">
    <button type="button" class="modal-backdrop" data-account-detail-close aria-label="Tutup detail"></button>
    <section class="modal-dialog account-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="accountDetailTitle">
        <header class="modal-header"><div class="modal-title-group"><span class="modal-title-icon">♙</span><div><p>DETAIL AKUN</p><h2 id="accountDetailTitle">Informasi Pengguna</h2></div></div><button type="button" class="modal-close" data-account-detail-close aria-label="Tutup">×</button></header>
        <div class="modal-body"><dl class="modal-detail-grid"><div><dt>Nama pengguna</dt><dd data-account-detail="display_name">-</dd></div><div><dt>Username</dt><dd data-account-detail="username">-</dd></div><div><dt>Role</dt><dd data-account-detail="role_label">-</dd></div><div><dt>PIN login admin</dt><dd data-account-detail="admin_pin_status">-</dd></div><div><dt>Dibuat</dt><dd data-account-detail="created_at">-</dd></div><div><dt>Terakhir diperbarui</dt><dd data-account-detail="updated_at">-</dd></div></dl></div>
        <footer class="modal-footer"><button type="button" class="btn btn-primary" data-account-detail-close>Tutup</button></footer>
    </section>
</div>

<div class="account-modal" id="accountDeleteModal" hidden aria-hidden="true">
    <button type="button" class="modal-backdrop" data-account-delete-close aria-label="Batal hapus"></button>
    <section class="modal-dialog delete-modal-dialog" role="alertdialog" aria-modal="true" aria-labelledby="accountDeleteTitle">
        <div class="delete-modal-body"><span class="delete-warning-icon">!</span><h2 id="accountDeleteTitle">Hapus Akun?</h2><p>Akun <strong data-account-delete-name></strong> akan dihapus permanen dari database.</p></div>
        <form method="post" action="" data-account-delete-form class="delete-modal-actions"><?= csrf_field() ?><button type="button" class="btn btn-ghost" data-account-delete-close>Batal</button><button type="submit" class="btn btn-delete">Ya, hapus</button></form>
    </section>
</div>

<script>
(() => {
    const formModal = document.getElementById('accountFormModal');
    const detailModal = document.getElementById('accountDetailModal');
    const deleteModal = document.getElementById('accountDeleteModal');
    const form = formModal.querySelector('[data-account-form]');
    const adminPinGroup = formModal.querySelector('[data-admin-login-pin-group]');
    const adminPinRequired = formModal.querySelector('[data-admin-pin-required]');
    const adminPinHelp = formModal.querySelector('[data-admin-pin-help]');
    const credentialVerification = formModal.querySelector('[data-admin-credential-verification]');
    const currentAdminPinGroup = formModal.querySelector('[data-current-admin-pin-group]');
    const currentPasswordRequired = formModal.querySelector('[data-current-password-required]');
    const currentAdminPinRequired = formModal.querySelector('[data-current-admin-pin-required]');
    const baseUrl = <?= json_encode(site_url('kelola-akun')) ?>;
    let editingAccount = false;
    let editingAccountHasPin = false;
    let editingOriginalRole = '';
    const openModal = modal => { modal.hidden = false; modal.setAttribute('aria-hidden', 'false'); requestAnimationFrame(() => modal.classList.add('open')); document.body.classList.add('modal-open'); };
    const closeModal = modal => { modal.classList.remove('open'); modal.setAttribute('aria-hidden', 'true'); setTimeout(() => { modal.hidden = true; if (!document.querySelector('.account-modal.open')) document.body.classList.remove('modal-open'); }, 180); };
    const synchronizeAdminPin = (editing = false, hasPin = false) => {
        const adminSelected = form.elements.role.value === 'admin';
        adminPinGroup.hidden = !adminSelected;
        form.elements.admin_login_pin.disabled = !adminSelected;
        if (!adminSelected) form.elements.admin_login_pin.value = '';
        form.elements.admin_login_pin.required = adminSelected && (!editing || !hasPin);
        adminPinRequired.hidden = !form.elements.admin_login_pin.required;
        adminPinHelp.textContent = editing && hasPin
            ? 'Kosongkan jika PIN 6 angka tidak diubah.'
            : 'Wajib 6 angka untuk mengeluarkan sesi admin dari perangkat lain.';
    };
    const synchronizeCredentialVerification = () => {
        const existingAdmin = editingAccount && editingOriginalRole === 'admin';
        const changingCredential = form.elements.password.value !== '' || form.elements.admin_login_pin.value !== '';
        credentialVerification.hidden = !existingAdmin;
        form.elements.current_password.disabled = !existingAdmin;
        form.elements.current_password.required = existingAdmin && changingCredential;
        currentPasswordRequired.hidden = !form.elements.current_password.required;
        currentAdminPinGroup.hidden = !existingAdmin || !editingAccountHasPin;
        form.elements.current_admin_login_pin.disabled = !existingAdmin || !editingAccountHasPin;
        form.elements.current_admin_login_pin.required = existingAdmin && editingAccountHasPin && changingCredential;
        currentAdminPinRequired.hidden = !form.elements.current_admin_login_pin.required;
    };
    const prepareCreate = (data = {}) => {
        editingAccount = false;
        editingAccountHasPin = false;
        editingOriginalRole = '';
        form.reset(); form.action = baseUrl;
        formModal.querySelector('[data-account-form-title]').textContent = 'Tambah Akun';
        formModal.querySelector('[data-account-form-icon]').textContent = '＋';
        formModal.querySelector('[data-account-submit]').textContent = 'Simpan akun';
        form.elements.password.required = true;
        formModal.querySelector('[data-account-password-required]').hidden = false;
        formModal.querySelector('[data-account-password-help]').textContent = 'Minimal 8 karakter';
        Object.entries(data).forEach(([key, value]) => { if (form.elements[key]) form.elements[key].value = value ?? ''; });
        synchronizeAdminPin(false, false);
        synchronizeCredentialVerification();
        openModal(formModal);
    };
    const prepareEdit = (data) => {
        editingAccount = true;
        editingAccountHasPin = Boolean(data.has_admin_login_pin);
        editingOriginalRole = data.original_role ?? data.role;
        form.reset(); form.action = `${baseUrl}/${data.id}`;
        formModal.querySelector('[data-account-form-title]').textContent = 'Edit Akun';
        formModal.querySelector('[data-account-form-icon]').textContent = '✎';
        formModal.querySelector('[data-account-submit]').textContent = 'Simpan perubahan';
        form.elements.display_name.value = data.display_name; form.elements.username.value = data.username; form.elements.role.value = data.role; form.elements.password.required = false;
        formModal.querySelector('[data-account-password-required]').hidden = true;
        formModal.querySelector('[data-account-password-help]').textContent = 'Kosongkan jika password tidak diubah';
        synchronizeAdminPin(editingAccount, editingAccountHasPin);
        synchronizeCredentialVerification();
        openModal(formModal);
    };
    form.elements.role.addEventListener('change', () => { synchronizeAdminPin(editingAccount, editingAccountHasPin); synchronizeCredentialVerification(); });
    form.elements.password.addEventListener('input', synchronizeCredentialVerification);
    form.elements.admin_login_pin.addEventListener('input', synchronizeCredentialVerification);
    document.querySelector('[data-account-create]')?.addEventListener('click', () => prepareCreate());
    document.querySelectorAll('[data-account-edit]').forEach(button => button.addEventListener('click', () => prepareEdit(JSON.parse(button.dataset.accountEdit))));
    document.querySelectorAll('[data-account-delete]').forEach(button => button.addEventListener('click', () => { const data = JSON.parse(button.dataset.accountDelete); deleteModal.querySelector('[data-account-delete-name]').textContent = `${data.display_name} (${data.username})`; deleteModal.querySelector('[data-account-delete-form]').action = `${baseUrl}/${data.id}/hapus`; openModal(deleteModal); }));
    document.querySelectorAll('[data-account-detail-url]').forEach(button => button.addEventListener('click', async () => { button.disabled = true; try { const response = await fetch(button.dataset.accountDetailUrl, {headers:{'X-Requested-With':'XMLHttpRequest'}}); const result = await response.json(); if (!response.ok || !result.success) throw new Error(result.message || 'Detail akun gagal dimuat.'); Object.entries(result.user).forEach(([key,value]) => { const target = detailModal.querySelector(`[data-account-detail="${key}"]`); if (target) target.textContent = value ?? '-'; }); openModal(detailModal); } catch (error) { alert(error.message); } finally { button.disabled = false; } }));
    document.querySelectorAll('[data-account-modal-close]').forEach(button => button.addEventListener('click', () => closeModal(formModal)));
    document.querySelectorAll('[data-account-detail-close]').forEach(button => button.addEventListener('click', () => closeModal(detailModal)));
    document.querySelectorAll('[data-account-delete-close]').forEach(button => button.addEventListener('click', () => closeModal(deleteModal)));
    document.addEventListener('keydown', event => { if (event.key !== 'Escape') return; [formModal, detailModal, deleteModal].forEach(modal => { if (!modal.hidden) closeModal(modal); }); });
    const failedModal = <?= json_encode($modalToOpen) ?>;
    const failedData = <?= json_encode($formData, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    const failedEditId = <?= json_encode($editId) ?>;
    if (failedModal === 'create') prepareCreate(failedData);
    if (failedModal === 'edit' && failedEditId) prepareEdit({...failedData, id: failedEditId});
})();
</script>
<?= $this->endSection() ?>
