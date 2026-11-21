<?php
/** @var string $csrfToken */
/** @var array<int, string> $errors */
/** @var array{email?: string} $old */
/** @var string|null $successMessage */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — AI Business Reporting Assistant</title>
</head>
<body>
    <main>
        <h1>Log in</h1>

        <?php if (!empty($successMessage)): ?>
            <p role="status"><?= htmlspecialchars((string) $successMessage, ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
            <div role="alert">
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?= htmlspecialchars((string) $error, ENT_QUOTES, 'UTF-8') ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="POST" action="/login">
            <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars((string) $csrfToken, ENT_QUOTES, 'UTF-8') ?>">

            <div>
                <label for="email">Email</label><br>
                <input id="email" name="email" type="email" required
                    value="<?= htmlspecialchars((string) ($old['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
            </div>

            <div>
                <label for="password">Password</label><br>
                <input id="password" name="password" type="password" required>
            </div>

            <button type="submit">Log in</button>
        </form>

        <p>No account yet? <a href="/register">Create an account</a></p>
    </main>
</body>
</html>