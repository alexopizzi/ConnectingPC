<?php
/** @var App\Core\View $this */
$nav = [
    ['route' => 'public.services', 'label' => 'nav.services', 'icon' => 'list', 'also' => ['public.need', 'public.search', 'public.service', 'public.organization']],
    ['route' => 'public.map', 'label' => 'nav.map', 'icon' => 'map', 'also' => []],
    ['route' => 'public.communities', 'label' => 'nav.communities', 'icon' => 'users-round', 'also' => []],
    ['route' => 'public.mediators', 'label' => 'nav.mediators', 'icon' => 'messages-square', 'also' => []],
];
$currentRoute = $this->shared('route_name');
$currentParams = (array) $this->shared('route_params', []);
$isParticipate = $currentRoute === 'public.section' && ($currentParams['section'] ?? null) === 'partecipa';
?>
<header class="site-header">
    <div class="site-header__bar container">
        <a class="brand" href="<?= $this->e($this->route('public.home')) ?>"<?= $currentRoute === 'public.home' ? ' aria-current="page"' : '' ?>>
            <span class="brand__mark"><?= $this->icon('map-pinned') ?></span>
            <span class="brand__text">
                <span class="brand__name"><?= $this->e($this->t('app.name')) ?></span>
                <span class="brand__tagline"><?= $this->e($this->t('ui.brand.area')) ?></span>
            </span>
        </a>
        <nav class="main-nav" aria-label="<?= $this->e($this->t('layout.main_navigation')) ?>" data-nav>
            <button class="main-nav__toggle" type="button" aria-expanded="false" aria-controls="main-nav-list" data-nav-toggle>
                <?= $this->icon('menu') ?><span><?= $this->e($this->t('layout.menu')) ?></span>
            </button>
            <ul class="main-nav__list" id="main-nav-list">
                <?php foreach ($nav as $item):
                    $isCurrent = $currentRoute === $item['route'] || in_array($currentRoute, $item['also'], true); ?>
                    <li>
                        <a href="<?= $this->e($this->route($item['route'])) ?>"<?= $isCurrent ? ' aria-current="page"' : '' ?>>
                            <?= $this->icon($item['icon']) ?><span><?= $this->e($this->t($item['label'])) ?></span>
                        </a>
                    </li>
                <?php endforeach; ?>
                <li>
                    <a href="<?= $this->e($this->route('public.section', ['section' => 'partecipa'])) ?>"<?= $isParticipate ? ' aria-current="page"' : '' ?>>
                        <?= $this->icon('handshake') ?><span><?= $this->e($this->t('nav.participate')) ?></span>
                    </a>
                </li>
            </ul>
        </nav>
        <div class="site-header__tools">
            <?= $this->partial('language-switcher') ?>
        </div>
    </div>
</header>
