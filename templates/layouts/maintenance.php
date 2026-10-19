<?php
/**
 * Layout minimo della pagina di manutenzione (D-041): nessuna query al database,
 * così funziona anche prima dell'installazione.
 *
 * @var App\Core\View $this
 * @var string $content
 * @var string|null $pageTitle
 */
?>
<!doctype html>
<html lang="it" dir="ltr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title><?= $this->e(($pageTitle ?? '') . ' — ' . $this->t('app.name')) ?></title>
    <link rel="stylesheet" href="<?= $this->e($this->asset('css/app.css')) ?>">
    <link rel="icon" href="<?= $this->e($this->asset('img/favicon.svg')) ?>" type="image/svg+xml">
</head>
<body class="layout-admin">
<a class="skip-link" href="#contenuto"><?= $this->e($this->t('layout.skip_to_content')) ?></a>
<header class="site-header">
    <div class="site-header__bar container">
        <span class="brand">
            <span class="brand__mark"><?= $this->icon('map-pinned') ?></span>
            <span class="brand__text"><span class="brand__name"><?= $this->e($this->t('app.name')) ?></span>
                <span class="brand__tagline"><?= $this->e($this->t('admin.maintenance.title')) ?></span></span>
        </span>
    </div>
</header>
<main id="contenuto" class="page" tabindex="-1">
    <?= $this->partial('flash') ?>
    <?= $content ?>
</main>
</body>
</html>
