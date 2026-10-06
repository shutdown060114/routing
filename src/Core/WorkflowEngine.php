<?php
namespace App\Core;

use PDO;

final class WorkflowEngine
{
    public static function definition(PDO $pdo, string $slug): ?array
    {
        $stmt = $pdo->prepare("SELECT * FROM workflows WHERE slug=? AND enabled=1 LIMIT 1");
        $stmt->execute([$slug]);
        $workflow = $stmt->fetch();
        if (!$workflow) return null;

        $steps = $pdo->prepare("SELECT * FROM workflow_steps WHERE workflow_id=? ORDER BY sort_order,id");
        $steps->execute([$workflow['id']]);
        $workflow['steps'] = $steps->fetchAll();
        return $workflow;
    }

    public static function start(PDO $pdo, int $workflowId, string $title, array $payload = []): int
    {
        $stmt = $pdo->prepare("SELECT id FROM workflow_steps WHERE workflow_id=? ORDER BY sort_order,id LIMIT 1");
        $stmt->execute([$workflowId]);
        $firstStep = $stmt->fetchColumn();
        if (!$firstStep) throw new \RuntimeException('Workflow has no steps.');

        $stmt = $pdo->prepare("INSERT INTO workflow_instances (workflow_id,current_step_id,title,payload,status,created_by,created_at,updated_at) VALUES (?,?,?,?, 'active', ?, NOW(), NOW())");
        $stmt->execute([$workflowId, $firstStep, $title, json_encode($payload, JSON_UNESCAPED_UNICODE), Auth::id()]);
        $id = (int)$pdo->lastInsertId();

        $pdo->prepare("INSERT INTO workflow_history (instance_id,from_step_id,to_step_id,action,acted_by,created_at) VALUES (?,NULL,?,'start',?,NOW())")
            ->execute([$id, $firstStep, Auth::id()]);
        Audit::log('workflow.start', 'workflow_instance', $id);
        return $id;
    }

    public static function instance(PDO $pdo, int $id): ?array
    {
        $stmt = $pdo->prepare("SELECT wi.*,w.name workflow_name,w.slug workflow_slug,ws.name step_name,ws.slug step_slug FROM workflow_instances wi JOIN workflows w ON w.id=wi.workflow_id JOIN workflow_steps ws ON ws.id=wi.current_step_id WHERE wi.id=?");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        if (!$row) return null;

        $t = $pdo->prepare("SELECT wt.* FROM workflow_transitions wt WHERE wt.workflow_id=? AND wt.from_step_id=? ORDER BY wt.id");
        $t->execute([$row['workflow_id'], $row['current_step_id']]);
        $row['transitions'] = array_values(array_filter($t->fetchAll(), fn($x) => !$x['permission'] || Rbac::can($x['permission'])));
        return $row;
    }

    public static function transition(PDO $pdo, int $instanceId, int $transitionId, ?string $comment = null): void
    {
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare("SELECT * FROM workflow_instances WHERE id=? FOR UPDATE");
            $stmt->execute([$instanceId]);
            $instance = $stmt->fetch();
            if (!$instance || $instance['status'] !== 'active') throw new \RuntimeException('Workflow instance is not active.');

            $stmt = $pdo->prepare("SELECT * FROM workflow_transitions WHERE id=? AND workflow_id=? AND from_step_id=?");
            $stmt->execute([$transitionId, $instance['workflow_id'], $instance['current_step_id']]);
            $transition = $stmt->fetch();
            if (!$transition) throw new \RuntimeException('Invalid workflow transition.');
            if ($transition['permission'] && !Rbac::can($transition['permission'])) throw new \RuntimeException('Not authorized for this action.');

            $status = $transition['closes_instance'] ? 'completed' : 'active';
            $pdo->prepare("UPDATE workflow_instances SET current_step_id=?,status=?,updated_at=NOW() WHERE id=?")
                ->execute([$transition['to_step_id'], $status, $instanceId]);
            $pdo->prepare("INSERT INTO workflow_history (instance_id,from_step_id,to_step_id,action,comment,acted_by,created_at) VALUES (?,?,?,?,?,?,NOW())")
                ->execute([$instanceId, $instance['current_step_id'], $transition['to_step_id'], $transition['action_label'], $comment, Auth::id()]);
            Audit::log('workflow.transition', 'workflow_instance', $instanceId, ['action' => $transition['action_label']]);
            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
}
