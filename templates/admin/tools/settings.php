<?php
/**
 * @var App\Core\View $this
 * @var array<string, string> $managers
 */
?>
<h1><?= $this->e($this->t('admin.settings.title')) ?></h1>
<?= $this->partial('form-errors', ['fields' => ['name', 'email', 'phone', 'hours', 'address']]) ?>
<h2><?= $this->e($this->t('page.managers.title')) ?></h2>
<p class="field__hint"><?= $this->e($this->t('admin.settings.managers_hint')) ?></p>
<form class="form" method="post" action="<?= $this->e($this->route('admin.settings.update')) ?>">
    <?= $this->csrfField() ?>
    <?= $this->partial('field', ['name' => 'name', 'label' => $this->t('manage.field.name'), 'value' => (string) ($managers['name'] ?? '')]) ?>
    <?= $this->partial('field', ['name' => 'email', 'label' => $this->t('manage.contacts.kind.email'), 'type' => 'email', 'value' => (string) ($managers['email'] ?? '')]) ?>
    <?= $this->partial('field', ['name' => 'phone', 'label' => $this->t('manage.contacts.kind.phone'), 'type' => 'tel', 'value' => (string) ($managers['phone'] ?? '')]) ?>
    <?= $this->partial('field', ['name' => 'hours', 'label' => $this->t('page.managers.hours'), 'value' => (string) ($managers['hours'] ?? '')]) ?>
    <?= $this->partial('field', ['name' => 'address', 'label' => $this->t('manage.field.address_line'), 'value' => (string) ($managers['address'] ?? '')]) ?>
    <div><button class="button" type="submit"><?= $this->e($this->t('manage.save')) ?></button></div>
</form>
