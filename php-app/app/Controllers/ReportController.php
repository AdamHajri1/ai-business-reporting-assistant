<?php

declare(strict_types=1);

final class ReportController
{
    private const PROMPT_VERSION = 'v1.0';

    public function __construct(
        private Dataset $datasetModel,
        private Report $reportModel,
        private AnalyticsClient $analyticsClient
    ) {
    }

    public function generate(): void
    {
        $user = AuthMiddleware::requireAuth();
        $userId = (int) $user['id'];

        if (!Csrf::verify($_POST['_csrf_token'] ?? null)) {
            Session::set('flash_error', 'Invalid security token. Please try again.');
            Response::redirect('/');
        }

        $datasetId = trim((string) ($_POST['dataset_id'] ?? ''));
        $startDate = trim((string) ($_POST['start_date'] ?? ''));
        $endDate = trim((string) ($_POST['end_date'] ?? ''));

        $dataset = $datasetId !== '' ? $this->datasetModel->findByIdForUser($datasetId, $userId) : null;

        if ($dataset === null) {
            Session::set('flash_error', 'Dataset not found.');
            Response::redirect('/');
        }

        if ($dataset['status'] !== 'ready') {
            Session::set('flash_error', 'Only confirmed datasets can generate executive reports.');
            Response::redirect('/datasets/preview?id=' . urlencode($datasetId));
        }

        $result = $this->analyticsClient->summarizeDataset(
            $datasetId,
            $startDate !== '' ? $startDate : null,
            $endDate !== '' ? $endDate : null
        );

        if (!$result['ok']) {
            Session::set('flash_error', (string) ($result['error'] ?? 'Failed to generate executive report.'));
            Response::redirect('/datasets/analyze?id=' . urlencode($datasetId));
        }

        $data = $result['data'];
        $summary = is_array($data['summary'] ?? null) ? $data['summary'] : [];
        $analytics = is_array($data['analytics'] ?? null) ? $data['analytics'] : [];
        $kpis = is_array($analytics['kpis'] ?? null) ? $analytics['kpis'] : [];

        $periodStart = (string) ($kpis['date_min'] ?? '');
        $periodEnd = (string) ($kpis['date_max'] ?? '');

        if ($periodStart === '' || $periodEnd === '') {
            Session::set('flash_error', 'Analytics service returned incomplete period dates.');
            Response::redirect('/datasets/analyze?id=' . urlencode($datasetId));
        }

        $metricsJson = json_encode($analytics, JSON_UNESCAPED_UNICODE);
        $summaryJson = json_encode($summary, JSON_UNESCAPED_UNICODE);

        if ($metricsJson === false || $summaryJson === false) {
            Session::set('flash_error', 'Failed to serialize report data.');
            Response::redirect('/datasets/analyze?id=' . urlencode($datasetId));
        }

        $aiModel = (string) ($summary['generation_mode'] ?? 'rule_based');

        $existing = $this->reportModel->findExistingForUser(
            $userId,
            $datasetId,
            $periodStart,
            $periodEnd,
            $aiModel,
            self::PROMPT_VERSION
        );

        if ($existing !== null) {
            Session::set('flash_warning', 'An executive report for this dataset and period already exists.');
            Response::redirect('/reports/show?id=' . (int) $existing['id']);
        }

        $reportId = $this->reportModel->create(
            $userId,
            $datasetId,
            $periodStart,
            $periodEnd,
            $metricsJson,
            $summaryJson,
            $aiModel,
            self::PROMPT_VERSION
        );

        Session::set('flash_success', 'Executive report generated and saved.');
        Response::redirect('/reports/show?id=' . $reportId);
    }

    public function show(): void
    {
        $user = AuthMiddleware::requireAuth();
        $userId = (int) $user['id'];

        $reportId = (int) ($_GET['id'] ?? 0);
        $report = $reportId > 0 ? $this->reportModel->findByIdForUser($reportId, $userId) : null;

        if ($report === null) {
            Session::set('flash_error', 'Report not found.');
            Response::redirect('/');
        }

        $metrics = json_decode((string) $report['metrics_json'], true);
        $summary = json_decode((string) $report['ai_summary'], true);

        if (!is_array($summary)) {
            $summary = [
                'headline' => 'Executive Summary',
                'executive_summary' => (string) $report['ai_summary'],
                'key_findings' => [],
                'recommendations' => [],
            ];
        }

        $successMessage = Session::get('flash_success');
        $warningMessage = Session::get('flash_warning');
        Session::remove('flash_success');
        Session::remove('flash_warning');

        Response::view('pages/report_show', [
            'user' => $user,
            'report' => $report,
            'metrics' => is_array($metrics) ? $metrics : [],
            'summary' => $summary,
            'successMessage' => is_string($successMessage) ? $successMessage : null,
            'warningMessage' => is_string($warningMessage) ? $warningMessage : null,
        ]);
    }
}