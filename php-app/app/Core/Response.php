<?php

declare(strict_types=1);

final class Response
{
    public static function html(string $content, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: text/html; charset=UTF-8');
        echo $content;
    }

    public static function view(string $viewPath, array $data = [], int $status = 200): void
    {
        $fullPath = dirname(__DIR__) . '/Views/' . ltrim($viewPath, '/') . '.php';

        if (!is_file($fullPath)) {
            self::html('<h1>500 - View not found</h1>', 500);
            return;
        }

        http_response_code($status);
        header('Content-Type: text/html; charset=UTF-8');

        extract($data, EXTR_SKIP);
        require $fullPath;
    }

    public static function redirect(string $location, int $status = 302): void
    {
        http_response_code($status);
        header('Location: ' . $location);
        exit;
    }

    public static function json(array $payload, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    }

    public static function notFound(): void
    {
        self::html('<h1>404 - Page not found</h1>', 404);
    }
}