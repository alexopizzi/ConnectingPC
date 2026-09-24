<?php
/**
 * @var App\Core\View $this
 * @var list<array<string, mixed>> $terms
 * @var array{locale: string, need: int} $filters
 * @var list<array{id: int, name: string}> $needs
 * @var list<string> $locales
 */
?>
<h1><?= $this->e($this->t('admin.synonyms.title')) ?></h1>
<p class="field__hint"><?= $this->e($this->t('admin.synonyms.intro')) ?></p>

<h2><?= $this->e($this->t('admin.synonyms.add')) ?></h2>
<form class="filters" method="post" action="<?= $this->e($this->route('admin.synonyms.store')) ?>">
    <?= $this->csrfField() ?>
    <div class="field">
        <label for="s-term"><?= $this->e($this->t('admin.synonyms.term')) ?></label>
        <input id="s-term" name="term" type="text" required maxlength="190">
    </div>
    <div class="field">
        <label for="s-locale"><?= $this->e($this->t('manage.field.language')) ?></label>
        <select id="s-locale" name="locale">
            <?php foreach ($locales as $code): ?>
                <option value="<?= $code ?>"<?= ($filters['locale'] ?: 'it') === $code ? ' selected' : '' ?>><?= $this->e($this->languageName($code)) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="field">
        <label for="s-need"><?= $this->e($this->t('admin.synonyms.need')) ?></label>
        <select id="s-need" name="need_id">
            <?php foreach ($needs as $need): ?>
                <option value="<?= $need['id'] ?>"<?= $filters['need'] === $need['id'] ? ' selected' : '' ?>><?= $this->e($need['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="field">
        <label for="s-weight"><?= $this->e($this->t('admin.synonyms.weight')) ?></label>
        <input id="s-weight" name="weight" type="number" min="1" max="10" value="8">
    </div>
    <div><button class="button" type="submit"><?= $this->e($this->t('admin.synonyms.add')) ?></button></div>
</form>

<h2><?= $this->e($this->t('admin.synonyms.list')) ?></h2>
<form class="filters" method="get" action="<?= $this->e($this->route('admin.synonyms.index')) ?>">
    <div class="field">
        <label for="f-locale"><?= $this->e($this->t('manage.field.language')) ?></label>
        <select id="f-locale" name="lingua">
            <option value=""><?= $this->e($this->t('admin.common.all')) ?></option>
            <?php foreach ($locales as $code): ?>
                <option value="<?= $code ?>"<?= $filters['locale'] === $code ? ' selected' : '' ?>><?= $this->e($this->languageName($code)) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="field">
        <label for="f-need"><?= $this->e($this->t('admin.synonyms.need')) ?></label>
        <select id="f-need" name="bisogno">
            <option value=""><?= $this->e($this->t('admin.common.all')) ?></option>
            <?php foreach ($needs as $need): ?>
                <option value="<?= $need['id'] ?>"<?= $filters['need'] === $need['id'] ? ' selected' : '' ?>><?= $this->e($need['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div><button class="button button--secondary" type="submit"><?= $this->e($this->t('admin.common.filter')) ?></button></div>
</form>

<div class="table-wrap">
    <table class="table">
        <caption><?= $this->e($this->t('admin.common.results', ['count' => count($terms)])) ?></caption>
        <thead><tr>
            <th scope="col"><?= $this->e($this->t('admin.synonyms.term')) ?></th>
            <th scope="col"><?= $this->e($this->t('manage.field.language')) ?></th>
            <th scope="col"><?= $this->e($this->t('admin.synonyms.need')) ?></th>
            <th scope="col"><?= $this->e($this->t('admin.synonyms.weight')) ?></th>
            <th scope="col"><?= $this->e($this->t('admin.common.actions')) ?></th>
        </tr></thead>
        <tbody>
        <?php foreach ($terms as $term): ?>
            <tr>
                <td lang="<?= $this->e($term['locale']) ?>" dir="<?= $term['locale'] === 'ar' ? 'rtl' : 'ltr' ?>"><?= $this->e($term['term']) ?></td>
                <td><?= $this->e(strtoupper((string) $term['locale'])) ?></td>
                <td><?= $this->e((string) $term['need_label']) ?></td>
                <td><?= (int) $term['weight'] ?></td>
                <td>
                    <form class="inline-form" method="post" action="<?= $this->e($this->route('admin.synonyms.delete', ['id' => (int) $term['id']])) ?>">
                        <?= $this->csrfField() ?>
                        <button class="button button--link" type="submit"><?= $this->e($this->t('admin.synonyms.delete')) ?></button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
