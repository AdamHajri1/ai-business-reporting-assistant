<?php
/** @var string|null $pageTitle */
/** @var array{id: int, name: string, email: string, role: string}|null $user */
$title = isset($pageTitle) && is_string($pageTitle)
    ? $pageTitle . ' — AI Business Reporting Assistant'
    : 'AI Business Reporting Assistant';
$isAuthPage = isset($isAuthPage) && $isAuthPage === true;
?>
<!DOCTYPE html>
<html lang="en">
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
                AI Business Reporting Assistant
            </a>
            <div class="nav-right">
                <span><?= htmlspecialchars((string) $user['name'], ENT_QUOTES, 'UTF-8') ?></span>
                <form method="POST" action="/logout" style="margin:0;">
                    <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars(Csrf::token(), ENT_QUOTES, 'UTF-8') ?>">
                    <button type="submit" class="btn btn-secondary btn-sm">Log out</button>
                </form>
            </div>
        </div>
    </header>
    <main class="container">
<?php else: ?>
    <main class="auth-wrap">
<?php endif; ?>