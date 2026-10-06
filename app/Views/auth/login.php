<?php use App\Core\Csrf; ?>
<section class="login-card">
    <div class="eyebrow">DYNAMIC ROUTING SYSTEM</div>
    <h1>Sign in</h1>
    <p>Use your system account to access panels and workflows.</p>

    <?php if (!empty($error)): ?>
        <div class="alert error"><?= e($error) ?></div>
    <?php endif; ?>

    <form method="post" action="<?= e(url('/login')) ?>" class="form-grid">
        <?= Csrf::field() ?>
        <label>
            <span>Username</span>
            <input type="text" name="username" value="<?= e($username ?? '') ?>" autocomplete="username" required autofocus>
        </label>
        <label>
            <span>Password</span>
            <input type="password" name="password" autocomplete="current-password" required>
        </label>
        <button class="btn primary" type="submit">Login</button>
    </form>
</section>
