<?php
namespace App\Services;

use App\Core\Auth;
use App\Models\AuditLog;

final class AuditService
{
    public static function log(string $action, string $entityType = '', ?int $entityId = null, array $meta = []): void
    {
        (new AuditLog($GLOBALS['pdo']))->create(
            Auth::id(),
            $action,
            $entityType,
            $entityId,
            $meta,
            $_SERVER['REMOTE_ADDR'] ?? null
        );
    }
}
