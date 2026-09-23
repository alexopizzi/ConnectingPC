<?php
/**
 * @var App\Core\View $this
 * @var array<string, mixed> $service
 */
?>
<article class="card service-card">
    <h3 class="service-card__title">
        <a href="<?= $this->e($this->route('public.service', ['id' => $service['id']])) ?>"><?= $this->localized($service['name']) ?></a>
    </h3>
    <p class="muted"><?= $this->e($service['organization_name']) ?><?= $service['towns'] !== [] ? ' · ' . $this->e(implode(', ', $service['towns'])) : '' ?></p>
    <?php if ($service['summary'] !== null): ?>
        <p><?= $this->localized($service['summary']) ?></p>
    <?php endif; ?>
    <ul class="badges">
        <?php if ($service['category'] !== null): ?>
            <li class="badge"><?= $this->localized($service['category']) ?></li>
        <?php endif; ?>
        <?php if ($service['cost_type'] === 'free'): ?>
            <li class="badge badge--success"><?= $this->e($this->t('catalog.cost.free')) ?></li>
        <?php endif; ?>
        <?php if (in_array($service['mediation'], ['available', 'on_request'], true)): ?>
            <li class="badge"><span aria-hidden="true">💬</span> <?= $this->e($this->t('catalog.mediation.' . $service['mediation'])) ?></li>
        <?php endif; ?>
        <?php if ($service['languages'] !== []): ?>
            <li class="badge"><span aria-hidden="true">🗣️</span> <?= $this->e(implode(', ', array_map($this->languageName(...), $service['languages']))) ?></li>
        <?php endif; ?>
    </ul>
</article>
