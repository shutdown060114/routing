<?php
use App\Controllers\AuthController;
use App\Controllers\DashboardController;
use App\Controllers\PanelBuilderController;
use App\Controllers\PanelController;
use App\Controllers\WorkflowController;

$router->get('/', [DashboardController::class, 'index']);
$router->get('/login', [AuthController::class, 'loginForm']);
$router->post('/login', [AuthController::class, 'login']);
$router->post('/logout', [AuthController::class, 'logout']);

// Panel access is enforced per panel by user/role access rules.
$router->get('/panel/{slug}', [PanelController::class, 'show']);
$router->post('/panel/{slug}', [PanelController::class, 'store']);

$router->get('/developer/panels/create', [PanelBuilderController::class, 'create']);
$router->post('/developer/panels', [PanelBuilderController::class, 'store']);

// WorkflowController combines RBAC with the linked panel's user/role access.
$router->get('/workflows', [WorkflowController::class, 'index']);
$router->get('/workflow/{slug}', [WorkflowController::class, 'definition']);
$router->post('/workflow/{slug}/start', [WorkflowController::class, 'start']);
$router->get('/workflow-instance/{id}', [WorkflowController::class, 'instance']);
$router->post('/workflow-instance/{id}/transition', [WorkflowController::class, 'transition']);
