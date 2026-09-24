<?php
/**
 * Registrazione (item = null) e gestione di una richiesta in ingresso.
 *
 * @var App\Core\View $this
 * @var array<string, mixed>|null $item
 * @var list<array{id: int, name: string}> $organizations
 * @var list<array<string, mixed>> $staff
 */
use App\Domain\Management\InboundRequestService;

$r = $item ?? [];
$value = fn (string $field, string $default = ''): string => $this->old($field, isset($r[$field]) && $r[$field] !== null ? (string) $r[$field] : $default);
$action = $item === null ? $this->route('admin.requests.store') : $this->route('admin.requests.update', ['id' => (int) $item['id']]);
?>
<p><a href="<?= $this->e($this->route('admin.requests.index')) ?>"><?= $this->e($this->t('admin.requests.back')) ?></a></p>
<h1><?= $this->e($item === null ? $this->t('admin.requests.create') : $this->t('admin.requests.item', ['id' => (int) $item['id']])) ?></h1>
<?= $this->partial('form-errors') ?>
<?php if ($item !== null): ?>
    <p class="muted"><?= $this->e($this->t('admin.requests.received')) ?>: <?= $this->e($this->datetime((string) $item['created_at'])) ?> ·
        <?= $this->e($this->t('admin.requests.retention', ['date' => (string) $item['retention_until']])) ?></p>
<?php endif; ?>

<form class="form form--wide" method="post" action="<?= $this->e($action) ?>">
    <?= $this->csrfField() ?>
    <div class="form-grid">
        <div class="field">
            <label for="f-type"><?= $this->e($this->t('admin.requests.type')) ?></label>
            <select id="f-type" name="type">
                <?php foreach (InboundRequestService::TYPES as $type): ?>
                    <option value="<?= $type ?>"<?= $value('type', 'census') === $type ? ' selected' : '' ?>><?= $this->e($this->t('admin.requests.type.' . $type)) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="field">
            <label for="f-channel"><?= $this->e($this->t('admin.requests.channel')) ?></label>
            <select id="f-channel" name="channel">
                <?php foreach (InboundRequestService::CHANNELS as $channel): ?>
                    <option value="<?= $channel ?>"<?= $value('channel', 'email') === $channel ? ' selected' : '' ?>><?= $this->e($this->t('admin.requests.channel.' . $channel)) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="field">
            <label for="f-org"><?= $this->e($this->t('manage.field.organization')) ?></label>
            <select id="f-org" name="organization_id">
                <option value=""><?= $this->e($this->t('admin.requests.no_organization')) ?></option>
                <?php foreach ($organizations as $organization): ?>
                    <option value="<?= $organization['id'] ?>"<?= (int) $value('organization_id') === $organization['id'] ? ' selected' : '' ?>><?= $this->e($organization['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <?= $this->partial('field', ['name' => 'requester_name', 'label' => $this->t('admin.requests.requester'), 'value' => $value('requester_name')]) ?>
        <?= $this->partial('field', ['name' => 'requester_email', 'label' => $this->t('manage.contacts.kind.email'), 'type' => 'email', 'value' => $value('requester_email')]) ?>
        <?= $this->partial('field', ['name' => 'requester_phone', 'label' => $this->t('manage.contacts.kind.phone'), 'type' => 'tel', 'value' => $value('requester_phone')]) ?>
    </div>
    <div class="field">
        <label for="f-message"><?= $this->e($this->t('admin.requests.message')) ?></label>
        <textarea id="f-message" name="message" rows="5"><?= $this->e($value('message')) ?></textarea>
    </div>
    <?php if ($item !== null): ?>
        <div class="form-grid">
            <div class="field">
                <label for="f-status"><?= $this->e($this->t('admin.requests.status')) ?></label>
                <select id="f-status" name="status">
                    <?php foreach (InboundRequestService::STATUSES as $status): ?>
                        <option value="<?= $status ?>"<?= $value('status') === $status ? ' selected' : '' ?>><?= $this->e($this->t('admin.requests.status.' . $status)) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field">
                <label for="f-assigned"><?= $this->e($this->t('admin.requests.assigned_to')) ?></label>
                <select id="f-assigned" name="assigned_to">
                    <option value=""><?= $this->e($this->t('admin.common.none')) ?></option>
                    <?php foreach ($staff as $person): ?>
                        <option value="<?= (int) $person['id'] ?>"<?= (int) $value('assigned_to') === (int) $person['id'] ? ' selected' : '' ?>><?= $this->e($person['display_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="field">
            <label for="f-resolution"><?= $this->e($this->t('admin.requests.resolution')) ?></label>
            <textarea id="f-resolution" name="resolution_note" rows="3"><?= $this->e($value('resolution_note')) ?></textarea>
        </div>
        <?php if (!empty($item['organization_id'])): ?>
            <p><a href="<?= $this->e($this->route('admin.organizations.show', ['id' => (int) $item['organization_id']])) ?>"><?= $this->e($this->t('admin.requests.open_organization')) ?></a></p>
        <?php endif; ?>
    <?php endif; ?>
    <p class="field__hint"><?= $this->e($this->t('admin.requests.privacy_hint')) ?></p>
    <div><button class="button" type="submit"><?= $this->e($this->t('manage.save')) ?></button></div>
</form>
