<?php
/**
 * Mediatori (vault "63", D-040): pagina pubblica e pagina per gli operatori dell'area riservata.
 * I dati arrivano già filtrati per visibilità dal MediatorRepository; qui si decide solo cosa mostrare.
 * Struttura come gli altri elenchi (D-038): intestazione con scorciatoie per lingua, filtri laterali,
 * filtri attivi rimovibili, schede in griglia (profili con consenso, poi forma aggregata per organizzazione).
 *
 * @var App\Core\View $this
 * @var string $pageTitle
 * @var list<array<string, mixed>> $profiles
 * @var list<array<string, mixed>> $groups
 * @var array<string, mixed> $filters
 * @var array<string, string> $query
 * @var list<array<string, mixed>> $domains
 * @var list<string> $languages
 * @var list<array<string, mixed>> $municipalities
 * @var 'public'|'operators' $audience
 * @var string $formRoute
 */
$activeCount = count(array_filter([$filters['language'], $filters['type'], $filters['domain'], $filters['territory'], $filters['available'] ?: null]));
$availability = [
    'available' => ['badge--success', 'circle-check'],
    'limited' => ['badge--warning', 'clock'],
    'unavailable' => ['badge--danger', 'x'],
    'unknown' => ['', 'circle-help'],
];

$domainNames = array_column(array_map(static fn (array $d): array => ['code' => $d['code'], 'name' => $d['name']['text']], $domains), 'name', 'code');
$territoryNames = [];
foreach ($municipalities as $district) {
    $territoryNames[$district['id']] = $this->t('catalog.filter.whole_district', ['district' => $district['name']]);
    foreach ($district['municipalities'] as $town) {
        $territoryNames[$town['id']] = $town['name'];
    }
}
$labels = array_filter([
    'lingua' => $filters['language'] !== null ? $this->languageName($filters['language']) : null,
    'tipo' => $filters['type'] !== null ? $this->t('mediators.type.' . $filters['type']) : null,
    'ambito' => $filters['domain'] !== null ? ($domainNames[$filters['domain']] ?? null) : null,
    'comune' => $filters['territory'] !== null ? ($territoryNames[$filters['territory']] ?? null) : null,
    'disponibili' => $filters['available'] ? $this->t('mediators.filter.available') : null,
]);

// Scorciatoie per lingua, nel nome italiano/della pagina e nella grafia della lingua stessa
$quick = [];
if ($filters['language'] === null) {
    foreach ($languages as $code) {
        $quick[$code] = $this->languageName($code);
    }
    asort($quick, SORT_LOCALE_STRING);
}

$initials = static function (string $name): string {
    $parts = preg_split('/\s+/u', trim($name)) ?: [];
    $letters = array_map(static fn (string $p): string => mb_strtoupper(mb_substr($p, 0, 1)), array_slice($parts, 0, 2));

    return implode('', $letters);
};
?>
<header class="page-head">
    <div class="container">
        <nav class="breadcrumb" aria-label="<?= $this->e($this->t('ui.breadcrumb')) ?>">
            <ol>
                <li><a href="<?= $this->e($this->route($audience === 'public' ? 'public.home' : 'portal.dashboard')) ?>"><?= $this->e($this->t($audience === 'public' ? 'nav.home' : 'portal.dashboard.title')) ?></a></li>
                <li><span aria-current="page"><?= $this->e($pageTitle) ?></span></li>
            </ol>
        </nav>
        <h1><span class="page-head__icon"><?= $this->icon('messages-square', 'icon--lg') ?></span><span><?= $this->e($pageTitle) ?></span></h1>
        <p class="lead"><?= $this->e($this->t($audience === 'public' ? 'mediators.intro' : 'mediators.operators.intro')) ?></p>
        <?php if ($audience === 'operators'): ?>
            <div class="alert alert--warning"><?= $this->icon('triangle-alert') ?><span><?= $this->e($this->t('mediators.operators.confidential')) ?></span></div>
        <?php endif; ?>

        <?php if ($quick !== []): ?>
            <div class="quick-communities">
                <h2 class="quick-communities__title" id="quick-languages-title"><?= $this->e($this->t('ui.mediators.quick_title')) ?></h2>
                <ul class="chip-list" aria-labelledby="quick-languages-title">
                    <?php foreach ($quick as $code => $name):
                        $native = Locale::getDisplayLanguage($code, $code); ?>
                        <li>
                            <a class="chip chip--small" href="<?= $this->e($this->route($formRoute, [...$query, 'lingua' => $code])) ?>">
                                <?= $this->icon('languages') ?><span><?= $this->e($name) ?></span>
                                <?php if ($native !== '' && mb_strtolower($native) !== mb_strtolower($name) && $native !== $code): ?>
                                    <span class="chip__native" lang="<?= $this->e($code) ?>"><?= $this->e($native) ?></span>
                                <?php endif; ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
    </div>
