<?php

declare(strict_types=1);

$config = require dirname(__DIR__) . '/config/config.php';

ini_set('display_errors', $config['app']['debug'] ? '1' : '0');
error_reporting(E_ALL);

require_once dirname(__DIR__) . '/app/Core/Database.php';
require_once dirname(__DIR__) . '/app/Core/Session.php';
require_once dirname(__DIR__) . '/app/Core/Csrf.php';
require_once dirname(__DIR__) . '/app/Core/Response.php';
require_once dirname(__DIR__) . '/app/Core/Router.php';
require_once dirname(__DIR__) . '/app/Models/User.php';
require_once dirname(__DIR__) . '/app/Models/Dataset.php';
require_once dirname(__DIR__) . '/app/Services/UploadService.php';
require_once dirname(__DIR__) . '/app/Services/AnalyticsClient.php';
require_once dirname(__DIR__) . '/app/Middleware/AuthMiddleware.php';
require_once dirname(__DIR__) . '/app/Controllers/AuthController.php';
require_once dirname(__DIR__) . '/app/Controllers/DatasetController.php';

Session::start();

$pdo = Database::connect($config['db']);
$userModel = new User($pdo);
$datasetModel = new Dataset($pdo);

$uploadsDir = dirname(__DIR__, 2) . '/storage/uploads';
$uploadService = new UploadService($uploadsDir);

$authController = new AuthController($userModel);
$analyticsClient = new AnalyticsClient($config['analytics']['url']);
$datasetController = new DatasetController($datasetModel, $uploadService, $analyticsClient);

$router = new Router();
$registerRoutes = require dirname(__DIR__) . '/routes/web.php';
$registerRoutes($router, $authController, $datasetController);

$router->dispatch(
    $_SERVER['REQUEST_METHOD'] ?? 'GET',
    $_SERVER['REQUEST_URI'] ?? '/'
);