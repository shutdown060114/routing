<div class="page-heading">
    <div>
        <div class="eyebrow">OVERVIEW</div>
        <h1>Dashboard</h1>
        <p>Dynamic panels, workflows, routing, and access control in one place.</p>
    </div>
</div>

<div class="stats-grid">
    <article><span>Active Users</span><strong><?= e($stats['users']) ?></strong></article>
    <article><span>Active Workflows</span><strong><?= e($stats['active_workflows']) ?></strong></article>
    <article><span>Dynamic Routes</span><strong><?= e($stats['routes']) ?></strong></article>
</div>

<section class="section-block">
    <div class="section-title"><h2>Dynamic Panels</h2><span>Database-driven modules</span></div>
    <div class="card-grid">
        <?php foreach ($panels as $panel): ?>
            <a class="module-card" href="<?= e(url('/panel/' . rawurlencode($panel['slug']))) ?>">
                <div class="module-icon"><?= e($panel['icon'] ?: '▦') ?></div>
                <strong><?= e($panel['name']) ?></strong>
                <span><?= e($panel['slug']) ?></span>
            </a>
        <?php endforeach; ?>
        <?php if (!$panels): ?><p class="muted">No accessible panels configured.</p><?php endif; ?>
    </div>
</section>

<section class="section-block">
    <div class="section-title"><h2>Workflows</h2><a href="<?= e(url('/workflows')) ?>">View all →</a></div>
    <div class="card-grid">
        <?php foreach ($workflows as $workflow): ?>
            <a class="module-card" href="<?= e(url('/workflow/' . rawurlencode($workflow['slug']))) ?>">
                <div class="module-icon">⇄</div>
                <strong><?= e($workflow['name']) ?></strong>
                <span><?= e($workflow['description']) ?></span>
            </a>
        <?php endforeach; ?>
    </div>
</section>
