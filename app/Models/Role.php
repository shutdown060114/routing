<?php
namespace App\Models;

use PDO;

final class Role
{
    public function __construct(private PDO $pdo) {}

    public function all(): array
    {
        return $this->pdo->query("SELECT id,name,slug,description FROM roles ORDER BY name")->fetchAll();
    }

    public function slugsForUser(int $userId): array
    {
        $stmt = $this->pdo->prepare("SELECT r.slug FROM roles r JOIN user_roles ur ON ur.role_id=r.id WHERE ur.user_id=?");
        $stmt->execute([$userId]);
        return array_column($stmt->fetchAll(), 'slug');
    }

    public function userHasPermission(int $userId, string $permission): bool
    {
        $stmt = $this->pdo->prepare(
            "SELECT 1 FROM permissions p
             JOIN role_permissions rp ON rp.permission_id=p.id
             JOIN user_roles ur ON ur.role_id=rp.role_id
             WHERE ur.user_id=? AND p.slug=? LIMIT 1"
        );
        $stmt->execute([$userId, $permission]);
        return (bool)$stmt->fetchColumn();
    }
}
