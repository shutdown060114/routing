<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Rbac;
use App\Core\View;
use App\Models\Panel;
use App\Services\AuditService;
use App\Services\PanelService;

final class PanelController
{
    private function service(): PanelService
    {
        return new PanelService(new Panel($GLOBALS['pdo']));
    }

    public function show(array $params): void
    {
        Auth::requireLogin();
        $this->renderPanel($params['slug']);
    }

    public function dynamicAlias(array $params): void
    {
        Auth::requireLogin();
        $this->renderPanel($params['_target']);
    }

    public function store(array $params): void
    {
        Auth::requireLogin();
        Csrf::validate();

        $service = $this->service();
        $panel = $service->definition($params['slug']);

        if (!$panel) {
            http_response_code(404);
            View::render('errors/404', ['title' => 'Panel not found']);
            return;
        }

        if ($panel['create_permission'] && !Rbac::can($panel['create_permission'])) {
            http_response_code(403);
            View::render('errors/403', ['title' => 'Access denied']);
            return;
        }

        try {
            $id = $service->create($panel, $_POST);
            AuditService::log('panel.create', $panel['source_table'], $id, ['panel' => $panel['slug']]);
            $_SESSION['flash'] = ['type' => 'success', 'message' => $panel['name'] . ' record created.'];
        } catch (\Throwable $e) {
            $_SESSION['flash'] = ['type' => 'error', 'message' => $e->getMessage()];
        }

        redirect('/panel/' . rawurlencode($params['slug']));
    }

    private function renderPanel(string $slug): void
    {
        $service = $this->service();
        $panel = $service->definition($slug);

        if (!$panel) {
            http_response_code(404);
            View::render('errors/404', ['title' => 'Panel not found']);
            return;
        }

        if ($panel['view_permission'] && !Rbac::can($panel['view_permission'])) {
            http_response_code(403);
            View::render('errors/403', ['title' => 'Access denied']);
            return;
        }

        View::render('panel/show', [
            'title' => $panel['name'],
            'panel' => $panel,
            'rows' => $service->rows($panel),
            'canCreate' => !$panel['create_permission'] || Rbac::can($panel['create_permission']),
        ]);
    }
}
