<?php

declare(strict_types=1);

return static function (
    Router $router,
    AuthController $authController,
    DatasetController $datasetController
): void {
    $router->get('/', [$datasetController, 'index']);
    $router->post('/datasets/upload', [$datasetController, 'upload']);
    $router->get('/datasets/preview', [$datasetController, 'preview']);
    $router->post('/datasets/confirm', [$datasetController, 'confirm']);

    $router->get('/register', [$authController, 'showRegister']);
    $router->post('/register', [$authController, 'register']);

    $router->get('/login', [$authController, 'showLogin']);
    $router->post('/login', [$authController, 'login']);

    $router->post('/logout', [$authController, 'logout']);
};