<?php
/**
 * Informativa privacy — MODELLO PROVVISORIO: il titolare del trattamento è da attribuire (D-025, vault "71").
 *
 * @var App\Core\View $this
 */
$sections = ['controller', 'data', 'cookies', 'location', 'organizations', 'rights'];
?>
<div class="container section prose">
    <h1><?= $this->e($this->t('nav.privacy')) ?></h1>
    <div class="alert alert--warning" role="note"><?= $this->e($this->t('page.privacy.draft')) ?></div>
    <?php foreach ($sections as $section): ?>
        <h2><?= $this->e($this->t('page.privacy.' . $section . '_title')) ?></h2>
        <p><?= $this->e($this->t('page.privacy.' . $section . '_text')) ?></p>
    <?php endforeach; ?>
</div>
