<?php use App\Core\Csrf; ?>
<div class="page-heading">
    <div><div class="eyebrow">WORKFLOW INSTANCE #<?= e($instance['id']) ?></div><h1><?= e($instance['title']) ?></h1><p><?= e($instance['workflow_name']) ?> · Current step: <strong><?= e($instance['step_name']) ?></strong></p></div>
    <span class="status-badge <?= e($instance['status']) ?>"><?= e($instance['status']) ?></span>
</div>

<?php if ($instance['status'] === 'active' && $instance['transitions']): ?>
<section class="section-block panel-form-card">
    <div class="section-title"><h2>Available Actions</h2></div>
    <?php foreach ($instance['transitions'] as $transition): ?>
        <form method="post" action="<?= e(url('/workflow-instance/' . $instance['id'] . '/transition')) ?>" class="transition-form">
            <?= Csrf::field() ?>
            <input type="hidden" name="transition_id" value="<?= e($transition['id']) ?>">
            <label><span>Comment</span><input type="text" name="comment" placeholder="Optional comment"></label>
            <button class="btn primary" type="submit"><?= e($transition['action_label']) ?></button>
        </form>
    <?php endforeach; ?>
</section>
<?php endif; ?>

<section class="section-block">
    <div class="section-title"><h2>History</h2></div>
    <div class="timeline">
    <?php foreach ($instance['history'] as $item): ?>
        <article>
            <strong><?= e($item['action']) ?></strong>
            <span><?= e($item['from_step'] ?: 'Start') ?> → <?= e($item['to_step']) ?></span>
            <small><?= e($item['display_name'] ?: $item['username'] ?: 'System') ?> · <?= e($item['created_at']) ?></small>
            <?php if ($item['comment']): ?><p><?= e($item['comment']) ?></p><?php endif; ?>
        </article>
    <?php endforeach; ?>
    </div>
</section>
