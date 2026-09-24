<?php
/**
 * Testi tradotti di un contenuto, una lingua per volta (vault "60"). La lingua sorgente è sempre approvata.
 *
 * @var App\Core\View $this
 * @var string $action
 * @var string $showRoute rotta della pagina, per i collegamenti fra le lingue
 * @var array<string, string|int> $showParams
 * @var list<string> $locales
 * @var string $locale lingua mostrata
 * @var string $source lingua sorgente
 * @var array<string, array<string, mixed>> $translations
 * @var array<string, string> $fields campo => 'line'|'text'
 */
$current = $translations[$locale] ?? [];
$sourceRow = $translations[$source] ?? [];
?>
<nav class="page-actions" aria-label="<?= $this->e($this->t('manage.texts.locales')) ?>">
    <?php foreach ($locales as $code):
        $status = $translations[$code]['status'] ?? null; ?>
        <a class="button <?= $code === $locale ? '' : 'button--secondary' ?>" href="<?= $this->e($this->route($showRoute, [...$showParams, 'lingua' => $code])) ?>#testi"<?= $code === $locale ? ' aria-current="true"' : '' ?>>
            <?= $this->e(strtoupper($code)) ?> · <?= $this->e($status === null ? $this->t('manage.texts.missing') : $this->t('manage.translation.' . $status)) ?><?= $code === $source ? ' · ' . $this->e($this->t('manage.texts.source')) : '' ?>
        </a>
    <?php endforeach; ?>
</nav>
<form class="form form--wide" method="post" action="<?= $this->e($action) ?>" lang="<?= $this->e($locale) ?>" dir="<?= $locale === 'ar' ? 'rtl' : 'ltr' ?>">
    <?= $this->csrfField() ?>
    <input type="hidden" name="locale" value="<?= $this->e($locale) ?>">
    <?php foreach ($fields as $field => $kind):
        $id = 'tx-' . $field; ?>
        <div class="field">
            <label for="<?= $id ?>"><?= $this->e($this->t('manage.field.' . $field)) ?></label>
            <?php if ($locale !== $source && !empty($sourceRow[$field])): ?>
                <p class="field__hint" id="<?= $id ?>-src" lang="<?= $this->e($source) ?>" dir="<?= $source === 'ar' ? 'rtl' : 'ltr' ?>"><?= $this->e($this->t('manage.texts.original')) ?>: <?= $this->e(mb_strimwidth((string) $sourceRow[$field], 0, 300, '…')) ?></p>
            <?php endif; ?>
            <?php if ($kind === 'line'): ?>
                <input id="<?= $id ?>" type="text" name="<?= $field ?>" value="<?= $this->e($current[$field] ?? '') ?>">
            <?php else: ?>
                <textarea id="<?= $id ?>" name="<?= $field ?>" rows="4"><?= $this->e($current[$field] ?? '') ?></textarea>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
    <?php if ($locale !== $source): ?>
        <label class="checkbox"><input type="checkbox" name="approve" value="1"<?= ($current['status'] ?? '') === 'approved' ? ' checked' : '' ?>> <?= $this->e($this->t('manage.texts.approve')) ?></label>
    <?php endif; ?>
    <p class="field__hint"><?= $this->e($this->t('manage.texts.hint')) ?></p>
    <div><button class="button" type="submit"><?= $this->e($this->t('manage.texts.save', ['locale' => strtoupper($locale)])) ?></button></div>
</form>
