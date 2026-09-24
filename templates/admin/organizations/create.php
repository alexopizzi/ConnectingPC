<?php
/**
 * @var App\Core\View $this
 * @var list<array{id: int, name: string}> $types
 * @var list<string> $locales
 */
?>
<p><a href="<?= $this->e($this->route('admin.organizations.index')) ?>"><?= $this->e($this->t('admin.organizations.back')) ?></a></p>
<h1><?= $this->e($this->t('admin.organizations.create')) ?></h1>
<?= $this->partial('form-errors') ?>
<form class="form" method="post" action="<?= $this->e($this->route('admin.organizations.store')) ?>">
    <?= $this->csrfField() ?>
    <?= $this->partial('field', ['name' => 'name', 'label' => $this->t('manage.field.name'), 'required' => true]) ?>
    <div class="field">
        <label for="f-type"><?= $this->e($this->t('manage.field.organization_type')) ?></label>
        <select id="f-type" name="organization_type_id" required>
            <?php foreach ($types as $type): ?>
                <option value="<?= $type['id'] ?>"<?= $this->old('organization_type_id') === (string) $type['id'] ? ' selected' : '' ?>><?= $this->e($type['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="field">
        <label for="f-source"><?= $this->e($this->t('manage.field.source_locale')) ?></label>
        <select id="f-source" name="source_locale">
            <?php foreach ($locales as $code): ?>
                <option value="<?= $code ?>"<?= $code === 'it' ? ' selected' : '' ?>><?= $this->e($this->languageName($code)) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <label class="checkbox"><input type="checkbox" name="is_community_based" value="1"> <?= $this->e($this->t('manage.field.is_community_based')) ?></label>
    <p class="field__hint"><?= $this->e($this->t('admin.organizations.create_hint')) ?></p>
    <div><button class="button" type="submit"><?= $this->e($this->t('admin.organizations.create')) ?></button></div>
</form>
