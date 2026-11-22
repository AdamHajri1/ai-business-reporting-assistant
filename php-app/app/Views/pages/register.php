<?php
/** @var string $csrfToken */
/** @var array<int, string> $errors */
/** @var array{name?: string, email?: string} $old */

$pageTitle = I18n::t('auth.register.title');
$isAuthPage = true;
require dirname(__DIR__) . '/layouts/header.php';
?>

<section class="card auth-card">
    <h1><?= htmlspecialchars(I18n::t('auth.register.title'), ENT_QUOTES, 'UTF-8') ?></h1>
    <p class="subtitle" style="margin-bottom:1.25rem;"><?= htmlspecialchars(I18n::t('auth.register.subtitle'), ENT_QUOTES, 'UTF-8') ?></p>

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
            <label for="name"><?= htmlspecialchars(I18n::t('auth.field.name'), ENT_QUOTES, 'UTF-8') ?></label>
            <input id="name" name="name" type="text" required value="<?= htmlspecialchars((string) ($old['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
        </div>
        <div class="form-group">
            <label for="email"><?= htmlspecialchars(I18n::t('auth.field.email'), ENT_QUOTES, 'UTF-8') ?></label>
            <input id="email" name="email" type="email" required value="<?= htmlspecialchars((string) ($old['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
        </div>
        <div class="form-group">
            <label for="password"><?= htmlspecialchars(I18n::t('auth.field.password_min'), ENT_QUOTES, 'UTF-8') ?></label>
            <input id="password" name="password" type="password" minlength="8" required>
        </div>
        <button type="submit" class="btn btn-primary" style="width:100%;"><?= htmlspecialchars(I18n::t('auth.register.button'), ENT_QUOTES, 'UTF-8') ?></button>
    </form>

    <p class="auth-footer">
        <?= htmlspecialchars(I18n::t('auth.register.have_account'), ENT_QUOTES, 'UTF-8') ?>
        <a href="/login"><?= htmlspecialchars(I18n::t('auth.register.login_link'), ENT_QUOTES, 'UTF-8') ?></a>
    </p>
</section>

<?php require dirname(__DIR__) . '/layouts/footer.php'; ?>