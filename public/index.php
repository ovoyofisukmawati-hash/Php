<?php

if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require __DIR__ . '/../vendor/autoload.php';
} elseif (file_exists(__DIR__ . '/../autoload.php')) {
    require __DIR__ . '/../autoload.php';
}

use Core\ErrorHandler;
use Core\Request;
use Core\Router;

$router = new Router();

require __DIR__ . '/../routes/api.php';
if (function_exists('registerApiRoutes')) {
    registerApiRoutes($router);
}

try {
    $router->dispatch(Request::method(), Request::uri());
} catch (\Throwable $e) {
    ErrorHandler::handle($e);
}
