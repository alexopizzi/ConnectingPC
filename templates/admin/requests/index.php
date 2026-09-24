<?php
/**
 * @var App\Core\View $this
 * @var list<array<string, mixed>> $requests
 * @var array{status: string, type: string, q: string} $filters
 */
use App\Domain\Management\InboundRequestService;

?>
<div class="page-actions">
    <h1><?= $this->e($this->t('admin.requests.title')) ?></h1>
    <a class="button" href="<?= $this->e($this->route('admin.requests.create')) ?>"><?= $this->e($this->t('admin.requests.create')) ?></a>
</div>
<p class="field__hint"><?= $this->e($this->t('admin.requests.intro')) ?></p>

<form class="filters" method="get" action="<?= $this->e($this->route('admin.requests.index')) ?>">
    <div class="field">
        <label for="f-q"><?= $this->e($this->t('admin.common.search')) ?></label>
        <input id="f-q" name="q" type="search" value="<?= $this->e($filters['q']) ?>">
    </div>
    <div class="field">
        <label for="f-status"><?= $this->e($this->t('admin.requests.status')) ?></label>
        <select id="f-status" name="stato">
            <option value="open"<?= $filters['status'] === 'open' ? ' selected' : '' ?>><?= $this->e($this->t('admin.requests.status.open')) ?></option>
            <option value="all"<?= $filters['status'] === 'all' ? ' selected' : '' ?>><?= $this->e($this->t('admin.common.all')) ?></option>
            <?php foreach (InboundRequestService::STATUSES as $status): ?>
                <option value="<?= $status ?>"<?= $filters['status'] === $status ? ' selected' : '' ?>><?= $this->e($this->t('admin.requests.status.' . $status)) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="field">
        <label for="f-type"><?= $this->e($this->t('admin.requests.type')) ?></label>
        <select id="f-type" name="tipo">
            <option value=""><?= $this->e($this->t('admin.common.all')) ?></option>
            <?php foreach (InboundRequestService::TYPES as $type): ?>
                <option value="<?= $type ?>"<?= $filters['type'] === $type ? ' selected' : '' ?>><?= $this->e($this->t('admin.requests.type.' . $type)) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div><button class="button button--secondary" type="submit"><?= $this->e($this->t('admin.common.filter')) ?></button></div>
</form>

<div class="table-wrap">
    <table class="table">
        <caption><?= $this->e($this->t('admin.common.results', ['count' => count($requests)])) ?></caption>
        <thead><tr>
            <th scope="col">#</th>
            <th scope="col"><?= $this->e($this->t('admin.requests.type')) ?></th>
            <th scope="col"><?= $this->e($this->t('manage.field.organization')) ?></th>
            <th scope="col"><?= $this->e($this->t('admin.requests.requester')) ?></th>
            <th scope="col"><?= $this->e($this->t('admin.requests.status')) ?></th>
            <th scope="col"><?= $this->e($this->t('admin.requests.assigned_to')) ?></th>
            <th scope="col"><?= $this->e($this->t('admin.requests.received')) ?></th>
        </tr></thead>
        <tbody>
        <?php foreach ($requests as $item): ?>
            <tr>
                <th scope="row"><a href="<?= $this->e($this->route('admin.requests.show', ['id' => (int) $item['id']])) ?>"><?= (int) $item['id'] ?></a></th>
                <td><?= $this->e($this->t('admin.requests.type.' . $item['type'])) ?> · <?= $this->e($this->t('admin.requests.channel.' . $item['channel'])) ?></td>
                <td><?= $this->e($item['organization_name'] ?? '—') ?></td>
                <td><?= $this->e($item['requester_name'] ?? '—') ?></td>
                <td><span class="badge<?= $item['status'] === 'new' ? ' badge--warning' : '' ?>"><?= $this->e($this->t('admin.requests.status.' . $item['status'])) ?></span></td>
                <td><?= $this->e($item['assigned_name'] ?? '—') ?></td>
                <td><?= $this->e($this->datetime((string) $item['created_at'])) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
