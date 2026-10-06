<?php
namespace App\Core;

use PDO;

final class Auth
{
    public static function attempt(PDO $pdo, string $username, string $password): bool
    {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username=? AND is_active=1 LIMIT 1");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        if (!$user || !password_verify($password, $user['password_hash'])) return false;

        session_regenerate_id(true);
        $_SESSION['user_id'] = (int)$user['id'];
        return true;
    }

    public static function user(): ?array
    {
        static $cached = null;
        if (!isset($_SESSION['user_id'])) return null;
        if ($cached) return $cached;
        $stmt = $GLOBALS['pdo']->prepare("SELECT id, username, email, display_name FROM users WHERE id=? AND is_active=1");
        $stmt->execute([$_SESSION['user_id']]);
        return $cached = ($stmt->fetch() ?: null);
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
        if (!self::check()) {
            header('Location: ' . url('/login'));
            exit;
        }
    }

    public static function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
    }
}
