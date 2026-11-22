<?php
/** @var array{id: int, name: string, email: string, role: string} $user */
/** @var array<string, mixed> $report */
/** @var array<string, mixed> $metrics */
/** @var array<string, mixed> $summary */
/** @var string|null $successMessage */

$kpis = is_array($metrics['kpis'] ?? null) ? $metrics['kpis'] : null;
$keyFindings = is_array($summary['key_findings'] ?? null) ? $summary['key_findings'] : [];
$recommendations = is_array($summary['recommendations'] ?? null) ? $summary['recommendations'] : [];
$monthlyTrends = is_array($metrics['monthly_trends'] ?? null) ? $metrics['monthly_trends'] : [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Executive Report #<?= (int) $report['id'] ?> — AI Business Reporting Assistant</title>
</head>
<body>
    <main>
        <h1>Executive Report #<?= (int) $report['id'] ?></h1>
        <p>
            <a href="/">← Back to Dashboard</a> |
            <a href="/datasets/analyze?id=<?= urlencode((string) $report['dataset_id']) ?>">View Live Analytics</a>
        </p>

        <?php if ($successMessage !== null): ?>
            <p role="status"><strong><?= htmlspecialchars($successMessage, ENT_QUOTES, 'UTF-8') ?></strong></p>
        <?php endif; ?>

        <section>
            <h2>Report Metadata</h2>
            <ul>
                <li><strong>Dataset:</strong> <?= htmlspecialchars((string) $report['original_filename'], ENT_QUOTES, 'UTF-8') ?></li>
                <li><strong>Period:</strong>
                    <?= htmlspecialchars((string) $report['period_start'], ENT_QUOTES, 'UTF-8') ?>
                    to
                    <?= htmlspecialchars((string) $report['period_end'], ENT_QUOTES, 'UTF-8') ?>
                </li>
                <li><strong>Engine / Model:</strong>
                    <?= htmlspecialchars((string) $report['ai_model'], ENT_QUOTES, 'UTF-8') ?>
                    (<?= htmlspecialchars((string) $report['prompt_version'], ENT_QUOTES, 'UTF-8') ?>)
                </li>
                <li><strong>Generated At:</strong> <?= htmlspecialchars((string) $report['created_at'], ENT_QUOTES, 'UTF-8') ?></li>
            </ul>
        </section>

        <hr>

        <section>
            <h2><?= htmlspecialchars((string) ($summary['headline'] ?? 'Executive Summary'), ENT_QUOTES, 'UTF-8') ?></h2>
            <p><?= htmlspecialchars((string) ($summary['executive_summary'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
        </section>

        <?php if ($keyFindings !== []): ?>
            <section>
                <h3>Key Findings</h3>
                <ul>
                    <?php foreach ($keyFindings as $finding): ?>
                        <li><?= htmlspecialchars((string) $finding, ENT_QUOTES, 'UTF-8') ?></li>
                    <?php endforeach; ?>
                </ul>
            </section>
        <?php endif; ?>

        <?php if ($recommendations !== []): ?>
            <section>
                <h3>Recommendations</h3>
                <ul>
                    <?php foreach ($recommendations as $rec): ?>
                        <li><?= htmlspecialchars((string) $rec, ENT_QUOTES, 'UTF-8') ?></li>
                    <?php endforeach; ?>
                </ul>
            </section>
        <?php endif; ?>

        <?php if ($kpis !== null): ?>
            <hr>
            <section>
                <h2>KPI Snapshot (Saved)</h2>
                <ul>
                    <li><strong>Total Revenue:</strong> $<?= number_format((float) $kpis['total_revenue'], 2) ?></li>
                    <li><strong>Total Orders:</strong> <?= (int) $kpis['total_orders'] ?></li>
                    <li><strong>Average Order Value (AOV):</strong> $<?= number_format((float) $kpis['average_order_value'], 2) ?></li>
                    <li><strong>Total Units Sold:</strong> <?= number_format((float) $kpis['total_units_sold'], 0) ?></li>
                </ul>
            </section>
        <?php endif; ?>

        <?php if ($monthlyTrends !== []): ?>
            <section>
                <h3>Monthly Breakdown (Saved)</h3>
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
        <?php endif; ?>
    </main>
</body>
</html>