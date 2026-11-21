<?php

declare(strict_types=1);

final class User
{
    public function __construct(private PDO $pdo)
    {
    }

    public function findByEmail(string $email): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, name, email, password_hash, role, created_at, last_login_at
             FROM users
             WHERE email = :email
             LIMIT 1'
        );
        $stmt->execute([':email' => $this->normalizeEmail($email)]);

        $user = $stmt->fetch();

        return $user !== false ? $user : null;
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, name, email, role, created_at, last_login_at
             FROM users
             WHERE id = :id
             LIMIT 1'
        );
        $stmt->execute([':id' => $id]);

        $user = $stmt->fetch();

        return $user !== false ? $user : null;
    }

    public function create(string $name, string $email, string $plainPassword): int
    {
        $passwordHash = password_hash($plainPassword, PASSWORD_DEFAULT);

        $stmt = $this->pdo->prepare(
            'INSERT INTO users (name, email, password_hash, role)
             VALUES (:name, :email, :password_hash, :role)'
        );
        $stmt->execute([
            ':name' => trim($name),
            ':email' => $this->normalizeEmail($email),
            ':password_hash' => $passwordHash,
            ':role' => 'user',
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function verifyCredentials(string $email, string $plainPassword): ?array
    {
        $user = $this->findByEmail($email);

        if ($user === null) {
            return null;
        }

        if (!password_verify($plainPassword, (string) $user['password_hash'])) {
            return null;
        }

        unset($user['password_hash']);

        return $user;
    }

    public function updateLastLogin(int $id): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE users
             SET last_login_at = CURRENT_TIMESTAMP
             WHERE id = :id'
        );
        $stmt->execute([':id' => $id]);
    }

    private function normalizeEmail(string $email): string
    {
        return mb_strtolower(trim($email), 'UTF-8');
    }
}