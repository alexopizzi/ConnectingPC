<?php
/**
 * Creazione (site = null) e gestione di una sede: dati e coordinate, orari, recapiti, testi.
 *
 * @var App\Core\View $this
 * @var 'admin'|'portal' $area
 * @var array<string, mixed> $organization
 * @var array<string, mixed>|null $site
 * @var list<array<string, mixed>> $municipalities
 * @var list<string> $locales
 * @var string $textLocale
 */
$orgId = (int) $organization['id'];
$s = $site ?? [];
$value = fn (string $field, string $default = ''): string => $this->old($field, isset($s[$field]) && $s[$field] !== null ? (string) $s[$field] : $default);
$selectedTown = (int) $value('territory_id');
$weekdays = range(1, 7);
$hours = $site === null ? [] : [...$site['hours'], ...array_fill(0, 3, ['weekday' => 1, 'opens' => '', 'closes' => '', 'appointment' => false])];
?>
<div class="manage-page<?= $area === 'portal' ? ' container section' : '' ?>">
<p><a href="<?= $this->e($this->route($area . '.organizations.show', ['id' => $orgId])) ?>#sedi"><?= $this->e($this->t('manage.back_to', ['name' => (string) $organization['name']])) ?></a></p>
<h1><?= $this->e($site === null ? $this->t('manage.sites.create') : ($site['name'] ?: $site['address_line'])) ?></h1>
<?= $this->partial('form-errors') ?>

<section class="section">
    <form class="form form--wide" method="post" action="<?= $this->e($site === null ? $this->route($area . '.sites.store', ['id' => $orgId]) : $this->route($area . '.sites.update', ['id' => (int) $site['id']])) ?>">
        <?= $this->csrfField() ?>
        <div class="form-grid">
            <?= $this->partial('field', ['name' => 'name', 'label' => $this->t('manage.field.site_name'), 'value' => $value('name')]) ?>
            <?= $this->partial('field', ['name' => 'address_line', 'label' => $this->t('manage.field.address_line'), 'value' => $value('address_line'), 'required' => true]) ?>
            <?= $this->partial('field', ['name' => 'postal_code', 'label' => $this->t('manage.field.postal_code'), 'value' => $value('postal_code')]) ?>
            <div class="field">
                <label for="f-town"><?= $this->e($this->t('manage.field.municipality')) ?></label>
                <select id="f-town" name="territory_id" required>
                    <?php foreach ($municipalities as $district): ?>
                        <optgroup label="<?= $this->e($district['name']) ?>">
                            <?php foreach ($district['municipalities'] as $town): ?>
                                <option value="<?= $town['id'] ?>"<?= $selectedTown === $town['id'] ? ' selected' : '' ?>><?= $this->e($town['name']) ?></option>
                            <?php endforeach; ?>
                        </optgroup>
                    <?php endforeach; ?>
                </select>
            </div>
            <?= $this->partial('field', ['name' => 'lat', 'label' => $this->t('manage.field.lat'), 'value' => $value('lat'), 'hint' => $this->t('manage.field.coordinates_hint')]) ?>
            <?= $this->partial('field', ['name' => 'lng', 'label' => $this->t('manage.field.lng'), 'value' => $value('lng')]) ?>
            <div class="field">
                <label for="f-step"><?= $this->e($this->t('manage.field.step_free_access')) ?></label>
                <select id="f-step" name="step_free_access">
                    <?php foreach (['yes', 'partial', 'no', 'unknown'] as $option): ?>
                        <option value="<?= $option ?>"<?= $value('step_free_access', 'unknown') === $option ? ' selected' : '' ?>><?= $this->e($this->t('manage.accessibility.' . $option)) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field">
                <label for="f-toilet"><?= $this->e($this->t('manage.field.accessible_toilet')) ?></label>
                <select id="f-toilet" name="accessible_toilet">
                    <?php foreach (['yes', 'no', 'unknown'] as $option): ?>
                        <option value="<?= $option ?>"<?= $value('accessible_toilet', 'unknown') === $option ? ' selected' : '' ?>><?= $this->e($this->t('manage.accessibility.' . $option)) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?= $this->partial('manage/publication-select', ['current' => $value('publication_status', 'draft')]) ?>
        </div>
        <label class="checkbox"><input type="checkbox" name="is_public_place" value="1"<?= $value('is_public_place', '1') === '1' ? ' checked' : '' ?>> <?= $this->e($this->t('manage.field.is_public_place')) ?></label>
        <p class="field__hint"><?= $this->e($this->t('manage.field.is_public_place_hint')) ?></p>
        <?php if ($site !== null && $site['lat'] !== null): ?>
            <p><a href="https://www.openstreetmap.org/?mlat=<?= $this->e($site['lat']) ?>&amp;mlon=<?= $this->e($site['lng']) ?>#map=18/<?= $this->e($site['lat']) ?>/<?= $this->e($site['lng']) ?>" rel="noopener noreferrer" target="_blank"><?= $this->e($this->t('manage.sites.check_on_map')) ?></a></p>
        <?php endif; ?>
        <div><button class="button" type="submit"><?= $this->e($this->t('manage.save')) ?></button></div>
    </form>
