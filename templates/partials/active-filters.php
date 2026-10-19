<?php
/**
 * Filtri attivi come etichette rimovibili: ognuna è un collegamento alla stessa pagina senza quel filtro.
 *
 * @var App\Core\View $this
 * @var array<string, string> $query parametri correnti (senza "page")
 * @var string $route
 * @var array<string, string> $routeParams
 * @var list<array<string, mixed>> $categories
 * @var list<array<string, mixed>> $municipalities
 * @var list<array<string, mixed>> $orgTypes
 * @var array<string, string>|null $extraQuery parametri da mantenere sempre (es. "q" nella ricerca)
 */
$extraQuery ??= [];
$labels = [];
$categoryNames = array_column(array_map(static fn (array $c): array => ['code' => $c['code'], 'name' => $c['name']['text']], $categories), 'name', 'code');
$territoryNames = [];
foreach ($municipalities as $district) {
    $territoryNames[$district['id']] = $this->t('catalog.filter.whole_district', ['district' => $district['name']]);
    foreach ($district['municipalities'] as $town) {
        $territoryNames[$town['id']] = $town['name'];
    }
}
$typeNames = array_column(array_map(static fn (array $t): array => ['code' => $t['code'], 'name' => $t['name']['text']], $orgTypes ?? []), 'name', 'code');
foreach ($query as $key => $value) {
    $text = match ($key) {
        'categoria' => $categoryNames[$value] ?? null,
        'comune' => $territoryNames[(int) $value] ?? null,
        'lingua' => $this->languageName($value),
        'tipo_ente' => $typeNames[$value] ?? null,
        'modalita' => $this->t('manage.access_mode.' . $value),
        'mediazione' => $this->t('catalog.filter.mediation'),
        'gratuito' => $this->t('catalog.filter.free'),
        'accessibile' => $this->t('catalog.filter.accessible'),
        default => null,
    };
    if ($text !== null && !isset($extraQuery[$key])) {
        $labels[$key] = $text;
    }
}
$icons = ['categoria' => 'layers', 'comune' => 'map-pin', 'lingua' => 'languages', 'tipo_ente' => 'building-2', 'modalita' => 'route', 'mediazione' => 'messages-square', 'gratuito' => 'badge-euro', 'accessibile' => 'accessibility'];

echo $this->partial('filter-chips', [
    'labels' => $labels, 'icons' => $icons, 'query' => $query, 'route' => $route, 'routeParams' => $routeParams, 'extraQuery' => $extraQuery,
]);
