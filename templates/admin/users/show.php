<?php
/**
 * @var App\Core\View $this
 * @var array<string, mixed> $target
 * @var list<array<string, mixed>> $assignments
 * @var list<array<string, mixed>> $roles
 * @var list<array<string, mixed>> $organizations
 * @var array<string, array<string, mixed>> $locales
 */
$id = (int) $target['id'];
?>
<p><a href="<?= $this->e($this->route('admin.users.index')) ?>"><?= $this->e($this->t('admin.users.back')) ?></a></p>
<h1><?= $this->e($target['display_name']) ?></h1>

<dl class="details">
    <dt><?= $this->e($this->t('auth.field.email')) ?></dt><dd class="ltr"><?= $this->e($target['email']) ?></dd>
    <dt><?= $this->e($this->t('admin.users.status')) ?></dt><dd><span class="badge"><?= $this->e($this->t('admin.users.status.' . $target['status'])) ?></span></dd>
    <dt><?= $this->e($this->t('admin.users.locale')) ?></dt><dd><?= $this->e($target['preferred_locale'] ?? '—') ?></dd>
    <dt><?= $this->e($this->t('admin.users.last_login')) ?></dt><dd><?= $this->e($this->datetime($target['last_login_at'] ?? null) ?: '—') ?></dd>
    <dt><?= $this->e($this->t('admin.users.created')) ?></dt><dd><?= $this->e($this->datetime((string) $target['created_at'])) ?></dd>
</dl>

<div class="page-actions">
    <?php if ($target['status'] === 'invited'): ?>
        <form class="inline-form" method="post" action="<?= $this->e($this->route('admin.users.invite', ['id' => $id])) ?>">
            <?= $this->csrfField() ?>
            <button class="button button--secondary" type="submit"><?= $this->e($this->t('admin.users.resend_invite')) ?></button>
        </form>
    <?php endif; ?>
    <?php foreach (['active' => 'admin.users.action.activate', 'suspended' => 'admin.users.action.suspend', 'disabled' => 'admin.users.action.disable'] as $status => $label):
        if ($target['status'] === $status || ($target['status'] === 'invited' && $status === 'active')) {
            continue;
        } ?>
        <form class="inline-form" method="post" action="<?= $this->e($this->route('admin.users.status', ['id' => $id])) ?>">
            <?= $this->csrfField() ?>
            <input type="hidden" name="status" value="<?= $status ?>">
            <button class="button <?= $status === 'active' ? '' : 'button--secondary' ?>" type="submit"><?= $this->e($this->t($label)) ?></button>
        </form>
    <?php endforeach; ?>
</div>

<h2><?= $this->e($this->t('admin.users.assignments')) ?></h2>
<div class="table-wrap">
    <table class="table">
        <thead><tr>
            <th scope="col"><?= $this->e($this->t('admin.users.role')) ?></th>
            <th scope="col"><?= $this->e($this->t('admin.users.scope')) ?></th>
            <th scope="col"><?= $this->e($this->t('admin.users.granted')) ?></th>
            <th scope="col"><?= $this->e($this->t('admin.common.actions')) ?></th>
        </tr></thead>
        <tbody>
        <?php foreach ($assignments as $assignment):
            $revoked = $assignment['revoked_at'] !== null;
            $scope = match ($assignment['scope_type']) {
                'global' => $this->t('admin.users.scope.global'),
                'organization' => (string) ($assignment['organization_name'] ?? '#' . $assignment['scope_key']),
                default => $this->t('admin.users.scope.' . $assignment['scope_type']) . ': ' . $assignment['scope_key'],
            }; ?>
            <tr<?= $revoked ? ' class="muted"' : '' ?>>
                <td><?= $this->e($assignment['role_name']) ?></td>
                <td><?= $this->e($scope) ?></td>
                <td><?= $this->e($this->datetime((string) $assignment['granted_at'])) ?>
                    <?= $revoked ? '<br>' . $this->e($this->t('admin.users.revoked_on', ['date' => $this->datetime((string) $assignment['revoked_at'])])) : '' ?></td>
                <td>
                    <?php if (!$revoked): ?>
                        <form class="inline-form" method="post" action="<?= $this->e($this->route('admin.users.roles.revoke', ['id' => $id, 'assignment' => (int) $assignment['id']])) ?>">
                            <?= $this->csrfField() ?>
                            <button class="button button--link" type="submit"><?= $this->e($this->t('admin.users.revoke')) ?></button>
                        </form>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<h2 class="section"><?= $this->e($this->t('admin.users.add_assignment')) ?></h2>
<form class="form" method="post" action="<?= $this->e($this->route('admin.users.roles.assign', ['id' => $id])) ?>">
    <?= $this->csrfField() ?>
    <?= $this->render('admin/users/assignment-fields', compact('roles', 'organizations', 'locales')) ?>
    <div><button class="button" type="submit"><?= $this->e($this->t('admin.users.assign')) ?></button></div>
</form>

<p class="section"><a href="<?= $this->e($this->route('admin.audit.index', ['entity_type' => 'user', 'entity_id' => $id])) ?>"><?= $this->e($this->t('admin.users.history')) ?></a></p>
