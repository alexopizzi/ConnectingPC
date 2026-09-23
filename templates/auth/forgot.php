<?php
/** @var App\Core\View $this */
?>
<div class="container section auth-page">
    <h1><?= $this->e($this->t('auth.forgot.title')) ?></h1>
    <p><?= $this->e($this->t('auth.forgot.intro')) ?></p>
    <?= $this->partial('form-errors', ['fields' => []]) ?>

    <form class="form" method="post" action="<?= $this->e($this->route('auth.forgot.submit')) ?>" novalidate>
        <?= $this->csrfField() ?>
        <?= $this->partial('field', ['name' => 'email', 'type' => 'email', 'label' => $this->t('auth.field.email'), 'autocomplete' => 'email', 'required' => true]) ?>
        <div>
            <button class="button" type="submit"><?= $this->e($this->t('auth.forgot.submit')) ?></button>
        </div>
    </form>
    <p><a href="<?= $this->e($this->route('auth.login')) ?>"><?= $this->e($this->t('auth.back_to_login')) ?></a></p>
</div>