</header>

<div class="container catalog-layout">
    <aside class="catalog-layout__aside" aria-label="<?= $this->e($this->t('catalog.filter.title')) ?>">
        <details class="filters-panel" data-filters-panel<?= $activeCount > 0 ? ' open' : '' ?>>
            <summary>
                <?= $this->icon('sliders-horizontal') ?>
                <span><?= $this->e($this->t('catalog.filter.title')) ?></span>
                <?php if ($activeCount > 0): ?>
                    <span class="count-pill"><?= $activeCount ?><span class="visually-hidden"> <?= $this->e($this->t('ui.filters.active_count')) ?></span></span>
                <?php endif; ?>
            </summary>
            <form class="filters" method="get" action="<?= $this->e($this->route($formRoute)) ?>">
                <div class="field">
                    <label for="m-lingua"><?= $this->e($this->t('mediators.filter.language')) ?></label>
                    <select id="m-lingua" name="lingua">
                        <option value=""><?= $this->e($this->t('catalog.filter.any')) ?></option>
                        <?php foreach ($languages as $code): ?>
                            <option value="<?= $this->e($code) ?>"<?= $filters['language'] === $code ? ' selected' : '' ?>><?= $this->e($this->languageName($code)) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="field">
                    <label for="m-tipo"><?= $this->e($this->t('mediators.filter.type')) ?></label>
                    <select id="m-tipo" name="tipo">
                        <option value=""><?= $this->e($this->t('catalog.filter.any')) ?></option>
                        <?php foreach (App\Domain\Mediators\MediatorRepository::TYPES as $type): ?>
                            <option value="<?= $this->e($type) ?>"<?= $filters['type'] === $type ? ' selected' : '' ?>><?= $this->e($this->t('mediators.type.' . $type)) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="field">
                    <label for="m-ambito"><?= $this->e($this->t('mediators.filter.domain')) ?></label>
                    <select id="m-ambito" name="ambito">
                        <option value=""><?= $this->e($this->t('catalog.filter.any')) ?></option>
                        <?php foreach ($domains as $domain): ?>
                            <option value="<?= $this->e($domain['code']) ?>"<?= $filters['domain'] === $domain['code'] ? ' selected' : '' ?>><?= $this->e($domain['name']['text']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?= $this->partial('territory-select', ['id' => 'm-comune', 'selected' => $filters['territory'], 'municipalities' => $municipalities]) ?>
                <label class="checkbox"><input type="checkbox" name="disponibili" value="1"<?= $filters['available'] ? ' checked' : '' ?>> <?= $this->e($this->t('mediators.filter.available')) ?></label>
                <div class="filters__actions">
                    <button class="button" type="submit"><?= $this->icon('check') ?><?= $this->e($this->t('catalog.filter.apply')) ?></button>
                    <a href="<?= $this->e($this->route($formRoute)) ?>"><?= $this->e($this->t('catalog.filter.reset')) ?></a>
                </div>
            </form>
        </details>
    </aside>

    <div class="catalog-layout__main">
        <?= $this->partial('filter-chips', [
            'labels' => $labels,
            'icons' => ['lingua' => 'languages', 'tipo' => 'messages-square', 'ambito' => 'layers', 'comune' => 'map-pin', 'disponibili' => 'circle-check'],
            'query' => $query, 'route' => $formRoute, 'routeParams' => [],
        ]) ?>

        <section class="list-section" aria-labelledby="profiles-title">
            <div class="section-head">
                <div>
                    <h2 id="profiles-title"><?= $this->e($this->t($audience === 'public' ? 'mediators.profiles.title' : 'mediators.operators.profiles')) ?></h2>
                    <p class="results-count" role="status"><?= $this->e($this->t('mediators.results', ['count' => count($profiles)])) ?></p>
                </div>
            </div>
            <?php if ($profiles !== []): ?>
                <ul class="org-grid">
                    <?php foreach ($profiles as $profile):
                        [$availabilityClass, $availabilityIcon] = $availability[$profile['availability']] ?? $availability['unknown']; ?>
                        <li>
                            <article class="card org-card mediator-card">
                                <div class="org-card__head">
                                    <span class="mediator-card__avatar" aria-hidden="true"><?= $this->e($initials((string) $profile['display_name'])) ?></span>
                                    <div>
                                        <h3 class="org-card__title">
                                            <?= $this->e($profile['display_name']) ?>
                                            <?php if ($profile['full_name'] !== null): ?>
                                                <span class="muted">(<?= $this->e($profile['full_name']) ?>)</span>
                                            <?php endif; ?>
                                        </h3>
                                        <p class="org-card__meta">
                                            <span class="badge <?= $availabilityClass ?>"><?= $this->icon($availabilityIcon) ?><?= $this->e($this->t('mediators.availability.' . $profile['availability'])) ?></span>
                                        </p>
                                    </div>
                                </div>

                                <?php if ($profile['languages'] !== []): ?>
                                    <div>
                                        <h4 class="mediator-card__label"><?= $this->e($this->t('mediators.languages')) ?></h4>
                                        <ul class="badges">
                                            <?php foreach ($profile['languages'] as $language): ?>
                                                <li class="badge badge--primary"><?= $this->icon('languages') ?><?= $this->e($this->languageName($language['code'])) ?> · <?= $this->e($this->t('mediators.level.' . $language['level'])) ?></li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                <?php endif; ?>

                                <?php if ($profile['texts']['bio'] !== null): ?>
                                    <p class="org-card__text"><?= $this->localized($profile['texts']['bio']) ?></p>
                                <?php endif; ?>

                                <dl class="mediator-card__facts">
                                    <?php if ($profile['types'] !== []): ?>
                                        <div><dt><?= $this->icon('messages-square') ?><span class="visually-hidden"><?= $this->e($this->t('mediators.filter.type')) ?></span></dt>
                                            <dd><?= $this->e(implode(', ', array_map(fn (string $t): string => $this->t('mediators.type.' . $t), $profile['types']))) ?></dd></div>
                                    <?php endif; ?>
                                    <?php if ($profile['domains'] !== []): ?>
                                        <div><dt><?= $this->icon('layers') ?><span class="visually-hidden"><?= $this->e($this->t('mediators.domains')) ?></span></dt>
                                            <dd><?= $this->e(implode(', ', array_map(static fn (array $d): string => $d['text'], $profile['domains']))) ?></dd></div>
                                    <?php endif; ?>
                                    <?php if ($profile['territories'] !== []): ?>
                                        <div><dt><?= $this->icon('map-pin') ?><span class="visually-hidden"><?= $this->e($this->t('mediators.territories')) ?></span></dt>
                                            <dd><?= $this->e(implode(', ', $profile['territories'])) ?></dd></div>
                                    <?php endif; ?>
                                    <?php if ($profile['organization'] !== null): ?>
                                        <div><dt><?= $this->icon('building-2') ?><span class="visually-hidden"><?= $this->e($this->t('mediators.organization')) ?></span></dt>
                                            <dd><a href="<?= $this->e($this->route('public.organization', ['id' => $profile['organization']['id']])) ?>"><?= $this->e($profile['organization']['name']) ?></a></dd></div>
                                    <?php endif; ?>
                                </dl>

                                <?php if ($profile['contacts'] !== []): ?>
                                    <div class="mediator-card__contacts"><?= $this->partial('contact-list', ['contacts' => $profile['contacts']]) ?></div>
                                <?php endif; ?>
                            </article>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <div class="empty-state">
                    <span class="empty-state__icon"><?= $this->icon('messages-square', 'icon--lg') ?></span>
                    <p><?= $this->e($this->t('mediators.results', ['count' => 0])) ?></p>
                    <?php if ($audience === 'public'): ?>
                        <p class="muted"><?= $this->e($this->t('ui.mediators.try_groups')) ?></p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </section>

        <?php if ($audience === 'public'): ?>
            <section class="list-section" aria-labelledby="groups-title">
                <div class="section-head">
                    <div>
                        <h2 id="groups-title"><?= $this->e($this->t('mediators.groups.title')) ?></h2>
                        <p><?= $this->e($this->t('mediators.groups.intro')) ?></p>
                    </div>
                </div>
                <?php if ($groups === []): ?>
                    <p class="muted"><?= $this->e($this->t('mediators.groups.none')) ?></p>
                <?php else: ?>
                    <ul class="org-grid">
                        <?php foreach ($groups as $group): ?>
                            <li>
                                <article class="card org-card">
                                    <div class="org-card__head">
                                        <span class="org-card__icon"><?= $this->icon('building-2') ?></span>
                                        <div>
                                            <h3 class="org-card__title">
                                                <a href="<?= $this->e($this->route('public.organization', ['id' => $group['organization_id']])) ?>"><?= $this->e($group['organization_name']) ?></a>
                                            </h3>
                                            <p class="org-card__meta"><span><?= $this->icon('users-round') ?><?= $this->e($this->t('mediators.groups.count', ['count' => $group['count']])) ?></span></p>
                                        </div>
                                    </div>
                                    <?php if ($group['languages'] !== []): ?>
                                        <div>
                                            <h4 class="mediator-card__label"><?= $this->e($this->t('mediators.languages')) ?></h4>
                                            <ul class="badges">
                                                <?php foreach ($group['languages'] as $code => $count): ?>
                                                    <li class="badge badge--primary"><?= $this->icon('languages') ?><?= $this->e($this->languageName((string) $code)) ?> · <?= (int) $count ?></li>
                                                <?php endforeach; ?>
                                            </ul>
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($group['domains'] !== []): ?>
                                        <dl class="mediator-card__facts">
                                            <div><dt><?= $this->icon('layers') ?><span class="visually-hidden"><?= $this->e($this->t('mediators.domains')) ?></span></dt>
                                                <dd><?= $this->e(implode(', ', array_map(static fn (array $d): string => $d['text'], $group['domains']))) ?></dd></div>
                                        </dl>
                                    <?php endif; ?>
                                    <div class="mediator-card__contacts">
                                        <p class="mediator-card__label"><?= $this->e($this->t('mediators.groups.contact_org')) ?></p>
                                        <?= $this->partial('contact-list', ['contacts' => $group['contacts']]) ?>
                                    </div>
                                </article>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </section>

            <div class="participate">
                <div>
                    <h2><?= $this->e($this->t('ui.mediators.privacy_title')) ?></h2>
                    <p><?= $this->e($this->t('mediators.privacy_note')) ?> <?= $this->e($this->t('mediators.operators.hint')) ?></p>
                </div>
                <a class="button button--secondary" href="<?= $this->e($this->route('auth.login')) ?>"><?= $this->icon('log-in') ?><?= $this->e($this->t('footer.login')) ?></a>
            </div>
        <?php endif; ?>
    </div>
</div>
