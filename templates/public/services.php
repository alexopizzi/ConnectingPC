<?php
/**
 * Elenco dei servizi: usato per "tutti i servizi", per ogni bisogno e per la ricerca.
 * Desktop: filtri nella colonna laterale; mobile: pannello a scomparsa sopra i risultati.
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
 * @var list<array<string, mixed>> $orgTypes
 */
$isSearch ??= false;
$need ??= null;
$keep = $isSearch && $filters['q'] !== '' ? ['q' => $filters['q']] : [];
?>
<header class="page-head">
    <div class="container">
        <nav class="breadcrumb" aria-label="<?= $this->e($this->t('ui.breadcrumb')) ?>">
            <ol>
                <li><a href="<?= $this->e($this->route('public.home')) ?>"><?= $this->e($this->t('nav.home')) ?></a></li>
                <?php if ($need !== null): ?>
                    <li><a href="<?= $this->e($this->route('public.services')) ?>"><?= $this->e($this->t('nav.services')) ?></a></li>
                <?php endif; ?>
                <li><span aria-current="page"><?= $need !== null ? $this->localized($need['label']) : $this->e($pageTitle) ?></span></li>
            </ol>
        </nav>
        <?php if ($need !== null): ?>
            <h1><span class="page-head__icon"><?= $this->icon($need['icon'], 'icon--lg') ?></span><span><?= $this->localized($need['label']) ?></span></h1>
        <?php else: ?>
            <h1><?= $this->e($pageTitle) ?></h1>
        <?php endif; ?>
        <?= $this->partial('search-form', ['query' => $filters['q'], 'compact' => !$isSearch]) ?>
    </div>
</header>

<div class="container catalog-layout">
    <aside class="catalog-layout__aside" aria-label="<?= $this->e($this->t('catalog.filter.title')) ?>">
        <?= $this->partial('catalog-filters', [
            'filters' => $filters,
            'action' => $this->route($routeName, $routeParams),
            'resetUrl' => $this->route($routeName, [...$routeParams, ...$keep]),
            'showCategory' => $need === null,
            'categories' => $categories,
            'municipalities' => $municipalities,
            'languages' => $languages,
            'orgTypes' => $orgTypes,
        ]) ?>
    </aside>

    <div class="catalog-layout__main">
        <div class="results-bar">
            <p class="results-count" role="status">
                <?php if ($isSearch && $filters['q'] === ''): ?>
                    <?= $this->e($this->t('catalog.search.empty_query')) ?>
                <?php else: ?>
                    <?= $this->e($this->t('catalog.results', ['count' => $total])) ?>
                <?php endif; ?>
            </p>
            <?php if ($total > 0): ?>
                <nav class="view-switch" aria-label="<?= $this->e($this->t('ui.view.label')) ?>">
                    <span aria-current="page"><?= $this->icon('list') ?><?= $this->e($this->t('ui.view.list')) ?></span>
                    <a href="<?= $this->e($this->route('public.map', $mapQuery)) ?>"><?= $this->icon('map') ?><?= $this->e($this->t('ui.view.map')) ?></a>
                </nav>
            <?php endif; ?>
        </div>

        <?= $this->partial('active-filters', [
            'query' => $query, 'route' => $routeName, 'routeParams' => $routeParams, 'extraQuery' => $keep,
            'categories' => $categories, 'municipalities' => $municipalities, 'orgTypes' => $orgTypes,
        ]) ?>

        <?php if ($services !== []): ?>
            <?= $this->partial('near-me') ?>
            <ul class="card-list" data-near-me-list>
                <?php foreach ($services as $service): ?>
                    <li data-lat="<?= $service['lat'] ?? '' ?>" data-lng="<?= $service['lng'] ?? '' ?>"><?= $this->partial('service-card', ['service' => $service, 'headingLevel' => 'h2']) ?></li>
                <?php endforeach; ?>
            </ul>
            <?= $this->partial('pagination', ['page' => $page, 'pages' => $pages, 'route' => $routeName, 'query' => [...$routeParams, ...$query]]) ?>
        <?php elseif (!$isSearch || $filters['q'] !== ''): ?>
            <div class="empty-state">
                <span class="empty-state__icon"><?= $this->icon('search', 'icon--lg') ?></span>
                <h2><?= $this->e($this->t('catalog.no_results')) ?></h2>
                <p><?= $this->e($this->t('catalog.no_results_hint')) ?></p>
                <ul class="chip-list">
                    <?php foreach ($needs as $item): ?>
                        <li><a class="chip chip--small" href="<?= $this->e($this->route('public.need', ['code' => $item['code']])) ?>"><?= $this->icon($item['icon']) ?><?= $this->localized($item['label']) ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
    </div>
</div>
<script src="<?= $this->e($this->asset('js/near-me.js')) ?>" defer></script>
