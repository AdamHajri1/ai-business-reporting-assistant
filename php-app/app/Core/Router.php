<?php

declare(strict_types=1);

final class Router
{
    /** @var array<string, array<string, callable>> */
    private array $routes = [
        'GET' => [],
        'POST' => [],
    ];

    public function get(string $path, callable $handler): void
    {
        $this->routes['GET'][$this->normalizePath($path)] = $handler;
    }

    public function post(string $path, callable $handler): void
    {
        $this->routes['POST'][$this->normalizePath($path)] = $handler;
    }

    public function dispatch(string $method, string $requestUri): void
    {
        $method = strtoupper($method);
        $rawPath = parse_url($requestUri, PHP_URL_PATH);
        $path = $this->normalizePath(is_string($rawPath) ? $rawPath : '/');

        if (isset($this->routes[$method][$path])) {
            $handler = $this->routes[$method][$path];
            $handler();
            return;
        }

        Response::notFound();
    }

    private function normalizePath(string $path): string
    {
        $trimmed = rtrim($path, '/');

        return $trimmed === '' ? '/' : $trimmed;
    }
}