<?php
/**
 * Selettore lingua: nomi delle lingue nella propria lingua e grafia, niente bandiere (vault "60").
 *
 * @var App\Core\View $this
 */
$current = $this->locale();
$locales = $this->publicLocales();
?>
<details class="lang-switcher" data-dropdown>
    <summary>
        <?= $this->icon('globe') ?>
        <span class="visually-hidden"><?= $this->e($this->t('layout.language')) ?>:</span>
        <span lang="<?= $this->e($current) ?>"><?= $this->e($locales[$current]['native_name'] ?? $current) ?></span>
        <?= $this->icon('chevron-down') ?>
    </summary>
    <ul class="lang-switcher__list">
        <?php foreach ($locales as $code => $locale): ?>
            <li>
                <a href="<?= $this->e($this->switchLocaleUrl($code)) ?>"
                   lang="<?= $this->e($code) ?>" hreflang="<?= $this->e($code) ?>" dir="<?= $this->e($locale['direction']) ?>"
                   <?= $code === $current ? 'aria-current="true"' : '' ?>>
                    <span><?= $this->e($locale['native_name']) ?></span>
                    <?= $code === $current ? $this->icon('check') : '' ?>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>
</details>
