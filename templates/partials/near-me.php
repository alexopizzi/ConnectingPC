<?php
/**
 * "Vicino a me": geolocalizzazione facoltativa, solo nel browser (D-006, vault "61").
 * La posizione non viene inviata al server: il calcolo delle distanze avviene in near-me.js.
 * Senza JavaScript il blocco resta nascosto (attributo hidden) e la pagina funziona comunque.
 *
 * @var App\Core\View $this
 */
$centroids = array_values(array_filter((array) $this->shared('municipality_centroids', []), static fn (array $m): bool => $m['lat'] !== null));
?>
<div class="near-me" data-near-me hidden
     data-msg-denied="<?= $this->e($this->t('geo.denied')) ?>"
     data-msg-error="<?= $this->e($this->t('geo.error')) ?>"
     data-msg-sorted="<?= $this->e($this->t('geo.sorted')) ?>"
     data-msg-km="<?= $this->e($this->t('geo.km')) ?>"
     data-msg-searching="<?= $this->e($this->t('geo.searching')) ?>">
    <p class="near-me__intro"><?= $this->e($this->t('geo.intro')) ?></p>
    <div class="near-me__actions">
        <button class="button button--secondary" type="button" data-near-me-locate><span aria-hidden="true">📍</span> <?= $this->e($this->t('geo.use_location')) ?></button>
        <label class="near-me__town">
            <span><?= $this->e($this->t('geo.or_choose_town')) ?></span>
            <select data-near-me-town>
                <option value=""><?= $this->e($this->t('geo.choose')) ?></option>
                <?php foreach ($centroids as $town): ?>
                    <option value="<?= $this->e(sprintf('%.6F,%.6F', $town['lat'], $town['lng'])) ?>"><?= $this->e($town['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
    </div>
    <p class="near-me__status" role="status" aria-live="polite" data-near-me-status></p>
</div>
