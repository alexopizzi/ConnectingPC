<?php
/**
 * Mediatori (vault "63"): pagina pubblica e pagina per gli operatori dell'area riservata.
 * I dati arrivano già filtrati per visibilità dal MediatorRepository; qui si decide solo cosa mostrare.
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
$availabilityBadge = ['available' => 'badge--success', 'limited' => 'badge--warning', 'unavailable' => 'badge--danger', 'unknown' => ''];
?>
<div class="container section">
    <h1><?= $this->e($pageTitle) ?></h1>
    <p class="lead"><?= $this->e($this->t($audience === 'public' ? 'mediators.intro' : 'mediators.operators.intro')) ?></p>
    <?php if ($audience === 'operators'): ?>
        <p class="alert alert--warning"><?= $this->e($this->t('mediators.operators.confidential')) ?></p>
    <?php endif; ?>

    <details class="filters-panel"<?= $activeCount > 0 ? ' open' : '' ?>>
        <summary><?= $this->e($this->t('catalog.filter.title')) ?><?= $activeCount > 0 ? ' (' . $activeCount . ')' : '' ?></summary>
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
                <button class="button" type="submit"><?= $this->e($this->t('catalog.filter.apply')) ?></button>
                <a href="<?= $this->e($this->route($formRoute)) ?>"><?= $this->e($this->t('catalog.filter.reset')) ?></a>
            </div>
        </form>
    </details>

    <section class="service-section" aria-labelledby="profiles-title">
        <h2 id="profiles-title"><?= $this->e($this->t($audience === 'public' ? 'mediators.profiles.title' : 'mediators.operators.profiles')) ?></h2>
        <p class="results-count" role="status"><?= $this->e($this->t('mediators.results', ['count' => count($profiles)])) ?></p>
        <?php if ($profiles !== []): ?>
            <ul class="card-list">
                <?php foreach ($profiles as $profile): ?>
                    <li>
                        <article class="card">
                            <h3 class="service-card__title">
                                <?= $this->e($profile['display_name']) ?>
                                <?php if ($profile['full_name'] !== null): ?>
                                    <span class="muted">(<?= $this->e($profile['full_name']) ?>)</span>
                                <?php endif; ?>
                            </h3>
                            <ul class="badges">
                                <li class="badge <?= $availabilityBadge[$profile['availability']] ?? '' ?>"><?= $this->e($this->t('mediators.availability.' . $profile['availability'])) ?></li>
                                <?php foreach ($profile['types'] as $type): ?>
                                    <li class="badge"><?= $this->e($this->t('mediators.type.' . $type)) ?></li>
                                <?php endforeach; ?>
                            </ul>
                            <?php if ($profile['texts']['bio'] !== null): ?>
                                <p><?= $this->localized($profile['texts']['bio']) ?></p>
                            <?php endif; ?>
                            <dl class="facts">
                                <?php if ($profile['languages'] !== []): ?>
                                    <dt><?= $this->e($this->t('mediators.languages')) ?></dt>
                                    <dd><?= $this->e(implode(', ', array_map(fn (array $l): string => $this->languageName($l['code']) . ' (' . $this->t('mediators.level.' . $l['level']) . ')', $profile['languages']))) ?></dd>
                                <?php endif; ?>
                                <?php if ($profile['domains'] !== []): ?>
                                    <dt><?= $this->e($this->t('mediators.domains')) ?></dt>
                                    <dd><?= $this->e(implode(', ', array_map(static fn (array $d): string => $d['text'], $profile['domains']))) ?></dd>
                                <?php endif; ?>
                                <?php if ($profile['territories'] !== []): ?>
                                    <dt><?= $this->e($this->t('mediators.territories')) ?></dt>
                                    <dd><?= $this->e(implode(', ', $profile['territories'])) ?></dd>
                                <?php endif; ?>
                                <?php if ($profile['organization'] !== null): ?>
                                    <dt><?= $this->e($this->t('mediators.organization')) ?></dt>
                                    <dd><a href="<?= $this->e($this->route('public.organization', ['id' => $profile['organization']['id']])) ?>"><?= $this->e($profile['organization']['name']) ?></a></dd>
                                <?php endif; ?>
                            </dl>
                            <?= $this->partial('contact-list', ['contacts' => $profile['contacts']]) ?>
                        </article>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>

    <?php if ($audience === 'public'): ?>
        <section class="service-section" aria-labelledby="groups-title">
            <h2 id="groups-title"><?= $this->e($this->t('mediators.groups.title')) ?></h2>
            <p><?= $this->e($this->t('mediators.groups.intro')) ?></p>
            <?php if ($groups === []): ?>
                <p class="muted"><?= $this->e($this->t('mediators.groups.none')) ?></p>
            <?php else: ?>
                <ul class="card-list">
                    <?php foreach ($groups as $group): ?>
                        <li>
                            <article class="card">
                                <h3 class="service-card__title">
                                    <a href="<?= $this->e($this->route('public.organization', ['id' => $group['organization_id']])) ?>"><?= $this->e($group['organization_name']) ?></a>
                                </h3>
                                <p><?= $this->e($this->t('mediators.groups.count', ['count' => $group['count']])) ?></p>
                                <dl class="facts">
                                    <?php if ($group['languages'] !== []): ?>
                                        <dt><?= $this->e($this->t('mediators.languages')) ?></dt>
                                        <dd><?= $this->e(implode(', ', array_map(fn (string $code, int $n): string => $this->languageName($code) . ' (' . $n . ')', array_keys($group['languages']), $group['languages']))) ?></dd>
                                    <?php endif; ?>
                                    <?php if ($group['domains'] !== []): ?>
                                        <dt><?= $this->e($this->t('mediators.domains')) ?></dt>
                                        <dd><?= $this->e(implode(', ', array_map(static fn (array $d): string => $d['text'], $group['domains']))) ?></dd>
                                    <?php endif; ?>
                                </dl>
                                <p class="muted"><?= $this->e($this->t('mediators.groups.contact_org')) ?></p>
                                <?= $this->partial('contact-list', ['contacts' => $group['contacts']]) ?>
                            </article>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </section>

        <div class="callout">
            <p><?= $this->e($this->t('mediators.privacy_note')) ?></p>
            <p><?= $this->e($this->t('mediators.operators.hint')) ?></p>
        </div>
    <?php else: ?>
        <p><a href="<?= $this->e($this->route('portal.dashboard')) ?>"><?= $this->e($this->t('portal.dashboard.title')) ?></a></p>
    <?php endif; ?>
</div>
