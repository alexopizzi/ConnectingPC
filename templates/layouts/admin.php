<?php
/**
 * Layout dell'area amministrativa (provvisorio, stesso foglio di stile del sito pubblico).
 *
 * @var App\Core\View $this
 * @var string $content
 * @var string|null $pageTitle
 * @var array<string, mixed> $currentUser
 */
$gate = $this->shared('gate');
$current = $this->shared('route_name');
$items = [
    ['admin.dashboard', 'admin.nav.dashboard', 'admin.access'],
    ['admin.users.index', 'admin.nav.users', 'users.manage'],
    ['admin.roles.index', 'admin.nav.roles', 'users.manage'],
    ['admin.audit.index', 'admin.nav.audit', 'audit.view'],
];
$title = (isset($pageTitle) && $pageTitle !== '' ? $pageTitle . ' — ' : '') . $this->t('admin.title');
?>
<!doctype html>
<html lang="<?= $this->e($this->locale()) ?>" dir="<?= $this->e($this->dir()) ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title><?= $this->e($title) ?></title>
    <link rel="stylesheet" href="<?= $this->e($this->asset('css/app.css')) ?>">
</head>
<body class="layout-admin">
<a class="skip-link" href="#contenuto"><?= $this->e($this->t('layout.skip_to_content')) ?></a>

<header class="site-header">
    <div class="site-header__bar container">
        <a class="brand" href="<?= $this->e($this->route('admin.dashboard')) ?>">
            <span class="brand__name"><?= $this->e($this->t('app.name')) ?></span>
            <span class="brand__tagline"><?= $this->e($this->t('admin.title')) ?></span>
        </a>
        <div class="user-menu">
            <span><?= $this->e($currentUser['display_name'] ?? '') ?></span>
            <form class="inline-form" method="post" action="<?= $this->e($this->route('auth.logout', ['locale' => 'it'])) ?>">
                <?= $this->csrfField() ?>
                <button class="button button--secondary" type="submit"><?= $this->e($this->t('auth.logout.submit')) ?></button>
            </form>
        </div>
    </div>
</header>

<div class="container admin-shell section">
    <nav class="admin-nav" aria-label="<?= $this->e($this->t('admin.nav.label')) ?>">
        <ul>
            <?php foreach ($items as [$route, $label, $permission]):
                if ($gate !== null && !$gate->allows($currentUser, $permission)) {
                    continue;
                } ?>
                <li><a href="<?= $this->e($this->route($route)) ?>"<?= $current === $route ? ' aria-current="page"' : '' ?>><?= $this->e($this->t($label)) ?></a></li>
            <?php endforeach; ?>
            <li><a href="<?= $this->e($this->route('public.home')) ?>"><?= $this->e($this->t('admin.nav.public_site')) ?></a></li>
        </ul>
    </nav>
    <main id="contenuto" tabindex="-1">
        <?= $this->partial('flash') ?>
        <?= $content ?>
    </main>
</div>

<footer class="site-footer">
    <div class="container">
        <p class="site-footer__version"><?= $this->e($this->t('footer.version', ['version' => App\Core\App::version()])) ?></p>
    </div>
</footer>
</body>
</html>
