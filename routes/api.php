<?php

require __DIR__ . '/../vendor/autoload.php';

use Core\Request;
use Core\Response;
use Core\Router;

$router = new Router();

$router->get('health', function () {
    return [
        'success' => true,
        'message' => 'Backend is healthy',
        'timestamp' => date('c'),
    ];
});

$router->post('api/auth/register', [\App\Controllers\AuthController::class, 'register']);
$router->post('api/auth/login', [\App\Controllers\AuthController::class, 'login']);
$router->get('api/auth/profile', [\App\Controllers\AuthController::class, 'profile']);
$router->post('api/auth/two-factor', [\App\Controllers\AuthController::class, 'enableTwoFactor']);

$router->get('api/admin/dashboard', [\App\Controllers\AdminController::class, 'dashboard']);
$router->get('api/admin/users', [\App\Controllers\AdminController::class, 'users']);
$router->post('api/admin/users/status', [\App\Controllers\AdminController::class, 'updateUserStatus']);
$router->get('api/admin/transactions', [\App\Controllers\AdminController::class, 'transactions']);
$router->post('api/admin/transactions/approve', [\App\Controllers\AdminController::class, 'approveTransaction']);
$router->post('api/admin/transactions/reject', [\App\Controllers\AdminController::class, 'rejectTransaction']);
$router->get('api/admin/investment-plans', [\App\Controllers\AdminController::class, 'investmentPlans']);
$router->post('api/admin/investment-plans', [\App\Controllers\AdminController::class, 'saveInvestmentPlan']);
$router->get('api/admin/settings', [\App\Controllers\AdminController::class, 'settings']);
$router->post('api/admin/settings', [\App\Controllers\AdminController::class, 'saveSettings']);

$router->get('api/investment/plans', [\App\Controllers\InvestmentController::class, 'plans']);
$router->post('api/investment/invest', [\App\Controllers\InvestmentController::class, 'invest']);
$router->get('api/investment/history', [\App\Controllers\InvestmentController::class, 'history']);

$method = Request::method();
$uri = Request::uri();
$router->dispatch($method, $uri);
