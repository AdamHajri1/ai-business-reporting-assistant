<?php

declare(strict_types=1);

$config = require dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/app/Core/Database.php';
require_once dirname(__DIR__) . '/app/Models/User.php';

$pdo = Database::connect($config['db']);
$userModel = new User($pdo);
$testEmail = 'test_user_model@example.com';

// Clean up any leftover test row before starting
$cleanupStmt = $pdo->prepare('DELETE FROM users WHERE email = :email');
$cleanupStmt->execute([':email' => $testEmail]);

try {
    $userId = $userModel->create('Test User', '  Test_User_Model@Example.com  ', 'SecretPass123!');
    echo 'Create user: ' . ($userId > 0 ? 'PASS' : 'FAIL') . PHP_EOL;

    $validLogin = $userModel->verifyCredentials($testEmail, 'SecretPass123!');
    $hashRemoved = is_array($validLogin) && !array_key_exists('password_hash', $validLogin);
    echo 'Valid credentials: ' . ($hashRemoved ? 'PASS' : 'FAIL') . PHP_EOL;

    $invalidLogin = $userModel->verifyCredentials($testEmail, 'WrongPassword');
    echo 'Invalid credentials: ' . ($invalidLogin === null ? 'PASS' : 'FAIL') . PHP_EOL;

    $userModel->updateLastLogin($userId);
    $loadedUser = $userModel->findById($userId);
    echo 'Last login updated: ' . ($loadedUser !== null && $loadedUser['last_login_at'] !== null ? 'PASS' : 'FAIL') . PHP_EOL;
} finally {
    // Always remove the temporary test user
    $cleanupStmt->execute([':email' => $testEmail]);
}