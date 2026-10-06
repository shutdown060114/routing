<?php use App\Core\Csrf; ?>
<div class="page-heading">
    <div><div class="eyebrow">DYNAMIC PANEL</div><h1><?= e($panel['name']) ?></h1><p><?= e($panel['description'] ?? '') ?></p></div>
</div>

<?php if ($canCreate): ?>
<section class="section-block panel-form-card">
    <div class="section-title"><h2>New Record</h2></div>
    <form method="post" action="<?= e(url('/panel/' . rawurlencode($panel['slug']))) ?>" class="form-grid two-col">
        <?= Csrf::field() ?>
        <?php foreach ($panel['fields'] as $field): ?>
            <?php if (!(int)$field['fillable']) continue; ?>
            <label class="<?= $field['input_type'] === 'textarea' ? 'full' : '' ?>">
                <span><?= e($field['label']) ?><?= (int)$field['required'] ? ' *' : '' ?></span>
                <?php if ($field['input_type'] === 'textarea'): ?>
                    <textarea name="<?= e($field['field_name']) ?>" <?= (int)$field['required'] ? 'required' : '' ?>></textarea>
                <?php elseif ($field['input_type'] === 'select'): ?>
                    <select name="<?= e($field['field_name']) ?>" <?= (int)$field['required'] ? 'required' : '' ?>>
                        <option value="">Select...</option>
                        <?php foreach ((json_decode($field['options_json'] ?: '[]', true) ?: []) as $value => $label): ?>
                            <option value="<?= e(is_int($value) ? $label : $value) ?>"><?= e($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                <?php else: ?>
                    <input type="<?= e(in_array($field['input_type'], ['text','number','date','email'], true) ? $field['input_type'] : 'text') ?>" name="<?= e($field['field_name']) ?>" <?= (int)$field['required'] ? 'required' : '' ?>>
                <?php endif; ?>
            </label>
        <?php endforeach; ?>
        <div class="full"><button class="btn primary" type="submit">Save Record</button></div>
    </form>
</section>
<?php endif; ?>

<section class="section-block">
    <div class="section-title"><h2>Records</h2><span><?= count($rows) ?> shown</span></div>
    <div class="table-wrap">
        <table>
            <thead><tr>
                <?php foreach ($panel['fields'] as $field): ?>
                    <?php if ((int)$field['list_visible']): ?><th><?= e($field['label']) ?></th><?php endif; ?>
                <?php endforeach; ?>
            </tr></thead>
            <tbody>
            <?php foreach ($rows as $row): ?>
                <tr>
                    <?php foreach ($panel['fields'] as $field): ?>
                        <?php if ((int)$field['list_visible']): ?><td><?= e($row[$field['field_name']] ?? '') ?></td><?php endif; ?>
                    <?php endforeach; ?>
                </tr>
            <?php endforeach; ?>
            <?php if (!$rows): ?><tr><td colspan="99" class="muted">No records yet.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
