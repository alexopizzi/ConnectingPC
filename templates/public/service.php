<?php
/**
 * Scheda servizio (vault "65"): cosa è → per chi → dove e quando → contatti → cosa portare → lingue → dettagli.
 *
 * @var App\Core\View $this
 * @var array<string, mixed> $service
 */
$texts = $service['texts'];
$hasFallback = count(array_filter($texts, static fn (?array $t): bool => $t !== null && $t['fallback'])) > 0;
$languageModes = [];
foreach ($service['languages'] as $row) {
    $languageModes[$row['language_code']][] = $row['mode'];
}
?>
<article class="container section service-page">
    <p class="breadcrumb">
        <a href="<?= $this->e($this->route('public.home')) ?>"><?= $this->e($this->t('nav.home')) ?></a>
        <?php foreach ($service['needs'] as $need): ?>
            · <a href="<?= $this->e($this->route('public.need', ['code' => $need['code']])) ?>"><?= $this->localized($need['label']) ?></a>
        <?php endforeach; ?>
    </p>

    <h1><?= $this->localized($texts['name']) ?></h1>
    <p class="lead-org">
        <?= $this->e($this->t('catalog.service.offered_by')) ?>
        <a href="<?= $this->e($this->route('public.organization', ['id' => (int) $service['organization_id']])) ?>"><?= $this->e($service['organization_name']) ?></a>
    </p>

    <?php if ($hasFallback): ?>
        <div class="alert alert--info" role="note"><?= $this->e($this->t('translation.partial_notice')) ?></div>
    <?php endif; ?>

    <?php if ($texts['summary'] !== null): ?>
        <p class="lead"><?= $this->localized($texts['summary']) ?></p>
    <?php endif; ?>

    <ul class="badges">
        <?php foreach ($service['categories'] as $category): ?>
            <li class="badge"><?= $this->localized($category['name']) ?></li>
        <?php endforeach; ?>
        <li class="badge<?= $service['cost_type'] === 'free' ? ' badge--success' : '' ?>"><?= $this->e($this->t('catalog.cost.' . $service['cost_type'])) ?></li>
    </ul>

    <?php if ($texts['target_audience'] !== null || $service['territories'] !== []): ?>
        <section class="service-section">
            <h2><?= $this->e($this->t('catalog.service.for_whom')) ?></h2>
            <?= $this->richText($texts['target_audience']) ?>
            <?php if ($service['territories'] !== []): ?>
                <p><?= $this->e($this->t('catalog.service.territory_only', ['places' => implode(', ', $service['territories'])])) ?></p>
            <?php endif; ?>
        </section>
    <?php endif; ?>

    <section class="service-section">
        <h2><?= $this->e($this->t('catalog.service.where_when')) ?></h2>
        <?php if ($service['sites'] === []): ?>
            <p><?= $this->e($this->t('catalog.service.no_site')) ?></p>
        <?php endif; ?>
        <?php foreach ($service['sites'] as $site): ?>
            <?= $this->partial('site', ['site' => $site]) ?>
        <?php endforeach; ?>
        <?php if ($service['online_url'] !== null): ?>
            <p><a class="button button--secondary" href="<?= $this->e($service['online_url']) ?>" rel="noopener noreferrer" target="_blank"><?= $this->e($this->t('catalog.service.online')) ?></a></p>
        <?php endif; ?>
    </section>

    <?php if ($service['contacts'] !== []): ?>
        <section class="service-section">
            <h2><?= $this->e($this->t('catalog.service.contacts')) ?></h2>
            <?= $this->partial('contact-list', ['contacts' => $service['contacts']]) ?>
        </section>
    <?php endif; ?>

    <?php if ($texts['requirements'] !== null || $texts['documents'] !== null): ?>
        <section class="service-section">
            <h2><?= $this->e($this->t('catalog.service.what_you_need')) ?></h2>
            <?php if ($texts['requirements'] !== null): ?>
                <h3><?= $this->e($this->t('catalog.service.requirements')) ?></h3>
                <?= $this->richText($texts['requirements']) ?>
            <?php endif; ?>
            <?php if ($texts['documents'] !== null): ?>
                <h3><?= $this->e($this->t('catalog.service.documents')) ?></h3>
                <?= $this->richText($texts['documents']) ?>
            <?php endif; ?>
        </section>
    <?php endif; ?>

    <section class="service-section">
        <h2><?= $this->e($this->t('catalog.service.how_to_access')) ?></h2>
        <ul class="badges">
            <?php foreach ($service['access_modes'] as $mode): ?>
                <li class="badge"><?= $this->e($this->t('catalog.access.' . $mode)) ?></li>
            <?php endforeach; ?>
            <li class="badge"><?= $this->e($this->t('catalog.booking.' . $service['booking'])) ?></li>
        </ul>
        <?= $this->richText($texts['access_info']) ?>
        <?= $this->richText($texts['booking_info']) ?>
    </section>

    <?php if ($texts['cost_info'] !== null): ?>
        <section class="service-section">
            <h2><?= $this->e($this->t('catalog.service.costs')) ?></h2>
            <?= $this->richText($texts['cost_info']) ?>
        </section>
    <?php endif; ?>

    <section class="service-section">
        <h2><?= $this->e($this->t('catalog.service.languages')) ?></h2>
        <?php if ($languageModes === []): ?>
            <p><?= $this->e($this->t('catalog.service.italian_only')) ?></p>
        <?php else: ?>
            <ul>
                <?php foreach ($languageModes as $code => $modes): ?>
                    <li><strong><?= $this->e($this->languageName($code)) ?></strong> —
                        <?= $this->e(implode(', ', array_map(fn (string $m): string => $this->t('catalog.language_mode.' . $m), $modes))) ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
        <p><?= $this->e($this->t('catalog.mediation.' . $service['mediation'])) ?></p>
    </section>

    <?php if ($texts['description'] !== null): ?>
        <section class="service-section">
            <h2><?= $this->e($this->t('catalog.service.details')) ?></h2>
            <?= $this->richText($texts['description']) ?>
            <?= $this->richText($texts['notes']) ?>
        </section>
    <?php endif; ?>

    <?php if ($service['verified_at'] !== null): ?>
        <p class="muted"><?= $this->e($this->t('catalog.verified_on', ['date' => $this->datetime((string) $service['verified_at'], \IntlDateFormatter::LONG, \IntlDateFormatter::NONE)])) ?></p>
    <?php endif; ?>
</article>
