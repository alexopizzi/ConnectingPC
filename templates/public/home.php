<?php
/**
 * Pagina iniziale riepilogativa (D-038): una domanda ("cosa ti serve?"), il comune sulla mappa,
 * gli argomenti e tre strade alternative. Tutto si raggiunge in uno o due clic.
 *
 * @var App\Core\View $this
 * @var list<array{id: int, code: string, icon: string, label: array{text: string, lang: string, fallback: bool}}> $needs
 * @var list<array<string, mixed>> $municipalities
 * @var list<array<string, mixed>> $towns
 * @var array<string, mixed> $shapes
 */
$examples = array_slice(array_values(array_filter($needs, static fn (array $n): bool => in_array($n['code'], ['documents', 'health', 'italian_language', 'work'], true))), 0, 4);
$languages = (array) $this->shared('spoken_languages', []);
?>
<section class="hero" aria-labelledby="home-title">
    <div class="container hero__grid">
        <div>
            <p class="eyebrow"><?= $this->icon('map-pin') ?><?= $this->e($this->t('ui.brand.area')) ?></p>
            <h1 class="hero__title" id="home-title"><?= $this->e($this->t('home.title')) ?></h1>
            <p class="hero__intro"><?= $this->e($this->t('home.intro')) ?></p>
            <?= $this->partial('search-form') ?>
            <?php if ($examples !== []): ?>
                <div class="hero__hints">
                    <span id="hints-label"><?= $this->e($this->t('ui.home.examples')) ?></span>
                    <ul class="chip-list" aria-labelledby="hints-label">
                        <?php foreach ($examples as $need): ?>
                            <li><a class="chip chip--small" href="<?= $this->e($this->route('public.need', ['code' => $need['code']])) ?>"><?= $this->icon($need['icon']) ?><?= $this->localized($need['label']) ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
        </div>

        <div class="town-card">
            <div class="town-card__head">
                <span class="town-card__badge"><?= $this->icon('map', 'icon--lg') ?></span>
                <div>
                    <h2><?= $this->e($this->t('ui.home.town_title')) ?></h2>
                    <p><?= $this->e($this->t('ui.home.town_text')) ?></p>
                </div>
            </div>
            <?= $this->partial('province-map', ['shapes' => $shapes, 'towns' => $towns]) ?>
            <form class="town-card__form" method="get" action="<?= $this->e($this->route('public.map')) ?>">
                <label for="home-town"><?= $this->e($this->t('ui.home.town_label')) ?></label>
                <div class="town-card__row">
                    <select id="home-town" name="comune" required>
                        <option value=""><?= $this->e($this->t('geo.choose')) ?></option>
                        <?php foreach ($municipalities as $district): ?>
                            <optgroup label="<?= $this->e($district['name']) ?>">
                                <?php foreach ($district['municipalities'] as $town): ?>
                                    <option value="<?= $town['id'] ?>"><?= $this->e($town['name']) ?></option>
                                <?php endforeach; ?>
                            </optgroup>
                        <?php endforeach; ?>
                    </select>
                    <button class="button" type="submit"><?= $this->e($this->t('ui.home.town_submit')) ?></button>
                </div>
            </form>
            <a class="town-card__more" href="<?= $this->e($this->route('public.map')) ?>"><?= $this->e($this->t('ui.home.open_map')) ?><?= $this->icon('arrow-right', 'icon--flip') ?></a>
        </div>
    </div>
</section>

