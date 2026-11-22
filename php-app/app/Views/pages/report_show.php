<?php
/** @var array{id: int, name: string, email: string, role: string} $user */
/** @var array<string, mixed> $report */
/** @var array<string, mixed> $metrics */
/** @var array<string, mixed> $summary */
/** @var string|null $successMessage */
/** @var string|null $warningMessage */

$kpis = is_array($metrics['kpis'] ?? null) ? $metrics['kpis'] : null;
$keyFindings = is_array($summary['key_findings'] ?? null) ? $summary['key_findings'] : [];
$recommendations = is_array($summary['recommendations'] ?? null) ? $summary['recommendations'] : [];
$monthlyTrends = is_array($metrics['monthly_trends'] ?? null) ? $metrics['monthly_trends'] : [];

$jsonFlags = JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT;
$chartMonths = json_encode(array_column($monthlyTrends, 'month'), $jsonFlags) ?: '[]';
$chartMonthlyRev = json_encode(array_column($monthlyTrends, 'revenue'), $jsonFlags) ?: '[]';

$pageTitle = 'Executive Report #' . (int) $report['id'];
require dirname(__DIR__) . '/layouts/header.php';
?>

<div class="page-header">
    <div>
        <h1>Executive Report #<?= (int) $report['id'] ?></h1>
        <p class="subtitle">
            <?= htmlspecialchars((string) $report['original_filename'], ENT_QUOTES, 'UTF-8') ?> ·
            Period: <?= htmlspecialchars((string) $report['period_start'], ENT_QUOTES, 'UTF-8') ?>
            → <?= htmlspecialchars((string) $report['period_end'], ENT_QUOTES, 'UTF-8') ?> ·
            Generated <?= htmlspecialchars((string) $report['created_at'], ENT_QUOTES, 'UTF-8') ?>
        </p>
    </div>
    <div class="actions no-print">
        <a href="/" class="btn btn-secondary">← Dashboard</a>
        <a href="/datasets/analyze?id=<?= urlencode((string) $report['dataset_id']) ?>" class="btn btn-secondary">Live Analytics</a>
        <button type="button" class="btn btn-primary" onclick="window.print()">Print / Save PDF</button>
    </div>
</div>

<?php if ($successMessage !== null): ?>
    <div class="alert alert-success no-print" role="status"><?= htmlspecialchars($successMessage, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>
<?php if (!empty($warningMessage)): ?>
    <div class="alert alert-warning no-print" role="status"><?= htmlspecialchars((string) $warningMessage, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>
<section class="card">
    <div style="display:flex;justify-content:space-between;align-items:center;gap:0.75rem;margin-bottom:0.65rem;">
        <h2 style="margin:0;"><?= htmlspecialchars((string) ($summary['headline'] ?? 'Executive Summary'), ENT_QUOTES, 'UTF-8') ?></h2>
        <span class="badge badge-validated">
            <?= htmlspecialchars((string) $report['ai_model'], ENT_QUOTES, 'UTF-8') ?>
            (<?= htmlspecialchars((string) $report['prompt_version'], ENT_QUOTES, 'UTF-8') ?>)
        </span>
    </div>
    <p style="margin:0;font-size:1rem;"><?= htmlspecialchars((string) ($summary['executive_summary'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
</section>

<?php if ($kpis !== null): ?>
    <section class="kpi-grid">
        <div class="kpi-card">
            <div class="kpi-label">Total Revenue</div>
            <div class="kpi-value">$<?= number_format((float) $kpis['total_revenue'], 2) ?></div>
        </div>
        <div class="kpi-card">
            <div class="kpi-label">Total Orders</div>
            <div class="kpi-value"><?= number_format((int) $kpis['total_orders']) ?></div>
        </div>
        <div class="kpi-card">
            <div class="kpi-label">Average Order Value</div>
            <div class="kpi-value">$<?= number_format((float) $kpis['average_order_value'], 2) ?></div>
        </div>
        <div class="kpi-card">
            <div class="kpi-label">Units Sold</div>
            <div class="kpi-value"><?= number_format((float) $kpis['total_units_sold'], 0) ?></div>
        </div>
    </section>
<?php endif; ?>

<section class="grid-2">
    <div class="card">
        <h2>Key Findings</h2>
        <?php if ($keyFindings !== []): ?>
            <ul style="margin:0;padding-left:1.25rem;">
                <?php foreach ($keyFindings as $finding): ?>
                    <li style="margin-bottom:0.45rem;"><?= htmlspecialchars((string) $finding, ENT_QUOTES, 'UTF-8') ?></li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p class="subtitle">No findings recorded.</p>
        <?php endif; ?>
    </div>

    <div class="card">
        <h2>Recommendations</h2>
        <?php if ($recommendations !== []): ?>
            <ul style="margin:0;padding-left:1.25rem;">
                <?php foreach ($recommendations as $rec): ?>
                    <li style="margin-bottom:0.45rem;"><?= htmlspecialchars((string) $rec, ENT_QUOTES, 'UTF-8') ?></li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p class="subtitle">No recommendations recorded.</p>
        <?php endif; ?>
    </div>
</section>

<?php if ($monthlyTrends !== []): ?>
    <section class="card">
        <h2>Monthly Performance (Saved Snapshot)</h2>
        <div class="chart-box" style="margin-bottom:1.25rem;">
            <canvas id="reportMonthlyChart"></canvas>
        </div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Month</th>
                        <th>Revenue</th>
                        <th>Orders</th>
                        <th>Units Sold</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($monthlyTrends as $row): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars((string) $row['month'], ENT_QUOTES, 'UTF-8') ?></strong></td>
                            <td>$<?= number_format((float) $row['revenue'], 2) ?></td>
                            <td><?= (int) $row['orders'] ?></td>
                            <td><?= number_format((float) $row['units'], 0) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
    <script>
        if (typeof Chart !== 'undefined') {
            new Chart(document.getElementById('reportMonthlyChart'), {
                type: 'bar',
                data: {
                    labels: <?= $chartMonths ?>,
                    datasets: [{
                        label: 'Revenue ($)',
                        data: <?= $chartMonthlyRev ?>,
                        backgroundColor: '#2563eb',
                        borderRadius: 6
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: { y: { beginAtZero: true } }
                }
            });
        }
    </script>
<?php endif; ?>

<?php require dirname(__DIR__) . '/layouts/footer.php'; ?>