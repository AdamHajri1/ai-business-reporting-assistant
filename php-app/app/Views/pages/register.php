<?php
/** @var string $csrfToken */
/** @var array<int, string> $errors */
/** @var array{name?: string, email?: string} $old */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register — AI Business Reporting Assistant</title>
</head>
<body>
    <main>
        <h1>Create an account</h1>

        <?php if (!empty($errors)): ?>
            <div role="alert">
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?= htmlspecialchars((string) $error, ENT_QUOTES, 'UTF-8') ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="POST" action="/register">
            <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars((string) $csrfToken, ENT_QUOTES, 'UTF-8') ?>">

            <div>
                <label for="name">Name</label><br>
                <input id="name" name="name" type="text" required maxlength="100"
                    value="<?= htmlspecialchars((string) ($old['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
            </div>

            <div>
                <label for="email">Email</label><br>
                <input id="email" name="email" type="email" required maxlength="255"
                    value="<?= htmlspecialchars((string) ($old['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
            </div>

            <div>
                <label for="password">Password (min 8 characters)</label><br>
                <input id="password" name="password" type="password" required minlength="8">
            </div>

            <div>
                <label for="password_confirm">Confirm password</label><br>
                <input id="password_confirm" name="password_confirm" type="password" required minlength="8">
            </div>

            <button type="submit">Register</button>
        </form>

        <p>Already have an account? <a href="/login">Log in</a></p>
    </main>
</body>
</html>