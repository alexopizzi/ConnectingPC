<?php
/**
 * @var App\Core\View $this
 * @var string|null $query
 */
?>
<form class="search-form" role="search" method="get" action="<?= $this->e($this->route('public.search')) ?>">
    <label class="search-form__label" for="search-q"><?= $this->e($this->t('search.label')) ?></label>
    <div class="search-form__row">
        <input class="search-form__input" id="search-q" name="q" type="search" autocomplete="off" maxlength="200"
               value="<?= $this->e($query ?? '') ?>"
               placeholder="<?= $this->e($this->t('search.placeholder')) ?>">
        <button class="button search-form__submit" type="submit"><?= $this->e($this->t('search.submit')) ?></button>
    </div>
</form>
