<?php
namespace App\Models;

use PDO;

final class PanelAccess
{
    public function __construct(private PDO $pdo) {}

    public function userCanView(int $panelId, int $userId): bool
    {
        return $this->allowed($panelId, $userId, 'can_view');
    }

    public function userCanCreate(int $panelId, int $userId): bool
    {
        return $this->allowed($panelId, $userId, 'can_create');
    }

    public function userCanViewWorkflow(string $workflowSlug, int $userId): bool
    {
        $stmt = $this->pdo->prepare("SELECT id FROM panels WHERE workflow_slug=? AND enabled=1 LIMIT 1");
        $stmt->execute([$workflowSlug]);
        $panelId = $stmt->fetchColumn();
        if (!$panelId) return true;
        return $this->userCanView((int)$panelId, $userId);
    }

    public function userCanActOnWorkflow(string $workflowSlug, int $userId): bool
    {
        $stmt = $this->pdo->prepare("SELECT id FROM panels WHERE workflow_slug=? AND enabled=1 LIMIT 1");
        $stmt->execute([$workflowSlug]);
        $panelId = $stmt->fetchColumn();
        if (!$panelId) return true;
        return $this->userCanCreate((int)$panelId, $userId);
    }

    private function allowed(int $panelId, int $userId, string $column): bool
    {
        $panel = $this->pdo->prepare("SELECT access_mode FROM panels WHERE id=? AND enabled=1 LIMIT 1");
        $panel->execute([$panelId]);
        $mode = $panel->fetchColumn();
        if (!$mode) return false;
        if ($mode === 'all') return true;

        $direct = $this->pdo->prepare("SELECT {$column} FROM panel_user_access WHERE panel_id=? AND user_id=? LIMIT 1");
        $direct->execute([$panelId, $userId]);
        if ((int)$direct->fetchColumn() === 1) return true;

        $role = $this->pdo->prepare(
            "SELECT 1
             FROM panel_role_access pra
             JOIN user_roles ur ON ur.role_id=pra.role_id
             WHERE pra.panel_id=? AND ur.user_id=? AND pra.{$column}=1
             LIMIT 1"
        );
        $role->execute([$panelId, $userId]);
        return (bool)$role->fetchColumn();
    }
}
