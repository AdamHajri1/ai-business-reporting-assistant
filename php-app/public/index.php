<?php

declare(strict_types=1);

header('Content-Type: text/html; charset=UTF-8');

$appName = 'AI Business Reporting Assistant';
$phpVersion = PHP_VERSION;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($appName, ENT_QUOTES, 'UTF-8') ?></title>
</head>
<body>
    <h1><?= htmlspecialchars($appName, ENT_QUOTES, 'UTF-8') ?></h1>
    <p>PHP front controller is running (PHP <?= htmlspecialchars($phpVersion, ENT_QUOTES, 'UTF-8') ?>).</p>
</body>
</html>