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
if ($labels === []) {
    return;
}
$icons = ['categoria' => 'layers', 'comune' => 'map-pin', 'lingua' => 'languages', 'tipo_ente' => 'building-2', 'modalita' => 'route', 'mediazione' => 'messages-square', 'gratuito' => 'badge-euro', 'accessibile' => 'accessibility'];
?>
<div class="active-filters">
    <span class="active-filters__label" id="active-filters-label"><?= $this->e($this->t('ui.filters.active')) ?></span>
    <ul class="chip-list" aria-labelledby="active-filters-label">
        <?php foreach ($labels as $key => $text):
            $without = array_diff_key($query, [$key => true]); ?>
            <li>
                <a class="chip chip--small chip--active" href="<?= $this->e($this->route($route, [...$routeParams, ...$without])) ?>">
                    <?= $this->icon($icons[$key] ?? 'check') ?>
                    <span><?= $this->e($text) ?></span>
                    <span class="visually-hidden"><?= $this->e($this->t('ui.filters.remove')) ?></span>
                    <?= $this->icon('x') ?>
                </a>
            </li>
        <?php endforeach; ?>
        <?php if (count($labels) > 1): ?>
            <li><a class="chip chip--small" href="<?= $this->e($this->route($route, [...$routeParams, ...array_intersect_key($query, $extraQuery)])) ?>"><?= $this->icon('rotate-ccw') ?><?= $this->e($this->t('catalog.filter.reset')) ?></a></li>
        <?php endif; ?>
    </ul>
</div>
