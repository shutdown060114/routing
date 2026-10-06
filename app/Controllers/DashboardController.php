<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Rbac;
use App\Core\View;
use App\Models\Panel;
use App\Models\RouteDefinition;
use App\Models\User;
use App\Models\Workflow;

final class DashboardController
{
    public function index(): void
    {
        Auth::requireLogin();

        $panelModel = new Panel($GLOBALS['pdo']);
        $workflowModel = new Workflow($GLOBALS['pdo']);
        $userModel = new User($GLOBALS['pdo']);
        $routeModel = new RouteDefinition($GLOBALS['pdo']);

        $panels = array_values(array_filter(
            $panelModel->visiblePanels(),
            fn(array $panel) => !$panel['view_permission'] || Rbac::can($panel['view_permission'])
        ));

        View::render('dashboard', [
            'title' => 'Dashboard',
            'panels' => $panels,
            'workflows' => $workflowModel->allEnabled(),
            'stats' => [
                'users' => $userModel->countActive(),
                'active_workflows' => $workflowModel->countActiveInstances(),
                'routes' => $routeModel->countEnabled(),
            ],
        ]);
    }
}
