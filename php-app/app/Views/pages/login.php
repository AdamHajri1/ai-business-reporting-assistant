<?php
/** @var string $csrfToken */
/** @var string|null $error */
/** @var array{email?: string} $old */

$pageTitle = 'Log In';
$isAuthPage = true;
require dirname(__DIR__) . '/layouts/header.php';
?>

<section class="card auth-card">
    <h1>Log in</h1>
    <p class="subtitle" style="margin-bottom:1.25rem;">Sign in to your AI Business Reporting workspace.</p>

    <?php if (!empty($error)): ?>
        <div class="alert alert-error" role="alert"><?= htmlspecialchars((string) $error, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <form method="POST" action="/login">
        <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
        <div class="form-group">
            <label for="email">Email</label>
            <input id="email" name="email" type="email" required value="<?= htmlspecialchars((string) ($old['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
        </div>
        <div class="form-group">
            <label for="password">Password</label>
            <input id="password" name="password" type="password" required>
        </div>
        <button type="submit" class="btn btn-primary" style="width:100%;">Log in</button>
    </form>

    <p class="auth-footer">Don't have an account? <a href="/register">Create one</a></p>
</section>

<?php require dirname(__DIR__) . '/layouts/footer.php'; ?>