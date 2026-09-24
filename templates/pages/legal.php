<?php
/**
 * Note legali — MODELLO PROVVISORIO: titolare e gestore del sito sono da attribuire (D-025).
 *
 * @var App\Core\View $this
 */
$sections = ['owner', 'content', 'liability', 'links', 'license', 'data'];
?>
<div class="container section prose">
    <h1><?= $this->e($this->t('nav.legal')) ?></h1>
    <div class="alert alert--warning" role="note"><?= $this->e($this->t('page.legal.draft')) ?></div>
    <?php foreach ($sections as $section): ?>
        <h2><?= $this->e($this->t('page.legal.' . $section . '_title')) ?></h2>
        <p><?= $this->e($this->t('page.legal.' . $section . '_text')) ?></p>
    <?php endforeach; ?>
</div>
