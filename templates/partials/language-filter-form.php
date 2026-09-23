<?php
/**
 * "Qualcuno che parla la mia lingua": elenco dei servizi filtrato per lingua parlata.
 *
 * @var App\Core\View $this
 */
$languages = (array) $this->shared('spoken_languages', []);
?>
<form class="inline-search" method="get" action="<?= $this->e($this->route('public.services')) ?>">
    <label for="home-lang"><?= $this->e($this->t('catalog.filter.language')) ?></label>
    <select id="home-lang" name="lingua">
        <?php foreach ($languages as $code): ?>
            <option value="<?= $this->e($code) ?>" lang="<?= $this->e($code) ?>"><?= $this->e($this->languageName($code)) ?> — <?= $this->e(Locale::getDisplayLanguage($code, $code)) ?></option>
        <?php endforeach; ?>
    </select>
    <button class="button" type="submit"><?= $this->e($this->t('catalog.filter.show')) ?></button>
</form>
