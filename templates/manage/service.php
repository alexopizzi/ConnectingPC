<?php
/**
 * Creazione (service = null) e gestione di un servizio: accesso, categorie, bisogni, lingue, sedi, testi.
 *
 * @var App\Core\View $this
 * @var 'admin'|'portal' $area
 * @var array<string, mixed> $organization
 * @var array<string, mixed>|null $service
 * @var list<array{id: int, label: string}> $organizationSites
 * @var list<array{id: int, name: string, parent: ?string}> $categories
 * @var list<array{id: int, name: string}> $needs
 * @var list<string> $languages
 * @var list<string> $locales
 * @var string $textLocale
 */
use App\Domain\Management\ServiceEditor;

$orgId = (int) $organization['id'];
$s = $service ?? [];
$value = fn (string $field, string $default = ''): string => $this->old($field, isset($s[$field]) && $s[$field] !== null ? (string) $s[$field] : $default);
$modes = $service === null ? ['in_person'] : explode(',', (string) $service['access_modes']);
$selectedCategories = $service['categories'] ?? [];
$selectedNeeds = $service['needs'] ?? [];
$selectedSites = $service['sites'] ?? [];
$languageRows = [...($service['languages'] ?? []), ...array_fill(0, 3, ['code' => '', 'mode' => 'staff'])];
$action = $service === null ? $this->route($area . '.services.store', ['id' => $orgId]) : $this->route($area . '.services.update', ['id' => (int) $service['id']]);
?>
<div class="manage-page<?= $area === 'portal' ? ' container section' : '' ?>">
<p><a href="<?= $this->e($this->route($area . '.organizations.show', ['id' => $orgId])) ?>#servizi"><?= $this->e($this->t('manage.back_to', ['name' => (string) $organization['name']])) ?></a></p>
<div class="page-actions">
    <h1><?= $this->e($service === null ? $this->t('manage.services.create') : (string) ($service['translations'][$service['source_locale']]['name'] ?? '#' . $service['id'])) ?></h1>
    <?php if ($service !== null && $service['publication_status'] === 'published'): ?>
        <a class="button button--secondary" href="<?= $this->e($this->route('public.service', ['id' => (int) $service['id']])) ?>"><?= $this->e($this->t('manage.view_public')) ?></a>
    <?php endif; ?>
</div>
<?= $this->partial('form-errors') ?>

