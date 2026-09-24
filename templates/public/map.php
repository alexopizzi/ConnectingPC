<?php
/**
 * Mappa dei servizi con elenco equivalente (vault "61"). Senza JavaScript resta l'elenco.
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
 */
$pointsUrl = $this->route('api.map.points', [...$query, 'locale' => $this->locale()]);
$serviceUrl = $this->route('public.service', ['id' => 0]);
?>
<link rel="stylesheet" href="<?= $this->e($this->asset('vendor/leaflet/leaflet.css')) ?>">
<link rel="stylesheet" href="<?= $this->e($this->asset('vendor/leaflet-markercluster/MarkerCluster.css')) ?>">
<link rel="stylesheet" href="<?= $this->e($this->asset('vendor/leaflet-markercluster/MarkerCluster.Default.css')) ?>">

<div class="container section">
    <h1><?= $this->e($this->t('nav.map')) ?></h1>
    <?= $this->partial('search-form', ['query' => $filters['q'], 'action' => $this->route('public.map')]) ?>
    <?= $this->partial('catalog-filters', [
        'filters' => $filters,
        'action' => $this->route('public.map'),
        'resetUrl' => $this->route('public.map'),
        'showCategory' => true,
        'categories' => $categories,
        'municipalities' => $municipalities,
        'languages' => $languages,
        'orgTypes' => $orgTypes,
    ]) ?>

    <div class="results-bar">
        <p class="results-count" role="status"><?= $this->e($this->t('catalog.results', ['count' => $total])) ?></p>
        <a class="button button--secondary" href="<?= $this->e($this->route('public.services', $query)) ?>"><span aria-hidden="true">📋</span> <?= $this->e($this->t('map.show_as_list')) ?></a>
    </div>

    <a class="skip-link skip-link--inline" href="#elenco-mappa"><?= $this->e($this->t('map.skip_map')) ?></a>
    <div class="map-layout">
        <div id="mappa" class="map" role="region" aria-label="<?= $this->e($this->t('map.region_label')) ?>"
             data-map
             data-points-url="<?= $this->e($pointsUrl) ?>"
             data-service-url="<?= $this->e($serviceUrl) ?>"
             data-tiles="<?= $this->e($map['tiles']) ?>"
             data-attribution="<?= $this->e($map['attribution']) ?>"
             data-center="<?= $this->e($map['center']) ?>"
             data-zoom="<?= $map['zoom'] ?>"
             data-dir="<?= $this->e($this->dir()) ?>"
             data-msg-services="<?= $this->e($this->t('map.popup.services')) ?>"
             data-msg-open="<?= $this->e($this->t('map.popup.open')) ?>"
             data-msg-directions="<?= $this->e($this->t('catalog.site.take_me')) ?>"
             data-msg-mediation="<?= $this->e($this->t('catalog.mediation.available')) ?>"
             data-msg-you="<?= $this->e($this->t('geo.you_are_here')) ?>">
            <noscript><p class="callout"><?= $this->e($this->t('map.noscript')) ?></p></noscript>
        </div>

        <div id="elenco-mappa" class="map-list" tabindex="-1">
            <h2 class="visually-hidden"><?= $this->e($this->t('map.list_title')) ?></h2>
            <?= $this->partial('near-me') ?>
            <ul class="card-list" data-near-me-list>
                <?php foreach ($services as $service): ?>
                    <li data-lat="<?= $service['lat'] ?? '' ?>" data-lng="<?= $service['lng'] ?? '' ?>" data-service-id="<?= (int) $service['id'] ?>"><?= $this->partial('service-card', ['service' => $service]) ?></li>
                <?php endforeach; ?>
            </ul>
            <?php if ($total > count($services)): ?>
                <p><a href="<?= $this->e($this->route('public.services', $query)) ?>"><?= $this->e($this->t('map.more_in_list', ['count' => $total - count($services)])) ?></a></p>
            <?php endif; ?>
        </div>
    </div>
    <p class="muted map-attribution-note"><?= $this->e($this->t('map.privacy_note')) ?></p>
</div>
<script src="<?= $this->e($this->asset('vendor/leaflet/leaflet.js')) ?>" defer></script>
<script src="<?= $this->e($this->asset('vendor/leaflet-markercluster/leaflet.markercluster.js')) ?>" defer></script>
<script src="<?= $this->e($this->asset('js/map.js')) ?>" defer></script>
<script src="<?= $this->e($this->asset('js/near-me.js')) ?>" defer></script>
