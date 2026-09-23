<?php
/** @var App\Core\View $this */
?>
<?= $this->partial('field', [
    'name' => 'password', 'type' => 'password', 'label' => $this->t('auth.field.new_password'),
    'hint' => $this->t('auth.password.rules', ['min' => App\Security\PasswordPolicy::MIN_LENGTH]),
    'autocomplete' => 'new-password', 'required' => true,
]) ?>
<?= $this->partial('field', [
    'name' => 'password_confirmation', 'type' => 'password', 'label' => $this->t('auth.field.password_confirmation'),
    'autocomplete' => 'new-password', 'required' => true,
]) ?>
