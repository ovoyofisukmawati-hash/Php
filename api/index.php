<?php

require __DIR__ . '/../vendor/autoload.php';

use Core\ErrorHandler;
use Core\Request;
use Core\Router;

$router = new Router();

require __DIR__ . '/../routes/api.php';
registerApiRoutes($router);

try {
    $router->dispatch(Request::method(), Request::uri());
} catch (\Throwable $e) {
    ErrorHandler::handle($e);
}
