<?php
require dirname(__DIR__) . '/bootstrap.php';

if (PHP_SAPI !== 'cli') exit("Run this script from CLI.\n");

[$script, $username, $email, $password] = array_pad($argv, 4, null);
if (!$username || !$email || !$password) {
    exit("Usage: php scripts/create_developer.php <username> <email> <password>\n");
}

$pdo = $GLOBALS['pdo'];
$pdo->beginTransaction();

try {
    $stmt = $pdo->prepare("INSERT INTO users(username,email,display_name,password_hash,is_active) VALUES (?,?,?,?,1)");
    $stmt->execute([$username, $email, $username, password_hash($password, PASSWORD_DEFAULT)]);
    $userId = (int)$pdo->lastInsertId();

    $roleId = (int)$pdo->query("SELECT id FROM roles WHERE slug='developer' LIMIT 1")->fetchColumn();
    if (!$roleId) throw new RuntimeException('Developer role not found. Import database/schema.sql first.');

    $pdo->prepare("INSERT INTO user_roles(user_id,role_id) VALUES (?,?)")->execute([$userId, $roleId]);
    $pdo->commit();

    echo "Developer account created: {$username}\n";
} catch (Throwable $e) {
    $pdo->rollBack();
    exit("Error: {$e->getMessage()}\n");
}
