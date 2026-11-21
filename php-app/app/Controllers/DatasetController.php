<?php

declare(strict_types=1);

final class DatasetController
{
    public function __construct(
        private Dataset $datasetModel,
        private UploadService $uploadService
    ) {
    }

    public function index(): void
    {
        $user = AuthMiddleware::requireAuth();
        $userId = (int) $user['id'];

        $datasets = $this->datasetModel->findAllForUser($userId);

        $successMessage = Session::get('flash_success');
        $errorMessage = Session::get('flash_error');
        Session::remove('flash_success');
        Session::remove('flash_error');

        Response::view('pages/home', [
            'user' => $user,
            'csrfToken' => Csrf::token(),
            'datasets' => $datasets,
            'successMessage' => is_string($successMessage) ? $successMessage : null,
            'errorMessage' => is_string($errorMessage) ? $errorMessage : null,
        ]);
    }

    public function upload(): void
    {
        $user = AuthMiddleware::requireAuth();
        $userId = (int) $user['id'];

        if (!Csrf::verify($_POST['_csrf_token'] ?? null)) {
            Session::set('flash_error', 'Invalid security token. Please try again.');
            Response::redirect('/');
        }

        if (!isset($_FILES['csv_file']) || !is_array($_FILES['csv_file'])) {
            Session::set('flash_error', 'Please select a CSV file to upload.');
            Response::redirect('/');
        }

        $result = $this->uploadService->storeCsv($_FILES['csv_file']);

        if (!$result['ok']) {
            Session::set('flash_error', (string) ($result['error'] ?? 'File upload failed.'));
            Response::redirect('/');
        }

        $this->datasetModel->create(
            (string) $result['dataset_id'],
            $userId,
            (string) $result['original_filename'],
            (string) $result['stored_filename'],
            (int) $result['file_size_bytes']
        );

        Session::set('flash_success', 'CSV uploaded successfully. Ready for validation.');
        Response::redirect('/');
    }
}