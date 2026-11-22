<?php

declare(strict_types=1);

return static function (
    Router $router,
    AuthController $authController,
    DatasetController $datasetController,
    ReportController $reportController
): void {
    $router->get('/', [$datasetController, 'index']);
    $router->post('/datasets/upload', [$datasetController, 'upload']);
    $router->get('/datasets/preview', [$datasetController, 'preview']);
    $router->post('/datasets/confirm', [$datasetController, 'confirm']);
    $router->get('/datasets/analyze', [$datasetController, 'analyze']);
    $router->post('/datasets/delete', [$datasetController, 'delete']);

    $router->post('/reports/generate', [$reportController, 'generate']);
    $router->get('/reports/show', [$reportController, 'show']);
    $router->post('/reports/delete', [$reportController, 'delete']);

    $router->get('/register', [$authController, 'showRegister']);
    $router->post('/register', [$authController, 'register']);

    $router->get('/login', [$authController, 'showLogin']);
    $router->post('/login', [$authController, 'login']);

    $router->post('/logout', [$authController, 'logout']);
    $router->post('/language', static function (): void {
        if (Csrf::verify($_POST['_csrf_token'] ?? null)) {
            $locale = trim((string) ($_POST['locale'] ?? 'en'));
            I18n::setLocale($locale);
        }

        $redirect = trim((string) ($_POST['redirect'] ?? '/'));
        if ($redirect === '' || $redirect[0] !== '/' || str_starts_with($redirect, '//')) {
            $redirect = '/';
        }

        Response::redirect($redirect);
    });
};