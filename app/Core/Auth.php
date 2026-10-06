<?php
namespace App\Core;

use App\Models\User;

final class Auth
{
    private static ?array $cachedUser = null;

    public static function attempt(string $username, string $password): bool
    {
        $user = (new User($GLOBALS['pdo']))->findActiveByUsername($username);
        if (!$user || !password_verify($password, $user['password_hash'])) return false;

        session_regenerate_id(true);
        $_SESSION['user_id'] = (int)$user['id'];
        self::$cachedUser = null;
        return true;
    }

    public static function user(): ?array
    {
        if (!isset($_SESSION['user_id'])) return null;
        if (self::$cachedUser) return self::$cachedUser;
        return self::$cachedUser = (new User($GLOBALS['pdo']))->findActiveById((int)$_SESSION['user_id']);
    }

    public static function id(): ?int
    {
        return self::user()['id'] ?? null;
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function requireLogin(): void
    {
        if (!self::check()) redirect('/login');
    }

    public static function logout(): void
    {
        self::$cachedUser = null;
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }

        session_destroy();
    }
}
