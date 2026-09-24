<?php
/**
 * Scelta di comune o distretto (parametro "comune"), raggruppata per distretto.
 *
 * @var App\Core\View $this
 * @var string $id
 * @var ?int $selected
 * @var list<array{id: int, name: string, municipalities: list<array{id: int, name: string}>}> $municipalities
 */
?>
<div class="field">
    <label for="<?= $this->e($id) ?>"><?= $this->e($this->t('catalog.filter.municipality')) ?></label>
    <select id="<?= $this->e($id) ?>" name="comune">
        <option value=""><?= $this->e($this->t('catalog.filter.any_municipality')) ?></option>
        <?php foreach ($municipalities as $district): ?>
            <optgroup label="<?= $this->e($district['name']) ?>">
                <option value="<?= $district['id'] ?>"<?= $selected === $district['id'] ? ' selected' : '' ?>><?= $this->e($this->t('catalog.filter.whole_district', ['district' => $district['name']])) ?></option>
                <?php foreach ($district['municipalities'] as $municipality): ?>
                    <option value="<?= $municipality['id'] ?>"<?= $selected === $municipality['id'] ? ' selected' : '' ?>><?= $this->e($municipality['name']) ?></option>
                <?php endforeach; ?>
            </optgroup>
        <?php endforeach; ?>
    </select>
</div>
