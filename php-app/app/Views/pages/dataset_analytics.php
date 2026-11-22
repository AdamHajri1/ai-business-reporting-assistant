<?php
/** @var array{id: int, name: string, email: string, role: string} $user */
/** @var string $csrfToken */
/** @var array<string, mixed> $dataset */
/** @var array<string, mixed>|null $analytics */
/** @var array{start_date: string, end_date: string} $filters */
/** @var string|null $errorMessage */

$kpis = is_array($analytics['kpis'] ?? null) ? $analytics['kpis'] : null;
$monthlyTrends = is_array($analytics['monthly_trends'] ?? null) ? $analytics['monthly_trends'] : [];
$categories = is_array($analytics['category_breakdown'] ?? null) ? $analytics['category_breakdown'] : [];
$topProducts = is_array($analytics['top_products'] ?? null) ? $analytics['top_products'] : [];
$comparison = is_array($analytics['period_comparison'] ?? null) ? $analytics['period_comparison'] : null;

$jsonFlags = JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT;
$chartMonths = json_encode(array_column($monthlyTrends, 'month'), $jsonFlags) ?: '[]';
$chartMonthlyRev = json_encode(array_column($monthlyTrends, 'revenue'), $jsonFlags) ?: '[]';
$chartCatLabels = json_encode(array_column($categories, 'category'), $jsonFlags) ?: '[]';
$chartCatRev = json_encode(array_column($categories, 'revenue'), $jsonFlags) ?: '[]';

$pageTitle = 'Analytics';
require dirname(__DIR__) . '/layouts/header.php';
?>

