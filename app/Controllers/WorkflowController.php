<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\View;
use App\Models\Workflow;
use App\Services\WorkflowService;

final class WorkflowController
{
    private function service(): WorkflowService
    {
        return new WorkflowService(new Workflow($GLOBALS['pdo']));
    }

    public function index(): void
    {
        Auth::requireLogin();
        View::render('workflow/index', [
            'title' => 'Workflows',
            'workflows' => $this->service()->allEnabled(),
        ]);
    }

    public function definition(array $params): void
    {
        Auth::requireLogin();
        $this->renderDefinition($params['slug']);
    }

    public function dynamicAlias(array $params): void
    {
        Auth::requireLogin();
        $this->renderDefinition($params['_target']);
    }

    public function start(array $params): void
    {
        Auth::requireLogin();
        Csrf::validate();

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

        View::render('workflow/instance', [
            'title' => $instance['title'],
            'instance' => $instance,
        ]);
    }

    public function transition(array $params): void
    {
        Auth::requireLogin();
        Csrf::validate();

        try {
            $this->service()->transition(
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
}
