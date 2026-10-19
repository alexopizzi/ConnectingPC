<?php
/**
 * Mappa dei servizi per comune (vault "61", D-038).
 * - La provincia è divisa nei 46 comuni (confini ISTAT): un clic su un comune filtra subito elenco e punti.
 * - Il menu "Scegli il tuo comune" è l'equivalente accessibile da tastiera e lettore di schermo.
 * - L'elenco dei risultati è sempre presente (la mappa non è mai l'unico accesso, RF-09).
 * - Senza JavaScript: elenco e moduli GET funzionano normalmente.
 *
 * @var App\Core\View $this
 * @var array<string, mixed> $filters
 * @var array<string, string> $query
 * @var int $total
 * @var list<array<string, mixed>> $services
 * @var array{tiles: string, attribution: string, center: string, zoom: int} $map
 * @var list<array<string, mixed>> $categories
 * @var list<array<string, mixed>> $municipalities
 * @var list<string> $languages
 * @var list<array<string, mixed>> $orgTypes
 * @var list<array<string, mixed>> $towns
 * @var array<string, mixed>|null $selection
 * @var array<string, string> $icons
 */
$otherFilters = array_diff_key($query, ['comune' => true]);
?>
<link rel="stylesheet" href="<?= $this->e($this->asset('vendor/leaflet/leaflet.css')) ?>">
<link rel="stylesheet" href="<?= $this->e($this->asset('vendor/leaflet-markercluster/MarkerCluster.css')) ?>">
<link rel="stylesheet" href="<?= $this->e($this->asset('vendor/leaflet-markercluster/MarkerCluster.Default.css')) ?>">

