<?php
/**
 * Elenco dei servizi: usato per "tutti i servizi", per ogni bisogno e per la ricerca.
 *
 * @var App\Core\View $this
 * @var string $pageTitle
 * @var array<string, mixed>|null $need
 * @var bool|null $isSearch
 * @var list<array<string, mixed>> $services
 * @var int $total
 * @var int $page
 * @var int $pages
 * @var array<string, mixed> $filters
 * @var array<string, string> $query
 * @var array<string, string> $mapQuery
 * @var string $routeName
 * @var array<string, string> $routeParams
 * @var list<array<string, mixed>> $categories
 * @var list<array<string, mixed>> $municipalities
 * @var list<string> $languages
 * @var list<array<string, mixed>> $needs
 */
$isSearch ??= false;
$need ??= null;
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

    <?= $this->partial('catalog-filters', [
        'filters' => $filters,
        'action' => $this->route($routeName, $routeParams),
        'resetUrl' => $this->route($routeName, $isSearch ? [...$routeParams, 'q' => $filters['q']] : $routeParams),
        'showCategory' => $need === null,
        'categories' => $categories,
        'municipalities' => $municipalities,
        'languages' => $languages,
    ]) ?>

    <div class="results-bar">
        <p class="results-count" role="status">
            <?php if ($isSearch && $filters['q'] === ''): ?>
                <?= $this->e($this->t('catalog.search.empty_query')) ?>
            <?php else: ?>
                <?= $this->e($this->t('catalog.results', ['count' => $total])) ?>
            <?php endif; ?>
        </p>
        <?php if ($total > 0): ?>
            <a class="button button--secondary" href="<?= $this->e($this->route('public.map', $mapQuery)) ?>"><span aria-hidden="true">🗺️</span> <?= $this->e($this->t('map.show_on_map')) ?></a>
        <?php endif; ?>
    </div>

    <?php if ($services !== []): ?>
        <?= $this->partial('near-me') ?>
        <ul class="card-list" data-near-me-list>
            <?php foreach ($services as $service): ?>
                <li data-lat="<?= $service['lat'] ?? '' ?>" data-lng="<?= $service['lng'] ?? '' ?>"><?= $this->partial('service-card', ['service' => $service]) ?></li>
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
<script src="<?= $this->e($this->asset('js/near-me.js')) ?>" defer></script>
