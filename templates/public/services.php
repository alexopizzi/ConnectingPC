<?php
/**
 * Elenco dei servizi: usato per "tutti i servizi", per ogni bisogno e per la ricerca.
 *
 * @var App\Core\View $this
 * @var string $pageTitle
 * @var array{text: string, lang: string, fallback: bool}|null $heading
 * @var array<string, mixed>|null $need
 * @var bool|null $isSearch
 * @var list<array<string, mixed>> $services
 * @var int $total
 * @var int $page
 * @var int $pages
 * @var array<string, mixed> $filters
 * @var array<string, string> $query
 * @var string $routeName
 * @var array<string, string> $routeParams
 * @var list<array<string, mixed>> $categories
 * @var list<array{id: int, name: string}> $municipalities
 * @var list<string> $languages
 * @var list<array<string, mixed>> $needs
 */
$isSearch ??= false;
$need ??= null;
$action = $this->route($routeName, $routeParams);
?>
<div class="container section">
    <?php if ($need !== null): ?>
        <p class="breadcrumb"><a href="<?= $this->e($this->route('public.home')) ?>"><?= $this->e($this->t('nav.home')) ?></a></p>
        <h1><span aria-hidden="true"><?= $this->icon($need['icon']) ?></span> <?= $this->localized($need['label']) ?></h1>
    <?php else: ?>
        <h1><?= $this->e($pageTitle) ?></h1>
    <?php endif; ?>

    <?php if ($isSearch): ?>
        <?= $this->partial('search-form', ['query' => $filters['q']]) ?>
    <?php endif; ?>

    <details class="filters-panel"<?= count(array_diff_key($query, ['q' => 1])) > 0 ? ' open' : '' ?>>
        <summary><?= $this->e($this->t('catalog.filter.title')) ?></summary>
        <form class="filters" method="get" action="<?= $this->e($action) ?>">
            <?php if ($filters['q'] !== '' || $isSearch): ?>
                <input type="hidden" name="q" value="<?= $this->e($filters['q']) ?>">
            <?php endif; ?>
            <?php if ($need === null): ?>
                <div class="field">
                    <label for="f-categoria"><?= $this->e($this->t('catalog.filter.category')) ?></label>
                    <select id="f-categoria" name="categoria">
                        <option value=""><?= $this->e($this->t('catalog.filter.any')) ?></option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?= $this->e($category['code']) ?>"<?= $filters['category'] === $category['code'] ? ' selected' : '' ?>><?= $this->e($category['name']['text']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>
            <div class="field">
                <label for="f-comune"><?= $this->e($this->t('catalog.filter.municipality')) ?></label>
                <select id="f-comune" name="comune">
                    <option value=""><?= $this->e($this->t('catalog.filter.any_municipality')) ?></option>
                    <?php foreach ($municipalities as $district): ?>
                        <optgroup label="<?= $this->e($district['name']) ?>">
                            <option value="<?= $district['id'] ?>"<?= $filters['territory'] === $district['id'] ? ' selected' : '' ?>><?= $this->e($this->t('catalog.filter.whole_district', ['district' => $district['name']])) ?></option>
                            <?php foreach ($district['municipalities'] as $municipality): ?>
                                <option value="<?= $municipality['id'] ?>"<?= $filters['territory'] === $municipality['id'] ? ' selected' : '' ?>><?= $this->e($municipality['name']) ?></option>
                            <?php endforeach; ?>
                        </optgroup>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field">
                <label for="f-lingua"><?= $this->e($this->t('catalog.filter.language')) ?></label>
                <select id="f-lingua" name="lingua">
                    <option value=""><?= $this->e($this->t('catalog.filter.any')) ?></option>
                    <?php foreach ($languages as $code): ?>
                        <option value="<?= $this->e($code) ?>"<?= $filters['language'] === $code ? ' selected' : '' ?>><?= $this->e($this->languageName($code)) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <label class="checkbox"><input type="checkbox" name="mediazione" value="1"<?= $filters['mediation'] ? ' checked' : '' ?>> <?= $this->e($this->t('catalog.filter.mediation')) ?></label>
            <label class="checkbox"><input type="checkbox" name="gratuito" value="1"<?= $filters['free'] ? ' checked' : '' ?>> <?= $this->e($this->t('catalog.filter.free')) ?></label>
            <div class="filters__actions">
                <button class="button" type="submit"><?= $this->e($this->t('catalog.filter.apply')) ?></button>
                <a href="<?= $this->e($this->route($routeName, $isSearch ? [...$routeParams, 'q' => $filters['q']] : $routeParams)) ?>"><?= $this->e($this->t('catalog.filter.reset')) ?></a>
            </div>
        </form>
    </details>

    <p class="results-count" role="status">
        <?php if ($isSearch && $filters['q'] === ''): ?>
            <?= $this->e($this->t('catalog.search.empty_query')) ?>
        <?php else: ?>
            <?= $this->e($this->t('catalog.results', ['count' => $total])) ?>
        <?php endif; ?>
    </p>

    <?php if ($services !== []): ?>
        <ul class="card-list">
            <?php foreach ($services as $service): ?>
                <li><?= $this->partial('service-card', ['service' => $service]) ?></li>
            <?php endforeach; ?>
        </ul>
        <?= $this->partial('pagination', ['page' => $page, 'pages' => $pages, 'route' => $routeName, 'query' => [...$routeParams, ...$query]]) ?>
    <?php elseif (!$isSearch || $filters['q'] !== ''): ?>
        <div class="callout">
            <p><?= $this->e($this->t('catalog.no_results')) ?></p>
            <p><?= $this->e($this->t('catalog.no_results_hint')) ?></p>
            <ul class="chip-list">
                <?php foreach ($needs as $item): ?>
                    <li><a class="chip" href="<?= $this->e($this->route('public.need', ['code' => $item['code']])) ?>"><span aria-hidden="true"><?= $this->icon($item['icon']) ?></span> <?= $this->localized($item['label']) ?></a></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
</div>
