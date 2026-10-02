<?php

/** @var list<array<string, mixed>> $sessions */
/** @var array<string, mixed> $filters */
/** @var int $totalUsers */
/** @var int $activeCount */
/** @var int $availableCount */
/** @var int|string $currentUserId */
/** @var list<array{name: string, username: string, duration: string, hours: float, last_accessed: string, is_active: bool, percentage: int}> $usageStats */
?>
<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<?php
$roleLabels = \Config\UserRoles::LABELS;
$usageChart = [
    'labels' => array_map(static fn (array $usage): string => (string) $usage['name'], $usageStats),
    'values' => array_map(static fn (array $usage): float => (float) $usage['hours'], $usageStats),
];
?>

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

<section class="panel session-usage-panel" aria-labelledby="sessionUsageTitle">
    <div class="session-usage-header">
        <div>
            <span class="eyebrow">PEMAKAIAN SESI</span>
            <h2 id="sessionUsageTitle">Grafik pemakaian seluruh akun</h2>
            <p>Garis menunjukkan akumulasi jam akses setiap akun. Rincian di bawah memuat tanggal terakhir diakses.</p>
        </div>
        <span class="session-usage-period">Semua akun</span>
    </div>
    <?php if ($usageStats === []): ?>
        <div class="empty-state compact session-usage-empty"><span>◷</span><strong>Belum ada pemakaian sesi</strong><p>Grafik akan muncul saat akun masuk ke sistem.</p></div>
    <?php else: ?>
        <div class="session-usage-line-panel">
            <canvas id="sessionUsageLineChart" data-chart='<?= esc(json_encode($usageChart, JSON_UNESCAPED_UNICODE), 'attr') ?>' aria-label="Grafik garis akumulasi jam akses per akun" role="img"></canvas>
        </div>
        <div class="session-usage-chart" aria-label="Rincian pemakaian setiap akun">
            <?php foreach ($usageStats as $usage): ?>
                <article class="session-usage-row <?= $usage['is_active'] ? 'is-active' : '' ?>">
                    <div class="session-usage-account"><span><?= esc(strtoupper(substr($usage['name'], 0, 1))) ?></span><div><strong><?= esc($usage['name']) ?></strong><small>@<?= esc($usage['username']) ?><?= $usage['is_active'] ? ' · Aktif' : '' ?></small></div></div>
                    <span class="session-usage-access"><small>Terakhir diakses</small><strong><?= esc($usage['last_accessed']) ?></strong></span>
                    <span class="session-usage-duration"><small>Akumulasi akses</small><strong><?= esc($usage['duration']) ?></strong></span>
                </article>
            <?php endforeach ?>
        </div>
    <?php endif ?>
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
        <?= view('components/list_order_filter', ['id' => 'sessionOrder', 'value' => $filters['order']]) ?>
        <div class="filter-actions"><button class="btn btn-outline" type="submit">Terapkan</button><a class="btn btn-ghost" href="<?= site_url('kelola-akun/session-account') ?>">Reset filter</a></div>
    </form>
</section>

