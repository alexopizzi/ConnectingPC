<?php
/**
 * @var App\Core\View $this
 * @var list<array{id: int, code: string, icon: string, label: array{text: string, lang: string, fallback: bool}}> $needs
 */
$quick = [
    ['route' => 'public.services', 'params' => [], 'label' => 'home.quick.all_services', 'icon' => '📋'],
    ['route' => 'public.map', 'params' => [], 'label' => 'home.quick.map', 'icon' => '🗺️'],
    ['route' => 'public.section', 'params' => ['section' => 'associazioni-comunita'], 'label' => 'home.quick.communities', 'icon' => '🤝'],
    ['route' => 'public.need', 'params' => ['code' => 'mediation'], 'label' => 'home.quick.mediators', 'icon' => '💬'],
];
?>
<section class="hero">
    <div class="container">
        <h1 class="hero__title"><?= $this->e($this->t('home.title')) ?></h1>
        <p class="hero__intro"><?= $this->e($this->t('home.intro')) ?></p>
        <?= $this->partial('search-form') ?>
    </div>
</section>

<section class="container section" aria-labelledby="needs-title">
    <h2 id="needs-title"><?= $this->e($this->t('home.needs_title')) ?></h2>
    <ul class="tile-grid tile-grid--needs">
        <?php foreach ($needs as $need): ?>
            <li>
                <a class="tile" href="<?= $this->e($this->route('public.need', ['code' => $need['code']])) ?>">
                    <span class="tile__icon" aria-hidden="true"><?= $this->icon($need['icon']) ?></span>
                    <span class="tile__label"><?= $this->localized($need['label']) ?></span>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>
</section>

<section class="container section" aria-labelledby="language-title">
    <h2 id="language-title"><?= $this->e($this->t('home.quick.language')) ?></h2>
    <p><?= $this->e($this->t('home.language_intro')) ?></p>
    <?= $this->partial('language-filter-form') ?>
</section>

<section class="container section" aria-labelledby="quick-title">
    <h2 id="quick-title"><?= $this->e($this->t('home.quick_title')) ?></h2>
    <ul class="tile-grid">
        <?php foreach ($quick as $item): ?>
            <li>
                <a class="tile" href="<?= $this->e($this->route($item['route'], $item['params'])) ?>">
                    <span class="tile__icon" aria-hidden="true"><?= $item['icon'] ?></span>
                    <span class="tile__label"><?= $this->e($this->t($item['label'])) ?></span>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>
</section>

<section class="container section">
    <div class="callout callout--emergency" role="region" aria-labelledby="emergency-title">
        <h2 id="emergency-title"><?= $this->e($this->t('emergency.title')) ?></h2>
        <p><?= $this->e($this->t('emergency.text')) ?></p>
        <a class="button button--danger" href="tel:112"><?= $this->e($this->t('emergency.call')) ?></a>
    </div>
</section>

<section class="container section">
    <div class="callout" role="region" aria-labelledby="participate-title">
        <h2 id="participate-title"><?= $this->e($this->t('participate.title')) ?></h2>
        <p><?= $this->e($this->t('participate.text')) ?></p>
        <a class="button" href="<?= $this->e($this->route('public.section', ['section' => 'partecipa'])) ?>">
            <?= $this->e($this->t('participate.cta')) ?>
        </a>
    </div>
</section>
