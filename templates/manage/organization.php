<?php
/**
 * Scheda di gestione di un'organizzazione, comune ad area amministrativa e area riservata:
 * profilo, stato (solo admin), testi, lingue e comunità, recapiti, sedi, servizi e mediatori.
 *
 * @var App\Core\View $this
 * @var 'admin'|'portal' $area
 * @var array<string, mixed> $organization
 * @var string $textLocale
 * @var list<string> $locales
 * @var list<array{id: int, name: string}> $types
 * @var list<string> $languages
 * @var list<array{id: int, name: string}> $communities
 * @var array<string, bool> $can
 */
$id = (int) $organization['id'];
$axes = App\Domain\Management\OrganizationEditor::STATUS_AXES;
$typeName = array_column($types, 'name', 'id')[(int) $organization['organization_type_id']] ?? '';
?>
<div class="manage-page<?= $area === 'portal' ? ' container section' : '' ?>">
<p><?php if ($area === 'admin'): ?>
    <a href="<?= $this->e($this->route('admin.organizations.index')) ?>"><?= $this->e($this->t('admin.organizations.back')) ?></a>
<?php else: ?>
    <a href="<?= $this->e($this->route('portal.dashboard')) ?>"><?= $this->e($this->t('manage.back_to_dashboard')) ?></a>
<?php endif; ?></p>
<div class="page-actions">
    <h1><?= $this->e($organization['name']) ?></h1>
    <?php if ($organization['publication_status'] === 'published' && $organization['listing_status'] === 'listed'): ?>
        <a class="button button--secondary" href="<?= $this->e($this->route('public.organization', ['id' => $id])) ?>"><?= $this->e($this->t('manage.view_public')) ?></a>
    <?php endif; ?>
</div>
<ul class="badges">
    <li class="badge"><?= $this->e($this->t('manage.publication.' . $organization['publication_status'])) ?></li>
    <?php if ($organization['listing_status'] === 'hidden'): ?><li class="badge badge--warning"><?= $this->e($this->t('manage.listing_status.hidden')) ?></li><?php endif; ?>
    <li class="badge"><?= $this->e($this->t($can['publish'] ? 'portal.policy.direct' : 'portal.policy.review')) ?></li>
</ul>
<?= $this->partial('form-errors') ?>
<?php if ($area === 'portal' && !$can['profile'] && !$can['sites'] && !$can['services']): ?>
    <p class="alert alert--info"><?= $this->e($this->t('manage.read_only')) ?></p>
<?php endif; ?>

<nav class="toc" aria-label="<?= $this->e($this->t('manage.sections')) ?>">
    <ul>
        <li><a href="#profilo"><?= $this->e($this->t('manage.organization.profile')) ?></a></li>
        <?php if ($can['status']): ?><li><a href="#stato"><?= $this->e($this->t('admin.organizations.status')) ?></a></li><?php endif; ?>
        <li><a href="#testi"><?= $this->e($this->t('manage.texts.title')) ?></a></li>
        <li><a href="#collegamenti"><?= $this->e($this->t('manage.organization.links')) ?></a></li>
        <li><a href="#recapiti"><?= $this->e($this->t('manage.contacts.title')) ?></a></li>
        <li><a href="#sedi"><?= $this->e($this->t('manage.sites.title')) ?> (<?= count($organization['sites']) ?>)</a></li>
        <li><a href="#servizi"><?= $this->e($this->t('manage.services.title')) ?> (<?= count($organization['services']) ?>)</a></li>
        <li><a href="#mediatori"><?= $this->e($this->t('manage.mediators.title')) ?> (<?= count($organization['mediators']) ?>)</a></li>
    </ul>
</nav>

