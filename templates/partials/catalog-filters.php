<?php
/**
 * Filtri del catalogo (elenco, bisogno, ricerca, mappa). Pannello <details>: chiuso su mobile salvo filtri attivi,
 * aperto dalla colonna laterale sugli schermi larghi (app.js). Senza JavaScript resta un modulo GET.
 *
 * @var App\Core\View $this
 * @var array<string, mixed> $filters
 * @var string $action
 * @var string $resetUrl
 * @var bool $showCategory
 * @var bool|null $showTerritory
 * @var list<array<string, mixed>> $categories
 * @var list<array<string, mixed>> $municipalities
 * @var list<string> $languages
 * @var list<array<string, mixed>> $orgTypes
 * @var string|null $idPrefix
 * @var bool|null $autoOpen aprire il pannello sugli schermi larghi (colonna laterale)
 */
$orgTypes ??= [];
$autoOpen ??= true;
$showTerritory ??= true;
$p = $idPrefix ?? 'f';
$activeCount = count(array_filter([
    $filters['category'] ?? null, $showTerritory ? ($filters['territory'] ?? null) : null, $filters['language'] ?? null, $filters['mediation'] ?: null, $filters['free'] ?: null,
    $filters['accessible'] ?? null ?: null, $filters['org_type'] ?? null, $filters['access_mode'] ?? null,
]));
?>
<details class="filters-panel"<?= $autoOpen ? ' data-filters-panel' : '' ?><?= $activeCount > 0 ? ' open' : '' ?>>
    <summary>
        <?= $this->icon('sliders-horizontal') ?>
        <span><?= $this->e($this->t('catalog.filter.title')) ?></span>
        <?php if ($activeCount > 0): ?>
            <span class="count-pill"><?= $activeCount ?><span class="visually-hidden"> <?= $this->e($this->t('ui.filters.active_count')) ?></span></span>
        <?php endif; ?>
    </summary>
    <form class="filters" method="get" action="<?= $this->e($action) ?>" data-catalog-filters>
        <?php if (($filters['q'] ?? '') !== ''): ?>
            <input type="hidden" name="q" value="<?= $this->e($filters['q']) ?>">
        <?php endif; ?>
        <?php if (!empty($filters['need']) && $showCategory): ?>
            <input type="hidden" name="bisogno" value="<?= $this->e($filters['need']) ?>">
        <?php endif; ?>
        <?php if (!$showTerritory && !empty($filters['territory'])): ?>
            <input type="hidden" name="comune" value="<?= (int) $filters['territory'] ?>">
        <?php endif; ?>
        <?php if ($showTerritory): ?>
            <?= $this->partial('territory-select', ['id' => $p . '-comune', 'selected' => $filters['territory'] ?? null, 'municipalities' => $municipalities]) ?>
        <?php endif; ?>
        <?php if ($showCategory): ?>
            <div class="field">
                <label for="<?= $p ?>-categoria"><?= $this->e($this->t('catalog.filter.category')) ?></label>
                <select id="<?= $p ?>-categoria" name="categoria">
                    <option value=""><?= $this->e($this->t('catalog.filter.any')) ?></option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?= $this->e($category['code']) ?>"<?= ($filters['category'] ?? null) === $category['code'] ? ' selected' : '' ?>><?= $this->e($category['name']['text']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        <?php endif; ?>
        <div class="field">
            <label for="<?= $p ?>-lingua"><?= $this->e($this->t('catalog.filter.language')) ?></label>
            <select id="<?= $p ?>-lingua" name="lingua">
                <option value=""><?= $this->e($this->t('catalog.filter.any')) ?></option>
                <?php foreach ($languages as $code): ?>
                    <option value="<?= $this->e($code) ?>"<?= ($filters['language'] ?? null) === $code ? ' selected' : '' ?>><?= $this->e($this->languageName($code)) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php if ($orgTypes !== []): ?>
            <div class="field">
                <label for="<?= $p ?>-tipo-ente"><?= $this->e($this->t('catalog.filter.org_type')) ?></label>
                <select id="<?= $p ?>-tipo-ente" name="tipo_ente">
                    <option value=""><?= $this->e($this->t('catalog.filter.any')) ?></option>
                    <?php foreach ($orgTypes as $type): ?>
                        <option value="<?= $this->e($type['code']) ?>"<?= ($filters['org_type'] ?? null) === $type['code'] ? ' selected' : '' ?>><?= $this->e($type['name']['text']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        <?php endif; ?>
        <div class="field">
            <label for="<?= $p ?>-modalita"><?= $this->e($this->t('catalog.filter.access_mode')) ?></label>
            <select id="<?= $p ?>-modalita" name="modalita">
                <option value=""><?= $this->e($this->t('catalog.filter.any')) ?></option>
                <?php foreach (App\Domain\Catalog\CatalogFilters::ACCESS_MODES as $mode): ?>
                    <option value="<?= $mode ?>"<?= ($filters['access_mode'] ?? null) === $mode ? ' selected' : '' ?>><?= $this->e($this->t('manage.access_mode.' . $mode)) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <fieldset class="field filters__checks">
            <legend class="visually-hidden"><?= $this->e($this->t('ui.filters.options')) ?></legend>
            <label class="checkbox"><input type="checkbox" name="gratuito" value="1"<?= !empty($filters['free']) ? ' checked' : '' ?>> <?= $this->e($this->t('catalog.filter.free')) ?></label>
            <label class="checkbox"><input type="checkbox" name="mediazione" value="1"<?= !empty($filters['mediation']) ? ' checked' : '' ?>> <?= $this->e($this->t('catalog.filter.mediation')) ?></label>
            <label class="checkbox"><input type="checkbox" name="accessibile" value="1"<?= !empty($filters['accessible']) ? ' checked' : '' ?>> <?= $this->e($this->t('catalog.filter.accessible')) ?></label>
        </fieldset>
        <div class="filters__actions">
            <button class="button" type="submit"><?= $this->icon('check') ?><?= $this->e($this->t('catalog.filter.apply')) ?></button>
            <a href="<?= $this->e($resetUrl) ?>"><?= $this->e($this->t('catalog.filter.reset')) ?></a>
        </div>
    </form>
</details>
