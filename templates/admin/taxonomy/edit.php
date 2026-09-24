<?php
/**
 * Creazione (item = null) e modifica di una voce di tassonomia.
 *
 * @var App\Core\View $this
 * @var string $type
 * @var array<string, mixed>|null $item
 * @var list<string> $locales
 * @var list<string> $icons
 * @var list<array{id: int, name: string, parent: ?string}> $areas
 * @var list<array{id: int, name: string, parent: ?string}> $categories
 */
use App\Domain\Management\TaxonomyEditor;

$fields = TaxonomyEditor::TYPES[$type][4];
$i = $item ?? [];
$value = fn (string $field, string $default = ''): string => $this->old($field, isset($i[$field]) && $i[$field] !== null ? (string) $i[$field] : $default);
$action = $item === null ? $this->route('admin.taxonomy.store', ['type' => $type]) : $this->route('admin.taxonomy.update', ['type' => $type, 'id' => (int) $item['id']]);
?>
<p><a href="<?= $this->e($this->route('admin.taxonomy.list', ['type' => $type])) ?>"><?= $this->e($this->t('admin.back_to_list', ['name' => $this->t('admin.taxonomy.' . $type)])) ?></a></p>
<h1><?= $this->e($item === null ? $this->t('admin.taxonomy.create') : (string) ($item['labels']['it']['text'] ?? $item['code'])) ?></h1>
<?= $this->partial('form-errors') ?>

<form class="form form--wide" method="post" action="<?= $this->e($action) ?>">
    <?= $this->csrfField() ?>
    <div class="form-grid">
        <?php if ($item === null): ?>
            <?= $this->partial('field', ['name' => 'code', 'label' => $this->t('admin.taxonomy.code'), 'required' => true, 'hint' => $this->t('admin.taxonomy.code_hint')]) ?>
        <?php else: ?>
            <p><strong><?= $this->e($this->t('admin.taxonomy.code')) ?>:</strong> <code><?= $this->e($item['code']) ?></code></p>
        <?php endif; ?>
        <?= $this->partial('field', ['name' => 'sort_order', 'label' => $this->t('admin.taxonomy.sort_order'), 'type' => 'number', 'value' => $value('sort_order', '0')]) ?>
        <?php if (in_array('icon', $fields, true)): ?>
            <div class="field">
                <label for="f-icon"><?= $this->e($this->t('admin.taxonomy.icon')) ?></label>
                <select id="f-icon" name="icon">
                    <option value=""><?= $this->e($this->t('admin.common.none')) ?></option>
                    <?php foreach ($icons as $icon): ?>
                        <option value="<?= $this->e($icon) ?>"<?= $value('icon') === $icon ? ' selected' : '' ?>><?= $this->icon($icon) ?> <?= $this->e($icon) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        <?php endif; ?>
        <?php if (in_array('parent_id', $fields, true)): ?>
            <div class="field">
                <label for="f-parent"><?= $this->e($this->t('admin.taxonomy.parent')) ?></label>
                <select id="f-parent" name="parent_id">
                    <option value=""><?= $this->e($this->t('admin.taxonomy.is_area')) ?></option>
                    <?php foreach ($areas as $area): if ($item !== null && $area['id'] === (int) $item['id']) { continue; } ?>
                        <option value="<?= $area['id'] ?>"<?= (int) $value('parent_id') === $area['id'] ? ' selected' : '' ?>><?= $this->e($area['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        <?php endif; ?>
        <?php if (in_array('kind', $fields, true)): ?>
            <div class="field">
                <label for="f-kind"><?= $this->e($this->t('admin.taxonomy.kind')) ?></label>
                <select id="f-kind" name="kind">
                    <?php foreach (TaxonomyEditor::COMMUNITY_KINDS as $kind): ?>
                        <option value="<?= $kind ?>"<?= $value('kind', 'national') === $kind ? ' selected' : '' ?>><?= $this->e($this->t('admin.taxonomy.kind.' . $kind)) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        <?php endif; ?>
    </div>
    <?php foreach (['is_active', 'is_featured', 'is_public_body'] as $flag): if (!in_array($flag, $fields, true)) { continue; } ?>
        <label class="checkbox"><input type="checkbox" name="<?= $flag ?>" value="1"<?= $value($flag, $flag === 'is_active' ? '1' : '0') === '1' ? ' checked' : '' ?>> <?= $this->e($this->t('admin.taxonomy.' . $flag)) ?></label>
    <?php endforeach; ?>

    <fieldset class="field">
        <legend><?= $this->e($this->t('admin.taxonomy.labels')) ?></legend>
        <?php foreach ($locales as $code):
            $label = $item['labels'][$code] ?? null; ?>
            <div class="field">
                <label for="f-label-<?= $code ?>"><?= $this->e($this->languageName($code)) ?><?= $label !== null && $label['status'] !== 'approved' ? ' (' . $this->e($this->t('manage.translation.' . $label['status'])) . ')' : '' ?></label>
                <input id="f-label-<?= $code ?>" type="text" name="labels[<?= $code ?>]" maxlength="120" lang="<?= $code ?>" dir="<?= $code === 'ar' ? 'rtl' : 'ltr' ?>"
                       value="<?= $this->e($label['text'] ?? '') ?>"<?= $code === 'it' ? ' required' : '' ?>>
            </div>
        <?php endforeach; ?>
        <label class="checkbox"><input type="checkbox" name="approve" value="1"> <?= $this->e($this->t('manage.texts.approve')) ?></label>
    </fieldset>

    <?php if ($type === 'needs'): ?>
        <fieldset class="field">
            <legend><?= $this->e($this->t('admin.taxonomy.need_categories')) ?></legend>
            <input type="hidden" name="categories_sent" value="1">
            <div class="checkbox-grid">
                <?php foreach ($categories as $category): ?>
                    <label class="checkbox"><input type="checkbox" name="categories[]" value="<?= $category['id'] ?>"<?= in_array($category['id'], $item['categories'] ?? [], true) ? ' checked' : '' ?>>
                        <?= $this->e(($category['parent'] !== null ? $category['parent'] . ' › ' : '') . $category['name']) ?></label>
                <?php endforeach; ?>
            </div>
        </fieldset>
    <?php endif; ?>
    <?php if ($type === 'communities'): ?>
        <?= $this->partial('field', ['name' => 'countries', 'label' => $this->t('manage.field.countries'), 'value' => implode(', ', $item['countries'] ?? []), 'hint' => $this->t('manage.field.countries_hint')]) ?>
    <?php endif; ?>
    <div><button class="button" type="submit"><?= $this->e($this->t('manage.save')) ?></button></div>
</form>
