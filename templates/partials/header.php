<?php
/** @var App\Core\View $this */
$nav = [
    ['route' => 'public.search', 'params' => [], 'label' => 'nav.search'],
    ['route' => 'public.services', 'params' => [], 'label' => 'nav.services'],
    ['route' => 'public.section', 'params' => ['section' => 'mappa'], 'label' => 'nav.map'],
    ['route' => 'public.section', 'params' => ['section' => 'associazioni-comunita'], 'label' => 'nav.communities'],
    ['route' => 'public.section', 'params' => ['section' => 'mediatori'], 'label' => 'nav.mediators'],
    ['route' => 'public.section', 'params' => ['section' => 'partecipa'], 'label' => 'nav.participate'],
];
$currentRoute = $this->shared('route_name');
$currentParams = (array) $this->shared('route_params', []);
?>
<header class="site-header">
    <div class="site-header__bar container">
        <a class="brand" href="<?= $this->e($this->route('public.home')) ?>">
            <span class="brand__name"><?= $this->e($this->t('app.name')) ?></span>
            <span class="brand__tagline"><?= $this->e($this->t('app.tagline')) ?></span>
        </a>
        <?= $this->partial('language-switcher') ?>
    </div>
    <nav class="main-nav container" aria-label="<?= $this->e($this->t('layout.main_navigation')) ?>">
        <details class="main-nav__toggle">
            <summary><?= $this->e($this->t('layout.menu')) ?></summary>
            <ul class="main-nav__list">
                <?php foreach ($nav as $item):
                    $isCurrent = $currentRoute === $item['route']
                        && (($item['params']['section'] ?? null) === ($currentParams['section'] ?? null)); ?>
                    <li>
                        <a href="<?= $this->e($this->route($item['route'], $item['params'])) ?>"<?= $isCurrent ? ' aria-current="page"' : '' ?>>
                            <?= $this->e($this->t($item['label'])) ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </details>
    </nav>
</header>
