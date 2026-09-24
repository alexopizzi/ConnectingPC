<?php
/**
 * @var App\Core\View $this
 * @var array<string, int> $counts
 */
?>
<h1><?= $this->e($this->t('admin.taxonomy.title')) ?></h1>
<p class="field__hint"><?= $this->e($this->t('admin.taxonomy.intro')) ?></p>
<ul class="card-list">
    <?php foreach ($counts as $type => $count): ?>
        <li class="card">
            <h2 class="service-card__title"><a href="<?= $this->e($this->route('admin.taxonomy.list', ['type' => $type])) ?>"><?= $this->e($this->t('admin.taxonomy.' . $type)) ?></a></h2>
            <p class="muted"><?= $this->e($this->t('admin.common.results', ['count' => $count])) ?></p>
        </li>
    <?php endforeach; ?>
</ul>
<p><a href="<?= $this->e($this->route('admin.synonyms.index')) ?>"><?= $this->e($this->t('admin.synonyms.title')) ?></a></p>
