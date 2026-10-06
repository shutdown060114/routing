<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Rbac;
use App\Core\View;

final class DashboardController
{
    public function index(): void
    {
        Auth::requireLogin();

        $panels = $GLOBALS['pdo']->query("SELECT name,slug,icon,view_permission FROM panels WHERE enabled=1 ORDER BY sort_order,id")->fetchAll();
        $panels = array_values(array_filter($panels, fn($p) => !$p['view_permission'] || Rbac::can($p['view_permission'])));

        $workflows = $GLOBALS['pdo']->query("SELECT name,slug,description FROM workflows WHERE enabled=1 ORDER BY name")->fetchAll();
        $stats = [
            'users' => (int)$GLOBALS['pdo']->query("SELECT COUNT(*) FROM users WHERE is_active=1")->fetchColumn(),
            'active_workflows' => (int)$GLOBALS['pdo']->query("SELECT COUNT(*) FROM workflow_instances WHERE status='active'")->fetchColumn(),
            'routes' => (int)$GLOBALS['pdo']->query("SELECT COUNT(*) FROM routes WHERE enabled=1")->fetchColumn(),
        ];

        View::render('dashboard', [
            'title' => 'Dashboard',
            'panels' => $panels,
            'workflows' => $workflows,
            'stats' => $stats,
        ]);
    }
}
