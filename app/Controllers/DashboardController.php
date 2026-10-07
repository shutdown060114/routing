<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Rbac;
use App\Core\View;
use App\Models\Panel;
use App\Models\PanelAccess;
use App\Models\RouteDefinition;
use App\Models\User;
use App\Models\Workflow;

final class DashboardController
{
    public function index(): void
    {
        Auth::requireLogin();

        $panelModel = new Panel($GLOBALS['pdo']);
        $panelAccess = new PanelAccess($GLOBALS['pdo']);
        $workflowModel = new Workflow($GLOBALS['pdo']);
        $userModel = new User($GLOBALS['pdo']);
        $routeModel = new RouteDefinition($GLOBALS['pdo']);

        $panels = array_values(array_filter(
            $panelModel->visiblePanels(),
            function (array $panel) use ($panelAccess): bool {
                if (Rbac::hasRole('developer')) return true;
                if ($panel['view_permission'] && !Rbac::can($panel['view_permission'])) return false;
                return $panelAccess->userCanView((int)$panel['id'], (int)Auth::id());
            }
        ));

        $workflows = array_values(array_filter(
            $workflowModel->allEnabled(),
            function (array $workflow) use ($panelAccess): bool {
                if (Rbac::hasRole('developer')) return true;
                if (!Rbac::can('workflow.view')) return false;
                return $panelAccess->userCanViewWorkflow($workflow['slug'], (int)Auth::id());
            }
        ));

        View::render('dashboard', [
            'title' => 'Dashboard',
            'panels' => $panels,
            'workflows' => $workflows,
            'stats' => [
                'users' => $userModel->countActive(),
                'active_workflows' => $workflowModel->countActiveInstances(),
                'routes' => $routeModel->countEnabled(),
            ],
        ]);
    }
}
