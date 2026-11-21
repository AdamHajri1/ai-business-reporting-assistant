<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/Core/Session.php';
require_once dirname(__DIR__) . '/app/Core/Csrf.php';

$token = Csrf::token();
$validCheck = Csrf::verify($token);
$invalidCheck = Csrf::verify('wrong-token');

echo 'Token length: ' . strlen($token) . PHP_EOL;
echo 'Valid check: ' . ($validCheck ? 'PASS' : 'FAIL') . PHP_EOL;
echo 'Invalid check: ' . ($invalidCheck ? 'FAIL' : 'PASS') . PHP_EOL;