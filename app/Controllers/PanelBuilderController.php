<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Rbac;
use App\Core\View;
use App\Models\Role;
use App\Models\User;
use App\Services\AuditService;
use App\Services\PanelBuilderService;

final class PanelBuilderController
{
    public function create(): void
    {
        Auth::requireLogin();
        $this->authorize();

        View::render('panel-builder/create', [
            'title' => 'Create Dynamic Panel',
            'users' => (new User($GLOBALS['pdo']))->allActive(),
            'roles' => (new Role($GLOBALS['pdo']))->all(),
        ]);
    }

    public function store(): void
    {
        Auth::requireLogin();
        $this->authorize();
        Csrf::validate();

        try {
            $panelId = (new PanelBuilderService($GLOBALS['pdo']))->create($_POST);
            AuditService::log('panel.builder.create', 'panel', $panelId, ['name' => $_POST['name'] ?? null]);
            $_SESSION['flash'] = ['type' => 'success', 'message' => 'Panel, database table, access rules, route and workflow were generated successfully.'];
            redirect('/panel/' . rawurlencode($this->slug((string)($_POST['slug'] ?? $_POST['name'] ?? ''))));
        } catch (\Throwable $e) {
            $_SESSION['flash'] = ['type' => 'error', 'message' => $e->getMessage()];
            $_SESSION['panel_builder_old'] = $_POST;
            redirect('/developer/panels/create');
        }
    }

    private function authorize(): void
    {
        if (Rbac::hasRole('developer') || Rbac::can('panels.manage')) return;
        http_response_code(403);
        View::render('errors/403', ['title' => 'Access denied']);
        exit;
    }

    private function slug(string $value): string
    {
        $value = strtolower(trim($value));
        $value = preg_replace('/[^a-z0-9]+/', '-', $value) ?? '';
        return trim($value, '-');
    }
}
