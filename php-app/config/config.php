<?php

declare(strict_types=1);

$projectRoot = dirname(__DIR__, 2);
$envPath = $projectRoot . DIRECTORY_SEPARATOR . '.env';

if (is_file($envPath) && is_readable($envPath)) {
    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    if ($lines !== false) {
        foreach ($lines as $line) {
            $trimmed = trim($line);

            if ($trimmed === '' || str_starts_with($trimmed, '#')) {
                continue;
            }

            if (!str_contains($trimmed, '=')) {
                continue;
            }

            [$key, $value] = explode('=', $trimmed, 2);
            $key = trim($key);
            $value = trim($value);
            $value = trim($value, "\"'");

            if ($key !== '' && getenv($key) === false) {
                $_ENV[$key] = $value;
            }
        }
    }
}

$env = static function (string $key, string $default = ''): string {
    $systemValue = getenv($key);
    if ($systemValue !== false) {
        return $systemValue;
    }

    return isset($_ENV[$key]) ? (string) $_ENV[$key] : $default;
};

return [
    'app' => [
        'env' => $env('APP_ENV', 'production'),
        'debug' => $env('APP_DEBUG', 'false') === 'true',
    ],
    'db' => [
        'host' => $env('DB_HOST', '127.0.0.1'),
        'port' => (int) $env('DB_PORT', '3306'),
        'name' => $env('DB_NAME', ''),
        'user' => $env('DB_USER', ''),
        'pass' => $env('DB_PASS', ''),
    ],
    'analytics' => [
        'url' => rtrim($env('ANALYTICS_API_URL', 'http://127.0.0.1:8000'), '/'),
        'shared_secret' => $env('ANALYTICS_SHARED_SECRET', ''),
    ],
    'storage' => [
        'uploads_path' => $projectRoot . DIRECTORY_SEPARATOR . $env('STORAGE_UPLOADS_PATH', 'storage/uploads'),
    ],
    'llm' => [
        'api_key' => $env('LLM_API_KEY', ''),
        'model' => $env('LLM_MODEL', ''),
    ],
];