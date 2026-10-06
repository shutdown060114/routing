<?php
namespace App\Models;

use PDO;

final class AuditLog
{
    public function __construct(private PDO $pdo) {}

    public function create(?int $userId, string $action, string $entityType = '', ?int $entityId = null, array $meta = [], ?string $ip = null): void
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO audit_logs (user_id,action,entity_type,entity_id,metadata,ip_address,created_at)
             VALUES (?,?,?,?,?,?,NOW())"
        );
        $stmt->execute([
            $userId,
            $action,
            $entityType,
            $entityId,
            $meta ? json_encode($meta, JSON_UNESCAPED_UNICODE) : null,
            $ip,
        ]);
    }
}