<div class="page-header">
    <div>
        <h1>Analytics: <?= htmlspecialchars((string) $dataset['original_filename'], ENT_QUOTES, 'UTF-8') ?></h1>
        <p class="subtitle">
            <?php if ($kpis !== null): ?>
                Active period: <?= htmlspecialchars((string) $kpis['date_min'], ENT_QUOTES, 'UTF-8') ?>
                → <?= htmlspecialchars((string) $kpis['date_max'], ENT_QUOTES, 'UTF-8') ?>
            <?php else: ?>
                Filter and explore verified business metrics.
            <?php endif; ?>
        </p>
    </div>
    <div class="actions">
        <a href="/" class="btn btn-secondary">← Back to Dashboard</a>
        <?php if ($kpis !== null): ?>
            <form method="POST" action="/reports/generate" style="margin:0;"
                onsubmit="const btn = this.querySelector('button[type=submit]'); btn.disabled = true; btn.textContent = 'Generating...';">
                <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="dataset_id" value="<?= htmlspecialchars((string) $dataset['id'], ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="start_date" value="<?= htmlspecialchars($filters['start_date'], ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="end_date" value="<?= htmlspecialchars($filters['end_date'], ENT_QUOTES, 'UTF-8') ?>">
                <button type="submit" class="btn btn-primary">Generate &amp; Save Executive Report</button>
            </form>
        <?php endif; ?>
    </div>
</div>

<section class="card">
    <h2>Filter by Date Range</h2>
    <form method="GET" action="/datasets/analyze" class="inline-form">
        <input type="hidden" name="id" value="<?= htmlspecialchars((string) $dataset['id'], ENT_QUOTES, 'UTF-8') ?>">
        <div class="form-group">
            <label for="start_date">Start date</label>
            <input id="start_date" name="start_date" type="date"
                   value="<?= htmlspecialchars($filters['start_date'], ENT_QUOTES, 'UTF-8') ?>">
        </div>
        <div class="form-group">
            <label for="end_date">End date</label>
            <input id="end_date" name="end_date" type="date"
                   value="<?= htmlspecialchars($filters['end_date'], ENT_QUOTES, 'UTF-8') ?>">
        </div>
        <button type="submit" class="btn btn-primary">Apply Filter</button>
        <a href="/datasets/analyze?id=<?= urlencode((string) $dataset['id']) ?>" class="btn btn-secondary">Reset</a>
    </form>
</section>

<?php if ($errorMessage !== null): ?>
    <div class="alert alert-error" role="alert"><?= htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<?php if ($kpis !== null): ?>
    <section class="kpi-grid">
        <div class="kpi-card">
            <div class="kpi-label">Total Revenue</div>
            <div class="kpi-value">$<?= number_format((float) $kpis['total_revenue'], 2) ?></div>
            <?php if ($comparison !== null && $comparison['revenue_change_pct'] !== null): ?>
                <?php $pct = (float) $comparison['revenue_change_pct']; ?>
                <div class="kpi-sub">
                    <?= $pct >= 0 ? '▲' : '▼' ?>
                    <?= number_format(abs($pct), 2) ?>% vs <?= htmlspecialchars((string) $comparison['previous_month'], ENT_QUOTES, 'UTF-8') ?>
                </div>
            <?php endif; ?>
        </div>
        <div class="kpi-card">
            <div class="kpi-label">Total Orders</div>
            <div class="kpi-value"><?= number_format((int) $kpis['total_orders']) ?></div>
            <div class="kpi-sub"><?= (int) $kpis['row_count'] ?> line items</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-label">Average Order Value</div>
            <div class="kpi-value">$<?= number_format((float) $kpis['average_order_value'], 2) ?></div>
            <div class="kpi-sub">Revenue per unique order</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-label">Units Sold</div>
            <div class="kpi-value"><?= number_format((float) $kpis['total_units_sold'], 0) ?></div>
            <div class="kpi-sub"><?= (int) $kpis['unique_products'] ?> products · <?= (int) $kpis['unique_categories'] ?> categories</div>
        </div>
    </section>

    <section class="grid-2">
        <div class="card">
            <h2>Monthly Revenue Trend</h2>
            <div class="chart-box">
                <canvas id="monthlyRevenueChart"></canvas>
            </div>
        </div>
        <div class="card">
            <h2>Revenue by Category</h2>
            <div class="chart-box">
                <canvas id="categoryShareChart"></canvas>
            </div>
        </div>
    </section>

    <?php if ($comparison !== null): ?>
        <section class="card">
            <h2>Month-over-Month Comparison (<?= htmlspecialchars((string) $comparison['previous_month'], ENT_QUOTES, 'UTF-8') ?> → <?= htmlspecialchars((string) $comparison['current_month'], ENT_QUOTES, 'UTF-8') ?>)</h2>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Metric</th>
                            <th><?= htmlspecialchars((string) $comparison['previous_month'], ENT_QUOTES, 'UTF-8') ?></th>
                            <th><?= htmlspecialchars((string) $comparison['current_month'], ENT_QUOTES, 'UTF-8') ?></th>
                            <th>Change</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><strong>Revenue</strong></td>
                            <td>$<?= number_format((float) $comparison['previous_revenue'], 2) ?></td>
                            <td>$<?= number_format((float) $comparison['current_revenue'], 2) ?></td>
                            <td>
                                $<?= number_format((float) $comparison['revenue_change'], 2) ?>
                                <?php if ($comparison['revenue_change_pct'] !== null): ?>
                                    (<?= number_format((float) $comparison['revenue_change_pct'], 2) ?>%)
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <td><strong>Orders</strong></td>
                            <td><?= (int) $comparison['previous_orders'] ?></td>
                            <td><?= (int) $comparison['current_orders'] ?></td>
                            <td>
                                <?php if ($comparison['orders_change_pct'] !== null): ?>
                                    <?= number_format((float) $comparison['orders_change_pct'], 2) ?>%
                                <?php else: ?>
                                    —
                                <?php endif; ?>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>
    <?php endif; ?>

    <section class="grid-2">
        <div class="card">
            <h2>Monthly Trends</h2>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Month</th>
                            <th>Revenue</th>
                            <th>Orders</th>
                            <th>Units</th>
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
        </div>

        <div class="card">
            <h2>Category Breakdown</h2>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Category</th>
                            <th>Revenue</th>
                            <th>Share</th>
                            <th>Orders</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($categories as $row): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars((string) $row['category'], ENT_QUOTES, 'UTF-8') ?></strong></td>
                                <td>$<?= number_format((float) $row['revenue'], 2) ?></td>
                                <td><?= number_format((float) $row['revenue_share_pct'], 2) ?>%</td>
                                <td><?= (int) $row['orders'] ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    <section class="card">
        <h2>Top Products</h2>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Category</th>
                        <th>Revenue</th>
                        <th>Units Sold</th>
                        <th>Orders</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($topProducts as $row): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars((string) $row['product'], ENT_QUOTES, 'UTF-8') ?></strong></td>
                            <td><?= htmlspecialchars((string) $row['category'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td>$<?= number_format((float) $row['revenue'], 2) ?></td>
                            <td><?= number_format((float) $row['units'], 0) ?></td>
                            <td><?= (int) $row['orders'] ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
    <script>
        if (typeof Chart !== 'undefined') {
            const months = <?= $chartMonths ?>;
            const monthlyRev = <?= $chartMonthlyRev ?>;
            const catLabels = <?= $chartCatLabels ?>;
            const catRev = <?= $chartCatRev ?>;

            new Chart(document.getElementById('monthlyRevenueChart'), {
                type: 'bar',
                data: {
                    labels: months,
                    datasets: [{
                        label: 'Revenue ($)',
                        data: monthlyRev,
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

            new Chart(document.getElementById('categoryShareChart'), {
                type: 'doughnut',
                data: {
                    labels: catLabels,
                    datasets: [{
                        data: catRev,
                        backgroundColor: ['#2563eb', '#0ea5e9', '#10b981', '#f59e0b', '#8b5cf6', '#ef4444']
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { position: 'bottom' } }
                }
            });
        }
    </script>
<?php endif; ?>

<?php require dirname(__DIR__) . '/layouts/footer.php'; ?>