<?php
namespace App\Core;

use PDO;

final class DynamicPanel
{
    public static function definition(PDO $pdo, string $slug): ?array
    {
        $stmt = $pdo->prepare("SELECT * FROM panels WHERE slug=? AND enabled=1 LIMIT 1");
        $stmt->execute([$slug]);
        $panel = $stmt->fetch();
        if (!$panel) return null;
        $fields = $pdo->prepare("SELECT * FROM panel_fields WHERE panel_id=? AND enabled=1 ORDER BY sort_order,id");
        $fields->execute([$panel['id']]);
        $panel['fields'] = $fields->fetchAll();
        return $panel;
    }

    public static function rows(PDO $pdo, array $panel, int $limit = 100): array
    {
        $table = self::identifier($panel['source_table']);
        return $pdo->query("SELECT * FROM `{$table}` ORDER BY id DESC LIMIT " . max(1, min($limit, 500)))->fetchAll();
    }

    public static function create(PDO $pdo, array $panel, array $input): int
    {
        $columns = [];
        $values = [];
        foreach ($panel['fields'] as $field) {
            if (!(int)$field['fillable']) continue;
            $name = self::identifier($field['field_name']);
            $value = trim((string)($input[$name] ?? ''));
            if ((int)$field['required'] && $value === '') throw new \InvalidArgumentException($field['label'] . ' is required.');
            $columns[] = $name;
            $values[] = $value === '' ? null : $value;
        }
        if (!$columns) throw new \RuntimeException('No fillable fields configured.');
        $table = self::identifier($panel['source_table']);
        $quoted = array_map(fn($c) => "`{$c}`", $columns);
        $sql = "INSERT INTO `{$table}` (" . implode(',', $quoted) . ") VALUES (" . implode(',', array_fill(0, count($columns), '?')) . ")";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($values);
        return (int)$pdo->lastInsertId();
    }

    private static function identifier(string $name): string
    {
        if (!preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $name)) throw new \RuntimeException('Unsafe database identifier.');
        return $name;
    }
}