<section id="profilo" class="section">
    <h2><?= $this->e($this->t('manage.organization.profile')) ?></h2>
    <?php if ($can['profile']): ?>
        <form class="form" method="post" action="<?= $this->e($this->route($area . '.organizations.update', ['id' => $id])) ?>">
            <?= $this->csrfField() ?>
            <?= $this->partial('field', ['name' => 'name', 'label' => $this->t('manage.field.name'), 'value' => (string) $organization['name'], 'required' => true]) ?>
            <?= $this->partial('field', ['name' => 'short_name', 'label' => $this->t('manage.field.short_name'), 'value' => (string) $organization['short_name']]) ?>
            <?= $this->partial('field', ['name' => 'website', 'label' => $this->t('manage.field.website'), 'type' => 'url', 'value' => (string) $organization['website']]) ?>
            <?php if ($can['manage_all']): ?>
                <div class="field">
                    <label for="f-type"><?= $this->e($this->t('manage.field.organization_type')) ?></label>
                    <select id="f-type" name="organization_type_id">
                        <?php foreach ($types as $type): ?>
                            <option value="<?= $type['id'] ?>"<?= (int) $organization['organization_type_id'] === $type['id'] ? ' selected' : '' ?>><?= $this->e($type['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php else: ?>
                <p><strong><?= $this->e($this->t('manage.field.organization_type')) ?>:</strong> <?= $this->e($typeName) ?></p>
            <?php endif; ?>
            <div class="field">
                <label for="f-source"><?= $this->e($this->t('manage.field.source_locale')) ?></label>
                <select id="f-source" name="source_locale">
                    <?php foreach ($locales as $code): ?>
                        <option value="<?= $code ?>"<?= $organization['source_locale'] === $code ? ' selected' : '' ?>><?= $this->e($this->languageName($code)) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <label class="checkbox"><input type="checkbox" name="is_community_based" value="1"<?= $organization['is_community_based'] ? ' checked' : '' ?>> <?= $this->e($this->t('manage.field.is_community_based')) ?></label>
            <div><button class="button" type="submit"><?= $this->e($this->t('manage.save')) ?></button></div>
        </form>
    <?php else: ?>
        <dl class="details">
            <dt><?= $this->e($this->t('manage.field.organization_type')) ?></dt><dd><?= $this->e($typeName) ?></dd>
            <dt><?= $this->e($this->t('manage.field.website')) ?></dt><dd class="ltr"><?= $this->e((string) $organization['website'] ?: '—') ?></dd>
        </dl>
    <?php endif; ?>
</section>

<?php if ($can['status']): ?>
<section id="stato" class="section">
    <h2><?= $this->e($this->t('admin.organizations.status')) ?></h2>
    <p class="field__hint"><?= $this->e($this->t('admin.organizations.status_hint')) ?></p>
    <form class="form form--wide" method="post" action="<?= $this->e($this->route('admin.organizations.status', ['id' => $id])) ?>">
        <?= $this->csrfField() ?>
        <div class="form-grid">
            <?php foreach ($axes as $axis => $values): ?>
                <div class="field">
                    <label for="f-<?= $axis ?>"><?= $this->e($this->t('manage.field.' . $axis)) ?></label>
                    <select id="f-<?= $axis ?>" name="<?= $axis ?>">
                        <?php foreach ($values as $value): ?>
                            <option value="<?= $value ?>"<?= $organization[$axis] === $value ? ' selected' : '' ?>><?= $this->e($this->t('manage.' . $axis . '.' . $value)) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endforeach; ?>
            <div class="field">
                <label for="f-policy"><?= $this->e($this->t('manage.field.publication_policy')) ?></label>
                <select id="f-policy" name="publication_policy">
                    <option value=""<?= $organization['publication_policy'] === null ? ' selected' : '' ?>><?= $this->e($this->t('manage.publication_policy.default')) ?></option>
                    <?php foreach (['direct', 'review'] as $policy): ?>
                        <option value="<?= $policy ?>"<?= $organization['publication_policy'] === $policy ? ' selected' : '' ?>><?= $this->e($this->t('manage.publication_policy.' . $policy)) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?= $this->partial('manage/publication-select', ['current' => (string) $organization['publication_status']]) ?>
            <?= $this->partial('field', ['name' => 'next_review_at', 'label' => $this->t('manage.field.next_review_at'), 'type' => 'date', 'value' => (string) $organization['next_review_at']]) ?>
        </div>
        <label class="checkbox"><input type="checkbox" name="portal_edit_enabled" value="1"<?= $organization['portal_edit_enabled'] ? ' checked' : '' ?>> <?= $this->e($this->t('manage.field.portal_edit_enabled')) ?></label>
        <div class="field">
            <label for="f-note"><?= $this->e($this->t('manage.field.status_note')) ?></label>
            <textarea id="f-note" name="status_note" rows="2" maxlength="500"><?= $this->e((string) $organization['status_note']) ?></textarea>
        </div>
        <p class="muted"><?= $this->e($this->t('manage.verified_at', ['date' => $this->datetime($organization['verified_at']) ?: '—'])) ?></p>
        <div><button class="button" type="submit"><?= $this->e($this->t('manage.save')) ?></button></div>
    </form>
</section>
<?php endif; ?>

<section id="testi" class="section">
    <h2><?= $this->e($this->t('manage.texts.title')) ?></h2>
    <?php if ($can['profile'] || $can['translations']): ?>
        <?= $this->partial('manage/texts-form', [
            'action' => $this->route($area . '.organizations.texts', ['id' => $id]),
            'showRoute' => $area . '.organizations.show',
            'showParams' => ['id' => $id],
            'locales' => $locales,
            'locale' => $textLocale,
            'source' => (string) $organization['source_locale'],
            'translations' => $organization['translations'],
            'fields' => ['description' => 'text', 'activities' => 'text', 'participation_info' => 'text'],
        ]) ?>
    <?php else: ?>
        <p class="muted"><?= $this->e($this->t('manage.read_only')) ?></p>
    <?php endif; ?>
</section>

<section id="collegamenti" class="section">
    <h2><?= $this->e($this->t('manage.organization.links')) ?></h2>
    <?php if ($can['profile']): ?>
    <form class="form form--wide" method="post" action="<?= $this->e($this->route($area . '.organizations.links', ['id' => $id])) ?>">
        <?= $this->csrfField() ?>
        <fieldset class="field">
            <legend><?= $this->e($this->t('manage.field.languages')) ?></legend>
            <div class="checkbox-grid">
                <?php foreach ($languages as $code): ?>
                    <label class="checkbox"><input type="checkbox" name="languages[]" value="<?= $this->e($code) ?>"<?= in_array($code, $organization['languages'], true) ? ' checked' : '' ?>> <?= $this->e($this->languageName($code)) ?></label>
                <?php endforeach; ?>
            </div>
        </fieldset>
        <fieldset class="field">
            <legend><?= $this->e($this->t('manage.field.communities')) ?></legend>
            <div class="checkbox-grid">
                <?php foreach ($communities as $community): ?>
                    <label class="checkbox"><input type="checkbox" name="communities[]" value="<?= $community['id'] ?>"<?= in_array($community['id'], $organization['communities'], true) ? ' checked' : '' ?>> <?= $this->e($community['name']) ?></label>
                <?php endforeach; ?>
            </div>
        </fieldset>
        <?= $this->partial('field', ['name' => 'countries', 'label' => $this->t('manage.field.countries'), 'value' => implode(', ', $organization['countries']), 'hint' => $this->t('manage.field.countries_hint')]) ?>
        <div><button class="button" type="submit"><?= $this->e($this->t('manage.save')) ?></button></div>
    </form>
    <?php else: ?>
        <p><?= $this->e(implode(', ', array_map($this->languageName(...), $organization['languages'])) ?: '—') ?></p>
    <?php endif; ?>
</section>

<section id="recapiti" class="section">
    <h2><?= $this->e($this->t('manage.contacts.title')) ?></h2>
    <?php if ($can['profile']): ?>
        <?= $this->partial('manage/contacts-form', ['action' => $this->route($area . '.organizations.contacts', ['id' => $id]), 'contacts' => $organization['contacts']]) ?>
    <?php else: ?>
        <p class="muted"><?= $this->e($this->t('manage.read_only')) ?></p>
    <?php endif; ?>
</section>

<section id="sedi" class="section">
    <div class="page-actions">
        <h2><?= $this->e($this->t('manage.sites.title')) ?></h2>
        <?php if ($can['sites']): ?>
            <a class="button button--secondary" href="<?= $this->e($this->route($area . '.sites.create', ['id' => $id])) ?>"><?= $this->e($this->t('manage.sites.create')) ?></a>
        <?php endif; ?>
    </div>
    <?php if ($organization['sites'] === []): ?>
        <p class="muted"><?= $this->e($this->t('manage.empty')) ?></p>
    <?php else: ?>
        <ul class="plain-list">
            <?php foreach ($organization['sites'] as $site): ?>
                <li><a href="<?= $this->e($this->route($area . '.sites.show', ['id' => (int) $site['id']])) ?>"><?= $this->e(($site['name'] ? $site['name'] . ' – ' : '') . $site['address_line'] . ', ' . $site['town']) ?></a>
                    <span class="badge"><?= $this->e($this->t('manage.publication.' . $site['publication_status'])) ?></span>
                    <?php if (!$site['is_public_place']): ?><span class="badge"><?= $this->e($this->t('manage.field.not_public_place')) ?></span><?php endif; ?>
                    <?php if ($site['lat'] === null): ?><span class="badge badge--warning"><?= $this->e($this->t('manage.sites.no_coordinates')) ?></span><?php endif; ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</section>

<section id="servizi" class="section">
    <div class="page-actions">
        <h2><?= $this->e($this->t('manage.services.title')) ?></h2>
        <?php if ($can['services']): ?>
            <a class="button button--secondary" href="<?= $this->e($this->route($area . '.services.create', ['id' => $id])) ?>"><?= $this->e($this->t('manage.services.create')) ?></a>
        <?php endif; ?>
    </div>
    <?php if ($organization['services'] === []): ?>
        <p class="muted"><?= $this->e($this->t('manage.empty')) ?></p>
    <?php else: ?>
        <ul class="plain-list">
            <?php foreach ($organization['services'] as $service): ?>
                <li><a href="<?= $this->e($this->route($area . '.services.show', ['id' => (int) $service['id']])) ?>"><?= $this->e($service['name']) ?></a>
                    <span class="badge"><?= $this->e($this->t('manage.publication.' . $service['publication_status'])) ?></span></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</section>

<section id="mediatori" class="section">
    <div class="page-actions">
        <h2><?= $this->e($this->t('manage.mediators.title')) ?></h2>
        <?php if ($can['mediators']): ?>
            <a class="button button--secondary" href="<?= $this->e($this->route($area . '.mediators.create', ['organizzazione' => $id])) ?>"><?= $this->e($this->t('manage.mediators.create')) ?></a>
        <?php endif; ?>
    </div>
    <?php if ($organization['mediators'] === []): ?>
        <p class="muted"><?= $this->e($this->t('manage.empty')) ?></p>
    <?php else: ?>
        <ul class="plain-list">
            <?php foreach ($organization['mediators'] as $mediator): ?>
                <li><?php if ($can['mediators'] || $area === 'admin'): ?><a href="<?= $this->e($this->route($area . '.mediators.show', ['id' => (int) $mediator['id']])) ?>"><?= $this->e($mediator['first_name'] . ' ' . $mediator['last_name']) ?></a><?php else: ?><?= $this->e($mediator['first_name'] . ' ' . $mediator['last_name']) ?><?php endif; ?>
                    <span class="badge"><?= $this->e($this->t('manage.visibility.' . $mediator['profile_visibility'])) ?></span></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</section>
</div>
