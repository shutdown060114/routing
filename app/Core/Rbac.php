<?php
namespace App\Core;

use App\Models\Role;

final class Rbac
{
    private static ?array $roles = null;

    public static function roles(): array
    {
        if (!Auth::id()) return [];
        if (self::$roles !== null) return self::$roles;
        return self::$roles = (new Role($GLOBALS['pdo']))->slugsForUser(Auth::id());
    }

    public static function hasRole(string $role): bool
    {
        return in_array($role, self::roles(), true);
    }

    public static function can(?string $permission): bool
    {
        if ($permission === null || $permission === '') return true;
        if (!Auth::id()) return false;

        // Developer is the trusted superuser role with full system access.
        if (self::hasRole('developer')) return true;

        return (new Role($GLOBALS['pdo']))->userHasPermission(Auth::id(), $permission);
    }
}
