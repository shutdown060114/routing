<div class="page-heading"><div><div class="eyebrow">WORKFLOWS</div><h1>Workflow Center</h1><p>Start and track database-defined processes.</p></div></div>
<div class="card-grid">
<?php foreach ($workflows as $workflow): ?>
    <a class="module-card" href="<?= e(url('/workflow/' . rawurlencode($workflow['slug']))) ?>">
        <div class="module-icon">⇄</div><strong><?= e($workflow['name']) ?></strong><span><?= e($workflow['description']) ?></span>
    </a>
<?php endforeach; ?>
</div>
