<?php
/** @var string $csrfToken */
/** @var array<int, string> $errors */
/** @var array{email?: string} $old */
/** @var string|null $successMessage */

$pageTitle = I18n::t('auth.login.title');
$isAuthPage = true;
require dirname(__DIR__) . '/layouts/header.php';
?>

<section class="card auth-card">
    <h1><?= htmlspecialchars(I18n::t('auth.login.title'), ENT_QUOTES, 'UTF-8') ?></h1>
    <p class="subtitle" style="margin-bottom:1.25rem;"><?= htmlspecialchars(I18n::t('auth.login.subtitle'), ENT_QUOTES, 'UTF-8') ?></p>

    <?php if (!empty($successMessage)): ?>
        <div class="alert alert-success" role="status"><?= htmlspecialchars((string) $successMessage, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-error" role="alert">
            <?php if (count($errors) === 1): ?>
                <?= htmlspecialchars((string) $errors[0], ENT_QUOTES, 'UTF-8') ?>
            <?php else: ?>
                <ul style="margin:0;padding-left:1.2rem;">
                    <?php foreach ($errors as $err): ?>
                        <li><?= htmlspecialchars((string) $err, ENT_QUOTES, 'UTF-8') ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="/login">
        <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
        <div class="form-group">
            <label for="email"><?= htmlspecialchars(I18n::t('auth.field.email'), ENT_QUOTES, 'UTF-8') ?></label>
            <input id="email" name="email" type="email" required value="<?= htmlspecialchars((string) ($old['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
        </div>
        <div class="form-group">
            <label for="password"><?= htmlspecialchars(I18n::t('auth.field.password'), ENT_QUOTES, 'UTF-8') ?></label>
            <input id="password" name="password" type="password" required>
        </div>
        <button type="submit" class="btn btn-primary" style="width:100%;"><?= htmlspecialchars(I18n::t('auth.login.button'), ENT_QUOTES, 'UTF-8') ?></button>
    </form>

    <p class="auth-footer">
        <?= htmlspecialchars(I18n::t('auth.login.no_account'), ENT_QUOTES, 'UTF-8') ?>
        <a href="/register"><?= htmlspecialchars(I18n::t('auth.login.create_one'), ENT_QUOTES, 'UTF-8') ?></a>
    </p>
</section>

<?php require dirname(__DIR__) . '/layouts/footer.php'; ?>