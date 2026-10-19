<?php
/**
 * Directory "Associazioni e comunità" (vault "64", D-039): ricerca, scorciatoie per comunità,
 * filtri nella colonna laterale (pannello su mobile), filtri attivi rimovibili, schede in griglia.
 *
 * @var App\Core\View $this
 * @var string $pageTitle
 * @var list<array<string, mixed>> $organizations
 * @var int $total
 * @var int $page
 * @var int $pages
 * @var array<string, mixed> $filters
 * @var array<string, string> $query
 * @var list<array<string, mixed>> $communities
 * @var array<string, int> $communityCounts
 * @var list<string> $countries
 * @var list<string> $languages
 * @var list<array<string, mixed>> $types
 * @var list<array<string, mixed>> $municipalities
 */
$activeCount = count(array_filter([$filters['language'], $filters['community'], $filters['country'], $filters['territory'], $filters['type'], $filters['community_based'] ?: null]));
$countryNames = array_combine($countries, array_map($this->countryName(...), $countries));
asort($countryNames, SORT_LOCALE_STRING);

$communityNames = array_column(array_map(static fn (array $c): array => ['code' => $c['code'], 'name' => $c['name']['text']], $communities), 'name', 'code');
$typeNames = array_column(array_map(static fn (array $t): array => ['code' => $t['code'], 'name' => $t['name']['text']], $types), 'name', 'code');
$territoryNames = [];
foreach ($municipalities as $district) {
    $territoryNames[$district['id']] = $this->t('catalog.filter.whole_district', ['district' => $district['name']]);
    foreach ($district['municipalities'] as $town) {
        $territoryNames[$town['id']] = $town['name'];
    }
}

// Etichette dei filtri attivi
$labels = array_filter([
    'comunita' => $filters['community'] !== null ? ($communityNames[$filters['community']] ?? null) : null,
    'paese' => $filters['country'] !== null ? ($countryNames[$filters['country']] ?? $this->countryName($filters['country'])) : null,
    'lingua' => $filters['language'] !== null ? $this->languageName($filters['language']) : null,
    'comune' => $filters['territory'] !== null ? ($territoryNames[$filters['territory']] ?? null) : null,
    'tipo' => $filters['type'] !== null ? ($typeNames[$filters['type']] ?? null) : null,
    'stranieri' => $filters['community_based'] ? $this->t('communities.filter.community_based') : null,
]);
$keep = $filters['q'] !== '' ? ['q' => $filters['q']] : [];

// Scorciatoie: le comunità più presenti (al massimo 8), solo se non se ne è già scelta una
$quick = $filters['community'] === null ? array_slice(array_keys(array_intersect_key($communityCounts, $communityNames)), 0, 8) : [];

// Icona per tipo di realtà
$typeIcons = [
    'association' => 'users-round', 'cultural_association' => 'languages', 'volunteer_association' => 'hand-heart',
    'community_association' => 'earth', 'cultural_centre' => 'landmark', 'religious_organization' => 'heart-handshake',
    'informal_network' => 'messages-square',
];
?>
<header class="page-head">
    <div class="container">
        <nav class="breadcrumb" aria-label="<?= $this->e($this->t('ui.breadcrumb')) ?>">
            <ol>
                <li><a href="<?= $this->e($this->route('public.home')) ?>"><?= $this->e($this->t('nav.home')) ?></a></li>
                <li><span aria-current="page"><?= $this->e($pageTitle) ?></span></li>
            </ol>
        </nav>
        <h1><span class="page-head__icon"><?= $this->icon('users-round', 'icon--lg') ?></span><span><?= $this->e($pageTitle) ?></span></h1>
        <p class="lead"><?= $this->e($this->t('communities.intro')) ?></p>

        <form class="search-form search-form--compact" method="get" action="<?= $this->e($this->route('public.communities')) ?>" role="search">
            <label class="visually-hidden" for="c-q"><?= $this->e($this->t('communities.search.label')) ?></label>
            <div class="search-form__row">
                <?= $this->icon('search') ?>
                <input class="search-form__input" id="c-q" type="search" name="q" value="<?= $this->e($filters['q']) ?>"
                       placeholder="<?= $this->e($this->t('communities.search.placeholder')) ?>" autocomplete="off">
                <button class="button search-form__submit" type="submit"><span class="button__text"><?= $this->e($this->t('search.submit')) ?></span><?= $this->icon('arrow-right', 'icon--flip') ?></button>
            </div>
            <?php foreach (array_diff_key($query, ['q' => true]) as $name => $value): ?>
                <input type="hidden" name="<?= $this->e($name) ?>" value="<?= $this->e($value) ?>">
            <?php endforeach; ?>
        </form>

        <?php if ($quick !== []): ?>
            <div class="quick-communities">
                <h2 class="quick-communities__title" id="quick-communities-title"><?= $this->e($this->t('ui.communities.quick_title')) ?></h2>
                <ul class="chip-list" aria-labelledby="quick-communities-title">
                    <?php foreach ($quick as $code): ?>
                        <li>
                            <a class="chip chip--small" href="<?= $this->e($this->route('public.communities', [...$query, 'comunita' => $code])) ?>">
                                <?= $this->icon('users-round') ?><span><?= $this->e($communityNames[$code]) ?></span>
                                <span class="chip__count"><?= $this->e($this->t('ui.communities.count', ['count' => $communityCounts[$code]])) ?></span>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
    </div>