<section class="panel account-table-panel session-table-panel">
    <div class="session-table-header">
        <div><strong>Akun yang sedang aktif</strong><small>Status aktif dan tanggal terakhir diakses diperbarui paling cepat setiap satu menit</small></div>
        <span><?= number_format(count($sessions), 0, ',', '.') ?> sesi ditampilkan</span>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>No.</th>
                    <th>Pengguna</th>
                    <th>Role</th>
                    <th>Perangkat</th>
                    <th>Alamat IP</th>
                    <th>Status</th>
                    <th>Terakhir Diakses</th>
                    <th>Aktif Sejak</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($sessions === []): ?>
                    <tr>
                        <td colspan="9">
                            <div class="empty-state compact"><span>✓</span><strong>Tidak ada sesi aktif</strong>
                                <p>Tidak ada akun aktif yang sesuai dengan filter saat ini.</p>
                            </div>
                        </td>
                    </tr>
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
                            <td><span class="session-status active"><i aria-hidden="true"></i>Aktif</span></td>
                            <td><span class="session-time"><strong><?= esc($activeSession['last_accessed_label']) ?></strong><small><?= esc($activeSession['last_accessed_ago']) ?></small></span></td>
                            <td><span class="session-time"><strong><?= esc($activeSession['active_since_label']) ?></strong><small>Sejak login</small></span></td>
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
            setTimeout(() => {
                modal.hidden = true;
                document.body.classList.remove('modal-open');
            }, 180);
        };
        document.querySelectorAll('[data-session-reset]').forEach(button => button.addEventListener('click', () => openModal(JSON.parse(button.dataset.sessionReset))));
        document.querySelectorAll('[data-session-reset-close]').forEach(button => button.addEventListener('click', closeModal));
        document.addEventListener('keydown', event => {
            if (event.key === 'Escape' && !modal.hidden) closeModal();
        });
    })();

    (() => {
        const canvas = document.getElementById('sessionUsageLineChart');
        if (!canvas) return;
        let chart;
        try { chart = JSON.parse(canvas.dataset.chart || '{}'); } catch (error) { return; }
        const labels = Array.isArray(chart.labels) ? chart.labels : [];
        const values = Array.isArray(chart.values) ? chart.values.map(Number) : [];
        const draw = () => {
            const context = canvas.getContext('2d');
            const width = Math.max(320, canvas.clientWidth || 320);
            const height = 205;
            const ratio = window.devicePixelRatio || 1;
            canvas.width = width * ratio;
            canvas.height = height * ratio;
            context.setTransform(ratio, 0, 0, ratio, 0, 0);
            context.clearRect(0, 0, width, height);
            const padding = {top: 22, right: 18, bottom: 43, left: 43};
            const plotWidth = width - padding.left - padding.right;
            const plotHeight = height - padding.top - padding.bottom;
            const maxValue = Math.max(1, ...values) * 1.12;
            context.font = '10px Arial';
            context.fillStyle = '#8aa0b2';
            context.strokeStyle = '#e7eff3';
            context.lineWidth = 1;
            for (let row = 0; row <= 4; row++) {
                const y = padding.top + (plotHeight * row / 4);
                context.beginPath(); context.moveTo(padding.left, y); context.lineTo(width - padding.right, y); context.stroke();
                const value = (maxValue * (4 - row) / 4).toFixed(1).replace('.0', '');
                context.fillText(`${value}j`, 6, y + 3);
            }
            if (!values.length) return;
            const points = values.map((value, index) => ({
                x: padding.left + (labels.length === 1 ? plotWidth / 2 : plotWidth * index / (labels.length - 1)),
                y: padding.top + plotHeight - (Math.max(0, value) / maxValue * plotHeight),
            }));
            const fill = context.createLinearGradient(0, padding.top, 0, padding.top + plotHeight);
            fill.addColorStop(0, 'rgba(31,143,216,.22)'); fill.addColorStop(1, 'rgba(50,197,189,0)');
            context.beginPath(); context.moveTo(points[0].x, padding.top + plotHeight);
            points.forEach(point => context.lineTo(point.x, point.y));
            context.lineTo(points.at(-1).x, padding.top + plotHeight); context.closePath(); context.fillStyle = fill; context.fill();
            context.beginPath(); points.forEach((point, index) => index ? context.lineTo(point.x, point.y) : context.moveTo(point.x, point.y));
            context.strokeStyle = '#1e8fd8'; context.lineWidth = 2.5; context.stroke();
            context.textAlign = 'center'; context.fillStyle = '#668197'; context.font = '9px Arial';
            points.forEach((point, index) => {
                context.beginPath(); context.arc(point.x, point.y, 3.5, 0, Math.PI * 2); context.fillStyle = '#fff'; context.fill(); context.lineWidth = 2; context.strokeStyle = '#20acb6'; context.stroke();
                const label = labels[index].split(' ').slice(0, 2).join(' ');
                context.fillStyle = '#668197'; context.fillText(label, point.x, height - 17);
            });
            context.textAlign = 'start';
        };
        draw();
        new ResizeObserver(draw).observe(canvas);
    })();
</script>
<?= $this->endSection() ?>
