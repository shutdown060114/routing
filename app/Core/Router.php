<?php
namespace App\Core;

use App\Models\RouteDefinition;
use PDO;

final class Router
{
    private array $routes = [];

    public function get(string $path, callable|array $handler, ?string $permission = null): void
    {
        $this->add('GET', $path, $handler, $permission);
    }

    public function post(string $path, callable|array $handler, ?string $permission = null): void
    {
        $this->add('POST', $path, $handler, $permission);
    }

    public function add(string $method, string $path, callable|array $handler, ?string $permission = null): void
    {
        $this->routes[] = compact('method', 'path', 'handler', 'permission');
    }

    public function loadDatabaseRoutes(PDO $pdo): void
    {
        foreach ((new RouteDefinition($pdo))->enabled() as $row) {
            $handler = match ($row['target_type']) {
                'panel' => [\App\Controllers\PanelController::class, 'dynamicAlias'],
                'workflow' => [\App\Controllers\WorkflowController::class, 'dynamicAlias'],
                default => null,
            };

            if (!$handler) continue;

            $this->routes[] = [
                'method' => strtoupper($row['method']),
                'path' => $row['path'],
                'handler' => $handler,
                'permission' => $row['permission'] ?: null,
                'target_value' => $row['target_value'],
            ];
        }
    }

    public function dispatch(string $method, string $uri): void
    {
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';
        $base = rtrim((string)($GLOBALS['config']['app']['base_path'] ?? ''), '/');

        if ($base && str_starts_with($path, $base)) {
            $path = substr($path, strlen($base)) ?: '/';
        }

        $path = '/' . trim($path, '/');
        if ($path === '//') $path = '/';

        foreach ($this->routes as $route) {
            if ($route['method'] !== strtoupper($method)) continue;

            $params = $this->match($route['path'], $path);
            if ($params === null) continue;

            if ($route['permission'] && !Rbac::can($route['permission'])) {
                http_response_code(403);
                View::render('errors/403', ['title' => 'Access denied']);
                return;
            }

            if (isset($route['target_value'])) $params['_target'] = $route['target_value'];
            $this->invoke($route['handler'], $params);
            return;
        }

        http_response_code(404);
        View::render('errors/404', ['title' => 'Page not found']);
    }

    private function match(string $pattern, string $path): ?array
    {
        $names = [];
        $regex = preg_replace_callback('/\{([A-Za-z_][A-Za-z0-9_]*)\}/', function ($m) use (&$names) {
            $names[] = $m[1];
            return '([^/]+)';
        }, rtrim($pattern, '/') ?: '/');

        if (!preg_match('#^' . $regex . '/?$#', $path, $matches)) return null;
        array_shift($matches);
        return array_combine($names, array_map('urldecode', $matches)) ?: [];
    }

    private function invoke(callable|array $handler, array $params): void
    {
        if (is_array($handler) && is_string($handler[0])) {
            $controller = new $handler[0]();
            $controller->{$handler[1]}($params);
            return;
        }
        $handler($params);
    }
}
