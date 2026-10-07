<?php
use App\Core\Csrf;
$old = $_SESSION['panel_builder_old'] ?? [];
unset($_SESSION['panel_builder_old']);
$selectedUsers = array_map('intval', $old['user_ids'] ?? []);
$selectedRoles = array_map('intval', $old['role_ids'] ?? []);
$accessModeValue = ($old['access_mode'] ?? 'restricted') === 'all' ? 'all' : 'restricted';
?>
<div class="page-heading">
    <div>
        <span class="eyebrow">DEVELOPER TOOLS</span>
        <h1>Create Dynamic Panel</h1>
        <p>Create a panel, database table, simple access rules, route, and optional workflow from one screen.</p>
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

    <div class="section-title" style="margin-top:28px"><h2>Easy Access</h2><span>Choose who can open this panel</span></div>

    <div class="card-grid" style="margin-bottom:18px">
        <label class="module-card" style="cursor:pointer">
            <span style="display:flex;align-items:center;gap:10px">
                <input type="radio" name="access_mode" value="all" <?= $accessModeValue === 'all' ? 'checked' : '' ?> style="width:auto">
                <strong>Everyone</strong>
            </span>
            <span>All logged-in users can open and use this panel.</span>
        </label>

        <label class="module-card" style="cursor:pointer">
            <span style="display:flex;align-items:center;gap:10px">
                <input type="radio" name="access_mode" value="restricted" <?= $accessModeValue === 'restricted' ? 'checked' : '' ?> style="width:auto">
                <strong>Specific Users / Roles</strong>
            </span>
            <span>Only the users or roles you check below can access it.</span>
        </label>
    </div>

    <div id="restrictedAccess" class="form-grid two-col" <?= $accessModeValue === 'all' ? 'style="display:none"' : '' ?>>
        <div class="section-block" style="margin:0;padding:18px">
            <div class="section-title"><h2 style="font-size:16px">Users</h2><span>Check allowed users</span></div>
            <div class="form-grid">
                <?php if ($users): ?>
                    <?php foreach ($users as $user): ?>
                        <label style="display:flex;grid-template-columns:auto 1fr;align-items:center;gap:10px;border:1px solid #e3e8f0;border-radius:8px;padding:10px 12px">
                            <input type="checkbox" name="user_ids[]" value="<?= (int)$user['id'] ?>" <?= in_array((int)$user['id'], $selectedUsers, true) ? 'checked' : '' ?> style="width:auto">
                            <span>
                                <?= e($user['display_name'] ?: $user['username']) ?>
                                <small style="display:block;color:#8290a3;font-weight:400"><?= e($user['email']) ?></small>
                            </span>
                        </label>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="muted">No users available yet.</p>
                <?php endif; ?>
            </div>
        </div>

        <div class="section-block" style="margin:0;padding:18px">
            <div class="section-title"><h2 style="font-size:16px">Roles</h2><span>Check allowed roles</span></div>
            <div class="form-grid">
                <?php foreach ($roles as $role): ?>
                    <label style="display:flex;grid-template-columns:auto 1fr;align-items:center;gap:10px;border:1px solid #e3e8f0;border-radius:8px;padding:10px 12px">
                        <input type="checkbox" name="role_ids[]" value="<?= (int)$role['id'] ?>" <?= in_array((int)$role['id'], $selectedRoles, true) ? 'checked' : '' ?> style="width:auto">
                        <span>
                            <?= e($role['name']) ?>
                            <small style="display:block;color:#8290a3;font-weight:400"><?= e($role['slug']) ?></small>
                        </span>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <p class="muted" style="margin-top:12px">Developer accounts always have full access automatically.</p>

    <div class="section-title" style="margin-top:28px"><h2>Workflow</h2><span>Optional automatic workflow</span></div>
    <div class="form-grid">
        <label style="display:flex;grid-template-columns:auto 1fr;align-items:center;gap:10px">
            <input type="checkbox" name="enable_workflow" id="enableWorkflow" value="1" <?= !empty($old['enable_workflow']) ? 'checked' : '' ?> style="width:auto">
            <span>Generate a workflow for this panel</span>
        </label>
        <label id="workflowStepsWrap" <?= empty($old['enable_workflow']) ? 'style="display:none"' : '' ?>>
            <span>Workflow Steps — one per line</span>
            <textarea name="workflow_steps"><?= e($old['workflow_steps'] ?? "Submitted\nFor Review\nApproved") ?></textarea>
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
    const accessRadios = document.querySelectorAll('input[name="access_mode"]');
    const restrictedAccess = document.getElementById('restrictedAccess');
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

    function updateAccessVisibility() {
        const selected = document.querySelector('input[name="access_mode"]:checked');
        restrictedAccess.style.display = selected && selected.value === 'all' ? 'none' : 'grid';
    }

    accessRadios.forEach(radio => radio.addEventListener('change', updateAccessVisibility));
    updateAccessVisibility();

    workflow.addEventListener('change', () => workflowWrap.style.display = workflow.checked ? 'grid' : 'none');
})();
</script>
