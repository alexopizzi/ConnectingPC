<?php
/**
 * @var App\Core\View $this
 * @var array<string, mixed>|null $invitedUser
 */
?>
<div class="container section auth-page">
    <h1><?= $this->e($this->t('auth.invite.title')) ?></h1>
    <?php if ($invitedUser === null): ?>
        <div class="alert alert--warning" role="alert"><?= $this->e($this->t('auth.invite.invalid')) ?></div>
    <?php else: ?>
        <p><?= $this->e($this->t('auth.invite.intro', ['name' => (string) $invitedUser['display_name']])) ?></p>
        <p><?= $this->e($this->t('auth.field.email')) ?>: <strong class="ltr"><?= $this->e($invitedUser['email']) ?></strong></p>
        <?= $this->partial('form-errors', ['fields' => ['password']]) ?>
        <form class="form" method="post" action="<?= $this->e($this->route('auth.invitation.submit')) ?>" novalidate>
            <?= $this->csrfField() ?>
            <?= $this->render('auth/password-fields') ?>
            <div><button class="button" type="submit"><?= $this->e($this->t('auth.invite.submit')) ?></button></div>
        </form>
    <?php endif; ?>
</div>
