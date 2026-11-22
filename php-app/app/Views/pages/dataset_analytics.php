<?php
/** @var array{id: int, name: string, email: string, role: string} $user */
/** @var array<string, mixed> $dataset */
/** @var array<string, mixed>|null $analytics */
/** @var array{start_date: string, end_date: string} $filters */
/** @var string|null $errorMessage */
/** @var string $csrfToken */

$kpis = is_array($analytics['kpis'] ?? null) ? $analytics['kpis'] : null;
$monthlyTrends = is_array($analytics['monthly_trends'] ?? null) ? $analytics['monthly_trends'] : [];
$categories = is_array($analytics['category_breakdown'] ?? null) ? $analytics['category_breakdown'] : [];
$topProducts = is_array($analytics['top_products'] ?? null) ? $analytics['top_products'] : [];
$comparison = is_array($analytics['period_comparison'] ?? null) ? $analytics['period_comparison'] : null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics — AI Business Reporting Assistant</title>
</head>
<body>
    <main>
        <h1>Analytics: <?= htmlspecialchars((string) $dataset['original_filename'], ENT_QUOTES, 'UTF-8') ?></h1>
        <p><a href="/">← Back to Dashboard</a></p>

        <section>
            <h2>Filter by Date Range</h2>
            <form method="GET" action="/datasets/analyze">
                <input type="hidden" name="id" value="<?= htmlspecialchars((string) $dataset['id'], ENT_QUOTES, 'UTF-8') ?>">
                <label for="start_date">Start date:</label>
                <input id="start_date" name="start_date" type="date"
                       value="<?= htmlspecialchars($filters['start_date'], ENT_QUOTES, 'UTF-8') ?>">

                <label for="end_date">End date:</label>
                <input id="end_date" name="end_date" type="date"
                       value="<?= htmlspecialchars($filters['end_date'], ENT_QUOTES, 'UTF-8') ?>">

                <button type="submit">Apply Filter</button>
                <a href="/datasets/analyze?id=<?= urlencode((string) $dataset['id']) ?>">Reset</a>
            </form>
        </section>

        <?php if ($errorMessage !== null): ?>
            <p role="alert"><?= htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>

        <?php if ($kpis !== null): ?>
            <hr>
            <section>
                <form method="POST" action="/reports/generate">
                    <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="dataset_id" value="<?= htmlspecialchars((string) $dataset['id'], ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="start_date" value="<?= htmlspecialchars($filters['start_date'], ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="end_date" value="<?= htmlspecialchars($filters['end_date'], ENT_QUOTES, 'UTF-8') ?>">
                    <button type="submit">Generate &amp; Save Executive Report</button>
                </form>
            </section>
            <section>
                <h2>Key Performance Indicators (KPIs)</h2>
                <ul>
                    <li><strong>Total Revenue:</strong> $<?= number_format((float) $kpis['total_revenue'], 2) ?></li>
                    <li><strong>Total Orders:</strong> <?= (int) $kpis['total_orders'] ?></li>
                    <li><strong>Average Order Value (AOV):</strong> $<?= number_format((float) $kpis['average_order_value'], 2) ?></li>
                    <li><strong>Total Units Sold:</strong> <?= number_format((float) $kpis['total_units_sold'], 0) ?></li>
                    <li><strong>Unique Products:</strong> <?= (int) $kpis['unique_products'] ?></li>
                    <li><strong>Unique Categories:</strong> <?= (int) $kpis['unique_categories'] ?></li>
                    <li><strong>Active Date Range:</strong>
                        <?= htmlspecialchars((string) $kpis['date_min'], ENT_QUOTES, 'UTF-8') ?>
                        to
                        <?= htmlspecialchars((string) $kpis['date_max'], ENT_QUOTES, 'UTF-8') ?>
                    </li>
                </ul>
            </section>

            <?php if ($comparison !== null): ?>
                <section>
                    <h2>Month-over-Month Comparison (<?= htmlspecialchars((string) $comparison['previous_month'], ENT_QUOTES, 'UTF-8') ?> → <?= htmlspecialchars((string) $comparison['current_month'], ENT_QUOTES, 'UTF-8') ?>)</h2>
                    <ul>
                        <li><strong>Previous Revenue:</strong> $<?= number_format((float) $comparison['previous_revenue'], 2) ?></li>
                        <li><strong>Current Revenue:</strong> $<?= number_format((float) $comparison['current_revenue'], 2) ?></li>
                        <li><strong>Revenue Change:</strong>
                            $<?= number_format((float) $comparison['revenue_change'], 2) ?>
                            <?php if ($comparison['revenue_change_pct'] !== null): ?>
                                (<?= number_format((float) $comparison['revenue_change_pct'], 2) ?>%)
                            <?php endif; ?>
                        </li>
                        <li><strong>Orders Change:</strong>
                            <?= (int) $comparison['previous_orders'] ?> → <?= (int) $comparison['current_orders'] ?>
                            <?php if ($comparison['orders_change_pct'] !== null): ?>
                                (<?= number_format((float) $comparison['orders_change_pct'], 2) ?>%)
                            <?php endif; ?>
                        </li>
                    </ul>
                </section>
            <?php endif; ?>

            <section>
                <h2>Monthly Trends</h2>
                <table border="1" cellpadding="6" cellspacing="0">
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
                                <td><?= htmlspecialchars((string) $row['month'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td>$<?= number_format((float) $row['revenue'], 2) ?></td>
                                <td><?= (int) $row['orders'] ?></td>
                                <td><?= number_format((float) $row['units'], 0) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </section>

            <section>
                <h2>Category Breakdown</h2>
                <table border="1" cellpadding="6" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Category</th>
                            <th>Revenue</th>
                            <th>Share (%)</th>
                            <th>Orders</th>
                            <th>Units Sold</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($categories as $row): ?>
                            <tr>
                                <td><?= htmlspecialchars((string) $row['category'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td>$<?= number_format((float) $row['revenue'], 2) ?></td>
                                <td><?= number_format((float) $row['revenue_share_pct'], 2) ?>%</td>
                                <td><?= (int) $row['orders'] ?></td>
                                <td><?= number_format((float) $row['units'], 0) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </section>

            <section>
                <h2>Top Products</h2>
                <table border="1" cellpadding="6" cellspacing="0">
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
                                <td><?= htmlspecialchars((string) $row['product'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars((string) $row['category'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td>$<?= number_format((float) $row['revenue'], 2) ?></td>
                                <td><?= number_format((float) $row['units'], 0) ?></td>
                                <td><?= (int) $row['orders'] ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </section>
        <?php endif; ?>
    </main>
</body>
</html>