</header>

<div class="container catalog-layout">
    <aside class="catalog-layout__aside" aria-label="<?= $this->e($this->t('catalog.filter.title')) ?>">
        <details class="filters-panel" data-filters-panel<?= $activeCount > 0 ? ' open' : '' ?>>
            <summary>
                <?= $this->icon('sliders-horizontal') ?>
                <span><?= $this->e($this->t('catalog.filter.title')) ?></span>
                <?php if ($activeCount > 0): ?>
                    <span class="count-pill"><?= $activeCount ?><span class="visually-hidden"> <?= $this->e($this->t('ui.filters.active_count')) ?></span></span>
                <?php endif; ?>
            </summary>
            <form class="filters" method="get" action="<?= $this->e($this->route('public.communities')) ?>">
                <?php if ($filters['q'] !== ''): ?>
                    <input type="hidden" name="q" value="<?= $this->e($filters['q']) ?>">
                <?php endif; ?>
                <div class="field">
                    <label for="c-comunita"><?= $this->e($this->t('communities.filter.community')) ?></label>
                    <select id="c-comunita" name="comunita">
                        <option value=""><?= $this->e($this->t('catalog.filter.any')) ?></option>
                        <?php foreach ($communities as $community): ?>
                            <option value="<?= $this->e($community['code']) ?>"<?= $filters['community'] === $community['code'] ? ' selected' : '' ?>><?= $this->e($community['name']['text']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="field">
                    <label for="c-paese"><?= $this->e($this->t('communities.filter.country')) ?></label>
                    <select id="c-paese" name="paese">
                        <option value=""><?= $this->e($this->t('catalog.filter.any')) ?></option>
                        <?php foreach ($countryNames as $code => $name): ?>
                            <option value="<?= $this->e($code) ?>"<?= $filters['country'] === $code ? ' selected' : '' ?>><?= $this->e($name) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="field">
                    <label for="c-lingua"><?= $this->e($this->t('catalog.filter.language')) ?></label>
                    <select id="c-lingua" name="lingua">
                        <option value=""><?= $this->e($this->t('catalog.filter.any')) ?></option>
                        <?php foreach ($languages as $code): ?>
                            <option value="<?= $this->e($code) ?>"<?= $filters['language'] === $code ? ' selected' : '' ?>><?= $this->e($this->languageName($code)) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?= $this->partial('territory-select', ['id' => 'c-comune', 'selected' => $filters['territory'], 'municipalities' => $municipalities]) ?>
                <div class="field">
                    <label for="c-tipo"><?= $this->e($this->t('communities.filter.type')) ?></label>
                    <select id="c-tipo" name="tipo">
                        <option value=""><?= $this->e($this->t('catalog.filter.any')) ?></option>
                        <?php foreach ($types as $type): ?>
                            <option value="<?= $this->e($type['code']) ?>"<?= $filters['type'] === $type['code'] ? ' selected' : '' ?>><?= $this->e($type['name']['text']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <label class="checkbox"><input type="checkbox" name="stranieri" value="1"<?= $filters['community_based'] ? ' checked' : '' ?>> <?= $this->e($this->t('communities.filter.community_based')) ?></label>
                <div class="filters__actions">
                    <button class="button" type="submit"><?= $this->icon('check') ?><?= $this->e($this->t('catalog.filter.apply')) ?></button>
                    <a href="<?= $this->e($this->route('public.communities', $keep)) ?>"><?= $this->e($this->t('catalog.filter.reset')) ?></a>
                </div>
            </form>
        </details>
    </aside>

    <div class="catalog-layout__main">
        <div class="results-bar">
            <p class="results-count" role="status"><?= $this->e($this->t('communities.results', ['count' => $total])) ?></p>
        </div>

        <?= $this->partial('filter-chips', [
            'labels' => $labels,
            'icons' => ['comunita' => 'users-round', 'paese' => 'earth', 'lingua' => 'languages', 'comune' => 'map-pin', 'tipo' => 'building-2', 'stranieri' => 'heart-handshake'],
            'query' => $query, 'route' => 'public.communities', 'routeParams' => [], 'extraQuery' => $keep,
        ]) ?>

        <?php if ($organizations === []): ?>
            <div class="empty-state">
                <span class="empty-state__icon"><?= $this->icon('users-round', 'icon--lg') ?></span>
                <h2><?= $this->e($this->t('communities.results', ['count' => 0])) ?></h2>
                <p><?= $this->e($this->t('communities.none')) ?></p>
            </div>
        <?php else: ?>
            <ul class="org-grid">
                <?php foreach ($organizations as $organization):
                    $shown = array_slice($organization['communities'], 0, 3);
                    $hidden = count($organization['communities']) - count($shown); ?>
                    <li>
                        <article class="card org-card">
                            <div class="org-card__head">
                                <span class="org-card__icon"><?= $this->icon($typeIcons[$organization['type_code']] ?? 'users-round') ?></span>
                                <div>
                                    <h2 class="org-card__title">
                                        <a class="org-card__link" href="<?= $this->e($this->route('public.organization', ['id' => $organization['id']])) ?>"><?= $this->e($organization['name']) ?></a>
                                    </h2>
                                    <p class="org-card__meta">
                                        <span><?= $this->localized($organization['type']) ?></span>
                                        <?php if ($organization['towns'] !== []): ?>
                                            <span><?= $this->icon('map-pin') ?><?= $this->e(implode(', ', $organization['towns'])) ?></span>
                                        <?php endif; ?>
                                    </p>
                                </div>
                            </div>
                            <?php if ($organization['description'] !== null): ?>
                                <p class="org-card__text"><?= $this->localized($organization['description']) ?></p>
                            <?php endif; ?>
                            <ul class="badges">
                                <?php if ($organization['is_community_based']): ?>
                                    <li class="badge badge--success"><?= $this->icon('heart-handshake') ?><?= $this->e($this->t('catalog.organization.community_based')) ?></li>
                                <?php endif; ?>
                                <?php foreach ($shown as $community): ?>
                                    <li class="badge badge--primary"><?= $this->localized($community['name']) ?></li>
                                <?php endforeach; ?>
                                <?php if ($hidden > 0): ?>
                                    <li class="badge"><?= $this->e($this->t('ui.communities.more', ['count' => $hidden])) ?></li>
                                <?php endif; ?>
                            </ul>
                            <?php if ($organization['languages'] !== []): ?>
                                <p class="org-card__languages"><?= $this->icon('languages') ?><span><span class="visually-hidden"><?= $this->e($this->t('catalog.organization.languages')) ?>: </span><?= $this->e(implode(', ', array_map($this->languageName(...), $organization['languages']))) ?></span></p>
                            <?php endif; ?>
                        </article>
                    </li>
                <?php endforeach; ?>
            </ul>
            <?= $this->partial('pagination', ['page' => $page, 'pages' => $pages, 'route' => 'public.communities', 'query' => $query]) ?>
        <?php endif; ?>

        <div class="participate">
            <div>
                <h2><?= $this->e($this->t('ui.communities.join_title')) ?></h2>
                <p><?= $this->e($this->t('communities.join')) ?></p>
            </div>
            <a class="button button--secondary" href="<?= $this->e($this->route('public.section', ['section' => 'partecipa'])) ?>"><?= $this->icon('handshake') ?><?= $this->e($this->t('nav.participate')) ?></a>
        </div>
    </div>
</div>
