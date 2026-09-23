<?php
/**
 * "Sei un ente o un'associazione?" (brief §11, vault "52").
 *
 * @var App\Core\View $this
 * @var array<string, string> $managers
 */
$steps = ['contact', 'verify', 'profile', 'account', 'enable'];
?>
<div class="container section prose">
    <h1><?= $this->e($this->t('participate.title')) ?></h1>
    <p class="lead"><?= $this->e($this->t('page.participate.intro')) ?></p>

    <h2><?= $this->e($this->t('page.participate.who_title')) ?></h2>
    <p><?= $this->e($this->t('page.participate.who_text')) ?></p>

    <h2><?= $this->e($this->t('page.participate.benefits_title')) ?></h2>
    <ul>
        <?php foreach (['1', '2', '3', '4'] as $n): ?>
            <li><?= $this->e($this->t('page.participate.benefit' . $n)) ?></li>
        <?php endforeach; ?>
    </ul>

    <h2><?= $this->e($this->t('page.participate.data_title')) ?></h2>
    <p><?= $this->e($this->t('page.participate.data_text')) ?></p>

    <h2><?= $this->e($this->t('page.participate.how_title')) ?></h2>
    <ol class="steps">
        <?php foreach ($steps as $step): ?>
            <li><?= $this->e($this->t('page.participate.step.' . $step)) ?></li>
        <?php endforeach; ?>
    </ol>
    <p><?= $this->e($this->t('page.participate.publication')) ?></p>

    <?= $this->partial('managers-contacts', ['managers' => $managers]) ?>

    <p><a href="<?= $this->e($this->route('auth.login')) ?>"><?= $this->e($this->t('page.participate.already')) ?></a></p>
</div>
