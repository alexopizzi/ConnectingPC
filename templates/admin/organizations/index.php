<?php
/**
 * @var App\Core\View $this
 * @var list<array<string, mixed>> $organizations
 * @var int $total
 * @var int $page
 * @var int $pages
 * @var array{q: string, access: string, publication: string, type: int} $filters
 * @var list<array{id: int, name: string}> $types
 */
?>
<div class="page-actions">
    <h1><?= $this->e($this->t('admin.organizations.title')) ?></h1>
    <a class="button" href="<?= $this->e($this->route('admin.organizations.create')) ?>"><?= $this->e($this->t('admin.organizations.create')) ?></a>
</div>
<p><a href="<?= $this->e($this->route('admin.export.organizations')) ?>"><?= $this->e($this->t('admin.export.organizations')) ?></a></p>

<form class="filters" method="get" action="<?= $this->e($this->route('admin.organizations.index')) ?>">
    <div class="field">
        <label for="f-q"><?= $this->e($this->t('admin.common.search')) ?></label>
        <input id="f-q" name="q" type="search" value="<?= $this->e($filters['q']) ?>">
    </div>
    <div class="field">
        <label for="f-tipo"><?= $this->e($this->t('manage.field.organization_type')) ?></label>
        <select id="f-tipo" name="tipo">
            <option value=""><?= $this->e($this->t('admin.common.all')) ?></option>
            <?php foreach ($types as $type): ?>
                <option value="<?= $type['id'] ?>"<?= $filters['type'] === $type['id'] ? ' selected' : '' ?>><?= $this->e($type['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="field">
        <label for="f-accesso"><?= $this->e($this->t('manage.field.access_status')) ?></label>
        <select id="f-accesso" name="accesso">
            <option value=""><?= $this->e($this->t('admin.common.all')) ?></option>
            <?php foreach (App\Domain\Management\OrganizationEditor::STATUS_AXES['access_status'] as $status): ?>
                <option value="<?= $status ?>"<?= $filters['access'] === $status ? ' selected' : '' ?>><?= $this->e($this->t('manage.access_status.' . $status)) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="field">
        <label for="f-pub"><?= $this->e($this->t('manage.publication')) ?></label>
        <select id="f-pub" name="pubblicazione">
            <option value=""><?= $this->e($this->t('admin.common.all')) ?></option>
            <?php foreach (App\Domain\Management\EditorialGuard::PUBLICATION_STATUSES as $status): ?>
                <option value="<?= $status ?>"<?= $filters['publication'] === $status ? ' selected' : '' ?>><?= $this->e($this->t('manage.publication.' . $status)) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div><button class="button button--secondary" type="submit"><?= $this->e($this->t('admin.common.filter')) ?></button></div>
</form>

<div class="table-wrap">
    <table class="table">
        <caption><?= $this->e($this->t('admin.common.results', ['count' => $total])) ?></caption>
        <thead><tr>
            <th scope="col"><?= $this->e($this->t('manage.field.name')) ?></th>
            <th scope="col"><?= $this->e($this->t('manage.field.organization_type')) ?></th>
            <th scope="col"><?= $this->e($this->t('manage.publication')) ?></th>
            <th scope="col"><?= $this->e($this->t('manage.field.access_status')) ?></th>
            <th scope="col"><?= $this->e($this->t('admin.organizations.counts')) ?></th>
        </tr></thead>
        <tbody>
        <?php foreach ($organizations as $organization): ?>
            <tr>
                <th scope="row"><a href="<?= $this->e($this->route('admin.organizations.show', ['id' => (int) $organization['id']])) ?>"><?= $this->e($organization['name']) ?></a>
                    <?php if ($organization['is_community_based']): ?><span class="badge"><?= $this->e($this->t('admin.organizations.community_based_short')) ?></span><?php endif; ?></th>
                <td><?= $this->e($organization['type_name']) ?></td>
                <td><span class="badge"><?= $this->e($this->t('manage.publication.' . $organization['publication_status'])) ?></span>
                    <?php if ($organization['listing_status'] === 'hidden'): ?><span class="badge badge--warning"><?= $this->e($this->t('manage.listing_status.hidden')) ?></span><?php endif; ?></td>
                <td><?= $this->e($this->t('manage.access_status.' . $organization['access_status'])) ?></td>
                <td><?= $this->e($this->t('admin.organizations.counts_value', ['services' => (int) $organization['services_count'], 'sites' => (int) $organization['sites_count']])) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?= $this->partial('pagination', ['page' => $page, 'pages' => $pages, 'route' => 'admin.organizations.index', 'query' => array_filter(['q' => $filters['q'], 'accesso' => $filters['access'], 'pubblicazione' => $filters['publication'], 'tipo' => $filters['type'] ?: ''])]) ?>
