<?php
/** @var App\Core\View $this */
$explore = [
    ['public.services', 'nav.services', 'list'],
    ['public.map', 'nav.map', 'map'],
    ['public.communities', 'nav.communities', 'users-round'],
    ['public.mediators', 'nav.mediators', 'messages-square'],
];
$info = ['progetto' => 'nav.project', 'partecipa' => 'nav.participate', 'contatti' => 'nav.contacts', 'privacy' => 'nav.privacy', 'accessibilita' => 'nav.accessibility', 'note-legali' => 'nav.legal'];
?>
<footer class="site-footer">
    <div class="container">
        <div class="site-footer__grid">
            <div class="site-footer__brand">
                <a class="brand" href="<?= $this->e($this->route('public.home')) ?>">
                    <span class="brand__mark"><?= $this->icon('map-pinned') ?></span>
                    <span class="brand__text"><span class="brand__name"><?= $this->e($this->t('app.name')) ?></span></span>
                </a>
                <p><?= $this->e($this->t('footer.about')) ?></p>
                <p><a class="site-footer__emergency" href="tel:112"><?= $this->icon('phone-call') ?><?= $this->e($this->t('ui.footer.emergency')) ?></a></p>
            </div>
            <nav aria-labelledby="footer-explore">
                <h2 id="footer-explore"><?= $this->e($this->t('ui.footer.explore')) ?></h2>
                <ul class="site-footer__links">
                    <?php foreach ($explore as [$route, $label, $icon]): ?>
                        <li><a href="<?= $this->e($this->route($route)) ?>"><?= $this->icon($icon) ?><?= $this->e($this->t($label)) ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </nav>
            <nav aria-labelledby="footer-info">
                <h2 id="footer-info"><?= $this->e($this->t('ui.footer.information')) ?></h2>
                <ul class="site-footer__links">
                    <?php foreach ($info as $section => $label): ?>
                        <li><a href="<?= $this->e($this->route('public.section', ['section' => $section])) ?>"><?= $this->e($this->t($label)) ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </nav>
            <div>
                <h2><?= $this->e($this->t('ui.footer.organizations')) ?></h2>
                <p class="muted"><?= $this->e($this->t('participate.text')) ?></p>
                <ul class="site-footer__links">
                    <li><a href="<?= $this->e($this->route('auth.login')) ?>"><?= $this->icon('log-in') ?><?= $this->e($this->t('footer.login')) ?></a></li>
                </ul>
            </div>
        </div>
        <div class="site-footer__bottom">
            <p><?= $this->e($this->t('ui.footer.map_credits')) ?></p>
            <p class="site-footer__version"><?= $this->e($this->t('footer.version', ['version' => App\Core\App::version()])) ?></p>
        </div>
    </div>
</footer>
