<?php
use App\Core\Csrf;
$old = $_SESSION['panel_builder_old'] ?? [];
unset($_SESSION['panel_builder_old']);
?>
<div class="page-heading">
    <div>
        <span class="eyebrow">DEVELOPER TOOLS</span>
        <h1>Create Dynamic Panel</h1>
        <p>Create a panel, database table, access rules, route, and optional workflow from one screen.</p>
    </div>
</div>

<form method="post" action="<?= e(url('/developer/panels')) ?>" class="section-block" id="panelBuilderForm">
    <?= Csrf::field() ?>

    <div class="section-title"><h2>Panel Setup</h2><span>Basic configuration</span></div>
    <div class="form-grid two-col">
        <label><span>Panel Name</span><input required name="name" id="panelName" value="<?= e($old['name'] ?? '') ?>" placeholder="Example: Travel Orders"></label>
        <label><span>Slug</span><input required name="slug" id="panelSlug" value="<?= e($old['slug'] ?? '') ?>" placeholder="travel-orders"></label>
        <label><span>Icon</span><input name="icon" value="<?= e($old['icon'] ?? '▦') ?>" maxlength="30"></label>
        <label><span>Sort Order</span><input type="number" name="sort_order" value="<?= e($old['sort_order'] ?? '50') ?>"></label>
        <label class="full"><span>Description</span><textarea name="description" placeholder="What is this panel for?"><?= e($old['description'] ?? '') ?></textarea></label>
    </div>

    <div class="section-title" style="margin-top:28px"><h2>Panel Fields</h2><button type="button" class="btn" id="addFieldBtn">+ Add Field</button></div>
    <div id="fieldRows" class="form-grid"></div>

    <div class="section-title" style="margin-top:28px"><h2>Access</h2><span>Specific users and roles</span></div>
    <div class="form-grid two-col">
        <label>
            <span>Access Mode</span>
            <select name="access_mode" id="accessMode">
                <option value="restricted">Restricted to selected users / roles</option>
                <option value="all">All authenticated users</option>
            </select>
        </label>
        <div></div>
        <label>
            <span>Allowed Users</span>
            <select name="user_ids[]" id="userAccess" multiple size="8">
                <?php foreach ($users as $user): ?>
                    <option value="<?= (int)$user['id'] ?>"><?= e(($user['display_name'] ?: $user['username']) . ' — ' . $user['email']) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>
            <span>Allowed Roles</span>
            <select name="role_ids[]" id="roleAccess" multiple size="8">
                <?php foreach ($roles as $role): ?>
                    <option value="<?= (int)$role['id'] ?>"><?= e($role['name'] . ' (' . $role['slug'] . ')') ?></option>
                <?php endforeach; ?>
            </select>
        </label>
    </div>

    <div class="section-title" style="margin-top:28px"><h2>Workflow</h2><span>Optional automatic workflow</span></div>
    <div class="form-grid">
        <label style="display:flex;grid-template-columns:auto 1fr;align-items:center;gap:10px">
            <input type="checkbox" name="enable_workflow" id="enableWorkflow" value="1" style="width:auto">
            <span>Generate a workflow for this panel</span>
        </label>
        <label id="workflowStepsWrap" style="display:none">
            <span>Workflow Steps — one per line</span>
            <textarea name="workflow_steps">Submitted
For Review
Approved</textarea>
        </label>
    </div>

    <div style="margin-top:28px;display:flex;gap:10px;flex-wrap:wrap">
        <button type="submit" class="btn primary">Generate Panel</button>
        <a class="btn" href="<?= e(url('/')) ?>">Cancel</a>
    </div>
</form>

<template id="fieldTemplate">
    <div class="section-block builder-field-row" style="padding:16px;margin-bottom:0">
        <div class="form-grid two-col">
            <label><span>Label</span><input required class="field-label" placeholder="Requester Name"></label>
            <label><span>Database Field</span><input required class="field-name" placeholder="requester_name"></label>
            <label>
                <span>Type</span>
                <select class="field-type">
                    <option value="text">Text</option>
                    <option value="textarea">Textarea</option>
                    <option value="number">Number</option>
                    <option value="date">Date</option>
                    <option value="email">Email</option>
                    <option value="select">Select</option>
                </select>
            </label>
            <label class="field-options-wrap" style="display:none"><span>Select Options</span><input class="field-options" placeholder="New, Processing, Completed"></label>
            <label style="display:flex;grid-template-columns:auto 1fr;align-items:center;gap:10px"><input type="checkbox" class="field-required" value="1" style="width:auto"><span>Required</span></label>
            <div><button type="button" class="btn remove-field">Remove</button></div>
        </div>
    </div>
</template>

<script>
(() => {
    const rows = document.getElementById('fieldRows');
    const template = document.getElementById('fieldTemplate');
    const addBtn = document.getElementById('addFieldBtn');
    const name = document.getElementById('panelName');
    const slug = document.getElementById('panelSlug');
    const accessMode = document.getElementById('accessMode');
    const userAccess = document.getElementById('userAccess');
    const roleAccess = document.getElementById('roleAccess');
    const workflow = document.getElementById('enableWorkflow');
    const workflowWrap = document.getElementById('workflowStepsWrap');
    let fieldKey = 0;

    const slugify = value => value.toLowerCase().trim().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '');
    const fieldify = value => {
        let v = value.toLowerCase().trim().replace(/[^a-z0-9]+/g, '_').replace(/^_+|_+$/g, '');
        if (/^\d/.test(v)) v = 'field_' + v;
        return v;
    };

    let slugTouched = slug.value !== '';
    slug.addEventListener('input', () => slugTouched = true);
    name.addEventListener('input', () => { if (!slugTouched) slug.value = slugify(name.value); });

    function addField(label = '', fieldName = '', type = 'text') {
        const key = fieldKey++;
        const node = template.content.firstElementChild.cloneNode(true);
        const labelInput = node.querySelector('.field-label');
        const nameInput = node.querySelector('.field-name');
        const typeInput = node.querySelector('.field-type');
        const optionsInput = node.querySelector('.field-options');
        const requiredInput = node.querySelector('.field-required');
        const optionsWrap = node.querySelector('.field-options-wrap');
        let nameTouched = fieldName !== '';

        labelInput.name = `field_label[${key}]`;
        nameInput.name = `field_name[${key}]`;
        typeInput.name = `field_type[${key}]`;
        optionsInput.name = `field_options[${key}]`;
        requiredInput.name = `field_required[${key}]`;
        labelInput.value = label;
        nameInput.value = fieldName;
        typeInput.value = type;

        labelInput.addEventListener('input', () => { if (!nameTouched) nameInput.value = fieldify(labelInput.value); });
        nameInput.addEventListener('input', () => nameTouched = true);
        typeInput.addEventListener('change', () => optionsWrap.style.display = typeInput.value === 'select' ? 'grid' : 'none');
        node.querySelector('.remove-field').addEventListener('click', () => node.remove());
        rows.appendChild(node);
    }

    addBtn.addEventListener('click', () => addField());
    addField('Title', 'title', 'text');
    addField('Description', 'description', 'textarea');

    accessMode.addEventListener('change', () => {
        const disabled = accessMode.value === 'all';
        userAccess.disabled = disabled;
        roleAccess.disabled = disabled;
    });

    workflow.addEventListener('change', () => workflowWrap.style.display = workflow.checked ? 'grid' : 'none');
})();
</script>
