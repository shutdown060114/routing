<?php
use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Rbac;

$user = Auth::user();
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title><?= e($title ?? 'Routing System') ?></title>
    <link rel="stylesheet" href="<?= e(url('/assets/app.css')) ?>">
</head>
<body>
<?php if ($user): ?>
<div class="app-shell">
    <aside class="sidebar" id="sidebar">
        <a class="brand" href="<?= e(url('/')) ?>">ROUTING<span>SYS</span></a>
        <nav>
            <a href="<?= e(url('/')) ?>">Dashboard</a>
            <?php if (Rbac::can('workflow.view')): ?>
                <a href="<?= e(url('/workflows')) ?>">Workflows</a>
            <?php endif; ?>
            <?php if (Rbac::hasRole('developer')): ?>
                <div class="nav-label">Developer</div>
                <a href="<?= e(url('/panel/system-routes')) ?>">Routes</a>
                <a href="<?= e(url('/panel/users')) ?>">Users</a>
                <a href="<?= e(url('/panel/roles')) ?>">Roles</a>
            <?php endif; ?>
        </nav>
    </aside>
    <div class="app-main">
        <header class="topbar">
            <button class="menu-button" type="button" onclick="document.getElementById('sidebar').classList.toggle('open')">☰</button>
            <div>
                <strong><?= e($user['display_name'] ?: $user['username']) ?></strong>
                <small><?= e(implode(', ', Rbac::roles())) ?></small>
            </div>
            <form method="post" action="<?= e(url('/logout')) ?>">
                <?= Csrf::field() ?>
                <button class="link-button" type="submit">Logout</button>
            </form>
        </header>
        <main class="content">
            <?php if ($flash): ?>
                <div class="alert <?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
            <?php endif; ?>
            <?php require $contentView; ?>
        </main>
    </div>
</div>
<?php else: ?>
<main class="guest-shell">
    <?php require $contentView; ?>
</main>
<?php endif; ?>
</body>
</html>
