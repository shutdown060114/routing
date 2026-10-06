<?php
namespace App\Models;

use PDO;

final class RouteDefinition
{
    public function __construct(private PDO $pdo) {}

    public function enabled(): array
    {
        return $this->pdo->query("SELECT method,path,target_type,target_value,permission FROM routes WHERE enabled=1 ORDER BY id")->fetchAll();
    }

    public function countEnabled(): int
    {
        return (int)$this->pdo->query("SELECT COUNT(*) FROM routes WHERE enabled=1")->fetchColumn();
    }
}
