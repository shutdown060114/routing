<?php
use App\Controllers\AuthController;
use App\Controllers\DashboardController;
use App\Controllers\PanelController;
use App\Controllers\WorkflowController;

$router->get('/', [DashboardController::class, 'index']);
$router->get('/login', [AuthController::class, 'loginForm']);
$router->post('/login', [AuthController::class, 'login']);
$router->post('/logout', [AuthController::class, 'logout']);

$router->get('/panel/{slug}', [PanelController::class, 'show'], 'panel.view');
$router->post('/panel/{slug}', [PanelController::class, 'store'], 'panel.create');

$router->get('/workflows', [WorkflowController::class, 'index'], 'workflow.view');
$router->get('/workflow/{slug}', [WorkflowController::class, 'definition'], 'workflow.view');
$router->post('/workflow/{slug}/start', [WorkflowController::class, 'start'], 'workflow.start');
$router->get('/workflow-instance/{id}', [WorkflowController::class, 'instance'], 'workflow.view');
$router->post('/workflow-instance/{id}/transition', [WorkflowController::class, 'transition'], 'workflow.action');
