<?php
/**
 * Filtri del catalogo (elenco, bisogno, ricerca, mappa).
 *
 * @var App\Core\View $this
 * @var array<string, mixed> $filters
 * @var string $action
 * @var string $resetUrl
 * @var bool $showCategory
 * @var list<array<string, mixed>> $categories
 * @var list<array<string, mixed>> $municipalities
 * @var list<string> $languages
 */
$activeCount = count(array_filter([$filters['category'] ?? null, $filters['territory'] ?? null, $filters['language'] ?? null, $filters['mediation'] ?: null, $filters['free'] ?: null]));
?>
<details class="filters-panel"<?= $activeCount > 0 ? ' open' : '' ?>>
    <summary><?= $this->e($this->t('catalog.filter.title')) ?><?= $activeCount > 0 ? ' (' . $activeCount . ')' : '' ?></summary>
    <form class="filters" method="get" action="<?= $this->e($action) ?>">
        <?php if (($filters['q'] ?? '') !== ''): ?>
            <input type="hidden" name="q" value="<?= $this->e($filters['q']) ?>">
        <?php endif; ?>
        <?php if (!empty($filters['need']) && $showCategory): ?>
            <input type="hidden" name="bisogno" value="<?= $this->e($filters['need']) ?>">
        <?php endif; ?>
        <?php if ($showCategory): ?>
            <div class="field">
                <label for="f-categoria"><?= $this->e($this->t('catalog.filter.category')) ?></label>
                <select id="f-categoria" name="categoria">
                    <option value=""><?= $this->e($this->t('catalog.filter.any')) ?></option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?= $this->e($category['code']) ?>"<?= ($filters['category'] ?? null) === $category['code'] ? ' selected' : '' ?>><?= $this->e($category['name']['text']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        <?php endif; ?>
        <div class="field">
            <label for="f-comune"><?= $this->e($this->t('catalog.filter.municipality')) ?></label>
            <select id="f-comune" name="comune">
                <option value=""><?= $this->e($this->t('catalog.filter.any_municipality')) ?></option>
                <?php foreach ($municipalities as $district): ?>
                    <optgroup label="<?= $this->e($district['name']) ?>">
                        <option value="<?= $district['id'] ?>"<?= ($filters['territory'] ?? null) === $district['id'] ? ' selected' : '' ?>><?= $this->e($this->t('catalog.filter.whole_district', ['district' => $district['name']])) ?></option>
                        <?php foreach ($district['municipalities'] as $municipality): ?>
                            <option value="<?= $municipality['id'] ?>"<?= ($filters['territory'] ?? null) === $municipality['id'] ? ' selected' : '' ?>><?= $this->e($municipality['name']) ?></option>
                        <?php endforeach; ?>
                    </optgroup>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="field">
            <label for="f-lingua"><?= $this->e($this->t('catalog.filter.language')) ?></label>
            <select id="f-lingua" name="lingua">
                <option value=""><?= $this->e($this->t('catalog.filter.any')) ?></option>
                <?php foreach ($languages as $code): ?>
                    <option value="<?= $this->e($code) ?>"<?= ($filters['language'] ?? null) === $code ? ' selected' : '' ?>><?= $this->e($this->languageName($code)) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <label class="checkbox"><input type="checkbox" name="mediazione" value="1"<?= !empty($filters['mediation']) ? ' checked' : '' ?>> <?= $this->e($this->t('catalog.filter.mediation')) ?></label>
        <label class="checkbox"><input type="checkbox" name="gratuito" value="1"<?= !empty($filters['free']) ? ' checked' : '' ?>> <?= $this->e($this->t('catalog.filter.free')) ?></label>
        <div class="filters__actions">
            <button class="button" type="submit"><?= $this->e($this->t('catalog.filter.apply')) ?></button>
            <a href="<?= $this->e($resetUrl) ?>"><?= $this->e($this->t('catalog.filter.reset')) ?></a>
        </div>
    </form>
</details>
