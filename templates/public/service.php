<?php
/**
 * Scheda servizio (vault "65", D-038): in alto cosa è e le azioni immediate (chiama, scrivi, portami qui);
 * poi per chi → cosa serve → come accedere → costi → lingue → dettagli; a lato dove e quando, contatti.
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

// Azioni immediate: primo telefono, prima email, prima sede con coordinate (tutti recapiti pubblici)
$allContacts = [...$service['contacts'], ...array_merge(...array_map(static fn (array $s): array => $s['contacts'], $service['sites'] ?: [['contacts' => []]]))];
$firstOf = static function (array $kinds) use ($allContacts): ?array {
    foreach ($allContacts as $contact) {
        if (in_array($contact['kind'], $kinds, true)) {
            return $contact;
        }
    }

    return null;
};
$phone = $firstOf(['phone', 'mobile']);
$email = $firstOf(['email']);
$located = array_values(array_filter($service['sites'], static fn (array $s): bool => $s['lat'] !== null && $s['lng'] !== null));
?>
<article class="service-page">
    <header class="detail-head">
        <div class="container">
            <nav class="breadcrumb" aria-label="<?= $this->e($this->t('ui.breadcrumb')) ?>">
                <ol>
                    <li><a href="<?= $this->e($this->route('public.home')) ?>"><?= $this->e($this->t('nav.home')) ?></a></li>
                    <?php if (($need = $service['needs'][0] ?? null) !== null): ?>
                        <li><a href="<?= $this->e($this->route('public.need', ['code' => $need['code']])) ?>"><?= $this->localized($need['label']) ?></a></li>
                    <?php else: ?>
                        <li><a href="<?= $this->e($this->route('public.services')) ?>"><?= $this->e($this->t('nav.services')) ?></a></li>
                    <?php endif; ?>
                    <li><span aria-current="page"><?= $this->localized($texts['name']) ?></span></li>
                </ol>
            </nav>

            <ul class="badges">
                <?php foreach ($service['categories'] as $category): ?>
                    <li class="badge badge--primary"><?= $this->localized($category['name']) ?></li>
                <?php endforeach; ?>
                <li class="badge<?= $service['cost_type'] === 'free' ? ' badge--success' : '' ?>"><?= $this->icon('badge-euro') ?><?= $this->e($this->t('catalog.cost.' . $service['cost_type'])) ?></li>
                <?php if (in_array($service['mediation'], ['available', 'on_request'], true)): ?>
                    <li class="badge badge--primary"><?= $this->icon('messages-square') ?><?= $this->e($this->t('catalog.mediation.' . $service['mediation'])) ?></li>
                <?php endif; ?>
            </ul>

            <h1><?= $this->localized($texts['name']) ?></h1>
            <p class="lead-org">
                <?= $this->e($this->t('catalog.service.offered_by')) ?>
                <a href="<?= $this->e($this->route('public.organization', ['id' => (int) $service['organization_id']])) ?>"><?= $this->e($service['organization_name']) ?></a>
            </p>
            <?php if ($texts['summary'] !== null): ?>
                <p class="lead"><?= $this->localized($texts['summary']) ?></p>
            <?php endif; ?>

            <?php if ($phone !== null || $email !== null || $located !== [] || $service['online_url'] !== null): ?>
                <div class="quick-actions">
                    <?php if ($phone !== null): ?>
                        <a class="button" href="tel:<?= $this->e(preg_replace('/[^0-9+]/', '', $phone['value'])) ?>"><?= $this->icon('phone') ?><?= $this->e($this->t('ui.service.call')) ?> <span class="ltr"><?= $this->e($phone['value']) ?></span></a>
                    <?php endif; ?>
                    <?php if ($located !== []):
                        $coords = sprintf('%.6F,%.6F', $located[0]['lat'], $located[0]['lng']); ?>
                        <a class="button button--secondary" href="<?= $this->e('https://www.openstreetmap.org/directions?route=%3B' . rawurlencode($coords)) ?>" rel="noopener noreferrer" target="_blank"><?= $this->icon('navigation') ?><?= $this->e($this->t('catalog.site.take_me')) ?></a>
                    <?php endif; ?>
                    <?php if ($email !== null): ?>
                        <a class="button button--secondary" href="mailto:<?= $this->e($email['value']) ?>"><?= $this->icon('mail') ?><?= $this->e($this->t('ui.service.write')) ?></a>
                    <?php endif; ?>
                    <?php if ($service['online_url'] !== null): ?>
                        <a class="button button--secondary" href="<?= $this->e($service['online_url']) ?>" rel="noopener noreferrer" target="_blank"><?= $this->icon('laptop') ?><?= $this->e($this->t('catalog.service.online')) ?></a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </header>

    <div class="container detail-layout">
        <div class="detail-layout__main">
            <?php if ($hasFallback): ?>
                <div class="alert alert--info" role="note"><?= $this->icon('info') ?><span><?= $this->e($this->t('translation.partial_notice')) ?></span></div>
            <?php endif; ?>

            <?php if ($texts['target_audience'] !== null || $service['territories'] !== []): ?>
                <section class="service-section">
                    <h2><?= $this->icon('user-round') ?><?= $this->e($this->t('catalog.service.for_whom')) ?></h2>
                    <div class="rich-text"><?= $this->richText($texts['target_audience']) ?></div>
                    <?php if ($service['territories'] !== []): ?>
                        <p><?= $this->e($this->t('catalog.service.territory_only', ['places' => implode(', ', $service['territories'])])) ?></p>
                    <?php endif; ?>
                </section>
            <?php endif; ?>

            <?php if ($texts['requirements'] !== null || $texts['documents'] !== null): ?>
                <section class="service-section">
                    <h2><?= $this->icon('file-text') ?><?= $this->e($this->t('catalog.service.what_you_need')) ?></h2>
                    <?php if ($texts['requirements'] !== null): ?>
                        <h3><?= $this->e($this->t('catalog.service.requirements')) ?></h3>
                        <div class="rich-text"><?= $this->richText($texts['requirements']) ?></div>
                    <?php endif; ?>
                    <?php if ($texts['documents'] !== null): ?>
                        <h3><?= $this->e($this->t('catalog.service.documents')) ?></h3>
                        <div class="rich-text"><?= $this->richText($texts['documents']) ?></div>
                    <?php endif; ?>
                </section>
            <?php endif; ?>

            <section class="service-section">
                <h2><?= $this->icon('route') ?><?= $this->e($this->t('catalog.service.how_to_access')) ?></h2>
                <ul class="badges">
                    <?php foreach ($service['access_modes'] as $mode): ?>
                        <li class="badge"><?= $this->e($this->t('catalog.access.' . $mode)) ?></li>
                    <?php endforeach; ?>
                    <li class="badge"><?= $this->icon('calendar-clock') ?><?= $this->e($this->t('catalog.booking.' . $service['booking'])) ?></li>
                </ul>
                <div class="rich-text"><?= $this->richText($texts['access_info']) ?><?= $this->richText($texts['booking_info']) ?></div>
            </section>

            <?php if ($texts['cost_info'] !== null): ?>
                <section class="service-section">
                    <h2><?= $this->icon('badge-euro') ?><?= $this->e($this->t('catalog.service.costs')) ?></h2>
                    <div class="rich-text"><?= $this->richText($texts['cost_info']) ?></div>
                </section>
            <?php endif; ?>

            <section class="service-section">
                <h2><?= $this->icon('languages') ?><?= $this->e($this->t('catalog.service.languages')) ?></h2>
                <?php if ($languageModes === []): ?>
                    <p><?= $this->e($this->t('catalog.service.italian_only')) ?></p>
                <?php else: ?>
                    <ul class="lang-list">
                        <?php foreach ($languageModes as $code => $modes): ?>
                            <li><strong><?= $this->e($this->languageName($code)) ?></strong>
                                <span class="muted"><?= $this->e(implode(', ', array_map(fn (string $m): string => $this->t('catalog.language_mode.' . $m), $modes))) ?></span></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
                <p><?= $this->e($this->t('catalog.mediation.' . $service['mediation'])) ?></p>
            </section>

            <?php if ($texts['description'] !== null): ?>
                <section class="service-section">
                    <h2><?= $this->icon('info') ?><?= $this->e($this->t('catalog.service.details')) ?></h2>
                    <div class="rich-text"><?= $this->richText($texts['description']) ?><?= $this->richText($texts['notes']) ?></div>
                </section>
            <?php endif; ?>

            <?php if ($service['verified_at'] !== null): ?>
                <p class="verified"><?= $this->icon('circle-check') ?><?= $this->e($this->t('catalog.verified_on', ['date' => $this->datetime((string) $service['verified_at'], \IntlDateFormatter::LONG, \IntlDateFormatter::NONE)])) ?></p>
            <?php endif; ?>
        </div>

        <aside class="detail-layout__aside" aria-labelledby="where-title">
            <h2 id="where-title"><?= $this->e($this->t('catalog.service.where_when')) ?></h2>
            <?php if ($service['sites'] === []): ?>
                <p><?= $this->e($this->t('catalog.service.no_site')) ?></p>
            <?php endif; ?>
            <?php foreach ($service['sites'] as $site): ?>
                <?= $this->partial('site', ['site' => $site]) ?>
            <?php endforeach; ?>
            <?php if ($service['contacts'] !== []): ?>
                <div class="site-card">
                    <h3><?= $this->e($this->t('catalog.service.contacts')) ?></h3>
                    <?= $this->partial('contact-list', ['contacts' => $service['contacts']]) ?>
                </div>
            <?php endif; ?>
        </aside>
    </div>
</article>