<div class="map-app" data-map-app
     data-map-url="<?= $this->e($this->route('public.map')) ?>"
     data-points-url="<?= $this->e($this->route('api.map.points')) ?>"
     data-counts-url="<?= $this->e($this->route('api.map.municipalities')) ?>"
     data-shapes-url="<?= $this->e($this->asset('data/comuni-piacenza.json')) ?>"
     data-locale="<?= $this->e($this->locale()) ?>">
    <div class="map-app__intro">
        <h1 id="map-title"><?= $this->e($this->t('ui.map.title')) ?></h1>
        <p class="lead"><?= $this->e($this->t('ui.map.intro')) ?></p>

        <form class="town-picker" method="get" action="<?= $this->e($this->route('public.map')) ?>" data-town-form>
            <label for="map-town"><?= $this->icon('map-pin') ?><?= $this->e($this->t('ui.map.town_label')) ?></label>
            <p class="town-picker__hint" id="map-town-hint"><?= $this->e($this->t('ui.map.town_hint')) ?></p>
            <div class="town-picker__row">
                <select id="map-town" name="comune" class="select" aria-describedby="map-town-hint" data-town-select>
                    <option value=""><?= $this->e($this->t('ui.map.whole_province')) ?></option>
                    <?php foreach ($municipalities as $district): ?>
                        <optgroup label="<?= $this->e($district['name']) ?>">
                            <option value="<?= $district['id'] ?>"<?= ($filters['territory'] ?? null) === $district['id'] ? ' selected' : '' ?>><?= $this->e($this->t('catalog.filter.whole_district', ['district' => $district['name']])) ?></option>
                            <?php foreach ($district['municipalities'] as $town): ?>
                                <option value="<?= $town['id'] ?>"<?= ($filters['territory'] ?? null) === $town['id'] ? ' selected' : '' ?>><?= $this->e($town['name']) ?></option>
                            <?php endforeach; ?>
                        </optgroup>
                    <?php endforeach; ?>
                </select>
                <button class="button" type="submit" data-town-submit><?= $this->e($this->t('catalog.filter.show')) ?></button>
            </div>
            <?php foreach ($otherFilters as $name => $value): ?>
                <input type="hidden" name="<?= $this->e($name) ?>" value="<?= $this->e($value) ?>">
            <?php endforeach; ?>
        </form>

        <?= $this->partial('search-form', ['query' => $filters['q'], 'action' => $this->route('public.map'), 'compact' => true,
            'hidden' => array_diff_key($query, ['q' => true])]) ?>

        <?= $this->partial('catalog-filters', [
            'filters' => $filters,
            'action' => $this->route('public.map'),
            'resetUrl' => $this->route('public.map', array_intersect_key($query, ['comune' => true])),
            'showCategory' => true,
            'showTerritory' => false,
            'categories' => $categories,
            'municipalities' => $municipalities,
            'languages' => $languages,
            'orgTypes' => $orgTypes,
            'idPrefix' => 'm',
            'autoOpen' => false,
        ]) ?>
        <p class="visually-hidden" role="status" aria-live="polite" data-map-announcer></p>
    </div>

    <a class="skip-link skip-link--inline" href="#risultati-mappa"><?= $this->e($this->t('map.skip_map')) ?></a>
    <div class="map-app__canvas">
        <div id="mappa" class="map" role="region" aria-label="<?= $this->e($this->t('map.region_label')) ?>" aria-describedby="map-help"
             data-map
             data-tiles="<?= $this->e($map['tiles']) ?>"
             data-attribution="<?= $this->e($map['attribution'] . ' · ' . $this->t('ui.map.boundaries_credit')) ?>"
             data-center="<?= $this->e($map['center']) ?>"
             data-zoom="<?= $map['zoom'] ?>"
             data-dir="<?= $this->e($this->dir()) ?>"
             data-sprite="<?= $this->e($this->asset('icons/sprite.svg')) ?>"
             data-icons="<?= $this->e(json_encode($icons, JSON_UNESCAPED_SLASHES)) ?>"
             data-service-url="<?= $this->e($this->route('public.service', ['id' => 0])) ?>"
             data-msg-services="<?= $this->e($this->t('map.popup.services')) ?>"
             data-msg-directions="<?= $this->e($this->t('catalog.site.take_me')) ?>"
             data-msg-mediation="<?= $this->e($this->t('catalog.mediation.available')) ?>"
             data-msg-step-free="<?= $this->e($this->t('ui.map.step_free')) ?>"
             data-msg-you="<?= $this->e($this->t('geo.you_are_here')) ?>"
             data-msg-town-count="<?= $this->e($this->t('ui.map.town_count')) ?>"
             data-msg-select-town="<?= $this->e($this->t('ui.map.select_town')) ?>"
             data-msg-loading="<?= $this->e($this->t('ui.map.loading')) ?>"
             data-msg-updated="<?= $this->e($this->t('ui.map.updated')) ?>">
            <noscript><p class="callout"><?= $this->e($this->t('map.noscript')) ?></p></noscript>
        </div>
        <p id="map-help" class="visually-hidden"><?= $this->e($this->t('ui.map.help')) ?></p>
        <div class="map-toolbar" data-map-toolbar hidden>
            <label class="checkbox"><input type="checkbox" checked data-toggle-towns> <?= $this->e($this->t('ui.map.show_towns')) ?></label>
            <label class="checkbox"><input type="checkbox" checked data-toggle-points> <?= $this->e($this->t('ui.map.show_points')) ?></label>
        </div>
        <div class="map-legend" data-map-legend hidden>
            <p class="map-legend__title"><?= $this->e($this->t('ui.map.legend')) ?></p>
            <ul data-map-legend-list></ul>
        </div>
    </div>

    <section class="map-app__results" id="risultati-mappa" tabindex="-1" aria-labelledby="map-results-title">
        <h2 class="visually-hidden" id="map-results-title"><?= $this->e($this->t('map.list_title')) ?></h2>
        <?= $this->partial('near-me') ?>
        <div class="map-results" data-map-results>
            <?php if ($selection !== null): ?>
                <div class="town-summary" data-selection="<?= $selection['id'] ?>">
                    <h3><?= $this->icon('map-pin') ?><?= $this->e($selection['name']) ?></h3>
                    <p><?= $this->e($this->t($selection['is_district'] ? 'ui.map.district_reach' : 'ui.map.town_reach', ['count' => $total, 'town' => $selection['name']])) ?></p>
                    <div class="town-summary__actions">
                        <?php if ($selection['district'] !== null && $selection['district']['count'] > $total): ?>
                            <a class="button button--secondary button--small" href="<?= $this->e($this->route('public.map', [...$otherFilters, 'comune' => $selection['district']['id']])) ?>" data-town-link="<?= $selection['district']['id'] ?>">
                                <?= $this->icon('layers') ?><?= $this->e($this->t('ui.map.widen', ['district' => $selection['district']['name'], 'count' => $selection['district']['count']])) ?>
                            </a>
                        <?php endif; ?>
                        <a class="button button--ghost button--small" href="<?= $this->e($this->route('public.map', $otherFilters)) ?>" data-town-link=""><?= $this->icon('x') ?><?= $this->e($this->t('ui.map.clear_town')) ?></a>
                    </div>
                </div>
            <?php endif; ?>

            <div class="results-bar">
                <p class="results-count"><?= $this->e($this->t('catalog.results', ['count' => $total])) ?></p>
                <nav class="view-switch" aria-label="<?= $this->e($this->t('ui.view.label')) ?>">
                    <a href="<?= $this->e($this->route('public.services', $query)) ?>"><?= $this->icon('list') ?><?= $this->e($this->t('ui.view.list')) ?></a>
                    <span aria-current="page"><?= $this->icon('map') ?><?= $this->e($this->t('ui.view.map')) ?></span>
                </nav>
            </div>

            <?= $this->partial('active-filters', [
                'query' => $otherFilters, 'route' => 'public.map', 'routeParams' => array_intersect_key($query, ['comune' => true]),
                'categories' => $categories, 'municipalities' => $municipalities, 'orgTypes' => $orgTypes,
            ]) ?>

            <?php if ($services === []): ?>
                <div class="empty-state">
                    <span class="empty-state__icon"><?= $this->icon('map-pin', 'icon--lg') ?></span>
                    <p><?= $this->e($this->t('catalog.no_results')) ?></p>
                    <p class="muted"><?= $this->e($this->t('ui.map.no_results_hint')) ?></p>
                </div>
            <?php else: ?>
                <ul class="card-list" data-near-me-list>
                    <?php foreach ($services as $service): ?>
                        <li data-lat="<?= $service['lat'] ?? '' ?>" data-lng="<?= $service['lng'] ?? '' ?>" data-service-id="<?= (int) $service['id'] ?>"><?= $this->partial('service-card', ['service' => $service]) ?></li>
                    <?php endforeach; ?>
                </ul>
                <?php if ($total > count($services)): ?>
                    <p><a href="<?= $this->e($this->route('public.services', $query)) ?>"><?= $this->e($this->t('map.more_in_list', ['count' => $total - count($services)])) ?></a></p>
                <?php endif; ?>
            <?php endif; ?>
        </div>
        <p class="muted map-attribution-note"><?= $this->e($this->t('map.privacy_note')) ?></p>
    </section>
</div>
<script src="<?= $this->e($this->asset('vendor/leaflet/leaflet.js')) ?>" defer></script>
<script src="<?= $this->e($this->asset('vendor/leaflet-markercluster/leaflet.markercluster.js')) ?>" defer></script>
<script src="<?= $this->e($this->asset('js/map.js')) ?>" defer></script>
<script src="<?= $this->e($this->asset('js/near-me.js')) ?>" defer></script>
