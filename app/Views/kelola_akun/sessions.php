<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<?php $roleLabels = \Config\UserRoles::LABELS; ?>

<section class="heading-actions account-heading session-account-heading">
    <div>
        <span class="eyebrow">ADMINISTRATOR</span>
        <h1>Session Account</h1>
        <p>Pantau akun yang sedang aktif dan cabut akses perangkat bila diperlukan.</p>
    </div>
    <span class="session-live-indicator"><i aria-hidden="true"></i> Pemantauan aktif</span>
</section>

<section class="account-stat-grid session-stat-grid" aria-label="Ringkasan sesi akun">
    <article><span>Total Akun</span><strong><?= number_format($totalUsers, 0, ',', '.') ?></strong></article>
    <article class="session-stat-active"><span>Sesi Aktif</span><strong><?= number_format($activeCount, 0, ',', '.') ?></strong></article>
    <article><span>Siap Digunakan</span><strong><?= number_format($availableCount, 0, ',', '.') ?></strong></article>
</section>

<section class="panel filter-panel account-filter-panel">
    <form action="<?= site_url('kelola-akun/session-account') ?>" method="get" class="account-filter-form session-filter-form">
        <div class="form-group search-group">
            <label for="sessionSearch">Cari sesi aktif</label>
            <div class="input-with-icon"><span>⌕</span><input id="sessionSearch" name="q" value="<?= esc($filters['keyword']) ?>" placeholder="Nama, username, atau alamat IP"></div>
        </div>
        <div class="form-group">
            <label for="sessionRoleFilter">Role</label>
            <select id="sessionRoleFilter" name="role">
                <option value="">Semua role</option>
                <?php foreach ($roleLabels as $value => $label): ?>
                    <option value="<?= esc($value) ?>" <?= $filters['role'] === $value ? 'selected' : '' ?>><?= esc($label) ?></option>
                <?php endforeach ?>
            </select>
        </div>
        <div class="filter-actions"><button class="btn btn-outline" type="submit">Terapkan</button><a class="btn btn-ghost" href="<?= site_url('kelola-akun/session-account') ?>">Reset filter</a></div>
    </form>
</section>

<section class="panel account-table-panel session-table-panel">
    <div class="session-table-header">
        <div><strong>Akun yang sedang aktif</strong><small>Aktivitas diperbarui paling cepat setiap satu menit</small></div>
        <span><?= number_format(count($sessions), 0, ',', '.') ?> sesi ditampilkan</span>
    </div>
    <div class="table-wrap">
        <table>
            <thead><tr><th>No.</th><th>Pengguna</th><th>Role</th><th>Perangkat</th><th>Alamat IP</th><th>Aktivitas Terakhir</th><th>Berakhir</th><th>Aksi</th></tr></thead>
            <tbody>
            <?php if ($sessions === []): ?>
                <tr><td colspan="8"><div class="empty-state compact"><span>✓</span><strong>Tidak ada sesi aktif</strong><p>Tidak ada akun aktif yang sesuai dengan filter saat ini.</p></div></td></tr>
            <?php else: ?>
                <?php foreach ($sessions as $index => $activeSession): ?>
                    <?php $isCurrentSession = (int) $activeSession['user_id'] === $currentUserId; ?>
                    <tr>
                        <td><strong><?= $index + 1 ?></strong></td>
                        <td>
                            <div class="account-name-cell">
                                <span><?= esc(strtoupper(substr($activeSession['display_name'], 0, 1))) ?></span>
                                <div><strong><?= esc($activeSession['display_name']) ?></strong><small>@<?= esc($activeSession['username']) ?></small></div>
                            </div>
                        </td>
                        <td><span class="account-role <?= esc($activeSession['role']) ?>"><?= esc($roleLabels[$activeSession['role']] ?? ucfirst($activeSession['role'])) ?></span></td>
                        <td><span class="session-device" title="<?= esc($activeSession['user_agent'], 'attr') ?>"><i aria-hidden="true">▣</i><?= esc($activeSession['device_label']) ?></span></td>
                        <td><code class="session-ip"><?= esc($activeSession['ip_address'] ?: '-') ?></code></td>
                        <td><?= date('d-m-Y H:i', strtotime($activeSession['last_seen_at'])) ?> WIB</td>
                        <td><?= date('d-m-Y H:i', strtotime($activeSession['expires_at'])) ?> WIB</td>
                        <td>
                            <?php if ($isCurrentSession): ?>
                                <span class="session-current-badge">Sesi ini</span>
                            <?php else: ?>
                                <button type="button" class="btn btn-danger-outline session-reset-button" data-session-reset='<?= esc(json_encode([
                                    'url' => site_url('kelola-akun/session-account/' . $activeSession['user_id'] . '/reset'),
                                    'name' => $activeSession['display_name'],
                                    'username' => $activeSession['username'],
                                ]), 'attr') ?>'>Reset Session</button>
                            <?php endif ?>
                        </td>
                    </tr>
                <?php endforeach ?>
            <?php endif ?>
            </tbody>
        </table>
    </div>
</section>

<div class="account-modal" id="sessionResetModal" hidden aria-hidden="true">
    <button type="button" class="modal-backdrop" data-session-reset-close aria-label="Batal reset sesi"></button>
    <section class="modal-dialog delete-modal-dialog" role="alertdialog" aria-modal="true" aria-labelledby="sessionResetTitle">
        <div class="delete-modal-body">
            <span class="session-reset-warning">↻</span>
            <h2 id="sessionResetTitle">Reset Session Account?</h2>
            <p>Sesi <strong data-session-reset-name></strong> akan dicabut. Perangkat tersebut harus login kembali untuk mengakses sistem.</p>
        </div>
        <form method="post" action="" data-session-reset-form class="delete-modal-actions">
            <?= csrf_field() ?>
            <button type="button" class="btn btn-ghost" data-session-reset-close>Batal</button>
            <button type="submit" class="btn btn-primary">Ya, reset session</button>
        </form>
    </section>
</div>

<script>
(() => {
    const modal = document.getElementById('sessionResetModal');
    if (!modal) return;
    const form = modal.querySelector('[data-session-reset-form]');
    const name = modal.querySelector('[data-session-reset-name]');
    const openModal = data => {
        form.action = data.url;
        name.textContent = `${data.name} (@${data.username})`;
        modal.hidden = false;
        modal.setAttribute('aria-hidden', 'false');
        requestAnimationFrame(() => modal.classList.add('open'));
        document.body.classList.add('modal-open');
    };
    const closeModal = () => {
        modal.classList.remove('open');
        modal.setAttribute('aria-hidden', 'true');
        setTimeout(() => { modal.hidden = true; document.body.classList.remove('modal-open'); }, 180);
    };
    document.querySelectorAll('[data-session-reset]').forEach(button => button.addEventListener('click', () => openModal(JSON.parse(button.dataset.sessionReset))));
    document.querySelectorAll('[data-session-reset-close]').forEach(button => button.addEventListener('click', closeModal));
    document.addEventListener('keydown', event => { if (event.key === 'Escape' && !modal.hidden) closeModal(); });
})();
</script>
<?= $this->endSection() ?>
