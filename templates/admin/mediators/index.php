<?php
/**
 * @var App\Core\View $this
 * @var list<array<string, mixed>> $mediators
 * @var array{q: string, visibility: string, organization: int} $filters
 * @var list<array{id: int, name: string}> $organizations
 */
?>
<div class="page-actions">
    <h1><?= $this->e($this->t('admin.mediators.title')) ?></h1>
    <a class="button" href="<?= $this->e($this->route('admin.mediators.create')) ?>"><?= $this->e($this->t('admin.mediators.create')) ?></a>
</div>
<p class="field__hint"><?= $this->e($this->t('admin.mediators.privacy_hint')) ?></p>

<form class="filters" method="get" action="<?= $this->e($this->route('admin.mediators.index')) ?>">
    <div class="field">
        <label for="f-q"><?= $this->e($this->t('admin.common.search')) ?></label>
        <input id="f-q" name="q" type="search" value="<?= $this->e($filters['q']) ?>">
    </div>
    <div class="field">
        <label for="f-vis"><?= $this->e($this->t('manage.field.profile_visibility')) ?></label>
        <select id="f-vis" name="visibilita">
            <option value=""><?= $this->e($this->t('admin.common.all')) ?></option>
            <?php foreach (App\Domain\Management\MediatorEditor::VISIBILITY as $visibility): ?>
                <option value="<?= $visibility ?>"<?= $filters['visibility'] === $visibility ? ' selected' : '' ?>><?= $this->e($this->t('manage.visibility.' . $visibility)) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="field">
        <label for="f-org"><?= $this->e($this->t('manage.field.organization')) ?></label>
        <select id="f-org" name="organizzazione">
            <option value=""><?= $this->e($this->t('admin.common.all')) ?></option>
            <?php foreach ($organizations as $organization): ?>
                <option value="<?= $organization['id'] ?>"<?= $filters['organization'] === $organization['id'] ? ' selected' : '' ?>><?= $this->e($organization['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div><button class="button button--secondary" type="submit"><?= $this->e($this->t('admin.common.filter')) ?></button></div>
</form>

<div class="table-wrap">
    <table class="table">
        <caption><?= $this->e($this->t('admin.common.results', ['count' => count($mediators)])) ?></caption>
        <thead><tr>
            <th scope="col"><?= $this->e($this->t('manage.field.name')) ?></th>
            <th scope="col"><?= $this->e($this->t('manage.field.organization')) ?></th>
            <th scope="col"><?= $this->e($this->t('manage.field.languages')) ?></th>
            <th scope="col"><?= $this->e($this->t('manage.field.profile_visibility')) ?></th>
            <th scope="col"><?= $this->e($this->t('manage.field.consent')) ?></th>
            <th scope="col"><?= $this->e($this->t('manage.publication')) ?></th>
        </tr></thead>
        <tbody>
        <?php foreach ($mediators as $mediator): ?>
            <tr>
                <th scope="row"><a href="<?= $this->e($this->route('admin.mediators.show', ['id' => (int) $mediator['id']])) ?>"><?= $this->e($mediator['last_name'] . ' ' . $mediator['first_name']) ?></a></th>
                <td><?= $this->e($mediator['organization_name'] ?? '—') ?></td>
                <td class="ltr"><?= $this->e(strtoupper(str_replace(',', ', ', (string) $mediator['languages']))) ?></td>
                <td><span class="badge"><?= $this->e($this->t('manage.visibility.' . $mediator['profile_visibility'])) ?></span></td>
                <td><?= $this->e($mediator['public_consent_at'] !== null ? $this->t('admin.common.yes') : $this->t('admin.common.no')) ?></td>
                <td><?= $this->e($this->t('manage.publication.' . $mediator['publication_status'])) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
