<?php
/**
 * @var App\Core\View $this
 * @var array<string, string> $managers
 * @var array<string, mixed> $options
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

<h2 class="section"><?= $this->e($this->t('admin.settings.platform')) ?></h2>
<form class="form" method="post" action="<?= $this->e($this->route('admin.settings.options')) ?>">
    <?= $this->csrfField() ?>
    <div class="field">
        <label for="f-policy"><?= $this->e($this->t('admin.settings.default_policy')) ?></label>
        <p class="field__hint" id="f-policy-hint"><?= $this->e($this->t('admin.settings.default_policy_hint')) ?></p>
        <select id="f-policy" name="publication_default_policy" aria-describedby="f-policy-hint">
            <?php foreach (['direct', 'review'] as $policy): ?>
                <option value="<?= $policy ?>"<?= $options['publication.default_policy'] === $policy ? ' selected' : '' ?>><?= $this->e($this->t('manage.publication_policy.' . $policy)) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <?= $this->partial('field', ['name' => 'review_interval_days', 'label' => $this->t('admin.settings.review_interval'), 'type' => 'number', 'value' => (string) $options['quality.review_interval_days'], 'hint' => $this->t('admin.settings.review_interval_hint')]) ?>
    <?= $this->partial('field', ['name' => 'retention_months', 'label' => $this->t('admin.settings.retention'), 'type' => 'number', 'value' => (string) $options['requests.retention_months'], 'hint' => $this->t('admin.settings.retention_hint')]) ?>
    <div><button class="button" type="submit"><?= $this->e($this->t('manage.save')) ?></button></div>
</form>
