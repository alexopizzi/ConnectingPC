<?php
/**
 * Directory "Associazioni e comunità" (vault "64").
 *
 * @var App\Core\View $this
 * @var string $pageTitle
 * @var list<array<string, mixed>> $organizations
 * @var int $total
 * @var int $page
 * @var int $pages
 * @var array<string, mixed> $filters
 * @var array<string, string> $query
 * @var list<array<string, mixed>> $communities
 * @var list<string> $countries
 * @var list<string> $languages
 * @var list<array<string, mixed>> $types
 * @var list<array<string, mixed>> $municipalities
 */
$activeCount = count(array_filter([$filters['language'], $filters['community'], $filters['country'], $filters['territory'], $filters['type'], $filters['community_based'] ?: null]));
$countryNames = array_combine($countries, array_map($this->countryName(...), $countries));
asort($countryNames, SORT_LOCALE_STRING);
?>
<div class="container section">
    <h1><?= $this->e($pageTitle) ?></h1>
    <p class="lead"><?= $this->e($this->t('communities.intro')) ?></p>

    <form class="search-form" method="get" action="<?= $this->e($this->route('public.communities')) ?>" role="search">
        <label class="search-form__label" for="c-q"><?= $this->e($this->t('communities.search.label')) ?></label>
        <div class="search-form__row">
            <input class="search-form__input" id="c-q" type="search" name="q" value="<?= $this->e($filters['q']) ?>"
                   placeholder="<?= $this->e($this->t('communities.search.placeholder')) ?>" autocomplete="off">
            <button class="button search-form__submit" type="submit"><?= $this->e($this->t('search.submit')) ?></button>
        </div>
        <?php foreach (array_diff_key($query, ['q' => true]) as $name => $value): ?>
            <input type="hidden" name="<?= $this->e($name) ?>" value="<?= $this->e($value) ?>">
        <?php endforeach; ?>
    </form>

    <details class="filters-panel"<?= $activeCount > 0 ? ' open' : '' ?>>
        <summary><?= $this->e($this->t('catalog.filter.title')) ?><?= $activeCount > 0 ? ' (' . $activeCount . ')' : '' ?></summary>
        <form class="filters" method="get" action="<?= $this->e($this->route('public.communities')) ?>">
            <?php if ($filters['q'] !== ''): ?>
                <input type="hidden" name="q" value="<?= $this->e($filters['q']) ?>">
            <?php endif; ?>
            <div class="field">
                <label for="c-comunita"><?= $this->e($this->t('communities.filter.community')) ?></label>
                <select id="c-comunita" name="comunita">
                    <option value=""><?= $this->e($this->t('catalog.filter.any')) ?></option>
                    <?php foreach ($communities as $community): ?>
                        <option value="<?= $this->e($community['code']) ?>"<?= $filters['community'] === $community['code'] ? ' selected' : '' ?>><?= $this->e($community['name']['text']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field">
                <label for="c-paese"><?= $this->e($this->t('communities.filter.country')) ?></label>
                <select id="c-paese" name="paese">
                    <option value=""><?= $this->e($this->t('catalog.filter.any')) ?></option>
                    <?php foreach ($countryNames as $code => $name): ?>
                        <option value="<?= $this->e($code) ?>"<?= $filters['country'] === $code ? ' selected' : '' ?>><?= $this->e($name) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field">
                <label for="c-lingua"><?= $this->e($this->t('catalog.filter.language')) ?></label>
                <select id="c-lingua" name="lingua">
                    <option value=""><?= $this->e($this->t('catalog.filter.any')) ?></option>
                    <?php foreach ($languages as $code): ?>
                        <option value="<?= $this->e($code) ?>"<?= $filters['language'] === $code ? ' selected' : '' ?>><?= $this->e($this->languageName($code)) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?= $this->partial('territory-select', ['id' => 'c-comune', 'selected' => $filters['territory'], 'municipalities' => $municipalities]) ?>
            <div class="field">
                <label for="c-tipo"><?= $this->e($this->t('communities.filter.type')) ?></label>
                <select id="c-tipo" name="tipo">
                    <option value=""><?= $this->e($this->t('catalog.filter.any')) ?></option>
                    <?php foreach ($types as $type): ?>
                        <option value="<?= $this->e($type['code']) ?>"<?= $filters['type'] === $type['code'] ? ' selected' : '' ?>><?= $this->e($type['name']['text']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <label class="checkbox"><input type="checkbox" name="stranieri" value="1"<?= $filters['community_based'] ? ' checked' : '' ?>> <?= $this->e($this->t('communities.filter.community_based')) ?></label>
            <div class="filters__actions">
                <button class="button" type="submit"><?= $this->e($this->t('catalog.filter.apply')) ?></button>
                <a href="<?= $this->e($this->route('public.communities')) ?>"><?= $this->e($this->t('catalog.filter.reset')) ?></a>
            </div>
        </form>
    </details>

    <div class="results-bar">
        <p class="results-count" role="status"><?= $this->e($this->t('communities.results', ['count' => $total])) ?></p>
    </div>

    <?php if ($organizations === []): ?>
        <p><?= $this->e($this->t('communities.none')) ?></p>
    <?php else: ?>
        <ul class="card-list">
            <?php foreach ($organizations as $organization): ?>
                <li>
                    <article class="card">
                        <h2 class="service-card__title">
                            <a href="<?= $this->e($this->route('public.organization', ['id' => $organization['id']])) ?>"><?= $this->e($organization['name']) ?></a>
                        </h2>
                        <p class="muted"><?= $this->localized($organization['type']) ?><?= $organization['towns'] !== [] ? ' · ' . $this->e(implode(', ', $organization['towns'])) : '' ?></p>
                        <?php if ($organization['description'] !== null): ?>
                            <p><?= $this->localized($organization['description']) ?></p>
                        <?php endif; ?>
                        <ul class="badges">
                            <?php if ($organization['is_community_based']): ?>
                                <li class="badge"><span aria-hidden="true">👥</span> <?= $this->e($this->t('catalog.organization.community_based')) ?></li>
                            <?php endif; ?>
                            <?php foreach ($organization['communities'] as $community): ?>
                                <li class="badge"><?= $this->localized($community['name']) ?></li>
                            <?php endforeach; ?>
                            <?php if ($organization['languages'] !== []): ?>
                                <li class="badge"><span aria-hidden="true">🗣️</span> <?= $this->e(implode(', ', array_map($this->languageName(...), $organization['languages']))) ?></li>
                            <?php endif; ?>
                        </ul>
                    </article>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <?= $this->partial('pagination', ['page' => $page, 'pages' => $pages, 'route' => 'public.communities', 'query' => $query]) ?>

    <div class="callout">
        <p><?= $this->e($this->t('communities.join')) ?></p>
        <a class="button button--secondary" href="<?= $this->e($this->route('public.section', ['section' => 'partecipa'])) ?>"><?= $this->e($this->t('nav.participate')) ?></a>
    </div>
</div>
