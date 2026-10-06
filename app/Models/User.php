<?php
namespace App\Models;

use PDO;

final class User
{
    public function __construct(private PDO $pdo) {}

    public function findActiveByUsername(string $username): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE username=? AND is_active=1 LIMIT 1");
        $stmt->execute([$username]);
        return $stmt->fetch() ?: null;
    }

    public function findActiveById(int $id): ?array
    {
        $stmt = $this->pdo->prepare("SELECT id,username,email,display_name FROM users WHERE id=? AND is_active=1 LIMIT 1");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function countActive(): int
    {
        return (int)$this->pdo->query("SELECT COUNT(*) FROM users WHERE is_active=1")->fetchColumn();
    }
}
