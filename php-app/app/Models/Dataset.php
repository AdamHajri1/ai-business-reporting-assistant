<?php

declare(strict_types=1);

final class Dataset
{
    public function __construct(private PDO $pdo)
    {
    }

    public function create(
        string $id,
        int $userId,
        string $originalFilename,
        string $storedFilename,
        int $fileSizeBytes
    ): void {
        $stmt = $this->pdo->prepare(
            'INSERT INTO datasets (id, user_id, original_filename, stored_filename, file_size_bytes, status)
             VALUES (:id, :user_id, :original_filename, :stored_filename, :file_size_bytes, :status)'
        );
        $stmt->execute([
            ':id' => $id,
            ':user_id' => $userId,
            ':original_filename' => $originalFilename,
            ':stored_filename' => $storedFilename,
            ':file_size_bytes' => $fileSizeBytes,
            ':status' => 'uploaded',
        ]);
    }

    public function findByIdForUser(string $id, int $userId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, user_id, original_filename, stored_filename, file_size_bytes,
                    row_count, valid_row_count, error_row_count, date_min, date_max, status, created_at
             FROM datasets
             WHERE id = :id AND user_id = :user_id AND status != :deleted
             LIMIT 1'
        );
        $stmt->execute([
            ':id' => $id,
            ':user_id' => $userId,
            ':deleted' => 'deleted',
        ]);

        $row = $stmt->fetch();

        return $row !== false ? $row : null;
    }

    /** @return array<int, array<string, mixed>> */
    public function findAllForUser(int $userId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, original_filename, file_size_bytes, row_count, valid_row_count,
                    error_row_count, date_min, date_max, status, created_at
             FROM datasets
             WHERE user_id = :user_id AND status != :deleted
             ORDER BY created_at DESC'
        );
        $stmt->execute([
            ':user_id' => $userId,
            ':deleted' => 'deleted',
        ]);

        return $stmt->fetchAll();
    }

    public function updateValidation(
        string $id,
        int $userId,
        int $rowCount,
        int $validRowCount,
        int $errorRowCount,
        ?string $dateMin,
        ?string $dateMax,
        string $status
    ): void {
        $stmt = $this->pdo->prepare(
            'UPDATE datasets
             SET row_count = :row_count,
                 valid_row_count = :valid_row_count,
                 error_row_count = :error_row_count,
                 date_min = :date_min,
                 date_max = :date_max,
                 status = :status
             WHERE id = :id AND user_id = :user_id'
        );
        $stmt->execute([
            ':row_count' => $rowCount,
            ':valid_row_count' => $validRowCount,
            ':error_row_count' => $errorRowCount,
            ':date_min' => $dateMin,
            ':date_max' => $dateMax,
            ':status' => $status,
            ':id' => $id,
            ':user_id' => $userId,
        ]);
    }

    public function updateStatus(string $id, int $userId, string $status): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE datasets
             SET status = :status
             WHERE id = :id AND user_id = :user_id'
        );
        $stmt->execute([
            ':status' => $status,
            ':id' => $id,
            ':user_id' => $userId,
        ]);
    }

    public function deleteByIdForUser(string $id, int $userId): bool
    {
        $stmt = $this->pdo->prepare(
            'DELETE FROM datasets
             WHERE id = :id AND user_id = :user_id'
        );
        $stmt->execute([
            ':id' => $id,
            ':user_id' => $userId,
        ]);

        return $stmt->rowCount() > 0;
    }

    /** @return array<int, array<string, mixed>> */
    public function findPotentialDuplicatesForUser(int $userId, string $originalFilename, int $fileSizeBytes): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, original_filename, stored_filename, file_size_bytes
             FROM datasets
             WHERE user_id = :user_id
               AND status != :deleted
               AND (LOWER(original_filename) = LOWER(:original_filename) OR file_size_bytes = :file_size_bytes)'
        );
        $stmt->execute([
            ':user_id' => $userId,
            ':deleted' => 'deleted',
            ':original_filename' => $originalFilename,
            ':file_size_bytes' => $fileSizeBytes,
        ]);

        return $stmt->fetchAll();
    }
}