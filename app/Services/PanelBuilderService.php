<?php
namespace App\Services;

use PDO;
use RuntimeException;

final class PanelBuilderService
{
    public function __construct(private PDO $pdo) {}

    public function create(array $input): int
    {
        $name = trim((string)($input['name'] ?? ''));
        $slug = $this->slug((string)($input['slug'] ?? $name));
        $description = trim((string)($input['description'] ?? ''));
        $icon = trim((string)($input['icon'] ?? '▦')) ?: '▦';
        $accessMode = (($input['access_mode'] ?? 'restricted') === 'all') ? 'all' : 'restricted';

        if ($name === '' || $slug === '') throw new RuntimeException('Panel name and slug are required.');

        if ($accessMode === 'restricted') {
            $userIds = array_values(array_filter(array_map('intval', $input['user_ids'] ?? [])));
            $roleIds = array_values(array_filter(array_map('intval', $input['role_ids'] ?? [])));
            if (!$userIds && !$roleIds) {
                throw new RuntimeException('Choose at least one user or role, or select Everyone.');
            }
        }

        $table = 'dyn_' . str_replace('-', '_', $slug);
        $this->identifier($table);
        $fields = $this->normalizeFields($input);
        if (!$fields) throw new RuntimeException('Add at least one panel field.');

        $exists = $this->pdo->prepare("SELECT 1 FROM panels WHERE slug=? LIMIT 1");
        $exists->execute([$slug]);
        if ($exists->fetchColumn()) throw new RuntimeException('Panel slug already exists.');

        $this->createPhysicalTable($table, $fields);

        try {
            $this->pdo->beginTransaction();

            $workflowSlug = null;
            if (!empty($input['enable_workflow'])) {
                $workflowSlug = $slug . '-workflow';
            }

            $stmt = $this->pdo->prepare(
                "INSERT INTO panels(name,slug,description,icon,source_table,view_permission,create_permission,enabled,sort_order,access_mode,workflow_slug)
                 VALUES (?,?,?,?,?,NULL,NULL,1,?,?,?)"
            );
            $stmt->execute([$name, $slug, $description ?: null, $icon, $table, (int)($input['sort_order'] ?? 50), $accessMode, $workflowSlug]);
            $panelId = (int)$this->pdo->lastInsertId();

            $fieldStmt = $this->pdo->prepare(
                "INSERT INTO panel_fields(panel_id,field_name,label,input_type,options_json,required,fillable,list_visible,enabled,sort_order)
                 VALUES (?,?,?,?,?,?,1,1,1,?)"
            );
            foreach ($fields as $i => $field) {
                $fieldStmt->execute([
                    $panelId,
                    $field['name'],
                    $field['label'],
                    $field['type'],
                    $field['options_json'],
                    $field['required'],
                    $i + 1,
                ]);
            }

            $this->saveAccess($panelId, $accessMode, $input);

            $route = $this->pdo->prepare(
                "INSERT INTO routes(method,path,target_type,target_value,permission,enabled) VALUES ('GET',?,'panel',?,NULL,1)"
            );
            $route->execute(['/module/' . $slug, $slug]);

            if ($workflowSlug) {
                $this->createWorkflow($panelId, $workflowSlug, $name, $input);
            }

            $this->pdo->commit();
            return $panelId;
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            $this->pdo->exec("DROP TABLE IF EXISTS `{$table}`");
            throw $e;
        }
    }

    private function normalizeFields(array $input): array
    {
        $labels = $input['field_label'] ?? [];
        $names = $input['field_name'] ?? [];
        $types = $input['field_type'] ?? [];
        $required = $input['field_required'] ?? [];
        $options = $input['field_options'] ?? [];
        $allowed = ['text','textarea','number','date','email','select'];
        $result = [];
        $seen = [];

        foreach ($labels as $i => $labelRaw) {
            $label = trim((string)$labelRaw);
            if ($label === '') continue;
            $name = $this->fieldName((string)($names[$i] ?? $label));
            if ($name === '' || isset($seen[$name])) throw new RuntimeException('Field names must be unique.');
            $seen[$name] = true;
            $type = in_array(($types[$i] ?? 'text'), $allowed, true) ? $types[$i] : 'text';
            $optionsJson = null;
            if ($type === 'select') {
                $pairs = [];
                foreach (preg_split('/\r\n|\r|\n|,/', (string)($options[$i] ?? '')) as $option) {
                    $option = trim($option);
                    if ($option !== '') $pairs[$option] = $option;
                }
                $optionsJson = $pairs ? json_encode($pairs, JSON_UNESCAPED_UNICODE) : null;
            }
            $result[] = [
                'label' => $label,
                'name' => $name,
                'type' => $type,
                'required' => isset($required[$i]) ? 1 : 0,
                'options_json' => $optionsJson,
            ];
        }
        return $result;
    }

