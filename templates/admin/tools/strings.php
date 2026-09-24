<?php
/**
 * Traduzione delle stringhe dell'interfaccia (RF-36).
 *
 * @var App\Core\View $this
 * @var list<string> $locales lingue che l'utente può tradurre
 * @var string $locale
 * @var string $filter
 * @var string $query
 * @var list<array{id: int, key: string, source: string, text: ?string, status: ?string}> $strings
 * @var array<string, int> $summary
 */
use App\Domain\Management\UiStringEditor;

?>
<h1><?= $this->e($this->t('admin.strings.title')) ?></h1>
<p class="field__hint"><?= $this->e($this->t('admin.strings.intro')) ?></p>
<?php if ($locales === []): ?>
    <p class="alert alert--info"><?= $this->e($this->t('manage.error.forbidden')) ?></p>
<?php else: ?>
    <form class="filters" method="get" action="<?= $this->e($this->route('admin.strings.index')) ?>">
        <div class="field">
            <label for="f-locale"><?= $this->e($this->t('manage.field.language')) ?></label>
            <select id="f-locale" name="lingua">
                <?php foreach ($locales as $code): ?>
                    <option value="<?= $code ?>"<?= $locale === $code ? ' selected' : '' ?>><?= $this->e($this->languageName($code)) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="field">
            <label for="f-state"><?= $this->e($this->t('admin.taxonomy.state')) ?></label>
            <select id="f-state" name="stato">
                <?php foreach (UiStringEditor::FILTERS as $option): ?>
                    <option value="<?= $option ?>"<?= $filter === $option ? ' selected' : '' ?>><?= $this->e($this->t('admin.strings.filter.' . $option)) ?> (<?= (int) ($summary[$option] ?? array_sum($summary)) ?>)</option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="field">
            <label for="f-q"><?= $this->e($this->t('admin.common.search')) ?></label>
            <input id="f-q" name="q" type="search" value="<?= $this->e($query) ?>">
        </div>
        <div><button class="button button--secondary" type="submit"><?= $this->e($this->t('admin.common.filter')) ?></button></div>
    </form>

    <?php if ($strings === []): ?>
        <p class="muted"><?= $this->e($this->t('admin.common.empty')) ?></p>
    <?php endif; ?>
    <ul class="card-list">
        <?php foreach ($strings as $string): ?>
            <li class="card">
                <form method="post" action="<?= $this->e($this->route('admin.strings.save', ['id' => $string['id']])) ?>">
                    <?= $this->csrfField() ?>
                    <input type="hidden" name="locale" value="<?= $this->e($locale) ?>">
                    <input type="hidden" name="stato" value="<?= $this->e($filter) ?>">
                    <input type="hidden" name="q" value="<?= $this->e($query) ?>">
                    <p class="muted"><code><?= $this->e($string['key']) ?></code><?= $string['status'] !== null ? ' · ' . $this->e($this->t('manage.translation.' . $string['status'])) : '' ?></p>
                    <p lang="it"><?= $this->e($string['source']) ?></p>
                    <div class="field">
                        <label for="s-<?= $string['id'] ?>" class="visually-hidden"><?= $this->e($this->languageName($locale)) ?></label>
                        <textarea id="s-<?= $string['id'] ?>" name="text" rows="2" lang="<?= $this->e($locale) ?>" dir="<?= $locale === 'ar' ? 'rtl' : 'ltr' ?>"><?= $this->e($string['text'] ?? '') ?></textarea>
                    </div>
                    <div class="page-actions">
                        <label class="checkbox"><input type="checkbox" name="approve" value="1"<?= $string['status'] === 'approved' ? ' checked' : '' ?>> <?= $this->e($this->t('admin.strings.approve')) ?></label>
                        <button class="button button--secondary" type="submit"><?= $this->e($this->t('manage.save')) ?></button>
                    </div>
                </form>
            </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>
