<?php
/**
 * Selettore lingua: nomi delle lingue nella propria lingua e grafia, niente bandiere (vault "60").
 *
 * @var App\Core\View $this
 */
$current = $this->locale();
$locales = $this->publicLocales();
?>
<details class="lang-switcher">
    <summary>
        <span class="lang-switcher__icon" aria-hidden="true">🌐</span>
        <span class="visually-hidden"><?= $this->e($this->t('layout.language')) ?>:</span>
        <span lang="<?= $this->e($current) ?>"><?= $this->e($locales[$current]['native_name'] ?? $current) ?></span>
    </summary>
    <ul class="lang-switcher__list">
        <?php foreach ($locales as $code => $locale): ?>
            <li>
                <a href="<?= $this->e($this->switchLocaleUrl($code)) ?>"
                   lang="<?= $this->e($code) ?>" hreflang="<?= $this->e($code) ?>" dir="<?= $this->e($locale['direction']) ?>"
                   <?= $code === $current ? 'aria-current="true"' : '' ?>>
                    <?= $this->e($locale['native_name']) ?>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>
</details>
