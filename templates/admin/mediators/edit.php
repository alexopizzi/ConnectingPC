<?php
/**
 * Creazione (mediator = null) e gestione di un mediatore (vault "63"): dati, consenso e visibilità,
 * lingue, ambiti, zone, dati riservati agli amministratori, testi e recapiti.
 *
 * @var App\Core\View $this
 * @var array<string, mixed>|null $mediator
 * @var list<array{id: int, name: string}> $organizations
 * @var list<string> $languages
 * @var list<array{id: int, name: string}> $domains
 * @var list<array{id: int, name: string, type: string}> $wideTerritories
 * @var list<array<string, mixed>> $municipalities
 * @var list<string> $locales
 * @var string $textLocale
 */
use App\Domain\Management\MediatorEditor;

$m = $mediator ?? [];
$value = fn (string $field, string $default = ''): string => $this->old($field, isset($m[$field]) && $m[$field] !== null ? (string) $m[$field] : $default);
$types = $mediator === null ? [] : explode(',', (string) $mediator['mediation_types']);
$selectedDomains = $mediator['domains'] ?? [];
$selectedTerritories = $mediator['territories'] ?? [];
$languageRows = [...($mediator['languages'] ?? []), ...array_fill(0, 3, ['code' => '', 'level' => 'native'])];
$action = $mediator === null ? $this->route('admin.mediators.store') : $this->route('admin.mediators.update', ['id' => (int) $mediator['id']]);
?>
<p><a href="<?= $this->e($this->route('admin.mediators.index')) ?>"><?= $this->e($this->t('admin.mediators.back')) ?></a></p>
<h1><?= $this->e($mediator === null ? $this->t('admin.mediators.create') : $mediator['first_name'] . ' ' . $mediator['last_name']) ?></h1>
<?= $this->partial('form-errors') ?>

