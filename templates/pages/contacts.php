<?php
/**
 * @var App\Core\View $this
 * @var array<string, string> $managers
 */
$numbers = ['112' => 'page.contacts.n112', '1522' => 'page.contacts.n1522', '800290290' => 'page.contacts.n_trafficking'];
?>
<div class="container section prose">
    <h1><?= $this->e($this->t('nav.contacts')) ?></h1>
    <p class="lead"><?= $this->e($this->t('page.contacts.intro')) ?></p>
    <?= $this->partial('managers-contacts', ['managers' => $managers]) ?>

    <h2><?= $this->e($this->t('page.contacts.useful_title')) ?></h2>
    <ul class="contact-list">
        <?php foreach ($numbers as $number => $label): ?>
            <li><span aria-hidden="true">📞</span> <?= $this->e($this->t($label)) ?>: <a class="ltr" href="tel:<?= $number ?>"><?= $number ?></a></li>
        <?php endforeach; ?>
    </ul>
    <p class="muted"><?= $this->e($this->t('page.contacts.numbers_note')) ?></p>
</div>
