<?php
namespace App\Core;

final class View
{
    public static function render(string $view, array $data = []): void
    {
        $file = dirname(__DIR__, 2) . '/views/' . $view . '.php';
        if (!is_file($file)) {
            http_response_code(500);
            exit('View not found: ' . htmlspecialchars($view));
        }
        extract($data, EXTR_SKIP);
        $contentView = $file;
        require dirname(__DIR__, 2) . '/views/layout.php';
    }
}