<section class="section">
    <form class="form form--wide" method="post" action="<?= $this->e($action) ?>">
        <?= $this->csrfField() ?>
        <div class="form-grid">
            <?= $this->partial('field', ['name' => 'first_name', 'label' => $this->t('manage.field.first_name'), 'value' => $value('first_name'), 'required' => true]) ?>
            <?= $this->partial('field', ['name' => 'last_name', 'label' => $this->t('manage.field.last_name'), 'value' => $value('last_name'), 'required' => true]) ?>
            <?= $this->partial('field', ['name' => 'public_display_name', 'label' => $this->t('manage.field.public_display_name'), 'value' => $value('public_display_name'), 'hint' => $this->t('manage.field.public_display_name_hint')]) ?>
            <div class="field">
                <label for="f-org"><?= $this->e($this->t('manage.field.organization')) ?></label>
                <select id="f-org" name="organization_id">
                    <option value=""><?= $this->e($this->t('admin.common.none')) ?></option>
                    <?php foreach ($organizations as $organization): ?>
                        <option value="<?= $organization['id'] ?>"<?= (int) $value('organization_id') === $organization['id'] ? ' selected' : '' ?>><?= $this->e($organization['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field">
                <label for="f-av"><?= $this->e($this->t('manage.field.availability')) ?></label>
                <select id="f-av" name="availability">
                    <?php foreach (MediatorEditor::AVAILABILITY as $option): ?>
                        <option value="<?= $option ?>"<?= $value('availability', 'unknown') === $option ? ' selected' : '' ?>><?= $this->e($this->t('mediators.availability.' . $option)) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?= $this->partial('field', ['name' => 'next_review_at', 'label' => $this->t('manage.field.next_review_at'), 'type' => 'date', 'value' => $value('next_review_at')]) ?>
            <?= $this->partial('manage/publication-select', ['current' => $value('publication_status', 'draft')]) ?>
        </div>

        <fieldset class="field">
            <legend><?= $this->e($this->t('manage.field.mediation_types')) ?></legend>
            <div class="checkbox-grid">
                <?php foreach (MediatorEditor::TYPES as $type): ?>
                    <label class="checkbox"><input type="checkbox" name="mediation_types[]" value="<?= $type ?>"<?= in_array($type, $types, true) ? ' checked' : '' ?>> <?= $this->e($this->t('mediators.type.' . $type)) ?></label>
                <?php endforeach; ?>
            </div>
        </fieldset>

        <fieldset class="field callout">
            <legend><?= $this->e($this->t('manage.field.privacy')) ?></legend>
            <div class="field">
                <label for="f-vis"><?= $this->e($this->t('manage.field.profile_visibility')) ?></label>
                <select id="f-vis" name="profile_visibility" aria-describedby="f-vis-hint">
                    <?php foreach (MediatorEditor::VISIBILITY as $visibility): ?>
                        <option value="<?= $visibility ?>"<?= $value('profile_visibility', 'admin') === $visibility ? ' selected' : '' ?>><?= $this->e($this->t('manage.mediator_visibility.' . $visibility)) ?></option>
                    <?php endforeach; ?>
                </select>
                <p class="field__hint" id="f-vis-hint"><?= $this->e($this->t('manage.mediator_visibility.hint')) ?></p>
            </div>
            <label class="checkbox"><input type="checkbox" name="consent_given" value="1"<?= !empty($m['public_consent_at']) ? ' checked' : '' ?>> <?= $this->e($this->t('manage.field.consent_given')) ?></label>
            <?php if (!empty($m['public_consent_at'])): ?>
                <p class="muted"><?= $this->e($this->t('manage.field.consent_since', ['date' => $this->datetime((string) $m['public_consent_at'])])) ?></p>
            <?php endif; ?>
            <?= $this->partial('field', ['name' => 'consent_reference', 'label' => $this->t('manage.field.consent_reference'), 'value' => $value('consent_reference')]) ?>
        </fieldset>

        <fieldset class="field">
            <legend><?= $this->e($this->t('manage.field.languages')) ?></legend>
            <div class="table-wrap">
                <table class="table">
                    <thead><tr><th scope="col"><?= $this->e($this->t('manage.field.language')) ?></th><th scope="col"><?= $this->e($this->t('manage.field.level')) ?></th></tr></thead>
                    <tbody>
                    <?php foreach ($languageRows as $i => $row): ?>
                        <tr>
                            <td><select name="languages[<?= $i ?>][code]" aria-label="<?= $this->e($this->t('manage.field.language')) ?>">
                                <option value="">—</option>
                                <?php foreach ($languages as $code): ?>
                                    <option value="<?= $this->e($code) ?>"<?= $row['code'] === $code ? ' selected' : '' ?>><?= $this->e($this->languageName($code)) ?></option>
                                <?php endforeach; ?>
                            </select></td>
                            <td><select name="languages[<?= $i ?>][level]" aria-label="<?= $this->e($this->t('manage.field.level')) ?>">
                                <?php foreach (MediatorEditor::LEVELS as $level): ?>
                                    <option value="<?= $level ?>"<?= $row['level'] === $level ? ' selected' : '' ?>><?= $this->e($this->t('mediators.level.' . $level)) ?></option>
                                <?php endforeach; ?>
                            </select></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </fieldset>

        <fieldset class="field">
            <legend><?= $this->e($this->t('mediators.domains')) ?></legend>
            <div class="checkbox-grid">
                <?php foreach ($domains as $domain): ?>
                    <label class="checkbox"><input type="checkbox" name="domains[]" value="<?= $domain['id'] ?>"<?= in_array($domain['id'], $selectedDomains, true) ? ' checked' : '' ?>> <?= $this->e($domain['name']) ?></label>
                <?php endforeach; ?>
            </div>
        </fieldset>

        <fieldset class="field">
            <legend><?= $this->e($this->t('mediators.territories')) ?></legend>
            <div class="checkbox-grid">
                <?php foreach ($wideTerritories as $territory): ?>
                    <label class="checkbox"><input type="checkbox" name="territories[]" value="<?= $territory['id'] ?>"<?= in_array($territory['id'], $selectedTerritories, true) ? ' checked' : '' ?>> <strong><?= $this->e($territory['name']) ?></strong></label>
                <?php endforeach; ?>
            </div>
            <details<?= array_diff($selectedTerritories, array_column($wideTerritories, 'id')) !== [] ? ' open' : '' ?>>
                <summary><?= $this->e($this->t('manage.field.single_municipalities')) ?></summary>
                <div class="checkbox-grid">
                    <?php foreach ($municipalities as $district):
                        foreach ($district['municipalities'] as $town): ?>
                            <label class="checkbox"><input type="checkbox" name="territories[]" value="<?= $town['id'] ?>"<?= in_array($town['id'], $selectedTerritories, true) ? ' checked' : '' ?>> <?= $this->e($town['name']) ?></label>
                        <?php endforeach;
                    endforeach; ?>
                </div>
            </details>
        </fieldset>

        <fieldset class="field callout">
            <legend><?= $this->e($this->t('manage.field.admin_only')) ?></legend>
            <div class="field">
                <label for="f-ver"><?= $this->e($this->t('manage.field.verification_status')) ?></label>
                <select id="f-ver" name="verification_status">
                    <?php foreach (MediatorEditor::VERIFICATION as $status): ?>
                        <option value="<?= $status ?>"<?= $value('verification_status', 'unverified') === $status ? ' selected' : '' ?>><?= $this->e($this->t('manage.verification_status.' . $status)) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field">
                <label for="f-qual"><?= $this->e($this->t('manage.field.qualifications_admin')) ?></label>
                <textarea id="f-qual" name="qualifications_admin" rows="3"><?= $this->e($value('qualifications_admin')) ?></textarea>
            </div>
            <div class="field">
                <label for="f-notes"><?= $this->e($this->t('manage.field.admin_notes')) ?></label>
                <textarea id="f-notes" name="admin_notes" rows="3"><?= $this->e($value('admin_notes')) ?></textarea>
            </div>
        </fieldset>
        <div><button class="button" type="submit"><?= $this->e($this->t('manage.save')) ?></button></div>
    </form>
</section>

<?php if ($mediator !== null): ?>
    <section id="recapiti" class="section">
        <h2><?= $this->e($this->t('manage.contacts.title')) ?></h2>
        <p class="field__hint"><?= $this->e($this->t('admin.mediators.contacts_hint')) ?></p>
        <?= $this->partial('manage/contacts-form', ['action' => $this->route('admin.mediators.contacts', ['id' => (int) $mediator['id']]), 'contacts' => $mediator['contacts']]) ?>
    </section>

    <section id="testi" class="section">
        <h2><?= $this->e($this->t('manage.texts.title')) ?></h2>
        <?= $this->partial('manage/texts-form', [
            'action' => $this->route('admin.mediators.texts', ['id' => (int) $mediator['id']]),
            'showRoute' => 'admin.mediators.show',
            'showParams' => ['id' => (int) $mediator['id']],
            'locales' => $locales,
            'locale' => $textLocale,
            'source' => 'it',
            'translations' => $mediator['translations'],
            'fields' => ['bio' => 'text', 'competences' => 'text', 'availability_notes' => 'text'],
        ]) ?>
    </section>
<?php endif; ?>
