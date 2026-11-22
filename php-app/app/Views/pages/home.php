<?php
/** @var array{id: int, name: string, email: string, role: string} $user */
/** @var string $csrfToken */
/** @var array<int, array<string, mixed>> $datasets */
/** @var string|null $successMessage */
/** @var string|null $errorMessage */
/** @var array<int, array<string, mixed>> $reports */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard — AI Business Reporting Assistant</title>
</head>
<body>
    <main>
        <h1>AI Business Reporting Assistant</h1>
        <p>Welcome, <strong><?= htmlspecialchars((string) $user['name'], ENT_QUOTES, 'UTF-8') ?></strong>
           (<?= htmlspecialchars((string) $user['email'], ENT_QUOTES, 'UTF-8') ?>).</p>

        <form method="POST" action="/logout">
            <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
            <button type="submit">Log out</button>
        </form>

        <hr>

        <section>
            <h2>Upload a CSV Dataset</h2>

            <?php if ($successMessage !== null): ?>
                <p role="status"><?= htmlspecialchars($successMessage, ENT_QUOTES, 'UTF-8') ?></p>
            <?php endif; ?>

            <?php if ($errorMessage !== null): ?>
                <p role="alert"><?= htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8') ?></p>
            <?php endif; ?>

            <form method="POST" action="/datasets/upload" enctype="multipart/form-data">
                <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                <div>
                    <label for="csv_file">Sales CSV file (max 5 MB)</label><br>
                    <input id="csv_file" name="csv_file" type="file" accept=".csv,text/csv" required>
                </div>
                <button type="submit">Upload CSV</button>
            </form>
        </section>

        <hr>

        <section>
            <h2>Your Datasets</h2>

            <?php if ($datasets === []): ?>
                <p>No datasets uploaded yet.</p>
            <?php else: ?>
                <table border="1" cellpadding="6" cellspacing="0">
                    <table border="1" cellpadding="6" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Filename</th>
                            <th>Size</th>
                            <th>Rows (Valid / Total)</th>
                            <th>Date Range</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($datasets as $dataset): ?>
                            <tr>
                                <td><?= htmlspecialchars((string) $dataset['original_filename'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars(number_format(((int) $dataset['file_size_bytes']) / 1024, 1) . ' KB', ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= (int) $dataset['valid_row_count'] ?> / <?= (int) $dataset['row_count'] ?></td>
                                <td>
                                    <?php if (!empty($dataset['date_min']) && !empty($dataset['date_max'])): ?>
                                        <?= htmlspecialchars((string) $dataset['date_min'] . ' → ' . (string) $dataset['date_max'], ENT_QUOTES, 'UTF-8') ?>
                                    <?php else: ?>
                                        —
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars((string) $dataset['status'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <a href="/datasets/preview?id=<?= urlencode((string) $dataset['id']) ?>">
                                        <?= $dataset['status'] === 'ready' ? 'View Preview' : 'Validate / Preview' ?>
                                    </a>
                                    <?php if ($dataset['status'] === 'ready'): ?>
                                        | <a href="/datasets/analyze?id=<?= urlencode((string) $dataset['id']) ?>">View Analytics</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                </table>
            <?php endif; ?>
        </section>
        <hr>

        <section>
            <h2>Saved Executive Reports</h2>

            <?php if ($reports === []): ?>
                <p>No reports generated yet. Open a ready dataset's analytics page to generate one.</p>
            <?php else: ?>
                <table border="1" cellpadding="6" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Report #</th>
                            <th>Dataset</th>
                            <th>Period</th>
                            <th>Engine</th>
                            <th>Created At</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reports as $report): ?>
                            <tr>
                                <td>#<?= (int) $report['id'] ?></td>
                                <td><?= htmlspecialchars((string) $report['original_filename'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <?= htmlspecialchars((string) $report['period_start'] . ' → ' . (string) $report['period_end'], ENT_QUOTES, 'UTF-8') ?>
                                </td>
                                <td><?= htmlspecialchars((string) $report['ai_model'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars((string) $report['created_at'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <a href="/reports/show?id=<?= (int) $report['id'] ?>">View Report</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </section>
    </main>
</body>
</html>