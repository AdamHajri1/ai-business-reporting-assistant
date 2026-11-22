<?php
/** @var string|null $pageTitle */
/** @var array{id: int, name: string, email: string, role: string}|null $user */
$currentLocale = I18n::locale();
$currentUri = (string) ($_SERVER['REQUEST_URI'] ?? '/');
$title = isset($pageTitle) && is_string($pageTitle)
    ? $pageTitle . ' — ' . I18n::t('app.name')
    : I18n::t('app.name');
$isAuthPage = isset($isAuthPage) && $isAuthPage === true;
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($currentLocale, ENT_QUOTES, 'UTF-8') ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body>
<?php if (!$isAuthPage && isset($user) && is_array($user)): ?>
    <header class="navbar">
        <div class="navbar-inner">
            <a href="/" class="brand">
                <span class="brand-dot" aria-hidden="true"></span>
                <?= htmlspecialchars(I18n::t('app.name'), ENT_QUOTES, 'UTF-8') ?>
            </a>
            <div class="nav-right">
                <form method="POST" action="/language" class="lang-switch" style="margin:0;">
                    <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars(Csrf::token(), ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="redirect" value="<?= htmlspecialchars($currentUri, ENT_QUOTES, 'UTF-8') ?>">
                    <button type="submit" name="locale" value="en" class="<?= $currentLocale === 'en' ? 'active' : '' ?>">EN</button>
                    <button type="submit" name="locale" value="fr" class="<?= $currentLocale === 'fr' ? 'active' : '' ?>">FR</button>
                </form>
                <span><?= htmlspecialchars((string) $user['name'], ENT_QUOTES, 'UTF-8') ?></span>
                <form method="POST" action="/logout" style="margin:0;">
                    <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars(Csrf::token(), ENT_QUOTES, 'UTF-8') ?>">
                    <button type="submit" class="btn btn-secondary btn-sm"><?= htmlspecialchars(I18n::t('nav.logout'), ENT_QUOTES, 'UTF-8') ?></button>
                </form>
            </div>
        </div>
    </header>
    <main class="container">
<?php else: ?>
    <main class="auth-wrap" style="flex-direction:column;gap:1rem;">
        <form method="POST" action="/language" class="lang-switch" style="margin:0;">
            <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars(Csrf::token(), ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="redirect" value="<?= htmlspecialchars($currentUri, ENT_QUOTES, 'UTF-8') ?>">
            <button type="submit" name="locale" value="en" class="<?= $currentLocale === 'en' ? 'active' : '' ?>">EN</button>
            <button type="submit" name="locale" value="fr" class="<?= $currentLocale === 'fr' ? 'active' : '' ?>">FR</button>
        </form>
<?php endif; ?>