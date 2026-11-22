<?php

declare(strict_types=1);

final class DatasetController
{
    public function __construct(
        private Dataset $datasetModel,
        private UploadService $uploadService,
        private AnalyticsClient $analyticsClient
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

        $datasetId = (string) $result['dataset_id'];

        $this->datasetModel->create(
            $datasetId,
            $userId,
            (string) $result['original_filename'],
            (string) $result['stored_filename'],
            (int) $result['file_size_bytes']
        );

        Response::redirect('/datasets/preview?id=' . urlencode($datasetId));
    }

    public function preview(): void
    {
        $user = AuthMiddleware::requireAuth();
        $userId = (int) $user['id'];

        $datasetId = trim((string) ($_GET['id'] ?? ''));
        $dataset = $datasetId !== '' ? $this->datasetModel->findByIdForUser($datasetId, $userId) : null;

        if ($dataset === null) {
            Session::set('flash_error', 'Dataset not found.');
            Response::redirect('/');
        }

        $validation = $this->analyticsClient->validateDataset($datasetId);

        if (!$validation['ok']) {
            Session::set('flash_error', (string) ($validation['error'] ?? 'Validation service failed.'));
            Response::redirect('/');
        }

        $data = $validation['data'];
        $isValid = (bool) ($data['is_valid'] ?? false);
        $newStatus = $dataset['status'] === 'ready'
            ? 'ready'
            : ($isValid ? 'validated' : 'rejected');

        $this->datasetModel->updateValidation(
            $datasetId,
            $userId,
            (int) ($data['row_count'] ?? 0),
            (int) ($data['valid_row_count'] ?? 0),
            (int) ($data['error_row_count'] ?? 0),
            isset($data['date_min']) ? (string) $data['date_min'] : null,
            isset($data['date_max']) ? (string) $data['date_max'] : null,
            $newStatus
        );

        $dataset['status'] = $newStatus;

        Response::view('pages/dataset_preview', [
            'user' => $user,
            'csrfToken' => Csrf::token(),
            'dataset' => $dataset,
            'validation' => $data,
        ]);
    }

    public function confirm(): void
    {
        $user = AuthMiddleware::requireAuth();
        $userId = (int) $user['id'];

        if (!Csrf::verify($_POST['_csrf_token'] ?? null)) {
            Session::set('flash_error', 'Invalid security token. Please try again.');
            Response::redirect('/');
        }

        $datasetId = trim((string) ($_POST['dataset_id'] ?? ''));
        $dataset = $datasetId !== '' ? $this->datasetModel->findByIdForUser($datasetId, $userId) : null;

        if ($dataset === null) {
            Session::set('flash_error', 'Dataset not found.');
            Response::redirect('/');
        }

        if ($dataset['status'] !== 'validated') {
            Session::set('flash_error', 'Only validated datasets can be confirmed.');
            Response::redirect('/');
        }

        $this->datasetModel->updateStatus($datasetId, $userId, 'ready');

        Session::set('flash_success', 'Dataset confirmed and ready for analytics.');
        Response::redirect('/');
    }
    public function analyze(): void
    {
        $user = AuthMiddleware::requireAuth();
        $userId = (int) $user['id'];

        $datasetId = trim((string) ($_GET['id'] ?? ''));
        $dataset = $datasetId !== '' ? $this->datasetModel->findByIdForUser($datasetId, $userId) : null;

        if ($dataset === null) {
            Session::set('flash_error', 'Dataset not found.');
            Response::redirect('/');
        }

        if ($dataset['status'] !== 'ready') {
            Session::set('flash_error', 'Please validate and confirm this dataset before viewing analytics.');
            Response::redirect('/datasets/preview?id=' . urlencode($datasetId));
        }

        $startDate = trim((string) ($_GET['start_date'] ?? ''));
        $endDate = trim((string) ($_GET['end_date'] ?? ''));

        $analysis = $this->analyticsClient->analyzeDataset(
            $datasetId,
            $startDate !== '' ? $startDate : null,
            $endDate !== '' ? $endDate : null
        );

        if (!$analysis['ok']) {
            if ($startDate === '' && $endDate === '') {
                Session::set('flash_error', (string) ($analysis['error'] ?? 'Analytics service failed.'));
                Response::redirect('/');
            }

            Response::view('pages/dataset_analytics', [
                'user' => $user,
                'dataset' => $dataset,
                'analytics' => null,
                'filters' => ['start_date' => $startDate, 'end_date' => $endDate],
                'errorMessage' => (string) ($analysis['error'] ?? 'Analytics request failed.'),
            ]);
            return;
        }

        Response::view('pages/dataset_analytics', [
            'user' => $user,
            'dataset' => $dataset,
            'analytics' => $analysis['data'],
            'filters' => ['start_date' => $startDate, 'end_date' => $endDate],
            'errorMessage' => null,
        ]);
    }
}