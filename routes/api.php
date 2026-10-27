<?php

use Core\ErrorHandler;
use Core\Request;
use Core\Router;

function registerApiRoutes(Router $router): void
{
    $router->get('health', fn () => [
        'success' => true,
        'message' => 'Backend is healthy',
        'timestamp' => date('c'),
    ]);

    $router->post('auth/register', [\App\Controllers\AuthController::class, 'register']);
    $router->post('auth/login', [\App\Controllers\AuthController::class, 'login']);
    $router->get('auth/profile', [\App\Controllers\AuthController::class, 'profile']);
    $router->post('auth/two-factor', [\App\Controllers\AuthController::class, 'enableTwoFactor']);

    $router->get('admin/dashboard', [\App\Controllers\AdminController::class, 'dashboard']);
    $router->get('admin/users', [\App\Controllers\AdminController::class, 'users']);
    $router->put('admin/users/{id}/status', [\App\Controllers\AdminController::class, 'updateUserStatus']);
    $router->get('admin/transactions', [\App\Controllers\AdminController::class, 'transactions']);
    $router->post('admin/transactions/{id}/approve', [\App\Controllers\AdminController::class, 'approveTransaction']);
    $router->post('admin/transactions/{id}/reject', [\App\Controllers\AdminController::class, 'rejectTransaction']);
    $router->get('admin/investment-plans', [\App\Controllers\AdminController::class, 'investmentPlans']);
    $router->post('admin/investment-plans', [\App\Controllers\AdminController::class, 'saveInvestmentPlan']);
    $router->get('admin/settings', [\App\Controllers\AdminController::class, 'settings']);
    $router->post('admin/settings', [\App\Controllers\AdminController::class, 'saveSettings']);

    $router->get('investment/plans', [\App\Controllers\InvestmentController::class, 'plans']);
    $router->post('investment/invest', [\App\Controllers\InvestmentController::class, 'invest']);
    $router->get('investment/history', [\App\Controllers\InvestmentController::class, 'history']);
}

if (PHP_SAPI !== 'cli') {
    $router = new Router();
    registerApiRoutes($router);

    try {
        $router->dispatch(Request::method(), Request::uri());
    } catch (\Throwable $e) {
        ErrorHandler::handle($e);
    }
}
