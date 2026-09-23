<?php
/**
 * @var App\Core\View $this
 * @var list<array<string, mixed>> $roles
 * @var list<array<string, mixed>> $organizations
 * @var array<string, array<string, mixed>> $locales
 */
?>
<h1><?= $this->e($this->t('admin.users.create_title')) ?></h1>
<p><?= $this->e($this->t('admin.users.create_intro')) ?></p>
<?= $this->partial('form-errors', ['fields' => ['email', 'display_name', 'preferred_locale', 'role', 'scope_type']]) ?>

<form class="form" method="post" action="<?= $this->e($this->route('admin.users.store')) ?>" novalidate>
    <?= $this->csrfField() ?>
    <?= $this->partial('field', ['name' => 'display_name', 'label' => $this->t('admin.users.name'), 'required' => true, 'autocomplete' => 'off']) ?>
    <?= $this->partial('field', ['name' => 'email', 'type' => 'email', 'label' => $this->t('auth.field.email'), 'required' => true, 'autocomplete' => 'off']) ?>
    <div class="field">
        <label for="f-preferred-locale"><?= $this->e($this->t('admin.users.locale')) ?></label>
        <select id="f-preferred-locale" name="preferred_locale">
            <?php foreach ($locales as $code => $locale): ?>
                <option value="<?= $this->e($code) ?>" lang="<?= $this->e($code) ?>"<?= $this->old('preferred_locale', 'it') === $code ? ' selected' : '' ?>><?= $this->e($locale['native_name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <?= $this->render('admin/users/assignment-fields', compact('roles', 'organizations', 'locales')) ?>
    <div><button class="button" type="submit"><?= $this->e($this->t('admin.users.create_submit')) ?></button></div>
</form>
