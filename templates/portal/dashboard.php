<?php
/**
 * @var App\Core\View $this
 * @var list<array<string, mixed>> $organizations
 * @var App\Authorization\Gate $gate
 * @var array<string, mixed> $user
 */
?>
<div class="container section">
    <div class="page-actions">
        <h1><?= $this->e($this->t('portal.dashboard.title')) ?></h1>
    </div>
    <p><?= $this->e($this->t('portal.dashboard.welcome', ['name' => (string) $user['display_name']])) ?></p>

    <h2><?= $this->e($this->t('portal.dashboard.organizations')) ?></h2>
    <?php if ($organizations === []): ?>
        <p class="muted"><?= $this->e($this->t('portal.dashboard.no_organizations')) ?></p>
    <?php else: ?>
        <ul class="card-list">
            <?php foreach ($organizations as $organization):
                $policy = $gate->effectivePublicationPolicy((int) $organization['id']); ?>
                <li class="card">
                    <h3><?= $this->e($organization['name']) ?></h3>
                    <p class="muted"><?= $this->e($organization['type_name']) ?></p>
                    <p><?= $this->e($this->t($policy === 'direct' ? 'portal.policy.direct' : 'portal.policy.review')) ?></p>
                    <?php if (!$organization['portal_edit_enabled']): ?>
                        <p class="badge badge--warning"><?= $this->e($this->t('portal.edit_disabled')) ?></p>
                    <?php endif; ?>
                    <p><a class="button" href="<?= $this->e($this->route('portal.organizations.show', ['id' => (int) $organization['id']])) ?>"><?= $this->e($this->t($organization['portal_edit_enabled'] ? 'portal.dashboard.manage' : 'portal.dashboard.view')) ?></a></p>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <?php if ($gate->allows($user, 'restricted.view')): ?>
        <h2><?= $this->e($this->t('portal.dashboard.tools')) ?></h2>
        <ul class="card-list">
            <li class="card">
                <h3><a href="<?= $this->e($this->route('portal.mediators')) ?>"><?= $this->e($this->t('mediators.operators.title')) ?></a></h3>
                <p class="muted"><?= $this->e($this->t('mediators.operators.intro')) ?></p>
            </li>
        </ul>
    <?php endif; ?>

    <div class="callout">
        <p><?= $this->e($this->t('portal.dashboard.manage_hint')) ?></p>
    </div>

    <form class="section" method="post" action="<?= $this->e($this->route('auth.logout')) ?>">
        <?= $this->csrfField() ?>
        <button class="button button--secondary" type="submit"><?= $this->e($this->t('auth.logout.submit')) ?></button>
    </form>
</div>
