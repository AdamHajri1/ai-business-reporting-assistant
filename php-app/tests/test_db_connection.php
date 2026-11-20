<?php

declare(strict_types=1);

$config = require dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/app/Core/Database.php';

$pdo = Database::connect($config['db']);
$row = $pdo->query('SELECT DATABASE() AS db, VERSION() AS ver')->fetch();

echo 'Connected to ' . $row['db'] . ' (MySQL ' . $row['ver'] . ')' . PHP_EOL;