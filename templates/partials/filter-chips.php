<?php
/**
 * Filtri attivi come etichette rimovibili: ognuna è un collegamento alla stessa pagina senza quel filtro.
 * Usato dal catalogo (active-filters) e dalla directory delle associazioni.
 *
 * @var App\Core\View $this
 * @var array<string, string> $labels parametro => testo dell'etichetta
 * @var array<string, string> $icons parametro => icona
 * @var array<string, string> $query parametri correnti
 * @var string $route
 * @var array<string, string> $routeParams
 * @var array<string, string>|null $extraQuery parametri da mantenere sempre (es. "q" nella ricerca)
 */
$extraQuery ??= [];
if ($labels === []) {
    return;
}
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
