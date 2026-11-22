<?php
/** @var string $csrfToken */
/** @var array<int, string> $errors */
/** @var array{name?: string, email?: string} $old */

$pageTitle = 'Create Account';
$isAuthPage = true;
require dirname(__DIR__) . '/layouts/header.php';
?>

<section class="card auth-card">
    <h1>Create account</h1>
    <p class="subtitle" style="margin-bottom:1.25rem;">Set up your local-first reporting account.</p>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-error" role="alert">
            <ul style="margin:0;padding-left:1.2rem;">
                <?php foreach ($errors as $err): ?>
                    <li><?= htmlspecialchars((string) $err, ENT_QUOTES, 'UTF-8') ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="POST" action="/register">
        <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
        <div class="form-group">
            <label for="name">Full name</label>
            <input id="name" name="name" type="text" required value="<?= htmlspecialchars((string) ($old['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
        </div>
        <div class="form-group">
            <label for="email">Email</label>
            <input id="email" name="email" type="email" required value="<?= htmlspecialchars((string) ($old['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
        </div>
        <div class="form-group">
            <label for="password">Password (min 8 chars)</label>
            <input id="password" name="password" type="password" minlength="8" required>
        </div>
        <button type="submit" class="btn btn-primary" style="width:100%;">Register</button>
    </form>

    <p class="auth-footer">Already have an account? <a href="/login">Log in</a></p>
</section>

<?php require dirname(__DIR__) . '/layouts/footer.php'; ?>