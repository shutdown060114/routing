<?php
namespace App\Models;

use PDO;

final class Workflow
{
    public function __construct(private PDO $pdo) {}

    public function allEnabled(): array
    {
        return $this->pdo->query("SELECT id,name,slug,description FROM workflows WHERE enabled=1 ORDER BY name")->fetchAll();
    }

    public function findBySlug(string $slug): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM workflows WHERE slug=? AND enabled=1 LIMIT 1");
        $stmt->execute([$slug]);
        $workflow = $stmt->fetch();
        if (!$workflow) return null;

        $steps = $this->pdo->prepare("SELECT * FROM workflow_steps WHERE workflow_id=? ORDER BY sort_order,id");
        $steps->execute([$workflow['id']]);
        $workflow['steps'] = $steps->fetchAll();
        return $workflow;
    }

    public function firstStepId(int $workflowId): ?int
    {
        $stmt = $this->pdo->prepare("SELECT id FROM workflow_steps WHERE workflow_id=? ORDER BY sort_order,id LIMIT 1");
        $stmt->execute([$workflowId]);
        $id = $stmt->fetchColumn();
        return $id ? (int)$id : null;
    }

    public function createInstance(int $workflowId, int $stepId, string $title, array $payload, ?int $userId): int
    {
        $stmt = $this->pdo->prepare("INSERT INTO workflow_instances (workflow_id,current_step_id,title,payload,status,created_by,created_at,updated_at) VALUES (?,?,?,?, 'active', ?, NOW(), NOW())");
        $stmt->execute([$workflowId, $stepId, $title, json_encode($payload, JSON_UNESCAPED_UNICODE), $userId]);
        return (int)$this->pdo->lastInsertId();
    }

    public function findInstance(int $id, bool $lock = false): ?array
    {
        $suffix = $lock ? ' FOR UPDATE' : '';
        $stmt = $this->pdo->prepare("SELECT wi.*,w.name workflow_name,w.slug workflow_slug,ws.name step_name,ws.slug step_slug FROM workflow_instances wi JOIN workflows w ON w.id=wi.workflow_id JOIN workflow_steps ws ON ws.id=wi.current_step_id WHERE wi.id=?{$suffix}");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function transitionsForStep(int $workflowId, int $stepId): array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM workflow_transitions WHERE workflow_id=? AND from_step_id=? ORDER BY id");
        $stmt->execute([$workflowId, $stepId]);
        return $stmt->fetchAll();
    }

    public function findTransition(int $transitionId, int $workflowId, int $fromStepId): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM workflow_transitions WHERE id=? AND workflow_id=? AND from_step_id=? LIMIT 1");
        $stmt->execute([$transitionId, $workflowId, $fromStepId]);
        return $stmt->fetch() ?: null;
    }

    public function updateInstanceStep(int $instanceId, int $stepId, string $status): void
    {
        $stmt = $this->pdo->prepare("UPDATE workflow_instances SET current_step_id=?,status=?,updated_at=NOW() WHERE id=?");
        $stmt->execute([$stepId, $status, $instanceId]);
    }

    public function addHistory(int $instanceId, ?int $fromStepId, int $toStepId, string $action, ?string $comment, ?int $userId): void
    {
        $stmt = $this->pdo->prepare("INSERT INTO workflow_history (instance_id,from_step_id,to_step_id,action,comment,acted_by,created_at) VALUES (?,?,?,?,?,?,NOW())");
        $stmt->execute([$instanceId, $fromStepId, $toStepId, $action, $comment, $userId]);
    }

    public function history(int $instanceId): array
    {
        $stmt = $this->pdo->prepare("SELECT wh.*,u.display_name,u.username,fs.name from_step,ts.name to_step FROM workflow_history wh LEFT JOIN users u ON u.id=wh.acted_by LEFT JOIN workflow_steps fs ON fs.id=wh.from_step_id LEFT JOIN workflow_steps ts ON ts.id=wh.to_step_id WHERE wh.instance_id=? ORDER BY wh.id DESC");
        $stmt->execute([$instanceId]);
        return $stmt->fetchAll();
    }

    public function countActiveInstances(): int
    {
        return (int)$this->pdo->query("SELECT COUNT(*) FROM workflow_instances WHERE status='active'")->fetchColumn();
    }
}
