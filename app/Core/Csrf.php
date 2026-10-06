<?php
namespace App\Core;

final class Csrf
{
    public static function token(): string
    {
        if (empty($_SESSION['_csrf'])) {
            $_SESSION['_csrf'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['_csrf'];
    }

    public static function field(): string
    {
        return '<input type="hidden" name="_csrf" value="' . htmlspecialchars(self::token(), ENT_QUOTES, 'UTF-8') . '">';
    }

    public static function validate(): void
    {
        $token = (string)($_POST['_csrf'] ?? '');
        if (!hash_equals(self::token(), $token)) {
            http_response_code(419);
            exit('Invalid or expired CSRF token.');
        }
    }
}
