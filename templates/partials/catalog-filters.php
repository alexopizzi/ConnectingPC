<?php
/**
 * Filtri del catalogo (elenco, bisogno, ricerca, mappa).
 *
 * @var App\Core\View $this
 * @var array<string, mixed> $filters
 * @var string $action
 * @var string $resetUrl
 * @var bool $showCategory
 * @var list<array<string, mixed>> $categories
 * @var list<array<string, mixed>> $municipalities
 * @var list<string> $languages
 * @var list<array<string, mixed>> $orgTypes
 */
$orgTypes ??= [];
$activeCount = count(array_filter([
    $filters['category'] ?? null, $filters['territory'] ?? null, $filters['language'] ?? null, $filters['mediation'] ?: null, $filters['free'] ?: null,
    $filters['accessible'] ?? null ?: null, $filters['org_type'] ?? null, $filters['access_mode'] ?? null,
]));
?>
<details class="filters-panel"<?= $activeCount > 0 ? ' open' : '' ?>>
    <summary><?= $this->e($this->t('catalog.filter.title')) ?><?= $activeCount > 0 ? ' (' . $activeCount . ')' : '' ?></summary>
    <form class="filters" method="get" action="<?= $this->e($action) ?>">
        <?php if (($filters['q'] ?? '') !== ''): ?>
            <input type="hidden" name="q" value="<?= $this->e($filters['q']) ?>">
        <?php endif; ?>
        <?php if (!empty($filters['need']) && $showCategory): ?>
            <input type="hidden" name="bisogno" value="<?= $this->e($filters['need']) ?>">
        <?php endif; ?>
        <?php if ($showCategory): ?>
            <div class="field">
                <label for="f-categoria"><?= $this->e($this->t('catalog.filter.category')) ?></label>
                <select id="f-categoria" name="categoria">
                    <option value=""><?= $this->e($this->t('catalog.filter.any')) ?></option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?= $this->e($category['code']) ?>"<?= ($filters['category'] ?? null) === $category['code'] ? ' selected' : '' ?>><?= $this->e($category['name']['text']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        <?php endif; ?>
        <?= $this->partial('territory-select', ['id' => 'f-comune', 'selected' => $filters['territory'] ?? null, 'municipalities' => $municipalities]) ?>
        <div class="field">
            <label for="f-lingua"><?= $this->e($this->t('catalog.filter.language')) ?></label>
            <select id="f-lingua" name="lingua">
                <option value=""><?= $this->e($this->t('catalog.filter.any')) ?></option>
                <?php foreach ($languages as $code): ?>
                    <option value="<?= $this->e($code) ?>"<?= ($filters['language'] ?? null) === $code ? ' selected' : '' ?>><?= $this->e($this->languageName($code)) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php if ($orgTypes !== []): ?>
            <div class="field">
                <label for="f-tipo-ente"><?= $this->e($this->t('catalog.filter.org_type')) ?></label>
                <select id="f-tipo-ente" name="tipo_ente">
                    <option value=""><?= $this->e($this->t('catalog.filter.any')) ?></option>
                    <?php foreach ($orgTypes as $type): ?>
                        <option value="<?= $this->e($type['code']) ?>"<?= ($filters['org_type'] ?? null) === $type['code'] ? ' selected' : '' ?>><?= $this->e($type['name']['text']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        <?php endif; ?>
        <div class="field">
            <label for="f-modalita"><?= $this->e($this->t('catalog.filter.access_mode')) ?></label>
            <select id="f-modalita" name="modalita">
                <option value=""><?= $this->e($this->t('catalog.filter.any')) ?></option>
                <?php foreach (App\Domain\Catalog\CatalogFilters::ACCESS_MODES as $mode): ?>
                    <option value="<?= $mode ?>"<?= ($filters['access_mode'] ?? null) === $mode ? ' selected' : '' ?>><?= $this->e($this->t('manage.access_mode.' . $mode)) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <label class="checkbox"><input type="checkbox" name="accessibile" value="1"<?= !empty($filters['accessible']) ? ' checked' : '' ?>> <?= $this->e($this->t('catalog.filter.accessible')) ?></label>
        <label class="checkbox"><input type="checkbox" name="mediazione" value="1"<?= !empty($filters['mediation']) ? ' checked' : '' ?>> <?= $this->e($this->t('catalog.filter.mediation')) ?></label>
        <label class="checkbox"><input type="checkbox" name="gratuito" value="1"<?= !empty($filters['free']) ? ' checked' : '' ?>> <?= $this->e($this->t('catalog.filter.free')) ?></label>
        <div class="filters__actions">
            <button class="button" type="submit"><?= $this->e($this->t('catalog.filter.apply')) ?></button>
            <a href="<?= $this->e($resetUrl) ?>"><?= $this->e($this->t('catalog.filter.reset')) ?></a>
        </div>
    </form>
</details>
