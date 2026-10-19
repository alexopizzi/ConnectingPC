<?php
/**
 * Mappa statica dei comuni della provincia (SVG generato dai confini ISTAT, D-038).
 * Ogni comune è un collegamento alla mappa filtrata: è una scorciatoia per chi usa il puntatore.
 * Per tastiera e lettori di schermo l'equivalente è il menu "Scegli il tuo comune" accanto
 * (per questo l'SVG è aria-hidden e i collegamenti sono fuori dall'ordine di tabulazione).
 *
 * @var App\Core\View $this
 * @var array{width: float, height: float, towns: array<string, array{name: string, d: string, cx: float, cy: float}>} $shapes
 * @var list<array{id: int, istat: string, name: string, district_id: int}> $towns
 */
if ($shapes['towns'] === []) {
    return;
}
$byIstat = array_column($towns, null, 'istat');
$districtIndex = array_flip(array_values(array_unique(array_column($towns, 'district_id'))));
$capital = $shapes['towns']['033032'] ?? null; // Piacenza
?>
<svg class="province-map" viewBox="0 0 <?= $shapes['width'] ?> <?= $shapes['height'] ?>" aria-hidden="true" focusable="false">
    <?php foreach ($shapes['towns'] as $istat => $shape):
        $town = $byIstat[$istat] ?? null;
        if ($town === null) {
            continue;
        } ?>
        <a href="<?= $this->e($this->route('public.map', ['comune' => $town['id']])) ?>" tabindex="-1" class="province-map__d<?= ($districtIndex[$town['district_id']] ?? 0) + 1 ?>">
            <title><?= $this->e($town['name']) ?></title>
            <path d="<?= $shape['d'] ?>"/>
        </a>
    <?php endforeach; ?>
    <?php if ($capital !== null): ?>
        <circle class="province-map__capital" cx="<?= $capital['cx'] ?>" cy="<?= $capital['cy'] ?>" r="4"/>
        <text class="province-map__label" x="<?= $capital['cx'] + 8 ?>" y="<?= $capital['cy'] + 4 ?>"><?= $this->e($capital['name']) ?></text>
    <?php endif; ?>
</svg>
