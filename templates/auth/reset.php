<?php
/**
 * @var App\Core\View $this
 * @var bool $valid
 */
?>
<div class="container section auth-page">
    <h1><?= $this->e($this->t('auth.reset.title')) ?></h1>
    <?php if (!$valid): ?>
        <div class="alert alert--warning" role="alert"><?= $this->e($this->t('auth.reset.invalid')) ?></div>
        <p><a class="button" href="<?= $this->e($this->route('auth.forgot')) ?>"><?= $this->e($this->t('auth.reset.request_new')) ?></a></p>
    <?php else: ?>
        <?= $this->partial('form-errors', ['fields' => ['password']]) ?>
        <form class="form" method="post" action="<?= $this->e($this->route('auth.reset.submit')) ?>" novalidate>
            <?= $this->csrfField() ?>
            <?= $this->render('auth/password-fields') ?>
            <div><button class="button" type="submit"><?= $this->e($this->t('auth.reset.submit')) ?></button></div>
        </form>
    <?php endif; ?>
</div>