<section class="section">
    <form class="form form--wide" method="post" action="<?= $this->e($action) ?>">
        <?= $this->csrfField() ?>
        <?php if ($service === null): ?>
            <?= $this->partial('field', ['name' => 'name', 'label' => $this->t('manage.field.service_name'), 'required' => true, 'hint' => $this->t('manage.services.name_hint')]) ?>
        <?php endif; ?>
        <div class="form-grid">
            <div class="field">
                <label for="f-cat"><?= $this->e($this->t('manage.field.primary_category')) ?></label>
                <select id="f-cat" name="primary_category_id" required>
                    <?php $group = null;
                    foreach ($categories as $category):
                        if ($category['parent'] === null):
                            if ($group !== null): ?></optgroup><?php endif;
                            $group = $category['name']; ?>
                            <optgroup label="<?= $this->e($group) ?>">
                        <?php endif; ?>
                        <option value="<?= $category['id'] ?>"<?= (int) $value('primary_category_id') === $category['id'] ? ' selected' : '' ?>><?= $this->e($category['parent'] === null ? $category['name'] . ' (' . $this->t('manage.field.whole_area') . ')' : $category['name']) ?></option>
                    <?php endforeach;
                    if ($group !== null): ?></optgroup><?php endif; ?>
                </select>
            </div>
            <div class="field">
                <label for="f-booking"><?= $this->e($this->t('manage.field.booking')) ?></label>
                <select id="f-booking" name="booking">
                    <?php foreach (ServiceEditor::BOOKING as $option): ?>
                        <option value="<?= $option ?>"<?= $value('booking', 'not_needed') === $option ? ' selected' : '' ?>><?= $this->e($this->t('manage.booking.' . $option)) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field">
                <label for="f-cost"><?= $this->e($this->t('manage.field.cost_type')) ?></label>
                <select id="f-cost" name="cost_type">
                    <?php foreach (ServiceEditor::COST as $option): ?>
                        <option value="<?= $option ?>"<?= $value('cost_type', 'unknown') === $option ? ' selected' : '' ?>><?= $this->e($this->t('manage.cost.' . $option)) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field">
                <label for="f-mediation"><?= $this->e($this->t('manage.field.mediation')) ?></label>
                <select id="f-mediation" name="mediation">
                    <?php foreach (ServiceEditor::MEDIATION as $option): ?>
                        <option value="<?= $option ?>"<?= $value('mediation', 'unknown') === $option ? ' selected' : '' ?>><?= $this->e($this->t('manage.mediation.' . $option)) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?= $this->partial('field', ['name' => 'booking_url', 'label' => $this->t('manage.field.booking_url'), 'type' => 'url', 'value' => $value('booking_url')]) ?>
            <?= $this->partial('field', ['name' => 'online_url', 'label' => $this->t('manage.field.online_url'), 'type' => 'url', 'value' => $value('online_url')]) ?>
            <?= $this->partial('field', ['name' => 'valid_from', 'label' => $this->t('manage.field.valid_from'), 'type' => 'date', 'value' => $value('valid_from')]) ?>
            <?= $this->partial('field', ['name' => 'valid_to', 'label' => $this->t('manage.field.valid_to'), 'type' => 'date', 'value' => $value('valid_to')]) ?>
            <?= $this->partial('field', ['name' => 'next_review_at', 'label' => $this->t('manage.field.next_review_at'), 'type' => 'date', 'value' => $value('next_review_at')]) ?>
            <?= $this->partial('manage/publication-select', ['current' => $value('publication_status', 'draft')]) ?>
        </div>

        <fieldset class="field">
            <legend><?= $this->e($this->t('manage.field.access_modes')) ?></legend>
            <div class="checkbox-grid">
                <?php foreach (ServiceEditor::ACCESS_MODES as $mode): ?>
                    <label class="checkbox"><input type="checkbox" name="access_modes[]" value="<?= $mode ?>"<?= in_array($mode, $modes, true) ? ' checked' : '' ?>> <?= $this->e($this->t('manage.access_mode.' . $mode)) ?></label>
                <?php endforeach; ?>
            </div>
        </fieldset>

        <fieldset class="field">
            <legend><?= $this->e($this->t('manage.field.sites')) ?></legend>
            <?php if ($organizationSites === []): ?>
                <p class="muted"><?= $this->e($this->t('manage.services.no_sites')) ?></p>
            <?php endif; ?>
            <?php foreach ($organizationSites as $site): ?>
                <div class="site-choice">
                    <label class="checkbox"><input type="checkbox" name="sites[]" value="<?= $site['id'] ?>"<?= in_array($site['id'], $selectedSites, true) ? ' checked' : '' ?>> <?= $this->e($site['label']) ?></label>
                    <label class="checkbox"><input type="radio" name="main_site" value="<?= $site['id'] ?>"<?= ($service['main_site'] ?? 0) === $site['id'] ? ' checked' : '' ?>> <?= $this->e($this->t('manage.field.main_site')) ?></label>
                </div>
            <?php endforeach; ?>
        </fieldset>

        <fieldset class="field">
            <legend><?= $this->e($this->t('manage.field.needs')) ?></legend>
            <div class="checkbox-grid">
                <?php foreach ($needs as $need): ?>
                    <label class="checkbox"><input type="checkbox" name="needs[]" value="<?= $need['id'] ?>"<?= in_array($need['id'], $selectedNeeds, true) ? ' checked' : '' ?>> <?= $this->e($need['name']) ?></label>
                <?php endforeach; ?>
            </div>
        </fieldset>

        <details class="field"<?= count($selectedCategories) > 1 ? ' open' : '' ?>>
            <summary><?= $this->e($this->t('manage.field.other_categories')) ?></summary>
            <div class="checkbox-grid">
                <?php foreach ($categories as $category):
                    if ($category['parent'] === null) {
                        continue;
                    } ?>
                    <label class="checkbox"><input type="checkbox" name="categories[]" value="<?= $category['id'] ?>"<?= in_array($category['id'], $selectedCategories, true) ? ' checked' : '' ?>> <?= $this->e($category['parent'] . ' › ' . $category['name']) ?></label>
                <?php endforeach; ?>
            </div>
        </details>

        <fieldset class="field">
            <legend><?= $this->e($this->t('manage.field.service_languages')) ?></legend>
            <div class="table-wrap">
                <table class="table">
                    <thead><tr><th scope="col"><?= $this->e($this->t('manage.field.language')) ?></th><th scope="col"><?= $this->e($this->t('manage.field.language_mode')) ?></th></tr></thead>
                    <tbody>
                    <?php foreach ($languageRows as $i => $row): ?>
                        <tr>
                            <td><select name="languages[<?= $i ?>][code]" aria-label="<?= $this->e($this->t('manage.field.language')) ?>">
                                <option value="">—</option>
                                <?php foreach ($languages as $code): ?>
                                    <option value="<?= $this->e($code) ?>"<?= $row['code'] === $code ? ' selected' : '' ?>><?= $this->e($this->languageName($code)) ?></option>
                                <?php endforeach; ?>
                            </select></td>
                            <td><select name="languages[<?= $i ?>][mode]" aria-label="<?= $this->e($this->t('manage.field.language_mode')) ?>">
                                <?php foreach (ServiceEditor::LANGUAGE_MODES as $mode): ?>
                                    <option value="<?= $mode ?>"<?= $row['mode'] === $mode ? ' selected' : '' ?>><?= $this->e($this->t('manage.language_mode.' . $mode)) ?></option>
                                <?php endforeach; ?>
                            </select></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </fieldset>

        <?php if ($service !== null): ?>
            <label class="checkbox"><input type="checkbox" name="mark_verified" value="1"> <?= $this->e($this->t('manage.field.mark_verified')) ?></label>
            <p class="muted"><?= $this->e($this->t('manage.verified_at', ['date' => $this->datetime($service['verified_at']) ?: '—'])) ?></p>
        <?php endif; ?>
        <div><button class="button" type="submit"><?= $this->e($this->t('manage.save')) ?></button></div>
    </form>
</section>

<?php if ($service !== null): ?>
    <section id="testi" class="section">
        <h2><?= $this->e($this->t('manage.texts.title')) ?></h2>
        <?= $this->partial('manage/texts-form', [
            'action' => $this->route($area . '.services.texts', ['id' => (int) $service['id']]),
            'showRoute' => $area . '.services.show',
            'showParams' => ['id' => (int) $service['id']],
            'locales' => $locales,
            'locale' => $textLocale,
            'source' => (string) $service['source_locale'],
            'translations' => $service['translations'],
            'fields' => [
                'name' => 'line', 'summary' => 'line', 'description' => 'text', 'target_audience' => 'text', 'requirements' => 'text',
                'documents' => 'text', 'access_info' => 'text', 'booking_info' => 'text', 'cost_info' => 'text', 'notes' => 'text', 'keywords' => 'line',
            ],
        ]) ?>
    </section>
<?php endif; ?>
</div>
