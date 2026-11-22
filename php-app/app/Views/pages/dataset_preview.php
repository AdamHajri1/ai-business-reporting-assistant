<?php
/** @var array{id: int, name: string, email: string, role: string} $user */
/** @var string $csrfToken */
/** @var array<string, mixed> $dataset */
/** @var array<string, mixed> $validation */

$isValid = (bool) ($validation['is_valid'] ?? false);
$missingColumns = is_array($validation['missing_columns'] ?? null) ? $validation['missing_columns'] : [];
$rowErrors = is_array($validation['row_errors'] ?? null) ? $validation['row_errors'] : [];
$previewRows = is_array($validation['preview'] ?? null) ? $validation['preview'] : [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dataset Validation — AI Business Reporting Assistant</title>
</head>
<body>
    <main>
        <h1>Dataset Validation &amp; Preview</h1>
        <p><a href="/">← Back to Dashboard</a></p>

        <section>
            <h2>Summary</h2>
            <ul>
                <li><strong>Filename:</strong> <?= htmlspecialchars((string) $dataset['original_filename'], ENT_QUOTES, 'UTF-8') ?></li>
                <li><strong>Status:</strong> <?= htmlspecialchars((string) $dataset['status'], ENT_QUOTES, 'UTF-8') ?></li>
                <li><strong>Total rows:</strong> <?= (int) ($validation['row_count'] ?? 0) ?></li>
                <li><strong>Valid rows:</strong> <?= (int) ($validation['valid_row_count'] ?? 0) ?></li>
                <li><strong>Error rows:</strong> <?= (int) ($validation['error_row_count'] ?? 0) ?></li>
                <li><strong>Date range:</strong>
                    <?= htmlspecialchars((string) ($validation['date_min'] ?? 'N/A'), ENT_QUOTES, 'UTF-8') ?>
                    to
                    <?= htmlspecialchars((string) ($validation['date_max'] ?? 'N/A'), ENT_QUOTES, 'UTF-8') ?>
                </li>
            </ul>
        </section>

        <?php if ($missingColumns !== []): ?>
            <section role="alert">
                <h2>Missing Required Columns</h2>
                <p><?= htmlspecialchars(implode(', ', $missingColumns), ENT_QUOTES, 'UTF-8') ?></p>
            </section>
        <?php endif; ?>

        <?php if ($rowErrors !== []): ?>
            <section role="alert">
                <h2>Validation Errors (first <?= count($rowErrors) ?>)</h2>
                <table border="1" cellpadding="6" cellspacing="0">
                    <thead>
                        <tr>
                            <th>CSV Line</th>
                            <th>Reason</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rowErrors as $err): ?>
                            <tr>
                                <td><?= (int) ($err['row'] ?? 0) ?></td>
                                <td><?= htmlspecialchars((string) ($err['reason'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </section>
        <?php endif; ?>

        <?php if ($previewRows !== []): ?>
            <section>
                <h2>Preview (first <?= count($previewRows) ?> valid rows)</h2>
                <table border="1" cellpadding="6" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Order ID</th>
                            <th>Product</th>
                            <th>Category</th>
                            <th>Quantity</th>
                            <th>Unit Price</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($previewRows as $row): ?>
                            <tr>
                                <td><?= htmlspecialchars((string) ($row['date'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars((string) ($row['order_id'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars((string) ($row['product'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars((string) ($row['category'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars((string) ($row['quantity'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars((string) ($row['unit_price'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </section>
        <?php endif; ?>

        <hr>

        <?php if ($isValid && $dataset['status'] === 'validated'): ?>
            <form method="POST" action="/datasets/confirm">
                <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="dataset_id" value="<?= htmlspecialchars((string) $dataset['id'], ENT_QUOTES, 'UTF-8') ?>">
                <button type="submit">Confirm Dataset for Analytics</button>
            </form>
        <?php elseif ($dataset['status'] === 'ready'): ?>
            <p role="status"><strong>This dataset is confirmed and ready for analytics.</strong></p>
        <?php else: ?>
            <p role="alert"><strong>This dataset cannot be confirmed until the errors above are fixed and re-uploaded.</strong></p>
        <?php endif; ?>
    </main>
</body>
</html>