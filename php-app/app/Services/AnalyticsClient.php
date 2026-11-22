<?php

declare(strict_types=1);

final class AnalyticsClient
{
    private string $baseUrl;

    public function __construct(
        string $baseUrl,
        private int $connectTimeoutSeconds = 3,
        private int $timeoutSeconds = 10
    ) {
        $this->baseUrl = rtrim($baseUrl, '/');
    }

    /**
     * @return array{ok: bool, data?: array<string, mixed>, error?: string}
     */
    public function health(): array
    {
        return $this->request('GET', '/health');
    }

    /**
     * @return array{ok: bool, data?: array<string, mixed>, error?: string}
     */
    public function validateDataset(string $datasetId): array
    {
        return $this->request('POST', '/validate', ['dataset_id' => $datasetId]);
    }
    /**
     * @return array{ok: bool, data?: array<string, mixed>, error?: string}
     */
    public function analyzeDataset(
        string $datasetId,
        ?string $startDate = null,
        ?string $endDate = null
    ): array {
        $payload = ['dataset_id' => $datasetId];

        if ($startDate !== null && $startDate !== '') {
            $payload['start_date'] = $startDate;
        }

        if ($endDate !== null && $endDate !== '') {
            $payload['end_date'] = $endDate;
        }

        return $this->request('POST', '/analyze', $payload);
    }
    /**
     * @return array{ok: bool, data?: array<string, mixed>, error?: string}
     */
    public function summarizeDataset(
        string $datasetId,
        ?string $startDate = null,
        ?string $endDate = null,
        string $locale = 'en'
    ): array {
        $payload = [
            'dataset_id' => $datasetId,
            'locale' => $locale,
        ];

        if ($startDate !== null && $startDate !== '') {
            $payload['start_date'] = $startDate;
        }

        if ($endDate !== null && $endDate !== '') {
            $payload['end_date'] = $endDate;
        }

        return $this->request('POST', '/summarize', $payload);
    }

    /**
     * @param array<string, mixed> $analytics
     * @return array{ok: bool, data?: array<string, mixed>, error?: string}
     */
    public function summarizeMetrics(array $analytics, string $locale = 'en'): array
    {
        return $this->request('POST', '/summarize/from-metrics', [
            'analytics' => $analytics,
            'locale' => $locale,
        ]);
    }
    /**
     * @param array<string, mixed>|null $payload
     * @return array{ok: bool, data?: array<string, mixed>, error?: string}
     */
    private function request(string $method, string $path, ?array $payload = null): array
    {
        $url = $this->baseUrl . $path;
        $ch = curl_init($url);

        if ($ch === false) {
            return ['ok' => false, 'error' => 'Failed to initialize HTTP client.'];
        }

        $headers = ['Accept: application/json'];
        $options = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => $this->connectTimeoutSeconds,
            CURLOPT_TIMEOUT => $this->timeoutSeconds,
        ];

        if ($method === 'POST') {
            $jsonBody = json_encode($payload ?? [], JSON_UNESCAPED_UNICODE);
            if ($jsonBody === false) {
                return ['ok' => false, 'error' => 'Failed to encode request payload.'];
            }

            $headers[] = 'Content-Type: application/json';
            $options[CURLOPT_POST] = true;
            $options[CURLOPT_POSTFIELDS] = $jsonBody;
        }

        $options[CURLOPT_HTTPHEADER] = $headers;
        curl_setopt_array($ch, $options);

        $rawResponse = curl_exec($ch);
        $curlError = curl_error($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($rawResponse === false) {
            return [
                'ok' => false,
                'error' => 'Analytics service is unreachable: ' . $curlError,
            ];
        }

        $decoded = json_decode((string) $rawResponse, true);
        if (!is_array($decoded)) {
            return [
                'ok' => false,
                'error' => 'Analytics service returned invalid JSON (HTTP ' . $httpCode . ').',
            ];
        }

        if ($httpCode >= 400 || ($decoded['status'] ?? null) !== 'ok') {
            $apiMessage = is_array($decoded['error'] ?? null)
                ? (string) ($decoded['error']['message'] ?? 'Validation request failed.')
                : 'Analytics service returned an error (HTTP ' . $httpCode . ').';

            return ['ok' => false, 'error' => $apiMessage];
        }

        $data = $decoded['data'] ?? null;
        if (!is_array($data)) {
            return ['ok' => false, 'error' => 'Analytics service returned an unexpected response shape.'];
        }

        return ['ok' => true, 'data' => $data];
    }
}