<?php

declare(strict_types=1);

$config = require dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/app/Services/AnalyticsClient.php';

$client = new AnalyticsClient($config['analytics']['url']);

$health = $client->health();
echo 'Health: ' . ($health['ok'] ? 'PASS' : 'FAIL - ' . ($health['error'] ?? '')) . PHP_EOL;

$uploadsDir = dirname(__DIR__, 2) . '/storage/uploads';
$files = glob($uploadsDir . '/*.csv') ?: [];

if ($files === []) {
    echo 'Validate: SKIP (no CSV file found in storage/uploads)' . PHP_EOL;
    exit(0);
}

$datasetId = pathinfo($files[0], PATHINFO_FILENAME);
$validation = $client->validateDataset($datasetId);

if ($validation['ok']) {
    $data = $validation['data'];
    echo sprintf(
        'Validate: PASS (rows=%d, valid=%d, errors=%d, range=%s..%s)' . PHP_EOL,
        (int) $data['row_count'],
        (int) $data['valid_row_count'],
        (int) $data['error_row_count'],
        (string) ($data['date_min'] ?? 'n/a'),
        (string) ($data['date_max'] ?? 'n/a')
    );
} else {
    echo 'Validate: FAIL - ' . ($validation['error'] ?? 'Unknown error') . PHP_EOL;
}