</section>

<?php if ($site !== null):
    $siteId = (int) $site['id']; ?>
    <section id="orari" class="section">
        <h2><?= $this->e($this->t('manage.hours.title')) ?></h2>
        <form class="form form--wide" method="post" action="<?= $this->e($this->route($area . '.sites.hours', ['id' => $siteId])) ?>">
            <?= $this->csrfField() ?>
            <div class="table-wrap">
                <table class="table">
                    <thead><tr>
                        <th scope="col"><?= $this->e($this->t('manage.hours.day')) ?></th>
                        <th scope="col"><?= $this->e($this->t('manage.hours.opens')) ?></th>
                        <th scope="col"><?= $this->e($this->t('manage.hours.closes')) ?></th>
                        <th scope="col"><?= $this->e($this->t('manage.hours.appointment')) ?></th>
                    </tr></thead>
                    <tbody>
                    <?php foreach ($hours as $i => $row): ?>
                        <tr>
                            <td><select name="hours[<?= $i ?>][weekday]" aria-label="<?= $this->e($this->t('manage.hours.day')) ?>">
                                <?php foreach ($weekdays as $day): ?>
                                    <option value="<?= $day ?>"<?= $row['weekday'] === $day ? ' selected' : '' ?>><?= $this->e($this->weekdayName($day)) ?></option>
                                <?php endforeach; ?>
                            </select></td>
                            <td><input type="time" name="hours[<?= $i ?>][opens]" value="<?= $this->e($row['opens']) ?>" aria-label="<?= $this->e($this->t('manage.hours.opens')) ?>"></td>
                            <td><input type="time" name="hours[<?= $i ?>][closes]" value="<?= $this->e($row['closes']) ?>" aria-label="<?= $this->e($this->t('manage.hours.closes')) ?>"></td>
                            <td><input type="checkbox" name="hours[<?= $i ?>][appointment]" value="1"<?= $row['appointment'] ? ' checked' : '' ?> aria-label="<?= $this->e($this->t('manage.hours.appointment')) ?>"></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <p class="field__hint"><?= $this->e($this->t('manage.hours.hint')) ?></p>
            <div><button class="button" type="submit"><?= $this->e($this->t('manage.save')) ?></button></div>
        </form>
    </section>

    <section id="recapiti" class="section">
        <h2><?= $this->e($this->t('manage.contacts.title')) ?></h2>
        <?= $this->partial('manage/contacts-form', ['action' => $this->route($area . '.sites.contacts', ['id' => $siteId]), 'contacts' => $site['contacts']]) ?>
    </section>

    <section id="testi" class="section">
        <h2><?= $this->e($this->t('manage.texts.title')) ?></h2>
        <?= $this->partial('manage/texts-form', [
            'action' => $this->route($area . '.sites.texts', ['id' => $siteId]),
            'showRoute' => $area . '.sites.show',
            'showParams' => ['id' => $siteId],
            'locales' => $locales,
            'locale' => $textLocale,
            'source' => (string) $site['source_locale'],
            'translations' => $site['translations'],
            'fields' => ['directions' => 'text', 'accessibility_notes' => 'text', 'hours_notes' => 'text'],
        ]) ?>
    </section>
<?php endif; ?>
</div>
