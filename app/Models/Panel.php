<?php
namespace App\Models;

use PDO;

final class Panel
{
    public function __construct(private PDO $pdo) {}

    public function findBySlug(string $slug): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM panels WHERE slug=? AND enabled=1 LIMIT 1");
        $stmt->execute([$slug]);
        $panel = $stmt->fetch();
        if (!$panel) return null;

        $fields = $this->pdo->prepare("SELECT * FROM panel_fields WHERE panel_id=? AND enabled=1 ORDER BY sort_order,id");
        $fields->execute([$panel['id']]);
        $panel['fields'] = $fields->fetchAll();
        return $panel;
    }

    public function visiblePanels(): array
    {
        return $this->pdo->query("SELECT id,name,slug,icon,view_permission,access_mode,workflow_slug FROM panels WHERE enabled=1 ORDER BY sort_order,id")->fetchAll();
    }

    public function rows(array $panel, int $limit = 100): array
    {
        $table = $this->identifier($panel['source_table']);
        return $this->pdo->query("SELECT * FROM `{$table}` ORDER BY id DESC LIMIT " . max(1, min($limit, 500)))->fetchAll();
    }

    public function insert(array $panel, array $input): int
    {
        $columns = [];
        $values = [];

        foreach ($panel['fields'] as $field) {
            if (!(int)$field['fillable']) continue;
            $name = $this->identifier($field['field_name']);
            $value = trim((string)($input[$name] ?? ''));
            if ((int)$field['required'] && $value === '') {
                throw new \InvalidArgumentException($field['label'] . ' is required.');
            }
            $columns[] = $name;
            $values[] = $value === '' ? null : $value;
        }

        if (!$columns) throw new \RuntimeException('No fillable fields configured.');

        $table = $this->identifier($panel['source_table']);
        $quoted = array_map(fn($column) => "`{$column}`", $columns);
        $sql = "INSERT INTO `{$table}` (" . implode(',', $quoted) . ") VALUES (" . implode(',', array_fill(0, count($columns), '?')) . ")";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($values);
        return (int)$this->pdo->lastInsertId();
    }

    private function identifier(string $name): string
    {
        if (!preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $name)) {
            throw new \RuntimeException('Unsafe database identifier.');
        }
        return $name;
    }
}
