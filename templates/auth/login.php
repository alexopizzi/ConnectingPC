<?php
/**
 * @var App\Core\View $this
 * @var bool $expired
 */
?>
<div class="container section auth-page">
    <h1><?= $this->e($this->t('auth.login.title')) ?></h1>
    <p><?= $this->e($this->t('auth.login.intro')) ?></p>

    <?php if ($expired): ?>
        <div class="alert alert--info" role="status"><?= $this->e($this->t('auth.login.expired')) ?></div>
    <?php endif; ?>
    <?= $this->partial('form-errors', ['fields' => []]) ?>

    <form class="form" method="post" action="<?= $this->e($this->route('auth.login.submit')) ?>" novalidate>
        <?= $this->csrfField() ?>
        <?= $this->partial('field', ['name' => 'email', 'type' => 'email', 'label' => $this->t('auth.field.email'), 'autocomplete' => 'username', 'required' => true]) ?>
        <?= $this->partial('field', ['name' => 'password', 'type' => 'password', 'label' => $this->t('auth.field.password'), 'autocomplete' => 'current-password', 'required' => true]) ?>
        <div>
            <button class="button" type="submit"><?= $this->e($this->t('auth.login.submit')) ?></button>
        </div>
    </form>

    <p><a href="<?= $this->e($this->route('auth.forgot')) ?>"><?= $this->e($this->t('auth.login.forgot')) ?></a></p>
    <p class="muted"><?= $this->e($this->t('auth.login.no_account')) ?>
        <a href="<?= $this->e($this->route('public.section', ['section' => 'partecipa'])) ?>"><?= $this->e($this->t('nav.participate')) ?></a></p>
</div>
