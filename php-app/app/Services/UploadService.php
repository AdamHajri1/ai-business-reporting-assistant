<?php

declare(strict_types=1);

final class UploadService
{
    public const MAX_FILE_SIZE_BYTES = 5 * 1024 * 1024; // 5 MB

    /** @var string[] */
    private const ALLOWED_MIME_TYPES = [
        'text/plain',
        'text/csv',
        'application/csv',
        'application/vnd.ms-excel',
        'text/x-csv',
    ];

    public function __construct(private string $uploadsDir)
    {
    }

    /**
     * @param array<string, mixed> $fileEntry Single entry from $_FILES
     * @return array{ok: bool, error?: string, dataset_id?: string, original_filename?: string, stored_filename?: string, file_size_bytes?: int}
     */
    public function storeCsv(array $fileEntry): array
    {
        if (!isset($fileEntry['error']) || !is_int($fileEntry['error'])) {
            return ['ok' => false, 'error' => 'Invalid file upload payload.'];
        }

        if ($fileEntry['error'] !== UPLOAD_ERR_OK) {
            return ['ok' => false, 'error' => $this->mapUploadError($fileEntry['error'])];
        }

        $tmpPath = (string) ($fileEntry['tmp_name'] ?? '');
        if ($tmpPath === '' || !is_uploaded_file($tmpPath)) {
            return ['ok' => false, 'error' => 'Uploaded file could not be verified.'];
        }

        $fileSize = (int) ($fileEntry['size'] ?? 0);
        if ($fileSize <= 0) {
            return ['ok' => false, 'error' => 'The uploaded file is empty.'];
        }

        if ($fileSize > self::MAX_FILE_SIZE_BYTES) {
            return ['ok' => false, 'error' => 'File exceeds the 5 MB size limit.'];
        }

        $rawName = (string) ($fileEntry['name'] ?? '');
        $extension = strtolower(pathinfo($rawName, PATHINFO_EXTENSION));
        if ($extension !== 'csv') {
            return ['ok' => false, 'error' => 'Only .csv files are allowed.'];
        }

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $detectedMime = $finfo->file($tmpPath);
        if (!is_string($detectedMime) || !in_array($detectedMime, self::ALLOWED_MIME_TYPES, true)) {
            return ['ok' => false, 'error' => 'Invalid file content type. Please upload a valid CSV file.'];
        }

        if (!is_dir($this->uploadsDir) && !mkdir($this->uploadsDir, 0750, true)) {
            return ['ok' => false, 'error' => 'Server storage directory is unavailable.'];
        }

        $datasetId = bin2hex(random_bytes(16));
        $storedFilename = $datasetId . '.csv';
        $destinationPath = rtrim($this->uploadsDir, "\\/") . DIRECTORY_SEPARATOR . $storedFilename;

        if (!move_uploaded_file($tmpPath, $destinationPath)) {
            return ['ok' => false, 'error' => 'Failed to save the uploaded file.'];
        }

        return [
            'ok' => true,
            'dataset_id' => $datasetId,
            'original_filename' => $this->sanitizeDisplayName($rawName),
            'stored_filename' => $storedFilename,
            'file_size_bytes' => $fileSize,
        ];
    }

    public function deleteStoredFile(string $storedFilename): void
    {
        $safeName = basename($storedFilename);
        if ($safeName === '' || $safeName === '.' || $safeName === '..') {
            return;
        }

        $path = rtrim($this->uploadsDir, "\\/") . DIRECTORY_SEPARATOR . $safeName;
        if (is_file($path)) {
            @unlink($path);
        }
    }

    public function filesHaveSameHash(string $storedFilenameA, string $storedFilenameB): bool
    {
        $pathA = rtrim($this->uploadsDir, "\\/") . DIRECTORY_SEPARATOR . basename($storedFilenameA);
        $pathB = rtrim($this->uploadsDir, "\\/") . DIRECTORY_SEPARATOR . basename($storedFilenameB);

        if (!is_file($pathA) || !is_file($pathB)) {
            return false;
        }

        $hashA = hash_file('sha256', $pathA);
        $hashB = hash_file('sha256', $pathB);

        return is_string($hashA) && is_string($hashB) && hash_equals($hashA, $hashB);
    }

    private function sanitizeDisplayName(string $rawName): string
    {
        $base = basename($rawName);
        $cleaned = preg_replace('/[\x00-\x1F\x7F]/', '', $base) ?? 'upload.csv';
        $cleaned = trim($cleaned);

        if ($cleaned === '') {
            return 'upload.csv';
        }

        return mb_substr($cleaned, 0, 255, 'UTF-8');
    }

    private function mapUploadError(int $errorCode): string
    {
        return match ($errorCode) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'File exceeds the maximum allowed upload size.',
            UPLOAD_ERR_PARTIAL => 'File was only partially uploaded. Please try again.',
            UPLOAD_ERR_NO_FILE => 'Please select a CSV file to upload.',
            default => 'An error occurred during file upload. Please try again.',
        };
    }
}