<section class="container section" id="argomenti" aria-labelledby="needs-title">
    <div class="section-head">
        <div>
            <h2 id="needs-title"><?= $this->e($this->t('home.needs_title')) ?></h2>
            <p><?= $this->e($this->t('ui.home.needs_text')) ?></p>
        </div>
        <a class="section-head__link" href="<?= $this->e($this->route('public.services')) ?>"><?= $this->e($this->t('home.quick.all_services')) ?><?= $this->icon('arrow-right', 'icon--flip') ?></a>
    </div>
    <?php if ($needs === []): ?>
        <p><?= $this->e($this->t('home.needs_soon')) ?></p>
    <?php else: ?>
        <ul class="tile-grid">
            <?php foreach ($needs as $need): ?>
                <li>
                    <a class="tile" href="<?= $this->e($this->route('public.need', ['code' => $need['code']])) ?>">
                        <span class="tile__icon"><?= $this->icon($need['icon']) ?></span>
                        <span class="tile__label"><?= $this->localized($need['label']) ?></span>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</section>

<section class="container section" aria-labelledby="ways-title">
    <div class="section-head">
        <h2 id="ways-title"><?= $this->e($this->t('home.quick_title')) ?></h2>
    </div>
    <ul class="action-grid">
        <li>
            <div class="action-card">
                <span class="action-card__icon"><?= $this->icon('languages') ?></span>
                <h3 id="lang-title"><?= $this->e($this->t('home.quick.language')) ?></h3>
                <p><?= $this->e($this->t('home.language_intro')) ?></p>
                <?php if ($languages !== []): ?>
                    <form method="get" action="<?= $this->e($this->route('public.services')) ?>" aria-labelledby="lang-title">
                        <label for="home-lang"><?= $this->e($this->t('catalog.filter.language')) ?></label>
                        <div class="town-card__row">
                            <select id="home-lang" name="lingua" class="select">
                                <?php foreach ($languages as $code): ?>
                                    <option value="<?= $this->e($code) ?>"><?= $this->e($this->languageName($code)) ?> — <?= $this->e(Locale::getDisplayLanguage($code, $code)) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button class="button" type="submit"><?= $this->e($this->t('catalog.filter.show')) ?></button>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </li>
        <li>
            <div class="action-card">
                <span class="action-card__icon"><?= $this->icon('messages-square') ?></span>
                <h3><?= $this->e($this->t('home.quick.mediators')) ?></h3>
                <p><?= $this->e($this->t('ui.home.mediators_text')) ?></p>
                <a class="action-card__link" href="<?= $this->e($this->route('public.mediators')) ?>"><?= $this->e($this->t('ui.home.mediators_cta')) ?><?= $this->icon('arrow-right', 'icon--flip') ?></a>
            </div>
        </li>
        <li>
            <div class="action-card">
                <span class="action-card__icon"><?= $this->icon('users-round') ?></span>
                <h3><?= $this->e($this->t('home.quick.communities')) ?></h3>
                <p><?= $this->e($this->t('ui.home.communities_text')) ?></p>
                <a class="action-card__link" href="<?= $this->e($this->route('public.communities')) ?>"><?= $this->e($this->t('ui.home.communities_cta')) ?><?= $this->icon('arrow-right', 'icon--flip') ?></a>
            </div>
        </li>
    </ul>
</section>

<section class="container section band">
    <div class="emergency" role="region" aria-labelledby="emergency-title">
        <span class="emergency__icon"><?= $this->icon('siren', 'icon--lg') ?></span>
        <div class="emergency__text">
            <h2 id="emergency-title"><?= $this->e($this->t('emergency.title')) ?></h2>
            <p><?= $this->e($this->t('emergency.text')) ?></p>
        </div>
        <a class="button button--danger" href="tel:112"><?= $this->icon('phone-call') ?><?= $this->e($this->t('emergency.call')) ?></a>
    </div>
    <div class="participate" role="region" aria-labelledby="participate-title">
        <div>
            <h2 id="participate-title"><?= $this->e($this->t('participate.title')) ?></h2>
            <p><?= $this->e($this->t('participate.text')) ?></p>
        </div>
        <a class="button button--secondary" href="<?= $this->e($this->route('public.section', ['section' => 'partecipa'])) ?>"><?= $this->icon('handshake') ?><?= $this->e($this->t('participate.cta')) ?></a>
    </div>
</section>
