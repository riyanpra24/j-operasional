<?php

/** @var list<array<string, mixed>> $sessions */
/** @var array<string, mixed> $filters */
/** @var int $totalUsers */
/** @var int $activeCount */
/** @var int $availableCount */
/** @var int|string $currentUserId */
/** @var list<array{name: string, username: string, duration: string, hours: float, daily_hours: list<float>, last_accessed: string, is_active: bool, percentage: int}> $usageStats */
/** @var array{month: int, year: int, days: int} $usagePeriod */
?>
<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<?php
$roleLabels = \Config\UserRoles::LABELS;
$usageColors = ['#168fd1', '#f26a43', '#8d5bd2', '#19a76b', '#db4c6e', '#c99619', '#0fa6ac', '#87563c'];
$monthLabels = [1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'];
$usageYears = range((int) date('Y'), max(2020, (int) date('Y') - 4));
$usageChart = [
    'labels' => array_map(static fn (int $day): string => (string) $day, range(1, (int) $usagePeriod['days'])),
    'datasets' => array_map(static fn (array $usage, int $index): array => [
        'name' => $usage['name'],
        'color' => $usageColors[$index % count($usageColors)],
        'values' => $usage['daily_hours'],
    ], $usageStats, array_keys($usageStats)),
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
            <p>Setiap garis menunjukkan jam akses satu akun pada tanggal yang dipilih. Rincian di bawah memuat tanggal terakhir diakses.</p>
        </div>
        <form class="session-usage-filter" action="<?= site_url('kelola-akun/session-account') ?>" method="get">
            <?php if ($filters['keyword'] !== ''): ?><input type="hidden" name="q" value="<?= esc($filters['keyword'], 'attr') ?>"><?php endif ?>
            <?php if ($filters['role'] !== ''): ?><input type="hidden" name="role" value="<?= esc($filters['role'], 'attr') ?>"><?php endif ?>
            <?php if ($filters['order'] !== ''): ?><input type="hidden" name="order" value="<?= esc($filters['order'], 'attr') ?>"><?php endif ?>
            <label for="usageMonth">Periode grafik</label>
            <select id="usageMonth" name="usage_month">
                <?php foreach ($monthLabels as $monthNumber => $monthName): ?><option value="<?= $monthNumber ?>" <?= (int) $usagePeriod['month'] === $monthNumber ? 'selected' : '' ?>><?= esc($monthName) ?></option><?php endforeach ?>
            </select>
            <select aria-label="Tahun grafik" name="usage_year">
                <?php foreach ($usageYears as $year): ?><option value="<?= $year ?>" <?= (int) $usagePeriod['year'] === $year ? 'selected' : '' ?>><?= $year ?></option><?php endforeach ?>
            </select>
            <button type="submit" class="btn btn-outline">Tampilkan</button>
        </form>
    </div>
    <?php if ($usageStats === []): ?>
        <div class="empty-state compact session-usage-empty"><span>◷</span><strong>Belum ada pemakaian sesi</strong><p>Grafik akan muncul saat akun masuk ke sistem.</p></div>
    <?php else: ?>
        <div class="session-usage-line-panel">
            <canvas id="sessionUsageLineChart" data-chart='<?= esc(json_encode($usageChart, JSON_UNESCAPED_UNICODE), 'attr') ?>' aria-label="Grafik garis akumulasi jam akses per akun" role="img"></canvas>
        </div>
        <div class="session-usage-chart" aria-label="Rincian pemakaian setiap akun">
            <?php foreach ($usageStats as $usageIndex => $usage): ?>
                <article class="session-usage-row <?= $usage['is_active'] ? 'is-active' : '' ?>">
                    <div class="session-usage-account"><span style="--account-color:<?= esc($usageColors[$usageIndex % count($usageColors)], 'attr') ?>"><?= esc(strtoupper(substr($usage['name'], 0, 1))) ?></span><div><strong><?= esc($usage['name']) ?></strong><small>@<?= esc($usage['username']) ?><?= $usage['is_active'] ? ' · Aktif' : '' ?></small></div></div>
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

<script src="<?= base_url('assets/vendor/chartjs/chart.umd.min.js') ?>"></script>
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
        if (!canvas || !window.Chart) return;
        let payload;
        try { payload = JSON.parse(canvas.dataset.chart || '{}'); } catch (error) { return; }
        const labels = Array.isArray(payload.labels) ? payload.labels : [];
        const datasets = (Array.isArray(payload.datasets) ? payload.datasets : []).map((dataset, index) => ({
            label: dataset.name || `Akun ${index + 1}`,
            data: Array.isArray(dataset.values) ? dataset.values.map(Number) : [],
            borderColor: dataset.color || '#168fd1',
            backgroundColor: index === 0 ? `${dataset.color || '#168fd1'}1c` : 'transparent',
            pointBackgroundColor: '#fff', pointBorderColor: dataset.color || '#168fd1', pointBorderWidth: 2,
            pointRadius: 3, pointHoverRadius: 5, borderWidth: 2.4, tension: .42,
            fill: index === 0, spanGaps: true,
        }));
        new Chart(canvas, {
            type: 'line', data: {labels, datasets},
            options: {
                responsive: true, maintainAspectRatio: false,
                animation: {duration: 850, easing: 'easeOutQuart'},
                interaction: {mode: 'index', intersect: false},
                plugins: {
                    legend: {position: 'bottom', labels: {usePointStyle: true, pointStyle: 'circle', boxWidth: 7, padding: 16, color: '#567795', font: {size: 10, weight: '700'}}},
                    tooltip: {backgroundColor: '#092e62', padding: 11, cornerRadius: 9, displayColors: true, callbacks: {label: context => `${context.dataset.label}: ${Number(context.raw || 0).toLocaleString('id-ID', {maximumFractionDigits: 2})} jam`}},
                },
                scales: {
                    x: {grid: {display: false}, border: {display: false}, ticks: {autoSkip: true, maxTicksLimit: 16, color: '#7893ad', font: {size: 10, weight: '700'}}},
                    y: {beginAtZero: true, grid: {color: 'rgba(76,134,180,.13)'}, border: {display: false}, ticks: {color: '#7893ad', font: {size: 10}, callback: value => `${value} jam`}},
                },
            },
        });
    })();
</script>
<?= $this->endSection() ?>
