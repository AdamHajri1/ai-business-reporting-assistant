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

        // 1. Grab active language ('en' or 'fr') and tag the prompt version
        $locale = I18n::locale();
        $promptVersion = self::PROMPT_VERSION . '-' . $locale;

        // 2. Pass $locale to FastAPI so it writes the summary in EN or FR
        $result = $this->analyticsClient->summarizeDataset(
            $datasetId,
            $startDate !== '' ? $startDate : null,
            $endDate !== '' ? $endDate : null,
            $locale
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

        $summaryForStorage = $summary;
        $summaryForStorage['translations'] = [$locale => $summary];

        $metricsJson = json_encode($analytics, JSON_UNESCAPED_UNICODE);
        $summaryJson = json_encode($summaryForStorage, JSON_UNESCAPED_UNICODE);

        if ($metricsJson === false || $summaryJson === false) {
            Session::set('flash_error', 'Failed to serialize report data.');
            Response::redirect('/datasets/analyze?id=' . urlencode($datasetId));
        }

        $aiModel = (string) ($summary['generation_mode'] ?? 'rule_based');

        // 3. Check duplicates regardless of language (matches 'v1.0', 'v1.0-en', 'v1.0-fr')
        $existing = $this->reportModel->findExistingForUser(
            $userId,
            $datasetId,
            $periodStart,
            $periodEnd,
            $aiModel,
            self::PROMPT_VERSION
        );

        if ($existing !== null) {
            Session::set('flash_warning', I18n::t('report.already_exists'));
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
            $promptVersion
        );

        Session::set('flash_success', I18n::t('report.generated_success'));
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
        $metrics = is_array($metrics) ? $metrics : [];
        $summary = json_decode((string) $report['ai_summary'], true);

        if (!is_array($summary)) {
            $summary = [
                'headline' => I18n::t('report.default_headline'),
                'executive_summary' => (string) $report['ai_summary'],
                'key_findings' => [],
                'recommendations' => [],
            ];
        } else {
            $activeLocale = I18n::locale();
            $summary = $this->resolveSummaryForLocale($report, $metrics, $summary, $userId, $activeLocale);

            if (is_string($summary['locale'] ?? null) && $summary['locale'] !== '') {
                $report['prompt_version'] = self::PROMPT_VERSION . '-' . $summary['locale'];
            }
        }

        $successMessage = Session::get('flash_success');
        $warningMessage = Session::get('flash_warning');
        Session::remove('flash_success');
        Session::remove('flash_warning');

        Response::view('pages/report_show', [
            'user' => $user,
            'csrfToken' => Csrf::token(),
            'report' => $report,
            'metrics' => $metrics,
            'summary' => $summary,
            'successMessage' => is_string($successMessage) ? $successMessage : null,
            'warningMessage' => is_string($warningMessage) ? $warningMessage : null,
        ]);
    }

    public function delete(): void
    {
        $user = AuthMiddleware::requireAuth();
        $userId = (int) $user['id'];

        $isAjax = strtolower((string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest'
            || str_contains((string) ($_SERVER['HTTP_ACCEPT'] ?? ''), 'application/json');

        if (!Csrf::verify($_POST['_csrf_token'] ?? null)) {
            if ($isAjax) {
                Response::json(['ok' => false, 'error' => 'Invalid security token. Please try again.'], 403);
                return;
            }
            Session::set('flash_error', 'Invalid security token. Please try again.');
            Response::redirect('/');
        }

        $reportId = (int) ($_POST['report_id'] ?? 0);
        if ($reportId <= 0 || !$this->reportModel->deleteByIdForUser($reportId, $userId)) {
            if ($isAjax) {
                Response::json(['ok' => false, 'error' => I18n::t('report.not_found')], 404);
                return;
            }
            Session::set('flash_error', I18n::t('report.not_found'));
            Response::redirect('/');
        }

        if ($isAjax) {
            Response::json(['ok' => true, 'message' => I18n::t('report.deleted_success')], 200);
            return;
        }

        Session::set('flash_success', I18n::t('report.deleted_success'));
        Response::redirect('/');
    }

    /**
     * Return the report summary in the requested locale, generating and caching it
     * from the saved metrics snapshot if that locale has not been generated yet.
     *
     * @param array<string, mixed> $report
     * @param array<string, mixed> $metrics
     * @param array<string, mixed> $summary
     * @return array<string, mixed>
     */
    private function resolveSummaryForLocale(
        array $report,
        array $metrics,
        array $summary,
        int $userId,
        string $activeLocale
    ): array {
        $translations = is_array($summary['translations'] ?? null) ? $summary['translations'] : [];
        if (is_array($translations[$activeLocale] ?? null)) {
            return $translations[$activeLocale];
        }

        $baseLocale = is_string($summary['locale'] ?? null) && $summary['locale'] !== ''
            ? (string) $summary['locale']
            : 'en';

        if ($baseLocale === $activeLocale) {
            return $summary;
        }

        if (!is_array($metrics['kpis'] ?? null)) {
            return $summary;
        }

        $translated = $this->analyticsClient->summarizeMetrics($metrics, $activeLocale);
        if (!$translated['ok'] || !is_array($translated['data']['summary'] ?? null)) {
            return $summary;
        }

        $localizedSummary = $translated['data']['summary'];

        if (!isset($translations[$baseLocale])) {
            $baseSummary = $summary;
            unset($baseSummary['translations']);
            $translations[$baseLocale] = $baseSummary;
        }
        $translations[$activeLocale] = $localizedSummary;

        $summary['translations'] = $translations;
        $updatedJson = json_encode($summary, JSON_UNESCAPED_UNICODE);
        if ($updatedJson !== false) {
            $this->reportModel->updateSummary((int) $report['id'], $userId, $updatedJson);
        }

        return $localizedSummary;
    }
}