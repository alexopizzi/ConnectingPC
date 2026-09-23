<?php
/**
 * Dichiarazione di accessibilità — BOZZA (vault "70"): da compilare su AGID Form se il titolare è una PA (Q-14).
 *
 * @var App\Core\View $this
 * @var array<string, string> $managers
 */
?>
<div class="container section prose">
    <h1><?= $this->e($this->t('nav.accessibility')) ?></h1>
    <div class="alert alert--warning" role="note"><?= $this->e($this->t('page.accessibility.draft')) ?></div>
    <p><?= $this->e($this->t('page.accessibility.intro')) ?></p>
    <h2><?= $this->e($this->t('page.accessibility.features_title')) ?></h2>
    <ul>
        <?php foreach (['1', '2', '3', '4', '5', '6'] as $n): ?>
            <li><?= $this->e($this->t('page.accessibility.feature' . $n)) ?></li>
        <?php endforeach; ?>
    </ul>
    <h2><?= $this->e($this->t('page.accessibility.limits_title')) ?></h2>
    <p><?= $this->e($this->t('page.accessibility.limits_text')) ?></p>
    <h2><?= $this->e($this->t('page.accessibility.feedback_title')) ?></h2>
    <p><?= $this->e($this->t('page.accessibility.feedback_text')) ?></p>
    <?= $this->partial('managers-contacts', ['managers' => $managers]) ?>
</div>
