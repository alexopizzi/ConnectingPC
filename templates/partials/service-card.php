<?php
/**
 * Scheda sintetica di un servizio negli elenchi: tutta la scheda è cliccabile tramite il titolo
 * (un solo collegamento, nome accessibile = nome del servizio).
 *
 * @var App\Core\View $this
 * @var array<string, mixed> $service
 * @var string|null $headingLevel
 */
$h = $headingLevel ?? 'h3';
?>
<article class="card service-card">
    <?php if ($service['category'] !== null): ?>
        <p class="service-card__top"><span class="service-card__category"><?= $this->icon((string) ($service['category_icon'] ?? 'default')) ?><?= $this->localized($service['category']) ?></span></p>
    <?php endif; ?>
    <<?= $h ?> class="service-card__title">
        <a class="service-card__link" href="<?= $this->e($this->route('public.service', ['id' => $service['id']])) ?>"><?= $this->localized($service['name']) ?></a>
    </<?= $h ?>>
    <p class="service-card__meta">
        <span><?= $this->icon('building-2') ?><?= $this->e($service['organization_name']) ?></span>
        <?php if ($service['towns'] !== []): ?>
            <span><?= $this->icon('map-pin') ?><?= $this->e(implode(', ', $service['towns'])) ?><span class="distance" data-distance></span></span>
        <?php endif; ?>
    </p>
    <?php if ($service['summary'] !== null): ?>
        <p class="service-card__summary"><?= $this->localized($service['summary']) ?></p>
    <?php endif; ?>
    <?php
    $hasBadges = $service['cost_type'] === 'free' || in_array($service['mediation'], ['available', 'on_request'], true) || $service['languages'] !== [];
    if ($hasBadges): ?>
        <ul class="badges">
            <?php if ($service['cost_type'] === 'free'): ?>
                <li class="badge badge--success"><?= $this->icon('badge-euro') ?><?= $this->e($this->t('catalog.cost.free')) ?></li>
            <?php endif; ?>
            <?php if (in_array($service['mediation'], ['available', 'on_request'], true)): ?>
                <li class="badge badge--primary"><?= $this->icon('messages-square') ?><?= $this->e($this->t('catalog.mediation.' . $service['mediation'])) ?></li>
            <?php endif; ?>
            <?php if ($service['languages'] !== []): ?>
                <li class="badge"><?= $this->icon('languages') ?><?= $this->e(implode(', ', array_map($this->languageName(...), $service['languages']))) ?></li>
            <?php endif; ?>
        </ul>
    <?php endif; ?>
</article>
