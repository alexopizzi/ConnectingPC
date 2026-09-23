<?php
/**
 * Layout pubblico PROVVISORIO (accessibile, mobile-first), da allineare al design system di Claude Design.
 *
 * @var App\Core\View $this
 * @var string $content
 * @var string|null $pageTitle
 */
$siteName = $this->t('app.name');
$title = isset($pageTitle) && $pageTitle !== null && $pageTitle !== '' ? $pageTitle . ' — ' . $siteName : $siteName . ' — ' . $this->t('app.tagline');
?>
<!doctype html>
<html lang="<?= $this->e($this->locale()) ?>" dir="<?= $this->e($this->dir()) ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $this->e($title) ?></title>
    <meta name="description" content="<?= $this->e($this->t('app.tagline')) ?>">
    <?php foreach ($this->publicLocales() as $code => $locale): ?>
        <link rel="alternate" hreflang="<?= $this->e($code) ?>" href="<?= $this->e($this->switchLocaleUrl($code)) ?>">
    <?php endforeach; ?>
    <link rel="stylesheet" href="<?= $this->e($this->asset('css/app.css')) ?>">
</head>
<body class="layout-public">
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
