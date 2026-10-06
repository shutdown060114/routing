<?php
use App\Core\Database;

$GLOBALS['config'] = require __DIR__ . '/config/config.php';
date_default_timezone_set($GLOBALS['config']['app']['timezone'] ?? 'UTC');

spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    if (!str_starts_with($class, $prefix)) return;
    $relative = substr($class, strlen($prefix));
    $parts = explode('\\', $relative);
    $base = array_shift($parts);
    $map = [
        'Core' => __DIR__ . '/src/Core/',
        'Controllers' => __DIR__ . '/app/Controllers/',
    ];
    if (!isset($map[$base])) return;
    $file = $map[$base] . implode('/', $parts) . '.php';
    if (is_file($file)) require $file;
});

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_name('routing_session');
    session_start();
}

$GLOBALS['pdo'] = Database::connect($GLOBALS['config']['db']);

function url(string $path = '/'): string
{
    $base = rtrim($GLOBALS['config']['app']['base_path'] ?? '', '/');
    return $base . '/' . ltrim($path, '/');
}

function e(mixed $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function redirect(string $path): never
{
    header('Location: ' . url($path));
    exit;
}
