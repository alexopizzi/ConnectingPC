<?php
/**
 * Layout pubblico (design system v0.12.0, D-038): tema chiaro, mobile-first, accessibile.
 *
 * @var App\Core\View $this
 * @var string $content
 * @var string|null $pageTitle
 * @var string|null $bodyClass
 */
$siteName = $this->t('app.name');
$title = isset($pageTitle) && $pageTitle !== null && $pageTitle !== '' ? $pageTitle . ' — ' . $siteName : $siteName . ' — ' . $this->t('app.tagline');
$classes = 'layout-public' . (isset($bodyClass) && $bodyClass !== '' ? ' ' . $bodyClass : '');
?>
<!doctype html>
<html lang="<?= $this->e($this->locale()) ?>" dir="<?= $this->e($this->dir()) ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $this->e($title) ?></title>
    <meta name="description" content="<?= $this->e($this->t('app.tagline')) ?>">
    <meta name="theme-color" content="#ffffff">
    <?php foreach ($this->publicLocales() as $code => $locale): ?>
        <link rel="alternate" hreflang="<?= $this->e($code) ?>" href="<?= $this->e($this->switchLocaleUrl($code)) ?>">
    <?php endforeach; ?>
    <link rel="preload" href="<?= $this->e($this->asset('fonts/atkinson-hyperlegible-next-latin-wght-normal.woff2')) ?>" as="font" type="font/woff2" crossorigin>
    <link rel="stylesheet" href="<?= $this->e($this->asset('css/app.css')) ?>">
    <link rel="icon" href="<?= $this->e($this->asset('img/favicon.svg')) ?>" type="image/svg+xml">
    <script src="<?= $this->e($this->asset('js/app.js')) ?>"></script>
</head>
<body class="<?= $this->e($classes) ?>">
<a class="skip-link" href="#contenuto"><?= $this->e($this->t('layout.skip_to_content')) ?></a>

<?= $this->partial('header') ?>

<main id="contenuto" class="page" tabindex="-1">
    <?= $this->partial('flash') ?>
    <?= $content ?>
</main>

<?= $this->partial('footer') ?>
<script src="<?= $this->e($this->asset('js/suggest.js')) ?>" defer></script>
</body>
</html>
