<?php
/**
 * @var App\Core\View $this
 * @var string|null $query
 */
?>
<form class="search-form" role="search" method="get" action="<?= $this->e($action ?? $this->route('public.search')) ?>"
      data-suggest-url="<?= $this->e($this->route('api.search.suggest', ['locale' => $this->locale()])) ?>"
      data-need-url="<?= $this->e($this->route('public.need', ['code' => '__code__'])) ?>"
      data-service-url="<?= $this->e($this->route('public.service', ['id' => 0])) ?>"
      data-msg-count="<?= $this->e($this->t('search.suggestions_count')) ?>">
    <label class="search-form__label" for="search-q"><?= $this->e($this->t('search.label')) ?></label>
    <div class="search-form__row">
        <input class="search-form__input" id="search-q" name="q" type="search" autocomplete="off" maxlength="200"
               value="<?= $this->e($query ?? '') ?>"
               placeholder="<?= $this->e($this->t('search.placeholder')) ?>">
        <button class="button search-form__submit" type="submit"><?= $this->e($this->t('search.submit')) ?></button>
    </div>
</form>
