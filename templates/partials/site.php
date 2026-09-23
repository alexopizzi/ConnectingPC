<?php
/**
 * Sede con indirizzo, orari (con "aperto ora"), accessibilità, contatti e collegamenti ai navigatori.
 *
 * @var App\Core\View $this
 * @var array<string, mixed> $site
 */
$status = $this->openingStatus($site['hours']);
$byDay = [];
foreach ($site['hours'] as $slot) {
    $byDay[$slot['weekday']][] = $slot;
}
?>
<div class="site-card">
    <h3><?= $this->e($site['name'] ?? $site['town']) ?></h3>
    <?php if ($site['address_line'] !== null): ?>
        <p class="site-card__address"><span aria-hidden="true">📍</span> <?= $this->e($site['address_line']) ?>, <?= $this->e(trim(($site['postal_code'] ?? '') . ' ' . $site['town'])) ?></p>
    <?php else: ?>
        <p class="site-card__address"><span aria-hidden="true">📍</span> <?= $this->e($site['town']) ?> — <?= $this->e($this->t('catalog.site.address_on_request')) ?></p>
    <?php endif; ?>

    <?php if ($status !== null): ?>
        <p class="opening-status <?= $status['open'] ? 'is-open' : 'is-closed' ?>">
            <?= $this->e($status['open'] ? $this->t('catalog.hours.open_until', ['time' => $status['until']]) : $this->t('catalog.hours.closed_now')) ?>
        </p>
    <?php endif; ?>

    <?php if ($byDay !== []): ?>
        <table class="table hours-table">
            <caption class="visually-hidden"><?= $this->e($this->t('catalog.hours.caption')) ?></caption>
            <tbody>
            <?php foreach ($byDay as $day => $slots): ?>
                <tr>
                    <th scope="row"><?= $this->e($this->weekdayName((int) $day)) ?></th>
                    <td class="ltr">
                        <?= $this->e(implode(' · ', array_map(static fn (array $s): string => $s['opens'] . '–' . $s['closes'], $slots))) ?>
                        <?php if (in_array(true, array_column($slots, 'appointment'), true)): ?>
                            <span class="badge"><?= $this->e($this->t('catalog.hours.by_appointment')) ?></span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <?php if (($site['texts']['directions'] ?? null) !== null): ?>
        <p><strong><?= $this->e($this->t('catalog.site.directions')) ?>:</strong> <?= $this->localized($site['texts']['directions']) ?></p>
    <?php endif; ?>
    <?php if ($site['step_free_access'] !== 'unknown'): ?>
        <p><span aria-hidden="true">♿</span> <?= $this->e($this->t('catalog.site.step_free.' . $site['step_free_access'])) ?></p>
    <?php endif; ?>

    <?= $this->partial('contact-list', ['contacts' => $site['contacts']]) ?>

    <?php if ($site['lat'] !== null && $site['lng'] !== null):
        $coords = sprintf('%.6F,%.6F', $site['lat'], $site['lng']); ?>
        <p class="site-card__actions">
            <a class="button" href="<?= $this->e('https://www.openstreetmap.org/directions?route=%3B' . rawurlencode($coords)) ?>" rel="noopener noreferrer" target="_blank"><span aria-hidden="true">🧭</span> <?= $this->e($this->t('catalog.site.take_me')) ?></a>
            <a class="button button--secondary" href="<?= $this->e('geo:' . $coords . '?q=' . rawurlencode($coords)) ?>"><?= $this->e($this->t('catalog.site.open_maps_app')) ?></a>
        </p>
    <?php endif; ?>
</div>
