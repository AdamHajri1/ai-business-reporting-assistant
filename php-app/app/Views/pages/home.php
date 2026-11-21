<?php
/** @var array{id: int, name: string, email: string, role: string} $user */
/** @var string $csrfToken */
/** @var array<int, array<string, mixed>> $datasets */
/** @var string|null $successMessage */
/** @var string|null $errorMessage */
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
                    <thead>
                        <tr>
                            <th>Filename</th>
                            <th>Size</th>
                            <th>Status</th>
                            <th>Uploaded At</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($datasets as $dataset): ?>
                            <tr>
                                <td><?= htmlspecialchars((string) $dataset['original_filename'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars(number_format(((int) $dataset['file_size_bytes']) / 1024, 1) . ' KB', ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars((string) $dataset['status'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars((string) $dataset['created_at'], ENT_QUOTES, 'UTF-8') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </section>
    </main>
</body>
</html>