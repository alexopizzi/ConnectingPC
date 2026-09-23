<?php
/** @var App\Core\View $this */
$links = ['progetto' => 'nav.project', 'contatti' => 'nav.contacts', 'privacy' => 'nav.privacy', 'accessibilita' => 'nav.accessibility'];
?>
<footer class="site-footer">
    <div class="container">
        <p class="site-footer__about"><?= $this->e($this->t('footer.about')) ?></p>
        <ul class="site-footer__links">
            <?php foreach ($links as $section => $label): ?>
                <li><a href="<?= $this->e($this->route('public.section', ['section' => $section])) ?>"><?= $this->e($this->t($label)) ?></a></li>
            <?php endforeach; ?>
        </ul>
        <p class="site-footer__version"><?= $this->e($this->t('footer.version', ['version' => App\Core\App::version()])) ?></p>
    </div>
</footer>
