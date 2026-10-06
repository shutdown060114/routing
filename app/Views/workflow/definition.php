<?php use App\Core\Csrf; ?>
<div class="page-heading"><div><div class="eyebrow">WORKFLOW</div><h1><?= e($workflow['name']) ?></h1><p><?= e($workflow['description']) ?></p></div></div>
<section class="section-block">
    <div class="section-title"><h2>Steps</h2></div>
    <div class="workflow-steps">
        <?php foreach ($workflow['steps'] as $index => $step): ?>
            <div class="step-card"><span><?= e($index + 1) ?></span><strong><?= e($step['name']) ?></strong><small><?= e($step['slug']) ?></small></div>
        <?php endforeach; ?>
    </div>
</section>
<section class="section-block panel-form-card">
    <div class="section-title"><h2>Start Workflow</h2></div>
    <form method="post" action="<?= e(url('/workflow/' . rawurlencode($workflow['slug']) . '/start')) ?>" class="form-grid">
        <?= Csrf::field() ?>
        <label><span>Title *</span><input type="text" name="title" required></label>
        <button class="btn primary" type="submit">Start</button>
    </form>
</section>
