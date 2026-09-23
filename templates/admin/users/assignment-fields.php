<?php
/**
 * Campi per assegnare un ruolo con il suo ambito (vault "51").
 *
 * @var App\Core\View $this
 * @var list<array<string, mixed>> $roles
 * @var list<array<string, mixed>> $organizations
 * @var array<string, array<string, mixed>> $locales
 */
?>
<div class="field">
    <label for="f-role"><?= $this->e($this->t('admin.users.role')) ?></label>
    <select id="f-role" name="role" required>
        <?php foreach ($roles as $role): ?>
            <option value="<?= $this->e($role['code']) ?>"<?= $this->old('role') === $role['code'] ? ' selected' : '' ?>>
                <?= $this->e($role['name']) ?> (<?= $this->e(str_replace(',', ', ', (string) $role['allowed_scopes'])) ?>)
            </option>
        <?php endforeach; ?>
    </select>
</div>
<fieldset class="field">
    <legend><?= $this->e($this->t('admin.users.scope')) ?></legend>
    <p class="field__hint"><?= $this->e($this->t('admin.users.scope_hint')) ?></p>
    <?php foreach (['global', 'organization', 'locale'] as $scope): ?>
        <label class="checkbox">
            <input type="radio" name="scope_type" value="<?= $scope ?>"<?= $this->old('scope_type', 'organization') === $scope ? ' checked' : '' ?>>
            <?= $this->e($this->t('admin.users.scope.' . $scope)) ?>
        </label>
    <?php endforeach; ?>
</fieldset>
<div class="field">
    <label for="f-organization"><?= $this->e($this->t('admin.users.scope.organization')) ?></label>
    <select id="f-organization" name="organization_id">
        <option value=""><?= $this->e($this->t('admin.common.none')) ?></option>
        <?php foreach ($organizations as $organization): ?>
            <option value="<?= (int) $organization['id'] ?>"<?= $this->old('organization_id') === (string) $organization['id'] ? ' selected' : '' ?>>
                <?= $this->e($organization['name']) ?> — <?= $this->e($this->t('admin.org.access.' . $organization['access_status'])) ?>
            </option>
        <?php endforeach; ?>
    </select>
</div>
<div class="field">
    <label for="f-scope-locale"><?= $this->e($this->t('admin.users.scope.locale')) ?></label>
    <select id="f-scope-locale" name="scope_locale">
        <?php foreach ($locales as $code => $locale): ?>
            <option value="<?= $this->e($code) ?>" lang="<?= $this->e($code) ?>"><?= $this->e($locale['native_name']) ?></option>
        <?php endforeach; ?>
    </select>
</div>
