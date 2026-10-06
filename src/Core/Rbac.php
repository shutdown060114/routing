<?php
namespace App\Core;

final class Rbac
{
    public static function roles(): array
    {
        if (!Auth::id()) return [];
        $stmt = $GLOBALS['pdo']->prepare("SELECT r.slug FROM roles r JOIN user_roles ur ON ur.role_id=r.id WHERE ur.user_id=?");
        $stmt->execute([Auth::id()]);
        return array_column($stmt->fetchAll(), 'slug');
    }

    public static function hasRole(string $role): bool
    {
        return in_array($role, self::roles(), true);
    }

    public static function can(?string $permission): bool
    {
        if ($permission === null || $permission === '') return true;
        if (!Auth::id()) return false;
        if (self::hasRole('developer')) return true;

        $stmt = $GLOBALS['pdo']->prepare(
            "SELECT 1 FROM permissions p
             JOIN role_permissions rp ON rp.permission_id=p.id
             JOIN user_roles ur ON ur.role_id=rp.role_id
             WHERE ur.user_id=? AND p.slug=? LIMIT 1"
        );
        $stmt->execute([Auth::id(), $permission]);
        return (bool)$stmt->fetchColumn();
    }
}
