<?php
namespace App\Core;

final class Audit
{
    public static function log(string $action, string $entityType = '', ?int $entityId = null, array $meta = []): void
    {
        $stmt = $GLOBALS['pdo']->prepare(
            "INSERT INTO audit_logs (user_id, action, entity_type, entity_id, metadata, ip_address, created_at)
             VALUES (?, ?, ?, ?, ?, ?, NOW())"
        );
        $stmt->execute([
            Auth::id(),
            $action,
            $entityType,
            $entityId,
            $meta ? json_encode($meta, JSON_UNESCAPED_UNICODE) : null,
            $_SERVER['REMOTE_ADDR'] ?? null,
        ]);
    }
}
