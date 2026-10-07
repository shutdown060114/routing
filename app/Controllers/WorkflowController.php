<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Rbac;
use App\Core\View;
use App\Models\PanelAccess;
use App\Models\Workflow;
use App\Services\WorkflowService;

final class WorkflowController
{
    private function service(): WorkflowService
    {
        return new WorkflowService(new Workflow($GLOBALS['pdo']));
    }

    private function panelAccess(): PanelAccess
    {
        return new PanelAccess($GLOBALS['pdo']);
    }

    public function index(): void
    {
        Auth::requireLogin();
        $workflows = array_values(array_filter(
            $this->service()->allEnabled(),
            fn(array $workflow) => $this->canViewWorkflow($workflow['slug'])
        ));

        View::render('workflow/index', [
            'title' => 'Workflows',
            'workflows' => $workflows,
        ]);
    }

    public function definition(array $params): void
    {
        Auth::requireLogin();
        if (!$this->canViewWorkflow($params['slug'])) {
            $this->deny();
            return;
        }
        $this->renderDefinition($params['slug']);
    }

    public function dynamicAlias(array $params): void
    {
        Auth::requireLogin();
        if (!$this->canViewWorkflow($params['_target'])) {
            $this->deny();
            return;
        }
        $this->renderDefinition($params['_target']);
    }

    public function start(array $params): void
    {
        Auth::requireLogin();
        Csrf::validate();

        if (!$this->canStartWorkflow($params['slug'])) {
            $this->deny();
            return;
        }

        $service = $this->service();
        $workflow = $service->definition($params['slug']);
        if (!$workflow) {
            http_response_code(404);
            View::render('errors/404', ['title' => 'Workflow not found']);
            return;
        }

        $title = trim((string)($_POST['title'] ?? ''));
        if ($title === '') {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Title is required.'];
            redirect('/workflow/' . rawurlencode($params['slug']));
        }

        try {
            $id = $service->start((int)$workflow['id'], $title, $_POST['payload'] ?? []);
            redirect('/workflow-instance/' . $id);
        } catch (\Throwable $e) {
            $_SESSION['flash'] = ['type' => 'error', 'message' => $e->getMessage()];
            redirect('/workflow/' . rawurlencode($params['slug']));
        }
    }

    public function instance(array $params): void
    {
        Auth::requireLogin();
        $instance = $this->service()->instance((int)$params['id']);
        if (!$instance) {
            http_response_code(404);
            View::render('errors/404', ['title' => 'Workflow instance not found']);
            return;
        }

        if (!$this->canViewWorkflow($instance['workflow_slug'])) {
            $this->deny();
            return;
        }

        View::render('workflow/instance', [
            'title' => $instance['title'],
            'instance' => $instance,
        ]);
    }

    public function transition(array $params): void
    {
        Auth::requireLogin();
        Csrf::validate();

        $service = $this->service();
        $instance = $service->instance((int)$params['id']);
        if (!$instance || !$this->canViewWorkflow($instance['workflow_slug'])) {
            $this->deny();
            return;
        }

        try {
            $service->transition(
                (int)$params['id'],
                (int)($_POST['transition_id'] ?? 0),
                trim((string)($_POST['comment'] ?? '')) ?: null
            );
            $_SESSION['flash'] = ['type' => 'success', 'message' => 'Workflow updated.'];
        } catch (\Throwable $e) {
            $_SESSION['flash'] = ['type' => 'error', 'message' => $e->getMessage()];
        }

        redirect('/workflow-instance/' . (int)$params['id']);
    }

    private function renderDefinition(string $slug): void
    {
        $workflow = $this->service()->definition($slug);
        if (!$workflow) {
            http_response_code(404);
            View::render('errors/404', ['title' => 'Workflow not found']);
            return;
        }

        View::render('workflow/definition', [
            'title' => $workflow['name'],
            'workflow' => $workflow,
        ]);
    }

    private function canViewWorkflow(string $slug): bool
    {
        if (Rbac::hasRole('developer')) return true;
        if (!$this->panelAccess()->userCanViewWorkflow($slug, (int)Auth::id())) return false;
        return Rbac::can('workflow.view');
    }

    private function canStartWorkflow(string $slug): bool
    {
        if (Rbac::hasRole('developer')) return true;
        if (!$this->panelAccess()->userCanActOnWorkflow($slug, (int)Auth::id())) return false;
        return Rbac::can('workflow.start');
    }

    private function deny(): void
    {
        http_response_code(403);
        View::render('errors/403', ['title' => 'Access denied']);
    }
}