    private function createPhysicalTable(string $table, array $fields): void
    {
        $columns = ["`id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY"];
        foreach ($fields as $field) {
            $name = $this->identifier($field['name']);
            $sqlType = match ($field['type']) {
                'textarea' => 'TEXT',
                'number' => 'DECIMAL(18,2)',
                'date' => 'DATE',
                'email' => 'VARCHAR(190)',
                'select' => 'VARCHAR(120)',
                default => 'VARCHAR(255)',
            };
            $null = $field['required'] ? 'NOT NULL' : 'NULL';
            $columns[] = "`{$name}` {$sqlType} {$null}";
        }
        $columns[] = "`created_by` BIGINT UNSIGNED NULL";
        $columns[] = "`created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP";
        $columns[] = "`updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP";

        $sql = "CREATE TABLE `{$table}` (" . implode(',', $columns) . ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        $this->pdo->exec($sql);
    }

    private function saveAccess(int $panelId, string $mode, array $input): void
    {
        if ($mode === 'all') return;

        $userStmt = $this->pdo->prepare(
            "INSERT IGNORE INTO panel_user_access(panel_id,user_id,can_view,can_create,can_edit,can_delete) VALUES (?,?,1,1,0,0)"
        );
        foreach (array_unique(array_map('intval', $input['user_ids'] ?? [])) as $userId) {
            if ($userId > 0) $userStmt->execute([$panelId, $userId]);
        }

        $roleStmt = $this->pdo->prepare(
            "INSERT IGNORE INTO panel_role_access(panel_id,role_id,can_view,can_create,can_edit,can_delete) VALUES (?,?,1,1,0,0)"
        );
        foreach (array_unique(array_map('intval', $input['role_ids'] ?? [])) as $roleId) {
            if ($roleId > 0) $roleStmt->execute([$panelId, $roleId]);
        }
    }

    private function createWorkflow(int $panelId, string $slug, string $panelName, array $input): void
    {
        $rawSteps = trim((string)($input['workflow_steps'] ?? "Submitted\nFor Review\nApproved"));
        $stepNames = array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $rawSteps))));
        if (count($stepNames) < 2) $stepNames = ['Submitted', 'Approved'];

        $stmt = $this->pdo->prepare("INSERT INTO workflows(name,slug,description,enabled) VALUES (?,?,?,1)");
        $stmt->execute([$panelName . ' Workflow', $slug, 'Auto-generated workflow for ' . $panelName]);
        $workflowId = (int)$this->pdo->lastInsertId();

        $stepStmt = $this->pdo->prepare("INSERT INTO workflow_steps(workflow_id,name,slug,sort_order) VALUES (?,?,?,?)");
        $ids = [];
        foreach ($stepNames as $i => $stepName) {
            $stepSlug = $this->slug($stepName) ?: 'step-' . ($i + 1);
            $stepStmt->execute([$workflowId, $stepName, $stepSlug, ($i + 1) * 10]);
            $ids[] = (int)$this->pdo->lastInsertId();
        }

        $transition = $this->pdo->prepare(
            "INSERT INTO workflow_transitions(workflow_id,from_step_id,to_step_id,action_label,permission,closes_instance) VALUES (?,?,?,?,?,?)"
        );
        for ($i = 0; $i < count($ids) - 1; $i++) {
            $toName = $stepNames[$i + 1];
            $isFinal = ($i + 1) === count($ids) - 1;
            $permission = $i === 0 ? 'workflow.start' : 'workflow.action';
            $transition->execute([$workflowId, $ids[$i], $ids[$i + 1], 'Move to ' . $toName, $permission, $isFinal ? 1 : 0]);
        }

        $this->pdo->prepare("INSERT INTO routes(method,path,target_type,target_value,permission,enabled) VALUES ('GET',?,'workflow',?,NULL,1)")
            ->execute(['/module/' . $this->slug($panelName) . '/workflow', $slug]);
    }

    private function slug(string $value): string
    {
        $value = strtolower(trim($value));
        $value = preg_replace('/[^a-z0-9]+/', '-', $value) ?? '';
        return trim($value, '-');
    }

    private function fieldName(string $value): string
    {
        $value = strtolower(trim($value));
        $value = preg_replace('/[^a-z0-9]+/', '_', $value) ?? '';
        $value = trim($value, '_');
        if ($value !== '' && ctype_digit($value[0])) $value = 'field_' . $value;
        return $value;
    }

    private function identifier(string $name): string
    {
        if (!preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $name)) throw new RuntimeException('Unsafe database identifier.');
        return $name;
    }
}
