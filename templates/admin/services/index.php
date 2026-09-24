<?php
/**
 * @var App\Core\View $this
 * @var list<array<string, mixed>> $services
 * @var int $total
 * @var int $page
 * @var int $pages
 * @var array{q: string, publication: string, category: int, organization: int, review_due: bool} $filters
 * @var list<array{id: int, name: string, parent: ?string}> $categories
 */
?>
<h1><?= $this->e($this->t('admin.services.title')) ?></h1>
<p class="field__hint"><?= $this->e($this->t('admin.services.create_hint')) ?></p>
<p><a href="<?= $this->e($this->route('admin.export.services')) ?>"><?= $this->e($this->t('admin.export.services')) ?></a></p>

<form class="filters" method="get" action="<?= $this->e($this->route('admin.services.index')) ?>">
    <div class="field">
        <label for="f-q"><?= $this->e($this->t('admin.common.search')) ?></label>
        <input id="f-q" name="q" type="search" value="<?= $this->e($filters['q']) ?>">
    </div>
    <div class="field">
        <label for="f-cat"><?= $this->e($this->t('manage.field.area')) ?></label>
        <select id="f-cat" name="categoria">
            <option value=""><?= $this->e($this->t('admin.common.all')) ?></option>
            <?php foreach ($categories as $category): ?>
                <option value="<?= $category['id'] ?>"<?= $filters['category'] === $category['id'] ? ' selected' : '' ?>><?= $this->e($category['name']) ?></option>
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
    <label class="checkbox"><input type="checkbox" name="revisione" value="1"<?= $filters['review_due'] ? ' checked' : '' ?>> <?= $this->e($this->t('admin.services.review_due')) ?></label>
    <?php if ($filters['organization'] > 0): ?><input type="hidden" name="organizzazione" value="<?= $filters['organization'] ?>"><?php endif; ?>
    <div><button class="button button--secondary" type="submit"><?= $this->e($this->t('admin.common.filter')) ?></button></div>
</form>

<div class="table-wrap">
    <table class="table">
        <caption><?= $this->e($this->t('admin.common.results', ['count' => $total])) ?></caption>
        <thead><tr>
            <th scope="col"><?= $this->e($this->t('manage.field.name')) ?></th>
            <th scope="col"><?= $this->e($this->t('admin.services.organization')) ?></th>
            <th scope="col"><?= $this->e($this->t('manage.field.primary_category')) ?></th>
            <th scope="col"><?= $this->e($this->t('manage.publication')) ?></th>
            <th scope="col"><?= $this->e($this->t('manage.texts.title')) ?></th>
            <th scope="col"><?= $this->e($this->t('manage.field.next_review_at')) ?></th>
        </tr></thead>
        <tbody>
        <?php foreach ($services as $service): ?>
            <tr>
                <th scope="row"><a href="<?= $this->e($this->route('admin.services.show', ['id' => (int) $service['id']])) ?>"><?= $this->e($service['name']) ?></a></th>
                <td><a href="<?= $this->e($this->route('admin.organizations.show', ['id' => (int) $service['organization_id']])) ?>"><?= $this->e($service['organization_name']) ?></a></td>
                <td><?= $this->e($service['category_name']) ?></td>
                <td><span class="badge"><?= $this->e($this->t('manage.publication.' . $service['publication_status'])) ?></span></td>
                <td class="ltr"><?php foreach (explode(',', (string) $service['translation_states']) as $state):
                    if ($state === '') {
                        continue;
                    }
                    [$code, $status] = explode(':', $state); ?>
                    <span class="badge<?= $status === 'approved' ? ' badge--success' : ($status === 'outdated' ? ' badge--warning' : '') ?>" title="<?= $this->e($this->t('manage.translation.' . $status)) ?>"><?= $this->e(strtoupper($code)) ?></span>
                <?php endforeach; ?></td>
                <td><?= $this->e((string) $service['next_review_at']) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?= $this->partial('pagination', ['page' => $page, 'pages' => $pages, 'route' => 'admin.services.index', 'query' => array_filter([
    'q' => $filters['q'], 'pubblicazione' => $filters['publication'], 'categoria' => $filters['category'] ?: '',
    'organizzazione' => $filters['organization'] ?: '', 'revisione' => $filters['review_due'] ? '1' : '',
])]) ?>
