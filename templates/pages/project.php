<?php
/** @var App\Core\View $this */
?>
<div class="container section prose">
    <h1><?= $this->e($this->t('nav.project')) ?></h1>
    <p class="lead"><?= $this->e($this->t('page.project.intro')) ?></p>
    <h2><?= $this->e($this->t('page.project.goals_title')) ?></h2>
    <ul>
        <?php foreach (['1', '2', '3', '4', '5'] as $n): ?>
            <li><?= $this->e($this->t('page.project.goal' . $n)) ?></li>
        <?php endforeach; ?>
    </ul>
    <h2><?= $this->e($this->t('page.project.network_title')) ?></h2>
    <p><?= $this->e($this->t('page.project.network_text')) ?></p>
    <h2><?= $this->e($this->t('page.project.area_title')) ?></h2>
    <p><?= $this->e($this->t('page.project.area_text')) ?></p>
    <p class="muted"><?= $this->e($this->t('page.provisional')) ?></p>
</div>
