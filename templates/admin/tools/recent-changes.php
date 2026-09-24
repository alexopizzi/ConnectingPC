<?php
/**
 * Modifiche recenti fatte dalle organizzazioni (controllo a posteriori della pubblicazione diretta, vault "53").
 *
 * @var App\Core\View $this
 * @var list<array<string, mixed>> $changes
 * @var array{days: int, organization: int} $filters
 * @var list<array{id: int, name: string}> $organizations
 */
$entityRoutes = ['organization' => 'admin.organizations.show', 'site' => 'admin.sites.show', 'service' => 'admin.services.show', 'mediator' => 'admin.mediators.show'];
?>
<h1><?= $this->e($this->t('admin.recent.title')) ?></h1>
<p class="field__hint"><?= $this->e($this->t('admin.recent.intro')) ?></p>

<form class="filters" method="get" action="<?= $this->e($this->route('admin.recent.index')) ?>">
    <div class="field">
        <label for="f-days"><?= $this->e($this->t('admin.recent.period')) ?></label>
        <select id="f-days" name="giorni">
            <?php foreach ([1, 7, 30, 90] as $days): ?>
                <option value="<?= $days ?>"<?= $filters['days'] === $days ? ' selected' : '' ?>><?= $this->e($this->t('admin.recent.days', ['count' => $days])) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="field">
        <label for="f-org"><?= $this->e($this->t('manage.field.organization')) ?></label>
        <select id="f-org" name="organizzazione">
            <option value=""><?= $this->e($this->t('admin.common.all')) ?></option>
            <?php foreach ($organizations as $organization): ?>
                <option value="<?= $organization['id'] ?>"<?= $filters['organization'] === $organization['id'] ? ' selected' : '' ?>><?= $this->e($organization['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div><button class="button button--secondary" type="submit"><?= $this->e($this->t('admin.common.filter')) ?></button></div>
</form>

<div class="table-wrap">
    <table class="table">
        <caption><?= $this->e($this->t('admin.common.results', ['count' => count($changes)])) ?></caption>
        <thead><tr>
            <th scope="col"><?= $this->e($this->t('admin.recent.when')) ?></th>
            <th scope="col"><?= $this->e($this->t('manage.field.organization')) ?></th>
            <th scope="col"><?= $this->e($this->t('admin.recent.who')) ?></th>
            <th scope="col"><?= $this->e($this->t('admin.recent.what')) ?></th>
            <th scope="col"><?= $this->e($this->t('admin.recent.changes')) ?></th>
        </tr></thead>
        <tbody>
        <?php foreach ($changes as $change):
            $route = $entityRoutes[$change['entity_type']] ?? null; ?>
            <tr>
                <td><?= $this->e($this->datetime((string) $change['occurred_at'])) ?></td>
                <td><a href="<?= $this->e($this->route('admin.organizations.show', ['id' => (int) $change['organization_id']])) ?>"><?= $this->e($change['organization_name']) ?></a></td>
                <td><?= $this->e($change['user_name']) ?></td>
                <td><code><?= $this->e($change['action']) ?></code>
                    <?php if ($route !== null && $change['entity_id'] !== null): ?>
                        · <a href="<?= $this->e($this->route($route, ['id' => (int) $change['entity_id']])) ?>"><?= $this->e($this->t('admin.recent.open')) ?></a>
                    <?php endif; ?></td>
                <td><?php if ($change['changes'] !== null): ?>
                    <details><summary><?= $this->e($this->t('admin.recent.show_changes')) ?></summary>
                        <pre class="diff"><?= $this->e(json_encode(json_decode((string) $change['changes'], true), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?></pre>
                    </details>
                <?php endif; ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
