<?php
namespace App\Services;

use App\Core\Auth;
use App\Core\Rbac;
use App\Models\Workflow;

final class WorkflowService
{
    public function __construct(private Workflow $workflows) {}

    public function definition(string $slug): ?array
    {
        return $this->workflows->findBySlug($slug);
    }

    public function allEnabled(): array
    {
        return $this->workflows->allEnabled();
    }

    public function start(int $workflowId, string $title, array $payload = []): int
    {
        $firstStepId = $this->workflows->firstStepId($workflowId);
        if (!$firstStepId) throw new \RuntimeException('Workflow has no steps.');

        $id = $this->workflows->createInstance($workflowId, $firstStepId, $title, $payload, Auth::id());
        $this->workflows->addHistory($id, null, $firstStepId, 'start', null, Auth::id());
        AuditService::log('workflow.start', 'workflow_instance', $id);
        return $id;
    }

    public function instance(int $id): ?array
    {
        $instance = $this->workflows->findInstance($id);
        if (!$instance) return null;

        $instance['transitions'] = array_values(array_filter(
            $this->workflows->transitionsForStep((int)$instance['workflow_id'], (int)$instance['current_step_id']),
            fn(array $transition) => !$transition['permission'] || Rbac::can($transition['permission'])
        ));
        $instance['history'] = $this->workflows->history($id);
        return $instance;
    }

    public function transition(int $instanceId, int $transitionId, ?string $comment = null): void
    {
        $pdo = $GLOBALS['pdo'];
        $pdo->beginTransaction();

        try {
            $instance = $this->workflows->findInstance($instanceId, true);
            if (!$instance || $instance['status'] !== 'active') {
                throw new \RuntimeException('Workflow instance is not active.');
            }

            $transition = $this->workflows->findTransition(
                $transitionId,
                (int)$instance['workflow_id'],
                (int)$instance['current_step_id']
            );

            if (!$transition) throw new \RuntimeException('Invalid workflow transition.');
            if ($transition['permission'] && !Rbac::can($transition['permission'])) {
                throw new \RuntimeException('Not authorized for this action.');
            }

            $status = (int)$transition['closes_instance'] ? 'completed' : 'active';
            $this->workflows->updateInstanceStep($instanceId, (int)$transition['to_step_id'], $status);
            $this->workflows->addHistory(
                $instanceId,
                (int)$instance['current_step_id'],
                (int)$transition['to_step_id'],
                $transition['action_label'],
                $comment,
                Auth::id()
            );

            AuditService::log('workflow.transition', 'workflow_instance', $instanceId, [
                'action' => $transition['action_label'],
            ]);

            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
}
