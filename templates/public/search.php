<?php
/**
 * @var App\Core\View $this
 * @var string $pageTitle
 * @var string $query
 */
?>
<div class="container section">
    <h1><?= $this->e($pageTitle) ?></h1>
    <?= $this->partial('search-form', ['query' => $query]) ?>
    <?php if ($query !== ''): ?>
        <p><?= $this->e($this->t('search.you_searched', ['query' => $query])) ?></p>
    <?php endif; ?>
    <p class="muted"><?= $this->e($this->t('search.soon')) ?></p>
</div>
