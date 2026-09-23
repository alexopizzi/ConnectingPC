<?php
/**
 * @var App\Core\View $this
 * @var string $pageTitle
 */
?>
<div class="container section">
    <h1><?= $this->e($pageTitle) ?></h1>
    <p><?= $this->e($this->t('section.soon')) ?></p>
    <p><a href="<?= $this->e($this->route('public.home')) ?>"><?= $this->e($this->t('common.back_home')) ?></a></p>
</div>
