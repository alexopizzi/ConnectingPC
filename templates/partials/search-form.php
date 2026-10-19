<?php
/**
 * Campo di ricerca con suggerimenti (suggest.js). Senza JavaScript è un normale modulo GET.
 *
 * @var App\Core\View $this
 * @var string|null $query
 * @var string|null $action
 * @var bool|null $compact
 * @var array<string, string>|null $hidden parametri da conservare (es. filtri attivi)
 */
$compact ??= false;
$fieldId = $compact ? 'search-q-compact' : 'search-q';
?>
<form class="search-form<?= $compact ? ' search-form--compact' : '' ?>" role="search" method="get" action="<?= $this->e($action ?? $this->route('public.search')) ?>"
      data-suggest-url="<?= $this->e($this->route('api.search.suggest', ['locale' => $this->locale()])) ?>"
      data-need-url="<?= $this->e($this->route('public.need', ['code' => '__code__'])) ?>"
      data-service-url="<?= $this->e($this->route('public.service', ['id' => 0])) ?>"
      data-sprite="<?= $this->e($this->asset('icons/sprite.svg')) ?>"
      data-msg-count="<?= $this->e($this->t('search.suggestions_count')) ?>">
    <label class="search-form__label<?= $compact ? ' visually-hidden' : '' ?>" for="<?= $fieldId ?>"><?= $this->e($this->t('search.label')) ?></label>
    <div class="search-form__row">
        <?= $this->icon('search') ?>
        <input class="search-form__input" id="<?= $fieldId ?>" name="q" type="search" autocomplete="off" maxlength="200"
               value="<?= $this->e($query ?? '') ?>"
               placeholder="<?= $this->e($this->t('search.placeholder')) ?>">
        <?php foreach (($hidden ?? []) as $name => $value): ?>
            <input type="hidden" name="<?= $this->e($name) ?>" value="<?= $this->e($value) ?>">
        <?php endforeach; ?>
        <button class="button search-form__submit" type="submit"><span class="button__text"><?= $this->e($this->t('search.submit')) ?></span><?= $this->icon('arrow-right', 'icon--flip') ?></button>
    </div>
</form>
